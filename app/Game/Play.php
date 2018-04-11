<?php
namespace App\Game;

use App\Deck;
use Ramsey\Uuid\Uuid;

class Play
{
  // initial state of a new game
  public static function generateNewGameState()
  {
    $gameId = Uuid::uuid4();
    $user = auth()->user();
    return [
      'id' => $gameId,
      'creator' => ['id' => $user->id, 'name' => $user->name],
      'deck' => [
        'content' => Deck::find(1)->cards,
        'name' => Deck::find(1)
          ->select('deck_name')
          ->get()
      ],
      // @TODO: winning_rounds;
      // winning_rounds => function(),
      'is_finished' => false,
      'players' => [],
      'current_player' => 0,
      'current_round' => [
        'number' => 0,
        'pile' => [],
        'played_cards' => [],
        'current_players' => [] // all players that are currently in game
      ],
      'test' => [] // just for testing purposes
    ];
  }

  // initial state for a player
  public static function generateNewPlayer()
  {
    $user = auth()->user();
    return [
      'id' => $user->id,
      'name' => $user->name,
      'hand' => [],
      'winning_rounds_count' => 0,
      'immunity' => false,
      'ia' => 0
    ];
  }

  // a human player is playing
  public static function playHuman($state, $params)
  {
    $user = auth()->user(); // if need to do something with the user informations
    // @TODO: edit the $state variable
    // just a test
    $state->test[] = $params;

    return $state;
  }

  // it's the IA turn to play
  public static function playIA($state, $params)
  {
    // @TODO: edit the $state variable
    return $state;
  }
}
