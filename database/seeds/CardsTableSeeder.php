<?php
use Illuminate\Database\Seeder;

class CardsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::table('cards')->insert([
      [
        'card_name' => 'Soldier',
        'choose_players' => 1,
        'choose_players_or_me' => 0,
        'choose_card_name' => 1,
        'value' => 1,
        'number_copies' => 5
      ],
      [
        'card_name' => 'Clown',
        'choose_players' => 1,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 2,
        'number_copies' => 2
      ],
      [
        'card_name' => 'Knight',
        'choose_players' => 1,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 3,
        'number_copies' => 2
      ],
      [
        'card_name' => 'Priestess',
        'choose_players' => 0,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 4,
        'number_copies' => 2
      ],
      [
        'card_name' => 'Sorcerer',
        'choose_players' => 1,
        'choose_players_or_me' => 1,
        'choose_card_name' => 0,
        'value' => 5,
        'number_copies' => 2
      ],
      [
        'card_name' => 'General',
        'choose_players' => 1,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 6,
        'number_copies' => 1
      ],
      [
        'card_name' => 'Minister',
        'choose_players' => 0,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 7,
        'number_copies' => 1
      ],
      [
        'card_name' => 'Princess/Prince',
        'choose_players' => 0,
        'choose_players_or_me' => 0,
        'choose_card_name' => 0,
        'value' => 8,
        'number_copies' => 1
      ]
    ]);
  }
}
