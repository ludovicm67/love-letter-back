<?php

namespace App\Http\Controllers;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class GameController extends Controller
{
    public function index()
    {
        // @TODO: move this inside a test
        Redis::set('test', 'redis is working, yay!');
        dd(Redis::get('test'));
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
