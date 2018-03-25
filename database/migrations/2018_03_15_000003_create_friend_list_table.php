<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFriendListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('friend_list', function (Blueprint $table) {
            $table->integer('player_id')->unsigned();
            $table->integer('friend_id')->unsigned();
            $table->foreign('player_id')->references('id')->on('players');
            $table->foreign('friend_id')->references('id')->on('players');
            $table->primary(['player_id','friend_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('friend_list');
    }
}
