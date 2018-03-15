<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeckCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deck_card', function (Blueprint $table) {
            $table->integer('deck_id');
            $table->integer('card_id');
            $table->foreign('deck_id')->references('deck_id')->on('decks');
            $table->foreign('card_id')->references('card_id')->on('cards');
            $table->primary(['deck_id','card_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deck_card');
    }
}
