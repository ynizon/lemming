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
            $table->char('player1_icon', 5)->nullable();
            $table->char('player2_icon', 5)->nullable();
            $table->char('player3_icon', 5)->nullable();
            $table->char('player4_icon', 5)->nullable();
            $table->char('player5_icon', 5)->nullable();
            $table->text('player1_lastcard');
            $table->text('player2_lastcard');
            $table->text('player3_lastcard');
            $table->text('player4_lastcard');
            $table->text('player5_lastcard');
            $table->text('player_lastmoves');
            $table->unsignedBigInteger('player')->nullable();
            $table->unsignedBigInteger('winner')->nullable();
            $table->integer('same')->default(0)->comment("The game is on the same PC");
            $table->text('earth');
            $table->text('rock');
            $table->text('water');
            $table->text('forest');
            $table->text('desert');
            $table->text('map_update');
            $table->unsignedBigInteger('map_id')->nullable();
            $table->text('lemmings_positions')->comment("x & y = -1 for start and x & y = -2 for end");

            $table->string('name');
            $table->text('cards')->comment('All cards in the game [cardId=>card] playerId=-2 is played, -1 is in dashboard, 0 is available in deck');

            $table->enum('status', Game::STATUS)->default("waiting");
            $table->timestamps();

            $table->foreign('player1_id')->references('id')->on('users');
            $table->foreign('player2_id')->references('id')->on('users');
            $table->foreign('player3_id')->references('id')->on('users');
            $table->foreign('player4_id')->references('id')->on('users');
            $table->foreign('player5_id')->references('id')->on('users');
            $table->foreign('map_id')->references('id')->on('maps');
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
