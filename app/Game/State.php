<?php
namespace App\Game;

use App\Deck;
use App\Game\Event;
use App\Game\Play;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class State
{
  // initial state of a new game
  public static function newGame()
  {
    $gameId = Uuid::uuid4();
    $user = auth()->user();
    return json_decode(
      json_encode([
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
          'number' => 1,
          'pile' => [],
          'played_cards' => [],
          'current_players' => [] // all players that are currently in game
        ],
        'test' => [],
        // @TODO: may remove this; was just for testing purposes
        'started' => false
      ])
    );
  }

  // initial state for a player
  public static function newPlayer()
  {
    $user = auth()->user();
    return (object) [
      'id' => $user->id,
      'name' => $user->name,
      'hand' => [],
      'turn' => 0,
      'winning_rounds_count' => 0,
      'immunity' => false,
      'can_play' => 0,
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
      'turn' => 0,
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

  public static function fillWithAI($state)
  {
    $nbPlayers = count($state->players);
    $initial = $nbPlayers;
    while ($nbPlayers < 4 && $state->slots[$nbPlayers - 1] > 0) {
      $state->players[] = self::newAI($state->slots[$nbPlayers - 1]);
      $nbPlayers++;
    }

    // only save if one AI was added
    if ($initial != $nbPlayers) {
      self::save('game:waiting:' . $state->id, $state);
    }

    return self::tryStartGame($state);
  }

  private static function startGame($state)
  {
    $waitingKey = 'game:waiting:' . $state->id;
    $startedKey = 'game:started:' . $state->id;

    // start the game by renaming the key
    Redis::rename($waitingKey, $startedKey);
    $state->started = true;
    unset($state->slots);

    $state = Play::setWinningRounds($state);
    $state = Play::setCurrentPlayers($state);
    $state = Play::setPile($state);
    $state = Play::distributeCards($state);

    self::save($startedKey, $state);

    Event::updateGameInfos();
    Event::startGame($state);

    return $state;
  }

  private static function tryStartGame($state)
  {
    if (!is_object($state) || !isset($state->id) || !isset($state->slots)) {
      return $state;
    }
    $canHumanJoin = false;
    foreach ($state->slots as $slot) {
      if ($slot === 0) {
        $canHumanJoin = true;
      }
    }
    if (!$canHumanJoin) {
      return self::startGame($state);
    }
    return $state;
  }
}
