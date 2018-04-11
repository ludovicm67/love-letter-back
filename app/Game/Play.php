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
    //put immunity to false, in case it was true
    $state->players[$state->current_player]->immunity = false;
    $user = auth()->user(); // if need to do something with the user informations
    // @TODO: edit the $state variable
    // just a test
    $state->test[] = $params;

    nextplayer($state);

    return $state;
  }

  // it's the IA turn to play
  public static function playIA($state, $params)
  {

    if($state->players[$state->current_player]->hand[0]->value==7) {
      //si on garde 7 en main, calcul total des valeurs de la main >12 _>eliminé
      $c1 = 7;
      $c2 = $state->players[$state->current_player]->hand[1]->value;
      if($c1+$c2>12) {
        //joueur a perdu
        // @TODO : moyen de le marqué comme perdant de la manche
        return $state;
      }
    }

    //put immunity to false, in case it was true
    $state->players[$state->current_player]->immunity = false;

    $ia = $state->players[$state->current_player];
    $carte;
    if($ia->ia ==1) {
      //ia aleatoire
      $nbr = rand(0,1);
      $carte = $ia->hand[nbr];
      if($nbr==0) {
        array_shift($state->players[$state->current_player]->hand);
      }
      else {
        array_pop($state->players[$state->current_player]->hand);
      }
      array_push($state->current_round->played_cards, $carte);

    }
    else if($ia->ia==2) {

    }
    $state = playactionsia($state, $carte, $ia->ia);
    $state = next_player($state);
    return $state;
  }

  public static function playactionsia($state,$carte, $ia) {
    $cartenb = $carte->value;

    if($cartenb == 1) {
      //Choisissez un joueur et un nom de carte (excepté “Soldat”).
      //Si le joueur possède cette carte, il est éliminé.
      if($ia==1) {
        $playernbr = rand(0,count($state->players));
        while(!playerisingame($state, $state->players[$playernbr]) && $playernbr != $state->current_player) {
          $playernbr = rand(0,count($state->players));
        }
        $carte = rand(2,8);
        if($state->players[$playernbr]->hand[0]->value == $carte) {
          //joueur a perdu
        }
      }
      else if($ia==2) {

      }
    }
    else if ($cartenb==2) {
      //Consultez la main d’un joueur.
    }
    else if($cartenb==3) {
      //Choisissez un joueur et comparez votre main avec la sienne.
      //Le joueur avec la carte avec la valeur la moins élevée est éliminé.
    }
    else if($cartenb==4) {
      //Jusqu’à votre prochain tour, vous ignorez les effets des cartes des autres joueurs.
      $state->players[$state->current_player]->immunity = true;
    }
    else if($cartenb==5) {
      //Choisissez un joueur ou vous-même.
      //Le joueur sélectionné défausse sa carte et en pioche une nouvelle
    }
    else if(cartenb==6) {
      //Choisissez un joueur et échangez votre main avec la sienne.
    }
    else if(cartenb==7) {
        //Si vous gardez cette carte en main, calculez le total des valeurs de votre main à chaque pioche.
        //Si celui-ci est égal ou supérieur à douze, vous êtes éliminé.
    }
    else if(cartenb==8) {
      //perdu
    }

    return $state;
  }

  public static function nextplayer($state) {
    $state->current_player = $state->current_player +1 % count($state->players);
    $p = $state->players[$state->current_player];
    $find = false;
    foreach($state->current_players as $cp) {
      if($cp->id == $p->id) {
        $find = true;
      }
    }
    while(!$find) {
      $state->current_player = $state->current_player +1 % count($state->players);
      $p = $state->players[$state->current_player];
      $find = false;
      foreach($state->current_players as $cp) {
        if($cp->id == $p->id) {
          $find = true;
        }
      }
    }
    return $state;
  }


  public static function playerisingame($state, $player) {
    $res = false;
    foreach($state->current_players as $cp) {
      if($cp->id == $player->id) {
        $res = true;
      }
    }
    return $res;
  }
}
