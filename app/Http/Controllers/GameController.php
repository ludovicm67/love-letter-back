<?php
namespace App\Http\Controllers;

use App\Game\Event;
use App\Game\Human;
use App\Game\Play;
use App\Game\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Validator;

class GameController extends Controller
{
  public function create(Request $request)
  {
    $params = $request->only('slot2', 'slot3', 'slot4');
    $rules = [
      'slot2' => 'required|integer|min:0|max:2',
      'slot3' => 'required|integer|min:-1|max:2',
      'slot4' => 'required|integer|min:-1|max:2'
    ];
    $validator = Validator::make($params, $rules);
    if ($validator->fails()) {
      return response()->json(
        ['success' => false, 'error' => $validator->messages()],
        400
      );
    }

    $game = State::newGame();

    // init the slots array
    $game->slots = [];
    $game->slots[] = intval($params['slot2']);
    if (intval($params['slot3']) == -1) {
      $game->slots[] = intval($params['slot4']);
      $game->slots[] = intval($params['slot3']);
    } else {
      $game->slots[] = intval($params['slot3']);
      $game->slots[] = intval($params['slot4']);
    }

    // add me as a player
    $game->players[] = State::newPlayer();
    $game = State::fillWithAI($game);

    State::save('game:waiting:' . $game->id, $game);
    Redis::expire('game:waiting:' . $game->id, 3600); // TTL at 1 hour
    Event::newGame($game->id);

    return response()->json(
      ['success' => true, 'data' => ['game' => $game]],
      201
    );
  }

  public function list()
  {
    $games = array_map('App\Game\State::getGameInfos', Redis::keys('game:*'));
    return response()->json(['success' => true, 'data' => ['games' => $games]]);
  }

  public function waitlist()
  {
    $games = State::getWaitingGames();
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
        return response()->json(
          ['success' => false, 'message' => 'game already started'],
          403
        );
      } else {
        return response()->json(
          ['success' => false, 'message' => 'game not found'],
          404
        );
      }
    }

    $game = State::getGameInfos($waitingKey);

    // in case we have too much players
    $nbHumanSlotsAvailable = 0;
    foreach ($game->slots as $slot) {
      if ($slot == 0) {
        $nbHumanSlotsAvailable++;
      }
    }
    if ($nbHumanSlotsAvailable === 0) {
      return response()->json(
        ['success' => false, 'error' => 'too many players'],
        403
      );
    }

    $me = State::newPlayer();
    if (in_array($me->id, State::getPlayersId($game))) {
      return response()->json(
        ['success' => false, 'error' => 'you already joined the game'],
        409
      );
    }

    $game = State::fillWithAI($game);

    // add me
    $nbPlayers = count($game->players);
    if ($nbPlayers < 4 && $game->slots[$nbPlayers - 1] == 0) {
      $game->players[] = $me;
      $game->slots[$nbPlayers - 1] = -2;
      $nbPlayers++;
      // save the new state conataining the new player
      State::save($waitingKey, $game);
    }
    $game = State::fillWithAI($game);

    Event::updateGameInfos();
    Event::updateGame($game);

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
      $game = State::getGameInfos($key);
      if (isset($game->creator->id) && $game->creator->id == $user->id) {
        Redis::del($key);
        Event::deleteGame();
        return 200;
      } else {
        return 403;
      }
    }
    return 404;
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
      return response()->json(
        ['success' => false, 'error' => 'cannot delete game of someone else'],
        403
      );
    }

    return response()->json(['success' => true]);
  }

  public function deleteAllGames()
  {
    $games = Redis::keys('game:*');
    foreach ($games as $game) {
      Redis::del($game);
    }
    Event::deleteGame();
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
        return response()->json(
          ['success' => false, 'message' => 'game not started'],
          403
        );
      } else {
        return response()->json(
          ['success' => false, 'message' => 'game not found'],
          404
        );
      }
    }

    $state = State::getGameInfos($startedKey);

    if (!State::isCurrentPlayer($state)) {
      return response()->json(
        ['success' => false, 'message' => 'not your turn to play; be patient!'],
        403
      );
    }

    // the human player will now play
    $state = Human::play($state, $params);
    Event::updateGame($state);

    // play while next player is an IA
    while (
      !$state->is_finished &&
      $state->players[$state->current_player]->ia > 0
    ) {
      if (
        in_array($state->current_player, $state->current_round->current_players)
      ) {
        $state = Play::pickCard($state, $state->current_player, false);
        Event::updateGame($state);
        sleep(1);
        $state = Play::playIA($state, $params);
        Event::updateGame($state);
        sleep(2);
      } else {
        $state = Play::nextPlayer($state);
      }
    }

    // save the state in redis again
    State::save($startedKey, $state);
    return response()->json(['success' => true, 'data' => ['game' => $state]]);
  }
}
