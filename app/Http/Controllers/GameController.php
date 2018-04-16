<?php
namespace App\Http\Controllers;

use App\Events\DeleteGameEvent;
use App\Events\NewGameEvent;
use App\Events\StartGameEvent;
use App\Events\UpdateGameEvent;
use App\Events\UpdateGameInfosEvent;
use App\Game\Play;
use App\Game\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Validator;

class GameController extends Controller
{
  private function getGameInfos($key)
  {
    return json_decode(Redis::get($key));
  }

  private function getWaitingGames()
  {
    return array_map([$this, 'getGameInfos'], Redis::keys('game:waiting:*'));
  }

  public function create(Request $request)
  {
    $params = $request->only('slot2', 'slot3', 'slot4');
    $rules = [
      'slot2' => 'required|integer|min:-1|max:2',
      'slot3' => 'required|integer|min:-1|max:2',
      'slot4' => 'required|integer|min:-1|max:2'
    ];
    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ], 400);
    }

    $game = State::newGame();

    // init the slots array
    $game->slots = [];
    $game->slots[] = intval($params['slot2']);
    $game->slots[] = intval($params['slot3']);
    $game->slots[] = intval($params['slot4']);

    // add me as a player
    $game->players[] = State::newPlayer();

    Redis::set('game:waiting:' . $game->id, json_encode($game));
    Redis::expire('game:waiting:' . $game->id, 3600); // TTL at 1 hour

    $event = new NewGameEvent([
      'game_id' => $game->id,
      'games' => $this->getWaitingGames()
    ]);
    event($event);

    return response()->json([
      'success' => true,
      'data' => ['game_id' => $game->id, 'game_infos' => $game]
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

    $game = $this->getGameInfos($startedKey);
    $game = Play::setWinningRounds($game);
    $game = Play::setCurrentPlayers($game);
    $game = Play::setPile($game);
    $game = Play::distributeCards($game);

    unset($game->slots);

    Redis::set($startedKey, json_encode($game));

    $event = new UpdateGameInfosEvent(['games' => $this->getWaitingGames()]);
    event($event);

    $event = new StartGameEvent($params['game_id'], [
      'game' => ['game_id' => $params['game_id'], 'game_infos' => $game]
    ]);
    event($event);

    return response()->json([
      'success' => true,
      'data' => ['game_id' => $params['game_id'], 'game_infos' => $game]
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
        ], 403);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'game not found'
        ], 404);
      }
    }

    $game = $this->getGameInfos($waitingKey);
    $nbPlayers = count($game->players);

    // in case we have too much players
    $maxPlayers = 4;
    foreach ($game->slots as $slot) {
      if ($slot != 0) {
        $maxPlayers--;
      }
    }
    if (isset($game->players) && $nbPlayers >= $maxPlayers) {
      return response()->json([
        'success' => false,
        'error' => 'too many players'
      ], 403);
    }

    $me = State::newPlayer();

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

    // add all IA before me
    while ($nbPlayers < 4 && $game->slots[$nbPlayers - 1] > 0) {
      $game->players[] = State::newAI($game->slots[$nbPlayers - 1]);
      $nbPlayers++;
    }

    // add me
    if ($nbPlayers < 4 && $game->slots[$nbPlayers - 1] == 0) {
      $game->players[] = $me;
      $game->slots[$nbPlayers - 2] = -2;
      $nbPlayers++;
    }

    // add all IA after me
    while ($nbPlayers < 4 && $game->slots[$nbPlayers - 1] > 0) {
      $game->players[] = State::newAI($game->slots[$nbPlayers - 1]);
      $nbPlayers++;
    }

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

  // Player1 is already the creator, cannot change it
  // SLOT =
  //  -  0 : Player2
  //  -  1 : Player3
  //  -  2 : Player4
  // VALUE =
  //  - -2 : used slot (a player is already in)
  //  - -1 : closed slot
  //  -  0 : human player slot
  //  -  1 : IA easy slot
  //  -  2 : IA difficult slot
  /**
   * DELETING PART
   **/
  private function deleteGameWithPermissions($key)
  {
    $user = auth()->user();
    if (Redis::exists($key)) {
      $game = $this->getGameInfos($key);
      if (isset($game->creator->id) && $game->creator->id == $user->id) {
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
    $params = $request->only(
      'game_id',
      'action',
      'played_card',
      'choosen_player',
      'choosen_card_name'
    );
    $rules = [
      'game_id' => 'required|string|min:36|max:36|regex:/^[0-9a-z-]+$/',
      'action' => 'required|string'
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
        ], 403);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'game not found'
        ], 404);
      }
    }

    $state = $this->getGameInfos($startedKey);

    if (!State::isCurrentPlayer($state)) {
      return response()->json([
        'success' => false,
        'message' => 'not your turn to play; be patient!'
      ], 403);
    }

    // the human player will now play
    $state = Play::playHuman($state, $params);
    $event = new UpdateGameEvent($params['game_id'], [
      'game' => ['game_id' => $params['game_id'], 'game_infos' => $state]
    ]);
    event($event);

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

    // save the state in redis again
    Redis::set($startedKey, json_encode($state));
    return response()->json(['success' => true, 'data' => ['game' => $state]]);
  }
}
