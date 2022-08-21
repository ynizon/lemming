<?php

namespace Database\Seeders;

use Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Card;
use App\Models\Game;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            DB::table('users')->insert([
                'name' => 'Player ' . $i,
                'email' => 'cpu'.$i.'@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make(uniqid()),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            DB::table('users')->insert([
                'name' => 'Player '.$i,
                'email' => 'player'.$i.'@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('player'.$i),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $scores = [4,3,3,2,2,2,1,1,1,0,0];
        foreach (Card::CARDS as $landscape) {
            foreach ($scores as $score) {
                $card = new Card();
                $card->landscape = $landscape;
                $card->score = $score;
                $card->game_id = 0;
                $card->save();
            }
        }

        DB::table('maps')->insert([
            'name' => 'Default',
            'user_id' => 1,
            'published' => 1,
            'map' => file_get_contents(storage_path("maps/map1.txt"))
        ]);

        DB::table('maps')->insert([
            'name' => 'empty',
            'user_id' => 1,
            'published' => 0,
            'map' => file_get_contents(storage_path("maps/map2.txt"))
        ]);

        DB::table('maps')->insert([
            'name' => 'Nature',
            'user_id' => 1,
            'published' => 1,
            'map' => file_get_contents(storage_path("maps/map3.txt"))
        ]);
    }
}
