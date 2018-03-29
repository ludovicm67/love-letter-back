<?php

namespace App\Http\Controllers;

use Validator;
use App\Events\TestEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class GameController extends Controller
{
    public function index()
    {
        // @TODO: move this inside a test
        Redis::set('test', 'redis is working, yay!');
        dd(Redis::get('test'));
    }

    public function event() {
      $event = new TestEvent([
        'hello' => 'world'
      ]);
      event($event);
      dd('ok');
    }

    public function create() {
      $gameId = Uuid::uuid4();

      $gameInfos = [
        'id' => $gameId,
        'creator' => auth()->user()->id
      ];

      Redis::set('game:waiting:' . $gameId, json_encode($gameInfos));

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
          'game_id' => 'required|string|max:255'
      ];

      $validator = Validator::make($params, $rules);
      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'error' => $validator->messages()
        ]);
      }

      $waitingKey = 'game:waiting:' . $params->game_id;
      $startedKey = 'game:started:' . $params->game_id;
      if (!Redis::exists($waitingKey)) {
        if (Redis::exists($startedKey)) {
          return response()->json([
            'success' => false,
            'message' => 'game already started'
          ]);
        } else {
          return response()->json([
            'success' => false,
            'message' => 'game not found'
          ]);
        }
      }

      // start the game by renaming the key
      Redis::rename($waitingKey, $startedKey);

      $gameInfos = json_decode(Redis::get($startedKey));

      return response()->json([
        'success' => true,
        'data' => [
          'game_id' => $params->game_id,
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
      $games = Redis::keys('game:waiting:*');
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
          'game_id' => 'required|string|max:255'
      ];
      $validator = Validator::make($params, $rules);
      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'error' => $validator->messages()
        ]);
      }

      return response()->json([
        'success' => true,
        'data' => 'ok'
      ]);
    }

    public function play(Request $request) {
      $params = $request->only('game_id');
      $rules = [
          'game_id' => 'required|string|max:255'
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
