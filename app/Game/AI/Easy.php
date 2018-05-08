<?php
namespace App\Game\AI;

class Easy
{
  public static function play($state, $ia)
  {
    // IA aleatoire
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

    return ['card' => $carte, 'state' => $state];
  }
}
