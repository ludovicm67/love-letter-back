<?php
use Illuminate\Database\Seeder;

class FriendListTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB
      ::table('friend_list')
      ->insert([
        ['player_id' => 1, 'friend_id' => 2],
        ['player_id' => 2, 'friend_id' => 1]
      ]);
  }
}
