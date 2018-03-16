<?php

use Illuminate\Database\Seeder;

class DeckCardTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('deck_card')->insert([
	        [
	        	'deck_id' => 1,
	        	'card_id' => 1,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 2,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 3,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 4,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 5,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 6,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 7,
	        ],
	        [
	        	'deck_id' => 1,
	        	'card_id' => 8,
	        ], 
        ]);
    }
}
