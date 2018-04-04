<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected nbPlayers;
    protected players;
    protected desk;
    protected nbWinningRounds;
}
