<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player1_id')->nullable();
            $table->unsignedBigInteger('player2_id')->nullable();
			$table->unsignedBigInteger('player3_id')->nullable();
            $table->unsignedBigInteger('player4_id')->nullable();
            $table->unsignedBigInteger('player5_id')->nullable();
            $table->unsignedBigInteger('player')->nullable();
            $table->unsignedBigInteger('winner')->nullable();
            $table->text('earth');
            $table->text('rock');
            $table->text('water');
            $table->text('forest');
            $table->text('desert');
            $table->text('map');
            $table->text('lemmings_positions')->comment("x & y = -1 for start and x & y = -2 for end");

            $table->string('name');
            $table->text('cards')->comment('All cards in the game [cardId=>card] playerId=-2 is played, -1 is in dashboard, 0 is available in deck');

            $table->enum('status',Game::STATUS)->default("waiting");
            $table->timestamps();

            $table->foreign('player1_id')->references('id')->on('users');
            $table->foreign('player2_id')->references('id')->on('users');
			$table->foreign('player3_id')->references('id')->on('users');
			$table->foreign('player4_id')->references('id')->on('users');
            $table->foreign('player5_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
