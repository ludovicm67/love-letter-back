<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $pile;
    protected $playedCards;
    protected $inGamePlayers;
    protected $currentPlayer;
    protected $gameId;
    protected $isFinished;

    public function createRound($players, $deck, $id) 
    {
      $pile = $deck;
      $playedCards = array(); //tableau vide
      $inGamePlayers = $players;
      $currentPlayer = array_values($players)[0];
      $gameId = $id;
      $isFinished = false;
    }

    public function setCurrentPlayer($player)
    {
    	$currentPlayer = $player;
    }

    public function getIsFinished()
    {
    	return $isFinished;
    }

    public function setIsFinished()
    {
    	$isFinished = true;
    }

    public function setInGamePlayers($player) //a player has been eliminated
    {
    	$number = array_search($player->name, array_column($inGamePlayers, 'name'));
    	array_splice($inGamePlayers, $number);

    	if(count($inGamePlayers) == 1)
    	{
    		setIsFinished();
    	}
    }
}
