<?php

namespace App\Models;

use Auth;
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

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function getCardsSummary(): array
    {
        $cardsSummary = [];
        foreach (Card::CARDS as $landscape) {
            $cardsSummary[$landscape] = unserialize($this->$landscape);
        }

        foreach ($cardsSummary as $landscape => $landscapeCards) {
            $cardsSummary['line_'.$landscape] = '';
            $cardsSummary['total_'.$landscape] = 0;
            $cardsSummary['min_'.$landscape] = 0;
            foreach ($landscapeCards as $landscapeCard) {
                if (!empty($cardsSummary['line_'.$landscape])) {
                    $cardsSummary['line_'.$landscape] .= ' + ';
                }
                $cardsSummary['total_'.$landscape] += $landscapeCard;
                $cardsSummary['line_'.$landscape] .= $landscapeCard;
                $cardsSummary['min_'.$landscape] = $landscapeCard;
            }
        }
        return $cardsSummary;
    }

    public function getIconCurrentPlayer($currentIcon)
    {
        $iconPlayer = 0;
        $num = 1;
        foreach (config("app.icons") as $icon) {
            if ($icon == $currentIcon) {
                $iconPlayer = $num;
            }
            $num++;
        }
        return $iconPlayer;
    }

    public function getMapWithUpdate(&$map, &$mapUpdate)
    {
        foreach (Card::CARDS as $land) {
            $mapUpdate[$land] = 0;
        }
        $mapUpdate["meadow"] = 0;
        $updates = unserialize($this->map_update);

        foreach ($updates as $row => $update) {
            foreach ($update as $column => $land) {
                $k = 0;
                foreach ($map as $tile) {
                    if ($tile["y"] == $column && $tile["x"] == $row) {
                        $map[$k]["picture"] = "/images/".$land.".png";
                        $map[$k]["landscape"] = $land;
                    }
                    $k++;
                }
                $mapUpdate[$land]++;
            }
        }
        $map = json_encode($map);
    }

    public function start()
    {
        $this->status = Game::STATUS_STARTED;

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

        //Needs to have 2 starting points
        $map = json_decode($this->map->map, true);
        $startTiles = [];
        foreach ($map as $tile) {
            if ($tile["start"]) {
                $startTiles[] = ["x"=>$tile["x"], "y"=>$tile["y"]];
            }
        }

        $lemmingsPositions = [];
        foreach ($players as $playerId) {
            $lemmingsPositions[$playerId] =
                [
                    1 => ["x" => $startTiles[0]["x"], "y" => $startTiles[0]["y"], "finish" =>0],
                    2 => ["x" => $startTiles[1]["x"], "y" => $startTiles[1]["y"], "finish" =>0]
                ];
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

        //No shuffle cards because first players have less cards...
        $this->player = $playersId[0];
        $this->save();
    }

    public function init($oldGame = null)
    {
        $this->map_id = 1;
        $this->player1_id = Auth::user()->id;
        $this->player1_icon = config("app.icons")[0];

        if (!empty($oldGame)) {
            for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                $fieldIcon = 'player' . $i . '_icon';
                if (!empty($oldGame->$field)) {
                    $this->$field = $oldGame->$field;
                    $this->$fieldIcon = $oldGame->$fieldIcon;
                }
            }
            $this->map_id = $oldGame->map_id;
            $this->same  = $oldGame->same;
        }
        $this->name = date("Y-m-d H:i:s");
        $this->created_at = date("Y-m-d H:i:s");
        $this->status = Game::STATUS_WAITING;
        $this->winner = 0;

        $cardsInit = Card::where("game_id", "=", 0)->get()->shuffle()->take(config("app.nb_cards"));
        $cards = [];
        $k=0;
        foreach ($cardsInit as $card) {
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
        $this->map_update = serialize([]);

        $this->save();
    }

    public function getPlayersInformations($cards): array
    {
        $playersInformations = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            $fieldIcon = 'player'.$i.'_icon';
            if (!empty($this->$field)) {
                $nbCards = 0;
                foreach ($cards as $card) {
                    if ($card['playerId'] == $this->$field) {
                        $nbCards++;
                    }
                }
                $playersInformations[$this->$field] =
                    ['name' => User::find($this->$field)->name, 'nbCards' => $nbCards, 'icon' => $this->$fieldIcon];
            }
        }
        return $playersInformations;
    }

    public function whichPlayerHasLeaved()
    {
        $playerId = 0;
        $now = strtotime("now");
        $lastUpdate = strtotime($this->updated_at);
        $diff = abs($lastUpdate- $now);

        if ($diff > 59) {
            $playerId = $this->player;
        }
        return $playerId;
    }

    public function getYourIcon()
    {
        $icon = '';
        if ($this->same) {
            for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                if ($this->player == $this->$field) {
                    $fieldIcon = 'player' . $i . '_icon';
                    $icon = $this->$fieldIcon;
                }
            }
        } else {
            for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                $fieldIcon = 'player' . $i . '_icon';
                if (Auth::user()->id == $this->$field) {
                    $icon = $this->$fieldIcon;
                }
            }
        }

        return $icon;
    }

    /**
     * Use for test moves
     * @return void
     */
    public function forceMove()
    {
        $lemmingsPositions = unserialize($this->lemmings_positions);
        $lemmingsPositions[4][1]["x"]=6;
        $lemmingsPositions[4][1]["y"]=3;
        $lemmingsPositions[4][2]["x"]=5;
        $lemmingsPositions[4][2]["y"]=3;
        $this->lemmings_positions = serialize($lemmingsPositions);
        $this->save();

    }
}
