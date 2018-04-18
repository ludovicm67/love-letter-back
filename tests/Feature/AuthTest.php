<?php
namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
  public function testRegister()
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
}
