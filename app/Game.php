<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected nbPlayers;
    protected players;
    protected desk;
    protected nbWinningRounds;
    protected id;

    public function createGame(players, nbp, d) {
        players = players;
        nbPlayers= nbp;
        desk = d;
        if(nbPlayers==2) {
          nbWinningRounds = 7;
        }
        else if (nbPlayers==3) {
          nbWinningRounds = 5;
        }
        else {
          nbWinningRounds = 4;
        }

        //id ?

        createRound(players,deck,id );


    }
}
