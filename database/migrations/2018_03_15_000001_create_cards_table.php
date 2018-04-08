<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('cards', function (Blueprint $table) {
      $table->increments('id');
      $table->string('card_name', 15);
      $table->string('effect', 150);
      $table->integer('value');
      $table->integer('number_copies');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('cards');
  }
}
