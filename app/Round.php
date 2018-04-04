<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected pile;
    protected playedcards;
    protected inGamePlayers;
    protected currentPlayer;
}
