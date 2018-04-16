<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardDeckTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('card_deck', function (Blueprint $table) {
      $table->integer('deck_id')->unsigned();
      $table->integer('card_id')->unsigned();
      $table
        ->foreign('deck_id')
        ->references('id')
        ->on('decks');
      $table
        ->foreign('card_id')
        ->references('id')
        ->on('cards');
      $table->primary(['deck_id', 'card_id']);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('card_deck');
  }
}
