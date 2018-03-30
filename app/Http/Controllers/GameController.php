<?php

namespace App\Http\Controllers;

use App\Events\NewGameEvent;
use App\Events\TestEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use Validator;

class GameController extends Controller
{
    public function event() {
      $event = new TestEvent([
        'hello' => 'world'
      ]);
      event($event);
      dd('ok');
    }

    private function getWaitingGames() {
      return Redis::keys('game:waiting:*');
    }

    public function create() {
      $gameId = Uuid::uuid4();

      $gameInfos = [
        'id' => $gameId,
        'creator' => auth()->user()->id
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

      $gameInfos = json_decode(Redis::get($startedKey));

      return response()->json([
        'success' => true,
        'data' => [
          'game_id' => $params['game_id'],
          'game_infos' => $gameInfos
        ]
      ]);
    }

    public function list() {
      $games = Redis::keys('game:*');
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

      // @TODO: make player really join a game...

      return response()->json([
        'success' => true,
        'data' => 'ok'
      ]);
    }

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

      // @TODO: fetch game state (+ check if game exists)
      $state = [];

      $this->playHuman($state, $params);
      while (false) { // play while next player is an IA
        $this->playIA($state, $params);
      }

      return response()->json([
        'success' => true
      ]);
    }

    private function playIA($state, $params) {
      // @TODO
    }

    private function playHuman($state, $params) {
      // @TODO
    }



    // @TODO: delete this when finished
    public function deleteAllGames() {
      $games = Redis::keys('game:*');
      foreach ($games as $game) {
        Redis::del($game);
      }
      return response()->json([
        'success' => true
      ]);
    }
}
