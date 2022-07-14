<?php

namespace App\Models;

use Auth;
use App\Models\Card;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public const STATUS = ['waiting','started','end'];
    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function getLandscapesPictures() {

        // ----------------------------------------------------------------------
        // --- This is a list of possible terrain types and the
        // --- image to use to render the hex.
        // ----------------------------------------------------------------------
        $terrain_images = [];
        foreach (Card::LANDSCAPES as $landscape) {
            $terrain_images[$landscape] = "/images/".$landscape.".png";
        }
        return $terrain_images;
    }

    public function init() {
        $this->name = date("Y-m-d H:i:s");
        $this->created_at = date("Y-m-d H:i:s");
        $this->player1_id = Auth::user()->id;
        $this->cards_played = serialize([]);
        $this->winner = 0;

        $cardsInit = Card::where("game_id","=",0)->get()->shuffle()->take(config("app.nb_cards"));
        $cards = [];
        foreach ($cardsInit as $card){
            $cards[] = ['score'=>$card->score, 'landscape'=>$card->landscape, 'player'=>0];
        }
        $this->earth = serialize([2]);
        $this->rock = serialize([2]);
        $this->water = serialize([2]);
        $this->forest = serialize([2]);
        $this->desert = serialize([2]);

        $this->cards = serialize($cards);
        $this->map = serialize($this->generate_map_data());
        $this->save();
    }

    public function generate_map_data() {
        // -------------------------------------------------------------
        // --- Fill the $map array with values identifying the terrain
        // --- type in each hex.  This example simply randomizes the
        // --- contents of each hex.  Your code could actually load the
        // --- values from a file or from a database.
        // -------------------------------------------------------------
        $MAP_WIDTH = config("app.map_width");
        $MAP_HEIGHT = config("app.map_height");
        $terrain_images = $this->getLandscapesPictures();
        $map = [];
        for ($x=0; $x<$MAP_WIDTH; $x++) {
            for ($y=0; $y<$MAP_HEIGHT; $y++) {
                // --- Randomly choose a terrain type from the terrain
                // --- images array and assign to this coordinate.
                $map[$x][$y] = array_rand($terrain_images);
                $map[$x][$y] = "none";
            }
        }

        for ($row = 0 ; $row < 5 ; $row++) {
            for ($column = 0 ; $column < 4 ; $column++) {
                $map[$column][$row] = "out";
            }
        }
        $map[4][0] = "out";
        $map[6][0] = "out";
        $map[8][0] = "out";
        $map[10][0] = "out";
        $map[12][0] = "out";
        $map[9][0] = "out";
        $map[10][1] = "out";
        $map[11][0] = "out";
        $map[12][1] = "out";
        $map[11][1] = "out";
        $map[12][2] = "out";
        $map[12][3] = "out";
        $map[4][4] = "out";
        $map[5][4] = "out";
        $map[5][5] = "out";
        $map[6][5] = "out";
        $map[6][6] = "out";
        $map[7][6] = "out";
        $map[7][5] = "out";

        $map[5][1] = "desert";
        $map[8][11] = "desert";
        $map[8][12] = "desert";
        $map[9][12] = "desert";
        $map[10][13] = "desert";
        $map[11][14] = "desert";
        $map[12][14] = "desert";

        $map[7][3] = "rock";
        $map[7][4] = "rock";
        $map[8][5] = "rock";
        $map[12][6] = "rock";
        $map[12][7] = "rock";
        $map[12][8] = "rock";

        $map[8][6] = "earth";
        $map[9][4] = "earth";
        $map[9][3] = "earth";
        $map[9][2] = "earth";
        $map[8][2] = "earth";
        $map[10][4] = "earth";
        $map[12][4] = "earth";
        $map[12][5] = "earth";

        $map[11][7] = "water";
        $map[11][8] = "water";
        $map[10][10] = "water";
        $map[10][8] = "water";
        $map[10][9] = "water";
        $map[9][7] = "water";
        $map[9][8] = "water";
        $map[9][9] = "water";
        $map[9][10] = "water";
        $map[8][9] = "water";
        $map[8][8] = "water";

        $map[12][12] = "water";

        return $map;
    }
}
