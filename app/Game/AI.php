<?php
use App\Game\Event;
use App\Game\Play;

namespace App\Game;

class AI
{
  public static function play($state)
  {
    if ($state->players[$state->current_player]->hand[0]->value == 7) {
      // si on garde 7 en main, calcul total des valeurs de la main >12 _>eliminé
      $c1 = 7;
      $c2 = $state->players[$state->current_player]->hand[1]->value;
      if ($c1 + $c2 > 12) {
        // joueur a perdu
        $state = Play::playerHasLost($state, $state->current_player);
        $infos = array(
          'eliminated_player' => $state->players[$state->current_player]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'minister'
        );
        Event::eliminatedPlayer($state, $infos);
        return $state;
      }
    }
    $state->players[$state->current_player]->turn++;

    // put immunity to false, in case it was true
    $state->players[$state->current_player]->immunity = false;
    $ia = $state->players[$state->current_player];

    // just play at the right level
    $results = [];
    switch ($ia->ia) {
      case 1:
        $results = \App\Game\AI\Easy::play($state, $ia);
        break;
      case 2:
        $results = \App\Game\AI\Hard::play($state, $ia);
        break;
      default:
        // do nothing
        break;
    }

    if (isset($results['state'])) {
      $state = $results['state'];
    }
    if (isset($results['card'])) {
      $state = self::playActions($state, $results['card'], $ia->ia);
    }

    $state = Play::nextPlayer($state);

    return $state;
  }

  private static function playActions($state, $carte, $ia)
  {
    $cartenb = $carte->value;

    if ($cartenb == 1) {
      // Choisissez un joueur et un nom de carte (excepté “Soldat”).
      // Si le joueur possède cette carte, il est éliminé.
      $playernbr = rand(0, count($state->players) - 1);
      while (
        !Play::playerIsInGame($state, $playernbr) ||
        $playernbr == $state->current_player ||
        !isset($state->players[$playernbr]->hand[0])
      ) {
        $playernbr = rand(0, count($state->players) - 1);
      }
      $cartev = ($ia == 2) ? rand(2, 5) : rand(2, 8);
      if (
        $state->players[$playernbr]->hand[0]->value == $cartev &&
        !$state->players[$playernbr]->immunity
      ) {
        // joueur a perdu
        $state = Play::playerHasLost($state, $playernbr);
        $infos = array(
          'eliminated_player' => $state->players[$playernbr]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'soldier'
        );
        Event::eliminatedPlayer($state, $infos);
      }
    } elseif ($cartenb == 2) {
      // Consultez la main d’un joueur.
      // ne rien faire : ia pas capable de retenir ce qu'elle a vu
    } elseif ($cartenb == 3) {
      // Choisissez un joueur et comparez votre main avec la sienne.
      // Le joueur avec la carte avec la valeur la moins élevée est éliminé.
      $playernbr = rand(0, count($state->players) - 1);
      while (
        !Play::playerIsInGame($state, $playernbr) ||
        $playernbr == $state->current_player
      ) {
        $playernbr = rand(0, count($state->players) - 1);
      }
      if ($state->players[$playernbr]->immunity) {
        // rien ne se passe joueur a immunité
      } elseif (
        isset($state->players[$playernbr]->hand[0]) &&
        $state->players[$playernbr]->hand[0]->value >
          $state->players[$state->current_player]->hand[0]->value
      ) {
        // joueur current a perdu
        $state = Play::playerHasLost($state, $state->current_player);
        $infos = array(
          'eliminated_player' => $state->players[$state->current_player]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'knight'
        );
        Event::eliminatedPlayer($state, $infos);
      } elseif (
        isset($state->players[$playernbr]->hand[0]) &&
        $state->players[$playernbr]->hand[0]->value <
          $state->players[$state->current_player]->hand[0]->value
      ) {
        // autre joeur a perdu
        $state = Play::playerHasLost($state, $playernbr);
        $infos = array(
          'eliminated_player' => $state->players[$playernbr]->name,
          'eliminator_player' => $state->players[$state->current_player]->name,
          'card' => 'knight'
        );
        Event::eliminatedPlayer($state, $infos);
      } else {
        // egalite
      }
    } elseif ($cartenb == 4) {
      // Jusqu’à votre prochain tour, vous ignorez les effets des cartes des autres joueurs.
      $state->players[$state->current_player]->immunity = true;
    } elseif ($cartenb == 5) {
      // Choisissez un joueur ou vous-même.
      // Le joueur sélectionné défausse sa carte et en pioche une nouvelle
      if ($ia == 1) {
        $playernbr = rand(0, count($state->players) - 1);
        if (isset($state->current_round->pile[0])) {
          while (!Play::playerIsInGame($state, $playernbr)) {
            $playernbr = rand(0, count($state->players) - 1);
          }
        }
      } elseif ($ia == 2) {
        if ($state->players[$state->current_player]->hand[0]->value < 5) {
          $playernbr = $state->current_player;
        } else {
          $playernbr = rand(0, count($state->players) - 1);
          while (!Play::playerIsInGame($state, $playernbr)) {
            $playernbr = rand(0, count($state->players) - 1);
          }
        }
      }

      // run only of there are cards in the pile
      if (isset($state->current_round->pile[0])) {
        array_shift($state->players[$playernbr]->hand);
        array_push(
          $state->players[$playernbr]->hand,
          $state->current_round->pile[0]
        );
        array_shift($state->current_round->pile);
      }
    } elseif ($cartenb == 6) {
      // Choisissez un joueur et échangez votre main avec la sienne.
      $playernbr = rand(0, count($state->players) - 1);
      while (
        !Play::playerIsInGame($state, $playernbr) ||
        $playernbr == $state->current_player
      ) {
        $playernbr = rand(0, count($state->players) - 1);
      }

      $handc = $state->players[$state->current_player]->hand;
      $handp = $state->players[$playernbr]->hand;
      $state->players[$state->current_player]->hand = $handp;
      $state->players[$playernbr]->hand = $handc;
    } elseif ($cartenb == 7) {
      // Si vous gardez cette carte en main, calculez le total des valeurs de votre main à chaque pioche.
      // Si celui-ci est égal ou supérieur à douze, vous êtes éliminé.
    } elseif ($cartenb == 8) {
      // perdu
      $state = Play::playerHasLost($state, $state->current_player);
      $infos = array(
        'eliminated_player' => $state->players[$state->current_player]->name,
        'eliminator_player' => $state->players[$state->current_player]->name,
        'card' => 'princess_prince'
      );
      Event::eliminatedPlayer($state, $infos);
    }

    return $state;
  }
}
