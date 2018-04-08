<?php
use Illuminate\Database\Seeder;

class DeckPlayerTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::table('deck_player')->insert([
      ['player_id' => 1, 'deck_id' => 1],
      ['player_id' => 2, 'deck_id' => 1],
      ['player_id' => 3, 'deck_id' => 1]
    ]);
  }
}
