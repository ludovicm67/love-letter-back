<?php
namespace App\Game;

class Play
{
  // it's the IA turn to play
  public static function playIA($state, $params)
  {
    if ($state->players[$state->current_player]->hand[0]->value == 7) {
      //si on garde 7 en main, calcul total des valeurs de la main >12 _>eliminé
      $c1 = 7;
      $c2 = $state->players[$state->current_player]->hand[1]->value;
      if ($c1 + $c2 > 12) {
        //joueur a perdu
        $state = self::playerHasLost($state, $state->current_player);
        $infos = array(
          'eliminated_player' => $state->players[$state->current_player]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'minister'
        );
        Event::eliminatedPlayer($state, $infos);
        // EVENT
        return $state;
      }
    }
    $state->players[$state->current_player]->turn++;

    //put immunity to false, in case it was true
    $state->players[$state->current_player]->immunity = false;

    $ia = $state->players[$state->current_player];
    // $carte
    if ($ia->ia == 1) {
      //ia aleatoire
      $nbr = rand(0, 1);
      $carte = (isset($ia->hand[$nbr])) ? $ia->hand[$nbr] : $ia->hand[0];
      if ($nbr == 0) {
        array_shift($state->players[$state->current_player]->hand);
      } else {
        array_pop($state->players[$state->current_player]->hand);
      }
      array_push($state->current_round->played_cards, [
        $state->current_player,
        $carte
      ]);
    } elseif ($ia->ia == 2) {
      $newcard = $state->players[$state->current_player]->hand[1];
      $oldcard = $state->players[$state->current_player]->hand[0];

      if ($newcard->value == 1) {
        //deviner carte d'un joueur
        $carte = $newcard; //jouer 1 car ne permet pas de gagner
        array_pop($state->players[$state->current_player]->hand);
      } elseif ($newcard->value == 2) {
        //regarder main autre joueur
        $carte = $newcard; //jouer 2 car ne permet pas de gagner
        array_pop($state->players[$state->current_player]->hand);
      } elseif ($newcard->value == 3) {
        //comparer main
        if ($oldcard->value >= 6) {
          //si possede une carte eleve, elle tente comparaison
          $carte = $newcard;
          array_pop($state->players[$state->current_player]->hand);
        } else {
          //sinon joue autre carte
          $carte = $oldcard;
          array_shift($state->players[$state->current_player]->hand);
        }
      } elseif ($newcard->value == 4) {
        //immunité
        $carte = $newcard; //joue la carte
        array_pop($state->players[$state->current_player]->hand);
      } elseif ($newcard->value == 5) {
        //nouveau tirage pour un joueur
        $carte = $newcard; //joue cette carte car pas très élévée
        array_pop($state->players[$state->current_player]->hand);
      } elseif ($newcard->value == 6) {
        //échange main
        if ($oldcard->value < 6) {
          //si petite main tente l'echange
          $carte = $oldcard;
          array_shift($state->players[$state->current_player]->hand);
        } else {
          //sinon joue autre
          $carte = $newcard;
          array_pop($state->players[$state->current_player]->hand);
        }
      } elseif ($newcard->value == 7) {
        //si garde main ne doit pas etre >12
        if (count($state->current_round->current_players) == 2) {
          //s'il reste que 2 joueur tente le coup
          $carte = $oldcard;
          array_shift($state->players[$state->current_player]->hand);
        } else {
          //sinon jete la carte
          $carte = $newcard;
          array_pop($state->players[$state->current_player]->hand);
        }
      } elseif ($newcard->value == 8) {
        //princesse si on la jete c'est perdu
        $carte = $oldcard;
        array_shift($state->players[$state->current_player]->hand);
      }

      array_push($state->current_round->played_cards, [
        $state->current_player,
        $carte
      ]);
    }
    $state = self::playActionsAI($state, $carte, $ia->ia);
    $state = self::nextPlayer($state);
    return $state;
  }

  private static function playActionsAI($state, $carte, $ia)
  {
    $cartenb = $carte->value;

    if ($cartenb == 1) {
      //Choisissez un joueur et un nom de carte (excepté “Soldat”).
      //Si le joueur possède cette carte, il est éliminé.
      if ($ia == 1) {
        $playernbr = rand(0, count($state->players) - 1);
        while (
          !self::playerIsInGame($state, $playernbr) ||
          $playernbr == $state->current_player
        ) {
          $playernbr = rand(0, count($state->players) - 1);
        }
        $cartev = rand(2, 8);
        if (
          $state->players[$playernbr]->hand[0]->value == $cartev &&
          !$state->players[$playernbr]->immunity
        ) {
          //joueur a perdu
          $state = self::playerHasLost($state, $playernbr);
          $infos = array(
            'eliminated_player' => $state->players[$playernbr]->name,
            'eliminator_player' => $state->players[
              $state->current_player
            ]->name,
            'card' => 'soldier'
          );
          Event::eliminatedPlayer($state, $infos); // EVENT
        }
      } elseif ($ia == 2) {
        $playernbr = rand(0, count($state->players) - 1);
        while (
          !self::playerIsInGame($state, $playernbr) ||
          $playernbr == $state->current_player
        ) {
          $playernbr = rand(0, count($state->players) - 1);
        }
        $cartev = rand(2, 5);
        if (
          $state->players[$playernbr]->hand[0]->value == $cartev &&
          !$state->players[$playernbr]->immunity
        ) {
          //joueur a perdu
          $state = self::playerHasLost($state, $playernbr);
          $infos = array(
            'eliminated_player' => $state->players[$playernbr]->name,
            'eliminator_player' => $state->players[
              $state->current_player
            ]->name,
            'card' => 'soldier'
          );
          Event::eliminatedPlayer($state); // EVENT
        }
      }
    } elseif ($cartenb == 2) {
      //Consultez la main d’un joueur.
      //ne rien faire : ia pas capable de retenir ce qu'elle a vu
    } elseif ($cartenb == 3) {
      //Choisissez un joueur et comparez votre main avec la sienne.
      //Le joueur avec la carte avec la valeur la moins élevée est éliminé.
      $playernbr = rand(0, count($state->players) - 1);
      while (
        !self::playerIsInGame($state, $playernbr) ||
        $playernbr == $state->current_player
      ) {
        $playernbr = rand(0, count($state->players) - 1);
      }
      if ($state->players[$playernbr]->immunity) {
        //rien ne se passe joueur a immunité
      } elseif (
        $state->players[$playernbr]->hand[0]->value >
        $state->players[$state->current_player]->hand[0]->value
      ) {
        //joueur current a perdu
        $state = self::playerHasLost($state, $state->current_player);
        $infos = array(
          'eliminated_player' => $state->players[$state->current_player]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'knight'
        );
        Event::eliminatedPlayer($state, $infos); // EVENT
      } elseif (
        $state->players[$playernbr]->hand[0]->value <
        $state->players[$state->current_player]->hand[0]->value
      ) {
        //autre joeur a perdu
        $state = self::playerHasLost($state, $playernbr);
        $infos = array(
          'eliminated_player' => $state->players[$playernbr]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'knight'
        );
        Event::eliminatedPlayer($state, $infos); // EVENT
      } else {
        //egalite
      }
    } elseif ($cartenb == 4) {
      //Jusqu’à votre prochain tour, vous ignorez les effets des cartes des autres joueurs.
      $state->players[$state->current_player]->immunity = true;
    } elseif ($cartenb == 5) {
      //Choisissez un joueur ou vous-même.
      //Le joueur sélectionné défausse sa carte et en pioche une nouvelle
      if ($ia == 1) {
        $playernbr = rand(0, count($state->players) - 1);
        while (!self::playerIsInGame($state, $playernbr)) {
          $playernbr = rand(0, count($state->players) - 1);
        }
        array_shift($state->players[$playernbr]->hand);
        array_push(
          $state->players[$playernbr]->hand,
          $state->current_round->pile[0]
        );
        array_shift($state->current_round->pile);
      } elseif ($ia == 2) {
        if ($state->players[$state->current_player]->hand[0]->value < 5) {
          $playernbr = $state->current_player;
        } else {
          $playernbr = rand(0, count($state->players) - 1);
          while (!self::playerIsInGame($state, $playernbr)) {
            $playernbr = rand(0, count($state->players) - 1);
          }
        }
        array_shift($state->players[$playernbr]->hand);
        array_push(
          $state->players[$playernbr]->hand,
          $state->current_round->pile[0]
        );
        array_shift($state->current_round->pile);
      }
    } elseif ($cartenb == 6) {
      //Choisissez un joueur et échangez votre main avec la sienne.
      $playernbr = rand(0, count($state->players) - 1);
      while (
        !self::playerIsInGame($state, $playernbr) ||
        $playernbr == $state->current_player
      ) {
        $playernbr = rand(0, count($state->players) - 1);
      }

      $handc = $state->players[$state->current_player]->hand;
      $handp = $state->players[$playernbr]->hand;
      $state->players[$state->current_player]->hand = $handp;
      $state->players[$playernbr]->hand = $handc;
    } elseif ($cartenb == 7) {
      //Si vous gardez cette carte en main, calculez le total des valeurs de votre main à chaque pioche.
      //Si celui-ci est égal ou supérieur à douze, vous êtes éliminé.
    } elseif ($cartenb == 8) {
      //perdu
      $state = self::playerHasLost($state, $state->current_player);
      $infos = array(
        'eliminated_player' => $state->players[$state->current_player]->name,
        'eliminator_player' => $state->players[$state->current_player]->name,
        'card' => 'princess_prince'
      );
      Event::eliminatedPlayer($state, $infos); // EVENT
    }

    return $state;
  }

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
