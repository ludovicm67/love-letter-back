<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
  protected $table = 'decks';

  public function cards()
  {
    return $this->belongsToMany('App\Card');
  }
}
