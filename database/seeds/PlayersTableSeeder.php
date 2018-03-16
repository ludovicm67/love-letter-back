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
	        	'game_name' => 'Aloy',
	        	'password' => bcrypt('HorizonZeroDawn'),
	        	'won_games' => 1,
	        	'lost_games' => 3,
	        ],
	        [
	        	'card_name' => 'Toothless',
	        	'password' => bcrypt('HowToTrainYourDragon'),
	        	'won_games' => 3,
	        	'lost_games' => 1,
	        ], 
        ]);
    }
}
