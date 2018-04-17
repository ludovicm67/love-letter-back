<?php
namespace App\Game;

use App\Events\DeleteGameEvent;
use App\Events\NewGameEvent;
use App\Events\StartGameEvent;
use App\Events\UpdateGameEvent;
use App\Events\UpdateGameInfosEvent;
use App\Game\State;

class Event
{
  public static function deleteGame()
  {
    $event = new DeleteGameEvent(['games' => State::getWaitingGames()]);
    event($event);
  }

  public static function newGame($gameId)
  {
    $event = new NewGameEvent([
      'game_id' => $gameId,
      'games' => State::getWaitingGames()
    ]);
    event($event);
  }

  public static function startGame($state)
  {
    $event = new StartGameEvent($state->id, ['game' => $state]);
    event($event);
  }

  public static function updateGame($state)
  {
    $event = new UpdateGameEvent($state->id, ['game' => $state]);
    event($event);
  }

  public static function updateGameInfos()
  {
    $event = new UpdateGameInfosEvent(['games' => State::getWaitingGames()]);
    event($event);
  }
}
