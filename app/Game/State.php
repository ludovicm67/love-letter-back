<?php
namespace App\Game;

use App\Deck;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class State
{
  // initial state of a new game
  public static function newGame()
  {
    $gameId = Uuid::uuid4();
    $user = auth()->user();
    return (object) [
      'id' => $gameId,
      'creator' => ['id' => $user->id, 'name' => $user->name],
      'deck' => [
        'content' => Deck::find(1)->cards,
        'name' => Deck
          ::find(1)
          ->select('deck_name')
          ->get()
      ],
      'winning_rounds' => 0,
      'is_finished' => false,
      'players' => [],
      'current_player' => 0,
      'current_round' => [
        'number' => 0,
        'pile' => [],
        'played_cards' => [],
        'current_players' => [] // all players that are currently in game
      ],
      'test' => [] // @TODO: may remove this; was just for testing purposes
    ];
  }

  // initial state for a player
  public static function newPlayer()
  {
    $user = auth()->user();
    return (object) [
      'id' => $user->id,
      'name' => $user->name,
      'hand' => [],
      'winning_rounds_count' => 0,
      'immunity' => false,
      'ia' => 0
    ];
  }

  // initial state for a new AI
  public static function newAI($level = 1)
  {
    $name = 'IA';
    if ($level > 1) {
      $name = 'IA++';
    }

    return (object) [
      'id' => round(microtime(true) * 10000),
      // just to send a unique ID
      'name' => $name,
      'hand' => [],
      'winning_rounds_count' => 0,
      'immunity' => false,
      'ia' => $level
    ];
  }

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

  public static function getGameInfos($key)
  {
    return json_decode(Redis::get($key));
  }

  public static function getWaitingGames()
  {
    return array_map('self::getGameInfos', Redis::keys('game:waiting:*'));
  }

  public static function save($key, $value)
  {
    if (is_array($value) || is_object($value)) {
      $value = json_encode($value);
    }
    Redis::set($key, $value);
  }
}
