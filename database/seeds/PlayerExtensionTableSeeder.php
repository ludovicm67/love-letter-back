<?php

use Illuminate\Database\Seeder;

class PlayerExtensionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('player_extension')->insert([
	        [
	        	'player_id' => 1,
	        	'deck_id' => 1,
	        ],
	        [
	        	'player_id' => 2,
	        	'deck_id' => 1,
	        ], 
        ]);
    }
}
