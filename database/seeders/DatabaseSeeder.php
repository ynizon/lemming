<?php

namespace Database\Seeders;

use Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Card;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i<=4; $i++) {
            DB::table('users')->insert([
                'name' => 'CPU' . $i,
                'email' => 'cpu'.$i.'@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('cpu'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        for ($i = 1; $i<=4; $i++) {
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
            'map' => file_get_contents(storage_path("maps/map0.txt"))
        ]);
    }
}
