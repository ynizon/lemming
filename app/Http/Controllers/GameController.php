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

            foreach (Card::CARDS as $landscape) {
                foreach ($cards as $cardId => $card) {
                    if ($card['score'] == 2 && $card['landscape'] == $landscape) {
                        $cards[$cardId]['playerId'] = Card::STATUS_IN_DASHBOARD;
                    }
                }
            }

            $lemmingsPositions = [];
            foreach ($players as $playerId) {
                $lemmingsPositions[$playerId] = [1=>["x"=>-1,"y"=>-1], 2=>["x"=>-1,"y"=>-1]];
                $nbCard = 0;
                while ($nbCard < 5) {
                    $k = 0;
                    foreach ($cards as $card) {
                        if ($card['playerId'] == Card::STATUS_AVAILABLE && $nbCard < 5) {
                            $cards[$k]['playerId'] = $playerId;
                            $nbCard++;
                        }
                        $k++;
                    }
                }
            }

            $game->cards = serialize($cards);
            $game->lemmings_positions = serialize($lemmingsPositions);

            //First player
            $playersId = [];
            foreach ([1, 2, 3, 4] as $i) {
                $field = 'player'.$i.'_id';
                if (!empty($game->$field)) {
                    $playersId[] = $game->$field;
                }
            }
            shuffle($playersId);
            $game->player = $playersId[0];
            $game->save();

            return redirect("/game/".$game->id);
        } else {
            return redirect("/game/".$game->id)->withError("This game is already started.");
        }
    }

    public function game($id, Request $request){
        $game = Game::findOrFail($id);
        $cards = unserialize($game->cards);
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

        $nbAvailableCards = 0;
        foreach ($cards as $card) {
            if ($card['playerId'] == Card::STATUS_AVAILABLE) {
                $nbAvailableCards++;
            }
        }

        $infoCards = $nbAvailableCards .'/'.count($cards);
        $playersName = $game->getPlayersName();
        return view('game',compact('cards','game', 'playersName', 'lemmingsPositions',
            'cardsSummary', 'infoCards'));
    }

    public function create(){
        $game = new Game();
        $game->init();

        return redirect("/game/".$game->id);
    }

    public function join($id){
        $game = Game::findOrFail($id);
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
                if (empty($game->$field) &&  $alreadyJoin == false) {
                    $alreadyJoin = true;
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
            $cardId = (int) $request->input('card_id');
            $cards = unserialize($game->cards);

            $this->moveLemming($game, $request);
            $this->playACard($game, $cards, $cardId);
            $this->renewTheDeck($cards);
            $this->takeANewCard($cards);
            $game->cards = serialize($cards);

            //@TODO Winner -> end ?

            //@TODO Next player ?
            $this->nextPlayer($game);
            $game->save();
        }

        return redirect("/game/".$game->id);
    }

    private function moveLemming(&$game, $request) {
        //@TODO Check le path (hack possible ?)
        $path = $request->input('path');
        $lemmingNumber = (int) $request->input('lemming_number');
        $x = (int) $request->input('hexa-x');
        $y = (int) $request->input('hexa-y');
        $lemmingsPositions = unserialize($game->lemmings_positions);
        $lemmingsPositions[Auth::user()->id][$lemmingNumber] = ["x"=>$x, "y"=>$y];
        $game->lemmings_positions = serialize($lemmingsPositions);
    }

    private function playACard(&$game, &$cards, $cardId){
        $landscape = $cards[$cardId]['landscape'];
        $landCards = unserialize($game->$landscape);
        $lastScore = end($landCards);
        $currentScore = $cards[$cardId]['score'];

        if ($currentScore <= $lastScore) {
            $landCards[] = $currentScore;
            $cards[$cardId]['playerId'] = Card::STATUS_IN_DASHBOARD;
        } else {
            //Remove old cards from the dashboard
            foreach ($cards as $cardIdTmp => $card) {
                if ($card['landscape'] == $landscape && $card['playerId'] == Card::STATUS_IN_DASHBOARD) {
                    $cards[$cardIdTmp]['playerId'] = Card::STATUS_PLAYED;
                }
            }
            $cards[$cardId]['playerId'] = Card::STATUS_IN_DASHBOARD;
            $landCards = [$currentScore];
        }
        $game->$landscape = serialize($landCards);
    }

    private function renewTheDeck(&$cards) {
        $nbCardAvailable = 0;
        foreach ($cards as $card){
            if ($card['playerId'] == Card::STATUS_AVAILABLE){
                $nbCardAvailable++;
            }
        }

        //@TODO Shuffle

        if ($nbCardAvailable == 0){
            foreach ($cards as $cardIdTmp=>$card){
                if ($card['playerId'] == Card::STATUS_PLAYED){
                    $cards[$cardIdTmp]['playerId'] = Card::STATUS_AVAILABLE;
                }
            }
        }
        return $cards;
    }

    private function takeANewCard(&$cards){
        $cardAffected = false;
        foreach ($cards as $cardIdTmp =>$card){
            if ($card['playerId'] == 0 && $cardAffected == false){
                $cards[$cardIdTmp]['playerId'] = Auth::user()->id;
                $cardAffected = true;
            }
        }
    }

    private function nextPlayer(&$game){
        $nextPlayerEvent = new NextPlayer($game->id);
        broadcast($nextPlayerEvent)->toOthers();

        switch ($game->player){
            case $game->player1_id:
                if (!empty($game->player2_id)) {
                    $game->player = $game->player2_id;
                }
                break;
            case $game->player2_id:
                if (!empty($game->player3_id)) {
                    $game->player = $game->player3_id;
                }else {
                    $game->player = $game->player1_id;
                }
                break;
            case $game->player3_id:
                if (!empty($game->player4_id)) {
                    $game->player = $game->player4_id;
                } else {
                    $game->player = $game->player1_id;
                }
                break;
            case $game->player4_id:
                $game->player = $game->player1_id;
                break;

        }
    }

    public function delete($id){
        $game = Game::findOrFail($id);
        if ($game->player1_id == Auth::user()->id){
            $game->delete();
        }
        return redirect('/home');
    }
}
