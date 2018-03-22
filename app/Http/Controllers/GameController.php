<?php

namespace App\Http\Controllers;

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
}
