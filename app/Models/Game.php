<?php

namespace App\Models;

use Auth;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public const NB_MAX_PLAYERS = 5;
    public const NB_CARDS_MAX_BY_PLAYER = 6;
    public const STATUS_WAITING = 'waiting';
    public const STATUS_STARTED = 'started';
    public const STATUS_PAUSE = 'pause';
    public const STATUS_ENDED = 'ended';
    public const STATUS = [self::STATUS_PAUSE,self::STATUS_WAITING,self::STATUS_STARTED,self::STATUS_ENDED];

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
            $terrain_images[$landscape] = $landscape;
        }
        return $terrain_images;
    }

    public function init() {
        $this->name = date("Y-m-d H:i:s");
        $this->created_at = date("Y-m-d H:i:s");
        $this->player1_id = Auth::user()->id;
        $this->winner = 0;

        $cardsInit = Card::where("game_id","=",0)->get()->shuffle()->take(config("app.nb_cards"));
        $cards = [];
        $k=0;
        foreach ($cardsInit as $card){
            $cards[$k] = ['score'=>$card->score, 'landscape'=>$card->landscape, 'playerId'=>0];
            $k++;
        }
        $this->earth = serialize([2]);
        $this->rock = serialize([2]);
        $this->water = serialize([2]);
        $this->forest = serialize([2]);
        $this->desert = serialize([2]);
        $this->lemmings_positions = serialize([]);
        $this->cards = serialize($cards);
        $this->map = serialize($this->importMap(0));
        //$this->map = serialize($this->generateOriginalMapData());//For generate new map
        $this->map_update = serialize([]);
        $this->save();
    }

    public function getPlayersName(){
        $playersName = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($this->$field)) {
                $playersName[$this->$field] = User::find($this->$field)->name;
            }
        }
        return $playersName;
    }

    public function generateOriginalMapData() {
        // -------------------------------------------------------------
        // --- Fill the $map array with values identifying the terrain
        // --- type in each hex.  This example simply randomizes the
        // --- contents of each hex.  Your code could actually load the
        // --- values from a file or from a database.
        // --- You can use $game->exportMap to export your map to storage/maps/map.txt
        // -------------------------------------------------------------
        $MAP_WIDTH = config("app.map_width");
        $MAP_HEIGHT = config("app.map_height");
        $map = [];
        for ($x=0; $x<$MAP_WIDTH; $x++) {
            for ($y=0; $y<$MAP_HEIGHT; $y++) {
                $map[$x][$y] = "none";
            }
        }

        $map[4][1] = 'start';
        $map[4][2] = 'start';
        $map[4][3] = 'start';
        $map[1][5] = 'finish';
        $map[0][6] = "finish";
        $map[2][5] = "finish";

        for ($row = 0 ; $row < 5 ; $row++) {
            for ($column = 0 ; $column < 4 ; $column++) {
                $map[$column][$row] = "out";
            }
        }

        $map[3][4] = "finish";
        $map[0][5] = "out";
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
        $map[4][5] = "out";
        $map[5][4] = "out";
        $map[5][5] = "out";
        $map[5][6] = "out";
        $map[6][5] = "out";
        $map[6][6] = "out";
        $map[7][6] = "out";
        $map[7][5] = "out";
        $map[7][10] = "out";
        $map[7][11] = "out";
        $map[12][15] = "out";
        $map[11][15] = "out";
        $map[10][16] = "out";
        $map[9][17] = "out";
        $map[9][16] = "out";
        $map[10][17] = "out";
        $map[11][17] = "out";
        $map[12][17] = "out";
        $map[11][16] = "out";
        $map[12][16] = "out";
        $map[8][17] = "out";
        $map[7][16] = "out";
        $map[5][16] = "out";
        $map[3][16] = "out";
        $map[2][16] = "out";
        $map[1][16] = "out";
        $map[0][14] = "out";
        $map[0][15] = "out";
        $map[1][15] = "out";
        $map[0][16] = "out";
        for ($row = 7 ; $row < 13; $row++){
            $map[5][$row] = "out";
            $map[6][$row] = "out";
        }

        $map[5][1] = "desert";
        $map[7][12] = "desert";
        $map[7][13] = "desert";
        $map[8][14] = "desert";
        $map[10][13] = "desert";
        $map[10][14] = "desert";
        $map[9][14] = "desert";
        $map[4][16] = "desert";
        $map[5][15] = "desert";
        $map[6][16] = "desert";

        $map[5][13] = "forest";
        $map[5][12] = "forest";
        $map[6][13] = "forest";
        $map[8][13] = "forest";
        $map[9][13] = "forest";
        $map[1][12] = "forest";
        $map[2][13] = "forest";
        $map[3][13] = "forest";
        $map[2][14] = "forest";
        $map[3][14] = "forest";
        $map[4][8] = "forest";
        $map[4][9] = "forest";

        $map[7][3] = "rock";
        $map[7][4] = "rock";
        $map[8][5] = "rock";
        $map[12][6] = "rock";
        $map[12][7] = "rock";
        $map[12][8] = "rock";
        $map[0][10] = "rock";
        $map[1][10] = "rock";
        $map[0][11] = "rock";

        $map[8][6] = "earth";
        $map[9][4] = "earth";
        $map[9][3] = "earth";
        $map[9][2] = "earth";
        $map[8][2] = "earth";
        $map[10][4] = "earth";
        $map[12][4] = "earth";
        $map[12][5] = "earth";
        $map[1][7] = "earth";
        $map[1][8] = "earth";
        $map[2][7] = "earth";
        $map[2][8] = "earth";


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
        $map[4][12] = "water";
        $map[4][13] = "water";
        $map[4][14] = "water";
        $map[3][12] = "water";

        //$this->exportMap();
        return $map;
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
            }
        }

        return $map;
    }

    public function exportMap() {
        $map = unserialize($this->map);
        $file = fopen(storage_path("maps/map".$this->id.".txt"), "w+");

        foreach ($map as $row => $rows){
            foreach ($rows as $col => $landscape){
                fputs($file,$row.'-'.$col.'-'.$landscape.PHP_EOL);
            }
        }
        fclose($file);
    }

    public function importMap($id) {
        $map = [];
        try {
            $file = fopen(storage_path("maps/map" . $id . ".txt"), "r");
            while (!feof($file)) {
                $line = fgets($file);
                if (trim($line != '')) {
                    $tab = explode("-", $line);
                    if (isset($tab[2])) {
                        $map[$tab[0]][$tab[1]] = trim($tab[2]);
                    }
                }
            }
            fclose($file);
        }catch (\Exception $e){
            $map = $this->generateOriginalMapData();
        }

        return $map;
    }
}
