<?php
namespace App\Game;

class Play
{
  public static function nextPlayer($state)
  {
    $currentPlayer = $state->current_player;
    $find = false;
    do {
      $state->current_player =
        ($state->current_player + 1) % count($state->players);
      $find = self::playerIsInGame($state, $state->current_player);
      if ($state->current_player === $currentPlayer) {
        return $state;
      }
    } while (!$find);

    return $state;
  }

  public static function playerIsInGame($state, $player)
  {
    $res = false;
    foreach ($state->current_round->current_players as $cp) {
      if ($state->players[$cp]->id == $state->players[$player]->id) {
        $res = true;
      }
    }
    return $res;
  }

  public static function playerHasLost($state, $playerIndex)
  {
    unset(
      $state->current_round->current_players[
        array_search($playerIndex, $state->current_round->current_players)
      ]
    );
    $state->current_round->current_players = array_values(
      $state->current_round->current_players
    );
    $card = array_pop($state->players[$playerIndex]->hand);
    array_push($state->current_round->played_cards, [$playerIndex, $card]);
    return $state;
  }

  // according to the players number, they'll need a certain number of winning rounds to win the game
  public static function setWinningRounds($gameInfos)
  {
    if (count($gameInfos->players) == 2) {
      $gameInfos->winning_rounds = 7;
    } elseif (count($gameInfos->players) == 3) {
      $gameInfos->winning_rounds = 5;
    } elseif (count($gameInfos->players) == 4) {
      $gameInfos->winning_rounds = 4;
    }
    return $gameInfos;
  }

  // before every round, every player is in game
  public static function setCurrentPlayers($state)
  {
    $state->current_round->current_players = range(
      0,
      count($state->players) - 1
    );
    return $state;
  }

  /* before each round, the pile is set up :
   * - the pile is sort out
   * - according to the players number, a few cards are taken from the pile and put away
   */
  public static function setPile($gameInfos)
  {
    // create the pile
    foreach ($gameInfos->deck->content as $card_copy) {
      for ($i = 0; $i < $card_copy->number_copies; $i++) {
        array_push($gameInfos->current_round->pile, $card_copy);
      }
    }

    // sort out the pile
    shuffle($gameInfos->current_round->pile);
    // a few cards are taken away from the pile
    $i = 0;
    if (count($gameInfos->players) == 2) {
      do {
        array_push($gameInfos->current_round->played_cards, [
          -1,
          $gameInfos->current_round->pile[0]
        ]);
        array_shift($gameInfos->current_round->pile);
        $i++;
      } while ($i < 3);
    } else {
      // for three or four players
      array_push($gameInfos->current_round->played_cards, [
        -2,
        $gameInfos->current_round->pile[0]
      ]);
      array_shift($gameInfos->current_round->pile);
    }
    return $gameInfos;
  }

  // after setting the pile, we need to distribute one card to each player
  public static function distributeCards($gameInfos)
  {
    foreach ($gameInfos->players as $player) {
      array_push($player->hand, $gameInfos->current_round->pile[0]);
      array_shift($gameInfos->current_round->pile);
    }
    return $gameInfos;
  }

  // when it's his turn to play, a player picks a card from the pile
  public static function pickCard($state, $player, $effect)
  {
    // test if the pile's empty
    if (count($state->current_round->pile) == 0) {
      $winner = Play::whoHasWon($state);
      $state->players[$winner]->winning_rounds_count++;
      if (
        $state->players[$winner]->winning_rounds_count == $state->winning_rounds
      ) {
        $state->is_finished = true;
        $infos = array('winner_name' => $state->players[$winner]->name);
        Event::endGame($state, $infos); // EVENT
      } else {
        // game's not finished, then we start another round
        $infos = array(
          'winner_name' => $state->players[$winner]->name,
          'reason_end' => 1
        );
        Event::endRound($state, $infos);
        // EVENT
        $state = Play::newRound($state);
      }
      Event::updateGame($state);
      return $state;
    }
    array_push($state->players[$player]->hand, $state->current_round->pile[0]);
    array_shift($state->current_round->pile);
    if ($effect == false) {
      if ($state->players[$player]->turn > 1) {
        if ($state->players[$player]->hand[0]->value == 7) {
          if (
            (
              $state->players[$player]->hand[0]->value +
                $state->players[$player]->hand[1]->value
            ) >= 12
          ) {
            array_push($state->current_round->played_cards, [
              $player,
              $state->players[$player]->hand[1]
            ]);
            array_pop($state->players[$player]->hand);
            $state = self::playerHasLost($state, $player);
            $infos = array(
              'eliminated_player' => $state->players[
                $state->current_player
              ]->name,
              'eliminator_player' => $state->players[
                $state->current_player
              ]->name,
              'card' => 'minister'
            );
            Event::eliminatedPlayer($state, $infos); // EVENT

            // test if there's only one player left in the game
            if (count($state->current_round->current_players) == 1) {
              $state->players[
                $state->current_round->current_players[0]
              ]->winning_rounds_count++;
              // event here ?!
              if (
                $state->players[
                  $state->current_round->current_players[0]
                ]->winning_rounds_count == $state->winning_rounds
              ) {
                // game's finished
                // event here ?!
                $state->is_finished = true;
                $infos = array(
                  'winner_name' => $state->players[
                    $state->current_round->current_players[0]
                  ]->name
                );
                Event::endGame($state, $infos); // EVENT
              } else {
                // game's not finished, then we start another round
                $infos = array(
                  'winner_name' => $state->players[
                    $state->current_round->current_players[0]
                  ]->name,
                  'reason_end' => 2
                );
                Event::endRound($state, $infos);
                // EVENT
                $state = Play::newRound($state);
              }
              return $state;
            }
            $state = self::nextPlayer($state); // ??
          }
        }
      }
    }
    return $state;
  }

  // reset parameters for a new round
  public static function newRound($state)
  {
    $state = self::setCurrentPlayers($state);
    // every player comes back in the game
    $i = 0;
    $size = count($state->current_round->pile);
    do {
      array_shift($state->current_round->pile);
      $i++;
    } while ($i < $size);

    $i = 0;
    $size = count($state->current_round->played_cards);
    do {
      array_shift($state->current_round->played_cards);
      $i++;
    } while ($i < $size);
    $state = self::setPile($state);
    // the pile is sort out
    foreach ($state->players as $player) {
      array_shift($player->hand);
      $player->turn = 0;
      $player->immunity = false;
    }
    $state = self::distributeCards($state);
    // every player gets a card to start playing
    $state->current_round->number++;
    $state->current_player = 0;
    return $state;
  }

  // if the pile is empty, it's the player who has the bigger card value
  public static function whoHasWon($state)
  {
    $winner = $state->current_round->current_players[0];
    $card = 0;
    for ($i = 0; $i < count($state->current_round->current_players); $i++) {
      if (
        isset(
          $state->players[$state->current_round->current_players[$i]]->hand[0]
        ) &&
        $card <
          $state->players[
            $state->current_round->current_players[$i]
          ]->hand[0]->value
      ) {
        $card = $state->players[
          $state->current_round->current_players[$i]
        ]->hand[0]->value;
        $winner = $state->current_round->current_players[$i];
      }
    }
    return $winner;
  }
}
