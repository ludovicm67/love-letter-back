<?php
namespace App\Game;

class State
{
  // get an array containing all players id
  public static function getPlayersId($state)
  {
    if (!is_object($state) || !isset($state->players)) {
      return [];
    }

    return array_map(function ($player) {
      return $player->id;
    }, $state->players);
  }
}
