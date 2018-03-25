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
        'name'       => 'Aloy',
        'password'   => bcrypt('HorizonZeroDawn'),
        'points'     => 1500,
        'won_games'  => 1,
        'lost_games' => 3,
      ],
      [
        'name'       => 'Toothless',
        'password'   => bcrypt('HowToTrainYourDragon'),
        'points'     => 750,
        'won_games'  => 3,
        'lost_games' => 1,
      ]
    ]);
  }
}
