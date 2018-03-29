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
      return response()->json([
        'success' => true,
        'data' => [
          'game_id' => Uuid::uuid4()
        ]
      ]);
    }

    public function list() {
      $uuid1 = Uuid::uuid4();
      $uuid2 = Uuid::uuid4();
      $uuid3 = Uuid::uuid4();
      return response()->json([
        'success' => true,
        'data' => [
          "$uuid1" => 'ok',
          "$uuid2" => 'ok',
          "$uuid3" => 'ok'
        ]
      ]);
    }

    public function waitlist() {
      $uuid1 = Uuid::uuid4();
      $uuid2 = Uuid::uuid4();
      $uuid3 = Uuid::uuid4();
      return response()->json([
        'success' => true,
        'data' => [
          "$uuid1" => 'ok',
          "$uuid2" => 'ok',
          "$uuid3" => 'ok'
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
}
