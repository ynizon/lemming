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
            $table->unsignedBigInteger('player')->nullable();
            $table->unsignedBigInteger('winner')->nullable();
            $table->unsignedBigInteger('cards1')->nullable();
            $table->unsignedBigInteger('cards2')->nullable();
			$table->unsignedBigInteger('cards3')->nullable();
			$table->unsignedBigInteger('cards4')->nullable();
            $table->integer('earth')->default(2);
            $table->integer('rock')->default(2);
            $table->integer('water')->default(2);
            $table->integer('forest')->default(2);
            $table->integer('desert')->default(2);
            $table->string('name');
            $table->text('cards_played')->comment('');
            $table->text('cards')->comment('All cards in the game [cardId=>card]');
            $table->enum('status',Game::STATUS)->default("waiting");
            $table->timestamps();

            $table->foreign('player1_id')->references('id')->on('users');
            $table->foreign('player2_id')->references('id')->on('users');
			$table->foreign('player3_id')->references('id')->on('users');
			$table->foreign('player4_id')->references('id')->on('users');
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
