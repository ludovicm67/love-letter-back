<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected pile;
    protected playedcards;
    protected inGamePlayers;
    protected currentPlayer;
    protected gameId;

    public function createRound(players, deck, id) {

      pile = desk;
      playercards = []; //table vide
      inGamePlayers =players;
      currentPlayer=players[0];
      gameId= id;


    }
}
