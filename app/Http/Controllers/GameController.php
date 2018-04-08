<?php
namespace App\Http\Controllers;

use App\Events\DeleteGameEvent;
use App\Events\NewGameEvent;
use App\Events\TestEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use Validator;

use App\Deck;
//use App\Deck_Card;
class GameController extends Controller
{
  // a simple event for some tests
  public function event()
  {
    $event = new TestEvent(['hello' => 'world']);
    event($event);
    dd('ok');
  }

  private function getGameInfos($key)
  {
    return json_decode(Redis::get($key));
  }

  private function getWaitingGames()
  {
    return array_map([$this, 'getGameInfos'], Redis::keys('game:waiting:*'));
  }

  /*for test purposes
    public function winningRoundsRequired($playersNumber)
    {
      if($playersNumber == 2)
      {
        $winningRounds = 7;
      }
      else if($playersNumber == 3)
      {
        $winningRounds = 5;
      }
      else
      {
        $winningRounds = 4;
      }
      return $winningRounds;
    }*/

  public function create()
  {
    $gameId = Uuid::uuid4();
    $user = auth()->user();
    $gameInfos = [
      // game_id
      'id' => $gameId,
      // creator
      'creator' => ['id' => $user->id, 'name' => $user->name],
      // deck
      'deck' => [
        'content' => Deck::find(1)->cards,
        'name' => Deck::find(1)
          ->select('deck_name')
          ->get()
      ],
      // isFinished
      'is_finished' => false,
      // participants
      'players' => [
        [
          'id' => auth()->user()->id,
          'name' => auth()->user()->name,
          'hand' => [],
          'winning_rounds_count' => 0,
          'immunity' => false,
          'is_human' => true // true if human, false if AI
        ]
      ],
      'current_player' => 0,
      // playersNumber
      'players_number' => 1,
      // currentRound
      'current_round' => [
        'number' => 0,
        'pile' => [],
        // @FIXME: in English?
        'played_cards' => [],
        'current_players' => []
        // all players that are currently in game
        // 'is_finished' => false // @FIXME: useful?
      ]
    ];

    Redis::set('game:waiting:' . $gameId, json_encode($gameInfos));

    $event = new NewGameEvent([
      'game_id' => $gameId,
      'games' => $this->getWaitingGames()
    ]);
    event($event);

    return response()->json([
      'success' => true,
      'data' => ['game_id' => $gameId, 'game_infos' => $gameInfos]
    ]);
  }

  public function start(Request $request)
  {
    $params = $request->only('game_id');
    $rules = [
      'game_id' => 'required|string|min:36|max:36|regex:/^[0-9a-z-]+$/'
    ];

    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ]);
    }

    $waitingKey = 'game:waiting:' . $params['game_id'];
    $startedKey = 'game:started:' . $params['game_id'];
    if (!Redis::exists($waitingKey)) {
      if (Redis::exists($startedKey)) {
        return response()->json([
          'success' => false,
          'message' => 'game already started'
        ], 409);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'game not found'
        ], 404);
      }
    }

    // start the game by renaming the key
    Redis::rename($waitingKey, $startedKey);

    $gameInfos = $this->getGameInfos($startedKey);

    return response()->json([
      'success' => true,
      'data' => ['game_id' => $params['game_id'], 'game_infos' => $gameInfos]
    ]);
  }

  public function list()
  {
    $games = array_map([$this, 'getGameInfos'], Redis::keys('game:*'));
    return response()->json(['success' => true, 'data' => ['games' => $games]]);
  }

  public function waitlist()
  {
    $games = $this->getWaitingGames();
    return response()->json(['success' => true, 'data' => ['games' => $games]]);
  }

  public function join(Request $request)
  {
    $params = $request->only('game_id');
    $rules = [
      'game_id' => 'required|string|min:36|max:36|regex:/^[0-9a-z-]+$/'
    ];
    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ]);
    }

    $waitingKey = 'game:waiting:' . $params['game_id'];
    $startedKey = 'game:started:' . $params['game_id'];
    if (!Redis::exists($waitingKey)) {
      if (Redis::exists($startedKey)) {
        return response()->json([
          'success' => false,
          'message' => 'game already started'
        ], 401);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'game not found'
        ], 404);
      }
    }

    $user = auth()->user();
    $game = $this->getGameInfos($waitingKey);
    $me = [
      'id' => auth()->user()->id,
      'name' => auth()->user()->name,
      'hand' => [],
      'wonRoundsNumber' => 0,
      'immunity' => false,
      'type' => true
    ];
    if (isset($game->players) && !in_array($me, $game->players)) {
      $game->players[] = $me;
    }
    Redis::set($waitingKey, json_encode($game));

    return response()->json(['success' => true, 'data' => 'ok']);
  }

  /**
   * DELETING PART
   **/
  private function deleteGameWithPermissions($key)
  {
    $user = auth()->user();
    if (Redis::exists($key)) {
      $gameInfos = $this->getGameInfos($key);
      if (
        isset($gameInfos->creator->id) && $gameInfos->creator->id == $user->id
      ) {
        Redis::del($key);
        $event = new DeleteGameEvent(['games' => $this->getWaitingGames()]);
        event($event);
        return 200;
      } else {
        return 403;
      }
    }
  }

  public function delete(Request $request)
  {
    $params = $request->only('game_id');
    $rules = [
      'game_id' => 'required|string|min:36|max:36|regex:/^[0-9a-z-]+$/'
    ];
    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ]);
    }

    // delete game by deleting redis keys that contains the game_id
    if (
      $this->deleteGameWithPermissions('game:waiting:' . $params['game_id']) ==
      403 ||
      $this->deleteGameWithPermissions('game:started:' . $params['game_id']) ==
      403
    ) {
      return response()->json([
        'success' => false,
        'error' => 'cannot delete game of someone else'
      ], 403);
    }

    return response()->json(['success' => true]);
  }

  public function deleteAllGames()
  {
    $games = Redis::keys('game:*');
    foreach ($games as $game) {
      Redis::del($game);
    }
    $event = new DeleteGameEvent(['games' => $this->getWaitingGames()]);
    event($event);
    return response()->json(['success' => true]);
  }

  /**
   * PLAYING PART
   **/
  public function play(Request $request)
  {
    $params = $request->only('game_id');
    $rules = [
      'game_id' => 'required|string|min:36|max:36|regex:/^[0-9a-z-]+$/'
    ];
    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ]);
    }

    $waitingKey = 'game:waiting:' . $params['game_id'];
    $startedKey = 'game:started:' . $params['game_id'];
    if (!Redis::exists($startedKey)) {
      if (Redis::exists($waitingKey)) {
        return response()->json([
          'success' => false,
          'message' => 'game not started'
        ], 401);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'game not found'
        ], 404);
      }
    }

    $user = auth()->user();
    $state = $this->getGameInfos($startedKey);

    $state = $this->playHuman($state, $params);

    // this return is just for debug purposes (will block the rest of the code)
    return response()->json(['success' => true, 'data' => ['game' => $state]]);

    // play while next player is an IA
    while (
      !$state->is_finished && !$state->players[$state->current_player]->is_human
    ) {
      $this->playIA($state, $params);
    }

    // @TODO: events
    // @TODO: store state in redis again
    return response()->json(['success' => true]);
  }

  private function playIA($state, $params)
  {
    // @TODO: edit the $state variable
    return $state;
  }

  private function playHuman($state, $params)
  {
    // @TODO: edit the $state variable
    return $state;
  }
}
