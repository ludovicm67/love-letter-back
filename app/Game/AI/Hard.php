<?php
namespace App\Game\AI;

class Hard
{
  public static function play($state, $ia)
  {
    $newcard = (isset($state->players[$state->current_player]->hand[1]))
      ? $state->players[$state->current_player]->hand[1]
      : $state->players[$state->current_player]->hand[0];
    $oldcard = $state->players[$state->current_player]->hand[0];

    if ($newcard->value == 1) {
      // deviner carte d'un joueur
      $carte = $newcard; // jouer 1 car ne permet pas de gagner
      array_pop($state->players[$state->current_player]->hand);
    } elseif ($newcard->value == 2) {
      // regarder main autre joueur
      $carte = $newcard; // jouer 2 car ne permet pas de gagner
      array_pop($state->players[$state->current_player]->hand);
    } elseif ($newcard->value == 3) {
      // comparer main
      if ($oldcard->value >= 6) {
        // si possede une carte eleve, elle tente comparaison
        $carte = $newcard;
        array_pop($state->players[$state->current_player]->hand);
      } else {
        // sinon joue autre carte
        $carte = $oldcard;
        array_shift($state->players[$state->current_player]->hand);
      }
    } elseif ($newcard->value == 4) {
      // immunité
      $carte = $newcard; // joue la carte
      array_pop($state->players[$state->current_player]->hand);
    } elseif ($newcard->value == 5) {
      // nouveau tirage pour un joueur
      $carte = $newcard; // joue cette carte car pas très élévée
      array_pop($state->players[$state->current_player]->hand);
    } elseif ($newcard->value == 6) {
      // échange main
      if ($oldcard->value < 6) {
        // si petite main tente l'echange
        $carte = $oldcard;
        array_shift($state->players[$state->current_player]->hand);
      } else {
        // sinon joue autre
        $carte = $newcard;
        array_pop($state->players[$state->current_player]->hand);
      }
    } elseif ($newcard->value == 7) {
      // si garde main ne doit pas etre >12
      if (count($state->current_round->current_players) == 2) {
        // s'il reste que 2 joueur tente le coup
        $carte = $oldcard;
        array_shift($state->players[$state->current_player]->hand);
      } else {
        // sinon jete la carte
        $carte = $newcard;
        array_pop($state->players[$state->current_player]->hand);
      }
    } elseif ($newcard->value == 8) {
      // princesse si on la jete c'est perdu
      $carte = $oldcard;
      array_shift($state->players[$state->current_player]->hand);
    }

    array_push($state->current_round->played_cards, [
      $state->current_player,
      $carte
    ]);

    return ['card' => $carte, 'state' => $state];
  }
}
