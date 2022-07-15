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
        if ($game->status == GAME::STATUS_WAITING && Auth()->user()->id == $game->player1_id){
            $game->status = GAME::STATUS_STARTED;

            $cards = unserialize($game->cards);
            shuffle($cards);
            $players = [];
            if (!empty($game->player4_id)) {
                $players[] = $game->player4_id;
            }
            if (!empty($game->player3_id)) {
                $players[] = $game->player3_id;
            }
            if (!empty($game->player2_id)) {
                $players[] = $game->player2_id;
            }
            $players[] = $game->player1_id;

            $lemmingsPositions = [];
            foreach ($players as $playerId) {
                $lemmingsPositions[$playerId] = [1=>["x"=>-1,"y"=>-1], 2=>["x"=>-1,"y"=>-1]];
                $nbCard = 0;
                while ($nbCard < 5) {
                    $k = 0;
                    foreach ($cards as $card) {
                        if ($card['player'] == 0 && $nbCard < 5) {
                            $cards[$k]['player'] = $playerId;
                            $nbCard++;
                        }
                        $k++;
                    }
                }
            }

            $game->cards = serialize($cards);
            $game->lemmings_positions = serialize($lemmingsPositions);
            $game->save();

            $nextPlayerEvent = new NextPlayer($game->id);
            broadcast($nextPlayerEvent)->toOthers();
            return redirect("/game/".$game->id);
        } else {
            return redirect("/game/".$game->id)->withError("This game is already started.");
        }
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
                $playersName[$game->$field] = User::find($game->$field)->name;
            }
        }
        shuffle($playersId);
        $game->player = $playersId[0];
        $game->save();

        $lemmingsPositions = unserialize($game->lemmings_positions);
        $cardsSummary = [];
        foreach (Card::CARDS as $landscape) {
            $cardsSummary[$landscape] = unserialize($game->$landscape);
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

        return view('game',compact('cards','game', 'playersName', 'lemmingsPositions',
            'cardsSummary'));
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

        if (!empty($request->input('path')) && Auth::user()->id == $game->player){
            $lemmingNumber = (int) $request->input('lemming_number');
            $x = (int) $request->input('hexa-x');
            $y = (int) $request->input('hexa-y');
            $path = $request->input('path');
            $cardId = (int) $request->input('card_id');
            $cards = unserialize($game->cards);

            //@TODO Check le path (hack ?)
            $lemmingsPositions = unserialize($game->lemmings_positions);
            $lemmingsPositions[Auth::user()->id][$lemmingNumber] = ["x"=>$x, "y"=>$y];
            $game->lemmings_positions = serialize($lemmingsPositions);
            $landscape = $cards[$cardId]['landscape'];
            $landCards = unserialize($game->$landscape);
            $lastScore = end($landCards);
            $currentScore = $cards[$cardId]['score'];
            if ($lastScore >= $currentScore) {
                $landCards[] = $currentScore;
            } else {
                $landCards = [$currentScore];
            }

            $game->$landscape = serialize($landCards);
            $cardsPlayed = unserialize($game->cards_played);
            $cardsPlayed[] = $cards[$cardId];
            $game->cards_played = serialize($cardsPlayed);
            $cards[$cardId]['player'] = -1;

            //New card
            $cardAffected = false;
            foreach ($cards as $cardId =>$card){
                if ($card['player'] == 0 && $cardAffected == false){
                    $cards[$cardId]['player'] == Auth::user()->id;
                    $cardAffected = true;
                }
            }
            $game->cards = serialize($cards);

            //@Todo refaire la pioche

            //@TODO Winner -> end ?

            //@TODO Next player ?
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
