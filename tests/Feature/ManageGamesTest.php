<?php
namespace Tests\Feature;

use App\Events\NewGameEvent;
use App\Events\StartGameEvent;
use App\Events\UpdateGameInfosEvent;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ManageGamesTest extends TestCase
{
  public function testCreateGameWithoutInformationsAndToken()
  {
    $response = $this->json('POST', '/api/game/create');
    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testCreateGameWithoutToken()
  {
    $response = $this->json('POST', '/api/game/create', [
      'slot2' => 0,
      'slot3' => 0,
      'slot4' => 0
    ]);
    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testCreateGameWithoutInformations()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token
    ]);
    $response->assertStatus(400)->assertJson(['success' => false]);
  }

  public function testCreateGame()
  {
    $this->expectsEvents(NewGameEvent::class);
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token,
      'slot2' => 0,
      'slot3' => 0,
      'slot4' => 0
    ]);
    $response
      ->assertStatus(201)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => false]]
      ]);
  }

  public function testCreateGameThatWillDirectlyBeStarted()
  {
    $this->expectsEvents(UpdateGameInfosEvent::class);
    $this->expectsEvents(NewGameEvent::class);
    $this->expectsEvents(StartGameEvent::class);
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token,
      'slot2' => 1,
      'slot3' => 2,
      'slot4' => -1
    ]);
    $response
      ->assertStatus(201)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => true]]
      ]);
  }

  public function testCreateGameWithBadInformationsForSlot2()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token,
      'slot2' => -1,
      'slot3' => -1,
      'slot4' => -1
    ]);
    $response->assertStatus(400)->assertJson(['success' => false]);
  }

  public function testCreateGameThatWillDirectlyBeStartedWrongOrder()
  {
    $this->expectsEvents(UpdateGameInfosEvent::class);
    $this->expectsEvents(NewGameEvent::class);
    $this->expectsEvents(StartGameEvent::class);
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token,
      'slot2' => 1,
      'slot3' => -1,
      'slot4' => 2
    ]);
    $response
      ->assertStatus(201)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => true]]
      ]);
  }

  public function testCreateGameThatWillNotDirectlyBeStartedWrongOrder()
  {
    $this->expectsEvents(NewGameEvent::class);
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $res->data->token,
      'slot2' => 1,
      'slot3' => -1,
      'slot4' => 0
    ]);
    $response
      ->assertStatus(201)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => false]]
      ]);
  }

  public function testCreateGameThatWillBeStartedAfterPlayerJoined()
  {
    $this->expectsEvents(UpdateGameInfosEvent::class);
    $this->expectsEvents(NewGameEvent::class);
    $this->expectsEvents(StartGameEvent::class);

    // JANE
    $user = User::whereName('TESTJane');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJane',
      'password' => 'TESTDoe'
    ]);
    $jane = json_decode($response->content());

    // JOHN
    $user = User::whereName('TESTJohn');
    $user->delete();
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $john = json_decode($response->content());

    $response = $this->json('POST', '/api/game/create', [
      'token' => $john->data->token,
      'slot2' => 1,
      'slot3' => -1,
      'slot4' => 0
    ]);
    $game = json_decode($response->content());
    $response
      ->assertStatus(201)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => false]]
      ]);

    $this->assertTrue(
      property_exists($game->data->game, 'id'),
      'no id property'
    );

    // try to join his own game
    $response = $this->json('POST', '/api/game/join', [
      'token' => $john->data->token,
      'game_id' => $game->data->game->id
    ]);
    $response->assertStatus(409)->assertJson(['success' => false]);

    $response = $this->json('POST', '/api/game/join', [
      'token' => $jane->data->token,
      'game_id' => $game->data->game->id
    ]);
    $response
      ->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => ['game' => ['started' => true]]
      ]);
  }
}
