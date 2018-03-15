<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerExtensionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_extension', function (Blueprint $table) {
            $table->integer('player_id')->unsigned();
            $table->integer('deck_id')->unsigned();
            $table->foreign('player_id')->references('player_id')->on('players');
            $table->foreign('deck_id')->references('deck_id')->on('decks');
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
