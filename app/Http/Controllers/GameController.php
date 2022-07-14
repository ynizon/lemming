<?php

namespace App\Http\Controllers;

use App\Events\NextPlayer;
use App\Models\Card;
use App\Models\Game;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function start($id)
    {
        $game = Game::findOrFail($id);
        if ($game->status == 'waiting' && Auth()->user()->id == $game->player1_id){
            $game->status = 'started';

            $cards = $game->cards;
            shuffle($cards);
            foreach ($cards as $card) {

            }

            $game->save();
            $nextPlayerEvent = new NextPlayer($game->id);
            broadcast($nextPlayerEvent)->toOthers();
        }

        return redirect("/game/".$game->id);
    }

    public function game($id, Request $request){
        $game = Game::findOrFail($id);
        $cards = unserialize($game->cards);
        $playersName = [];
        $playersId = [];
        foreach ([1, 2, 3, 4] as $i) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $playersId[] = $game->$field;
                $playersName[$game->$field] = [User::find($game->$field)->name];
            }
        }
        shuffle($playersId);
        $game->player = $playersId[0];
        $game->save();

        return view('game',compact('cards','game', 'playersName'));
    }

    public function create(){
        $game = new Game();
        $game->init();

        return redirect("/game/".$game->id);
    }

    public function join($id){
        $game = new Game();
        $alreadyJoin = false;
        foreach ([1, 2, 3, 4] as $playerId) {
            $field = 'player' . $playerId . '_id';
            if ($game->$field == Auth::user()->id) {
                $alreadyJoin = true;
            }
        }
        if (!$alreadyJoin) {
            foreach ([2, 3, 4] as $playerId) {
                $field = 'player' . $playerId . '_id';
                if (empty($game->$field)) {
                    $game->$field = Auth::user()->id;
                    $game->save();
                }
            }
        }
        return redirect("/game/".$game->id);
    }

    public function update($id, Request $request){
        $game = Game::findOrFail($id);
        if (!empty($request->input('question')) && Auth::user()->id == $game->player){

            //Winner ?
            /*
            if ($nbVisibleCards == 1 && empty($game->winner)){
                if ($game->player2_id == Auth::user()->id){
                    $game->winner = $game->player2_id;
                }else{
                    $game->winner = $game->player1_id;
                }
                $game->save();
            }
            */

            //Next player
            if ($game->player1_id == $game->player) {
                $game->player = $game->player2_id;
            }else{
                $game->player = $game->player1_id;
            }
            $game->save();
            $nextPlayerEvent = new NextPlayer($game->id);
            broadcast($nextPlayerEvent)->toOthers();
        }

        return redirect("/game/".$game->id);
    }

    public function delete($id){
        $game = Game::findOrFail($id);
        if ($game->player1_id == Auth::user()->id){
            $game->delete();
        }
        return redirect('/home');
    }
}
