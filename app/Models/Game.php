<?php

namespace App\Models;

use App\Models\Card;
use App\Models\Map;
use Auth;
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

    public function map()
    {
        return $this->belongsTo(Map::class);
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

    public function start()
    {
        $this->status = GAME::STATUS_STARTED;

        $cards = unserialize($this->cards);
        shuffle($cards);
        $players = [];
        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($this->$field)) {
                $players[] = $this->$field;
            }
        }

        foreach (Card::CARDS as $landscape) {
            $cardTaken = false;
            foreach ($cards as $cardId => $card) {
                if ($card['score'] == 2 && $cardTaken == false && $card['landscape'] == $landscape) {
                    $cards[$cardId]['playerId'] = Card::STATUS_IN_DASHBOARD;
                    $cardTaken = true;
                }
            }
        }

        $lemmingsPositions = [];
        foreach ($players as $playerId) {
            //@TODO update (it depends from start tiles from map)
            $lemmingsPositions[$playerId] = [1 => ["x" => 1, "y" => 4, "finish" =>0], 2 => ["x" => 2, "y" => 4, "finish" =>0]];
        }

        $nbCards = 0;
        switch (count($players)) {
            case 1:
            case 2:
                $nbCards = 5;
                break;
            case 3:
                $nbCards = 4;
                break;
            case 4:
                $nbCards = 3;
                break;
            case 5:
                $nbCards = 2;
                break;
        }

        foreach ($players as $playerId) {
            $nbCard = 0;
            while ($nbCard < $nbCards) {
                $k = 0;
                foreach ($cards as $card) {
                    if ($card['playerId'] == Card::STATUS_AVAILABLE && $nbCard < $nbCards) {
                        $cards[$k]['playerId'] = $playerId;
                        $nbCard++;
                    }
                    $k++;
                }
            }
            $nbCards++;
        }

        $this->cards = serialize($cards);
        $this->lemmings_positions = serialize($lemmingsPositions);

        //First player
        $playersId = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($this->$field)) {
                $playersId[] = $this->$field;
            }
        }

        //No shuffle because first player have less cards...
        //shuffle($playersId);
        $this->player = $playersId[0];
        $this->save();
    }

    public function init() {
        $this->name = date("Y-m-d H:i:s");
        $this->created_at = date("Y-m-d H:i:s");
        $this->player1_id = Auth::user()->id;
        $this->status = Game::STATUS_WAITING;
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
        $this->map_id = 1;
        $this->map_update = serialize([]);
        $this->save();
    }

    public function getPlayersInformations($cards){
        $playersInformations = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($this->$field)) {
                $nbCards = 0;
                foreach ($cards as $card) {
                    if ($card['playerId'] == $this->$field) {
                        $nbCards++;
                    }
                }
                $playersInformations[$this->$field] = ['name' => User::find($this->$field)->name, 'nbCards' => $nbCards ];
            }
        }
        return $playersInformations;
    }
}
