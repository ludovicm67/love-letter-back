<?php
namespace App\Game;

use App\Game\Play;
use App\Game\Event;

class Human
{
  /* a human player is playing
   * $params can contain :
   * action
   * played_card
   * choosen_player
   * choosen_card_name
   */
  public static function play($state, $params)
  {
    $state->players[$state->current_player]->immunity = false;
    // put immunity to false, in case it was true
    $user = auth()->user(); // if need to do something with the user informations

    if ($params['action'] == 'pick_card') {
      if (count($state->players[$state->current_player]->hand) == 1) {
        $state->players[$state->current_player]->turn++;
        $state = Play::pickCard($state, $state->current_player, false);
        $state->players[$state->current_player]->can_play = 1;
      }
    } elseif ($params['action'] == 'play_card') {
      if ($state->players[$state->current_player]->can_play == 1) {
        $state->players[$state->current_player]->can_play = 0;
        $key_card = array_search(
          $params['played_card'],
          array_column($state->players[$state->current_player]->hand, 'value')
        ); // discard the card that has been played
        array_push($state->current_round->played_cards, [
          $state->current_player,
          $state->players[$state->current_player]->hand[$key_card]
        ]);
        if ($key_card == 0) {
          array_shift($state->players[$state->current_player]->hand);
        } elseif ($key_card == 1) {
          array_pop($state->players[$state->current_player]->hand);
        }

        if ($params['played_card'] == 1) {
          // Soldier

          if (!isset($state->players[$params['choosen_player']]->hand[0])) {
            return $state;
          }

          if (
            $params['choosen_card_name'] ==
            $state->players[$params['choosen_player']]->hand[0]->card_name
          ) {
            if ($state->players[$params['choosen_player']]->immunity == false) {
              $state = Play::playerHasLost($state, $params['choosen_player']);
              $infos = array(
                'eliminated_player' => $state->players[
                  $params['choosen_player']
                ]->name,
                'eliminator_player' => $state->players[
                  $state->current_player
                ]->name,
                'card' => 'soldier'
              );
              Event::eliminatedPlayer($state, $infos); // EVENT
            }
          }
        } elseif ($params['played_card'] == 3) {
          // Knight
          if (
            $state->players[$state->current_player]->hand[0]->value >
            $state->players[$params['choosen_player']]->hand[0]->value
          ) {
            if ($state->players[$params['choosen_player']]->immunity == false) {
              $state = Play::playerHasLost($state, $params['choosen_player']);
              $infos = array(
                'eliminated_player' => $state->players[
                  $params['choosen_player']
                ]->name,
                'eliminator_player' => $state->players[
                  $state->current_player
                ]->name,
                'card' => 'knight'
              );
              Event::eliminatedPlayer($state, $infos); // EVENT
            }
          } elseif (
            $state->players[$state->current_player]->hand[0]->value <
            $state->players[$params['choosen_player']]->hand[0]->value
          ) {
            $state = Play::playerHasLost($state, $state->current_player);
            $infos = array(
              'eliminated_player' => $state->players[
                $state->current_player
              ]->name,
              'eliminator_player' => $state->players[
                $state->current_player
              ]->name,
              'card' => 'knight'
            );
            Event::eliminatedPlayer($state, $infos); // EVENT
          }
        } elseif ($params['played_card'] == 4) {
          // Priestess
          $state->players[$state->current_player]->immunity = true;
        } elseif ($params['played_card'] == 5) {
          // Sorcerer
          if ($state->players[$params['choosen_player']]->immunity == false) {
            if (
              $state->players[$params['choosen_player']]->hand[0]->value == 8
            ) {
              $state = Play::playerHasLost($state, $params['choosen_player']);
              $infos = array(
                'eliminated_player' => $state->players[
                  $params['choosen_player']
                ]->name,
                'eliminator_player' => $state->players[
                  $state->current_player
                ]->name,
                'card' => 'sorcerer'
              );
              Event::eliminatedPlayer($state, $infos); // EVENT
            } else {
              array_push($state->current_round->played_cards, [
                $params['choosen_player'],
                $state->players[$params['choosen_player']]->hand[0]
              ]);
              array_pop($state->players[$params['choosen_player']]->hand);
              $state = Play::pickCard($state, $params['choosen_player'], true);
            }
          }
        } elseif ($params['played_card'] == 6) {
          // General
          if ($state->players[$params['choosen_player']]->immunity == false) {
            $card = $state->players[$state->current_player]->hand[0];
            $state->players[$state->current_player]->hand[0] = $state->players[
              $params['choosen_player']
            ]->hand[0];
            $state->players[$params['choosen_player']]->hand[0] = $card;
          }
        } elseif ($params['played_card'] == 8) {
          // Princess/Prince
          $state = Play::playerHasLost($state, $state->current_player);
          $infos = array(
            'eliminated_player' => $state->players[
              $state->current_player
            ]->name,
            'eliminator_player' => $state->players[
              $state->current_player
            ]->name,
            'card' => 'princess_prince'
          );
          Event::eliminatedPlayer($state, $infos); // EVENT
        }

        // test if the pile's empty
        if (count($state->current_round->pile) == 0) {
          $winner = Play::whoHasWon($state);
          $state->players[$winner]->winning_rounds_count++;
          // event here ?!
          if (
            $state->players[$winner]->winning_rounds_count ==
            $state->winning_rounds
          ) {
            // game's finished
            // event here ?!
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
          return $state;
        }

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

        // just a test
        $state->test[] = $params;
        $state = Play::nextPlayer($state);
      }
    }
    return $state;
  }
}
