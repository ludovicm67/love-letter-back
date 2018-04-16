<?php
namespace Tests\Unit;

use App\Events\DeleteGameEvent;
use App\Events\NewGameEvent;
use App\Events\StartGameEvent;
use App\Events\UpdateGameEvent;
use App\Events\UpdateGameInfosEvent;
use App\Game\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventTest extends TestCase
{
  public function testDeleteGame()
  {
    $this->expectsEvents(DeleteGameEvent::class);
    Event::deleteGame();
  }

  public function testNewGame()
  {
    $this->expectsEvents(NewGameEvent::class);
    Event::newGame('42');
  }

  public function testStartGame()
  {
    $this->expectsEvents(StartGameEvent::class);
    Event::startGame((object) ['id' => '42']);
  }

  public function testUpdateGame()
  {
    $this->expectsEvents(UpdateGameEvent::class);
    Event::updateGame((object) ['id' => '42']);
  }

  public function testUpdateGameInfos()
  {
    $this->expectsEvents(UpdateGameInfosEvent::class);
    Event::updateGameInfos();
  }
}
