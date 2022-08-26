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

        $maps = scandir(storage_path("maps"));
        foreach ($maps as $file) {
            if ($file != "." && $file != "..") {
                $published = 1;
                $userId = 1;
                //This map is for editor only (reset map button)
                if ($file == 'empty.json') {
                    $published = 0;
                }

                //Thoses maps cant be modified
                if ($file != 'empty.json' && $file != 'Default.json') {
                    $userId = 6;
                }
                DB::table('maps')->insert([
                    'name' => str_replace('.json', '', $file),
                    'user_id' => $userId,
                    'published' => $published,
                    'map' => file_get_contents(storage_path("maps/".$file))
                ]);
            }
        }
    }
}
