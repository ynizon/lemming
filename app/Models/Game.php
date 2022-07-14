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

        $this->cards = serialize($cards);
        $this->save();
    }
}
