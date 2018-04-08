<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeckPlayerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deck_player', function (Blueprint $table) {
            $table->integer('player_id')->unsigned();
            $table->integer('deck_id')->unsigned();
            $table->foreign('player_id')->references('id')->on('players');
            $table->foreign('deck_id')->references('id')->on('decks');
            $table->primary(['player_id','deck_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('player_extension');
    }
}
