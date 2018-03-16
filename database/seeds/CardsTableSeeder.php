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
	        	'effect' => 'Choose a player and a card name (except “Soldier”). If the player has this card, he is eliminated.',
	        	'value' => 1,
	        	'number_copies' => 5,
            ],
            [
                'card_name' => 'Clown',
                'effect' => 'Look at a player’s hand.',
                'value' => 2,
                'number_copies' => 2,
            ],
            [
                'card_name' => 'Knight',
                'effect' => 'Choose a player and compare your hand with his hand. The player who has the card with the lowest value is eliminated.',
                'value' => 3,
                'number_copies' => 2,
            ],
            [
                'card_name' => 'Priestess',
                'effect' => 'Until you next tour, you can’t be affected by the card’s effect from the other players.',
                'value' => 4,
                'number_copies' => 2,
            ],
            [
                'card_name' => 'Sorcerer',
                'effect' => 'Choose a player or yourself. The chosen player loses his card and takes another one.',
                'value' => 5,
                'number_copies' => 2,
            ],
            [
                'card_name' => 'General',
                'effect' => 'Choose a player and exchange your hand with his hand.',
                'value' => 6,
                'number_copies' => 1,
            ],
            [
                'card_name' => 'Minister',
                'effect' => 'If you keep this card, calculate the total of all card’s value from your hand. If this total is equal or superior to twelve, you are eliminated.',
                'value' => 7,
                'number_copies' => 1,
            ],
            [
                'card_name' => 'Princess/Prince',
                'effect' => 'If you play this card, you are eliminated.',
                'value' => 8,
                'number_copies' => 1,
            ],
        ]);
    }
}
