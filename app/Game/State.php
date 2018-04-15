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

  // get informations about the current player
  public static function getCurrentPlayerInfos($state)
  {
    if (
      !is_object($state) ||
      !isset($state->players) ||
      !isset($state->current_player)
    ) {
      return [];
    }

    return $state->players[$state->current_player];
  }

  // am I the current player ?
  public static function isCurrentPlayer($state)
  {
    $currentPlayer = self::getCurrentPlayerInfos($state);
    return (!empty($currentPlayer) && $currentPlayer->id == auth()->user()->id);
  }
}
