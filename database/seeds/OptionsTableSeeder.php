<?php
use Illuminate\Database\Seeder;

class OptionsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB
      ::table('options')
      ->insert([
        ['player_id' => 1, 'interface_color' => 'blue'],
        ['player_id' => 2, 'interface_color' => 'pink']
      ]);
  }
}
