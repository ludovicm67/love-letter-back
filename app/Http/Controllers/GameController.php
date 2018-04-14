<?php
namespace App\Http\Controllers;

use App\Events\DeleteGameEvent;
use App\Events\NewGameEvent;
use App\Events\StartGameEvent;
use App\Events\TestEvent;
use App\Events\UpdateGameEvent;
use App\Events\UpdateGameInfosEvent;
use App\Game\Play;
use App\Game\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Validator;

class GameController extends Controller
{
  const MAX_PLAYERS = 4;

  private function getGameInfos($key)
  {
    return json_decode(Redis::get($key));
  }

  private function getWaitingGames()
  {
    return array_map([$this, 'getGameInfos'], Redis::keys('game:waiting:*'));
  }

  public function create()
  {
    $gameInfos = Play::generateNewGameState();
    $gameId = $gameInfos['id'];

    // add me as a player
    array_push($gameInfos['players'], Play::generateNewPlayer());

    Redis::set('game:waiting:' . $gameId, json_encode($gameInfos));
    Redis::expire('game:waiting:' . $gameId, 3600); // TTL at 1 hour

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
    $gameInfos = Play::setWinningRounds($gameInfos);
    $gameInfos = Play::setPile($gameInfos);
    $gameInfos = Play::distributeCards($gameInfos);

    $event = new UpdateGameInfosEvent(['games' => $this->getWaitingGames()]);
    event($event);

    $event = new StartGameEvent($params['game_id'], [
      'game' => ['game_id' => $params['game_id'], 'game_infos' => $gameInfos]
    ]);
    event($event);

    Redis::set($waitingKey, json_encode($gameInfos));

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

    $game = $this->getGameInfos($waitingKey);

    // in case we have too much players
    if (isset($game->players) && count($game->players) >= self::MAX_PLAYERS) {
      return response()->json([
        'success' => false,
        'error' => 'too many players'
      ], 401);
    }

    $me = Play::generateNewPlayer();

    // bad game initialization
    if (!isset($game->players)) {
      return response()->json([
        'success' => false,
        'error' => 'game was badly initialized'
      ], 400);
    }

    if (in_array($me['id'], State::getPlayersId($game))) {
      return response()->json([
        'success' => false,
        'error' => 'you already joined the game'
      ], 409);
    }

    // add the new player
    $game->players[] = $me;

    // save the new state conataining the new player
    Redis::set($waitingKey, json_encode($game));

    $event = new UpdateGameInfosEvent(['games' => $this->getWaitingGames()]);
    event($event);

    $event = new UpdateGameEvent($game->id, [
      'game' => ['game_id' => $game->id, 'game_infos' => $game]
    ]);
    event($event);

    return response()->json(['success' => true, 'data' => ['game' => $game]]);
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
    $params = $request->only('game_id', 'action', 'card');
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

    // the human player will now play
    $state = Play::playHuman($state, $params);
    $event = new UpdateGameEvent($params['game_id'], [
      'game' => ['game_id' => $params['game_id'], 'game_infos' => $state]
    ]);
    event($event);

    // @TODO: move this at the bottom
    // save the state in redis again
    Redis::set($startedKey, json_encode($state));

    // this return is just for debug purposes (will block the rest of the code)
    return response()->json(['success' => true, 'data' => ['game' => $state]]);

    // play while next player is an IA
    while (
      !$state->is_finished && $state->players[$state->current_player]->ia > 0
    ) {
      pickCard($state);
      $state = Play::playIA($state, $params);
      $event = new UpdateGameEvent($params['game_id'], [
        'game' => ['game_id' => $params['game_id'], 'game_infos' => $state]
      ]);
      event($event);
      sleep(2);
    }
    return response()->json(['success' => true, 'data' => ['game' => $state]]);
  }
}
