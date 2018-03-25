<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $this->call([
      PlayersTableSeeder::class,
      CardsTableSeeder::class,
      DecksTableSeeder::class,
      DeckCardTableSeeder::class,
      FriendListTableSeeder::class,
      PlayerExtensionTableSeeder::class,
      OptionsTableSeeder::class,
    ]);
  }
}
