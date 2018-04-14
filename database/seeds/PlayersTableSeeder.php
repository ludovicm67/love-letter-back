<?php
use Illuminate\Database\Seeder;

class PlayersTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::table('players')->insert([
      [
        'name' => 'Aloy',
        'password' => bcrypt('hzd'),
        'points' => 1500,
        'won_games' => 1,
        'lost_games' => 3
      ],
      [
        'name' => 'Toothless',
        'password' => bcrypt('httyd'),
        'points' => 750,
        'won_games' => 3,
        'lost_games' => 1
      ],
      [
        'name' => 'root',
        'password' => bcrypt('root'),
        'points' => 4242,
        'won_games' => 42,
        'lost_games' => 0
      ]
    ]);
  }
}
