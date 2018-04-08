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
    public function event() {
      $event = new TestEvent([
        'hello' => 'world'
      ]);
      event($event);
      dd('ok');
    }

    private function getGameInfos($key) {
      return json_decode(Redis::get($key));
    }

    private function getWaitingGames() {
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

    public function create() {
      $gameId = Uuid::uuid4();
      $user = auth()->user();
      $gameInfos = [

        //game_id
        'id' => $gameId,

        //creator
        'creator' => [ //creator of the game
          'id' => $user->id,
          'name' => $user->name
        ],

        //deck
        'deck' => [
          'content' => Deck::find(1)->cards,
          'name' => Deck::find(1)->select('deck_name')->get()
        ],

        //isFinished
        'isFinished' => false,

        //participants
        'participants' => [ //participants
          [
           	'id' => auth()->user()->id,
           	'name' => auth()->user()->name,
           	'hand' => array(),
           	'wonRoundsNumber' => 0,
           	'immunity' => false,
           	'type' => true //true if human, false if AI
          ]
        ],

        //playersNumber
        'playersNumber' => count('participants'),

        //currentRound
		    'currentRound' => [ // currentRound
          'number' => 0,
          'pile' => array(),
          'playedCards' => array(),
          'inGamePlayers' => array(),
          'currentPlayer' => 0,
          'isFinished' => false
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
        'data' => [
          'game_id' => $gameId,
          'game_infos' => $gameInfos
        ]
      ]);
    }

    public function start(Request $request) {
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
        'data' => [
          'game_id' => $params['game_id'],
          'game_infos' => $gameInfos
        ]
      ]);
    }

    public function list() {
      $games = array_map([$this, 'getGameInfos'], Redis::keys('game:*'));
      return response()->json([
        'success' => true,
        'data' => [
          'games' => $games
        ]
      ]);
    }

    public function waitlist() {
      $games = $this->getWaitingGames();
      return response()->json([
        'success' => true,
        'data' => [
          'games' => $games
        ]
      ]);
    }

    public function join(Request $request) {

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
        'hand' => array(),
        'wonRoundsNumber' => 0,
        'immunity' => false,
        'type' => true
      ];
      if (isset($game->players) && !in_array($me, $game->players)) {
        $game->players[] = $me;
      }
      Redis::set($waitingKey, json_encode($game));

      return response()->json([
        'success' => true,
        'data' => 'ok'
      ]);
    }


    /**
     * DELETING PART
     **/
    private function deleteGameWithPermissions($key) {
      $user = auth()->user();
      if (Redis::exists($key)) {
        $gameInfos = $this->getGameInfos($key);
        if (isset($gameInfos->creator->id) && $gameInfos->creator->id == $user->id) {
          Redis::del($key);
          $event = new DeleteGameEvent([
            'games' => $this->getWaitingGames()
          ]);
          event($event);
          return 200;
        } else {
          return 403;
        }
      }
    }

    public function delete(Request $request) {
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
      if ($this->deleteGameWithPermissions('game:waiting:' . $params['game_id']) == 403
      || $this->deleteGameWithPermissions('game:started:' . $params['game_id']) == 403) {
        return response()->json([
          'success' => false,
          'error' => 'cannot delete game of someone else'
        ], 403);
      }

      return response()->json([
        'success' => true
      ]);
    }

    public function deleteAllGames() {
      $games = Redis::keys('game:*');
      foreach ($games as $game) {
        Redis::del($game);
      }
      $event = new DeleteGameEvent([
        'games' => $this->getWaitingGames()
      ]);
      event($event);
      return response()->json([
        'success' => true
      ]);
    }


    /**
     * PLAYING PART
     **/
    public function play(Request $request) {
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

      $this->playHuman($state, $params);
      $state = $this->getGameInfos($startedKey);

      // this return is just for debug purposes (will block the rest of the code)
      return response()->json([
        'success' => true,
        'data' => [
          'game' => $state
        ]
      ]);

      // play while next player is an IA
      while (!$state->finished && $state->players[$state->playing]->type == 'IA') {
        $state = $this->getGameInfos($startedKey);
        $this->playIA($state, $params);
      }

      return response()->json([
        'success' => true
      ]);
    }

    private function playIA($state, $params) {
      // @TODO: edit the $state variable
      Redis::set($state->id, json_encode($state));
    }

    private function playHuman($state, $params) {
      // @TODO: edit the $state variable
      Redis::set($state->id, json_encode($state));
    }
}
