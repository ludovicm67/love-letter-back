<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
  public function testRegisterNewUser()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    $response->assertStatus(201)->assertJson(['success' => true]);
    $res = json_decode($response->content());
    $this->assertTrue(property_exists($res, 'data'), 'no data property');
    $this->assertTrue(
      property_exists($res->data, 'token'),
      'no token property'
    );
    $this->assertNotEmpty($res->data->token);
    $this->assertTrue(property_exists($res->data, 'user'), 'no user property');
    $this->assertTrue(
      property_exists($res->data->user, 'id'),
      'no user.id property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'name'),
      'no user.name property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'points'),
      'no user.points property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'won_games'),
      'no user.won_games property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'lost_games'),
      'no user.lost_games property'
    );
  }

  public function testRegisterExistingUser()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    // first one: create user
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    // do the request a second one
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    $response->assertStatus(400)->assertJson(['success' => false]);
  }

  public function testLogin()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    // create user
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    // login
    $response = $this->json('POST', '/api/login', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);
    $res = json_decode($response->content());
    $this->assertTrue(property_exists($res, 'data'), 'no data property');
    $this->assertTrue(
      property_exists($res->data, 'token'),
      'no token property'
    );
    $this->assertNotEmpty($res->data->token);
    $this->assertTrue(property_exists($res->data, 'user'), 'no user property');
    $this->assertTrue(
      property_exists($res->data->user, 'id'),
      'no user.id property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'name'),
      'no user.name property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'points'),
      'no user.points property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'won_games'),
      'no user.won_games property'
    );
    $this->assertTrue(
      property_exists($res->data->user, 'lost_games'),
      'no user.lost_games property'
    );
  }

  public function testLoginBadCredentials()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    // create user
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    // login
    $response = $this->json('POST', '/api/login', [
      'name' => 'TESTJohn',
      'password' => 'TESTJohn'
    ]);

    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testLoginNonExistingUser()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    // try to login
    $response = $this->json('POST', '/api/login', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);

    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testLogoutWithoutToken()
  {
    $response = $this->json('GET', '/api/logout');
    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testLogoutWithBadToken()
  {
    $response = $this->json('GET', '/api/logout', [
      'token' => 'ichBinEinToken'
    ]);
    $response->assertStatus(401)->assertJson(['success' => false]);
  }

  public function testLogout()
  {
    $user = User::whereName('TESTJohn');
    $user->delete();

    // create user
    $response = $this->json('POST', '/api/register', [
      'name' => 'TESTJohn',
      'password' => 'TESTDoe'
    ]);
    $res = json_decode($response->content());

    $response = $this->json('GET', '/api/logout', [
      'token' => $res->data->token
    ]);
    $response->assertStatus(200)->assertJson(['success' => true]);
  }
}
