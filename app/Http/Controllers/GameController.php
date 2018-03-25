<?php

namespace App\Http\Controllers;

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
      dd("ok");
    }

    public function create() {
      return response()->json([
        'success' => true,
        'data' => [
          'game_id' => Uuid::uuid4()
        ]
      ]);
    }
}
