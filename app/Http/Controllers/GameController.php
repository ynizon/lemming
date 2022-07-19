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
            $game->start();

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
        $playersInformations = $game->getPlayersInformations($cards);

        $mapUpdate = [];
        foreach (Card::CARDS as $land){
            $mapUpdate[$land] = 0;
        }
        $updates = unserialize($game->map_update);
        foreach ($updates as $updateRow => $update){
            foreach ($update as $column => $land){
                $mapUpdate[$land]++;
            }
        }

        return view('game',compact('cards','game', 'playersInformations', 'lemmingsPositions',
            'cardsSummary', 'infoCards', 'mapUpdate'));
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
            foreach ([2, 3, 4, 5] as $playerId) {
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
            $lemmingsPositions = unserialize($game->lemmings_positions);

            $this->moveLemming($game, $request);
            $this->playACard($game, $cards, $cardId);
            $this->updateMap($game, $request);
            $this->hasWinner($game, $lemmingsPositions);

            $game->cards = serialize($cards);
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

    private function updateMap(&$game, $request) {
        $x = (int) $request->input('changemap-x');
        $y = (int) $request->input('changemap-y');
        $landscape = $request->input('changemap-landscape');
        if (!empty($landscape)) {
            $map = unserialize($game->map_update );
            $map[$x][$y] = $landscape;
            $game->map_update = serialize($map);
        }
    }

    private function hasWinner(&$game, $lemmingsPositions) {
        $winnerId = 0;
        foreach ($lemmingsPositions as $playerId => $lemmings) {
            if ($lemmings[1]['x'] == -2 && $lemmings[1]['y'] == -2 &&
                $lemmings[2]['x'] == -2 && $lemmings[2]['y'] == -2
            ) {
                $winnerId = $playerId;
            }
        }
        if (!empty($winnerId)){
            $game->winner = $winnerId;
        } else {
            $this->nextPlayer($game);
        }
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
        $this->renewTheDeck($cards);
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
                if (!empty($game->player5_id)) {
                    $game->player = $game->player5_id;
                } else {
                    $game->player = $game->player1_id;
                }
                break;
            case $game->player5_id:
                $game->player = $game->player1_id;
                break;

        }
    }

    public function renew($id, Request $request) {
        $game = Game::findOrFail($id);
        if ($game->player == Auth::user()->id) {
            $cards = unserialize($game->cards);
            $cardsId = $request->input("renewCards");
            if (!empty($cardsId)) {
                foreach ($cards as $cardId => $card) {
                    if ($card['playerId'] == Auth::user()->id && in_array($cardId, $cardsId)) {
                        $cards[$cardId]['playerId'] = Card::STATUS_PLAYED;
                    }
                }
            }

            $nbCard = 0;
            foreach ($cards as $cardId => $card) {
                if ($card['playerId'] == Auth::user()->id) {
                    $nbCard++;
                }
            }

            while ($nbCard < Game::NB_CARDS_MAX_BY_PLAYER) {
                $this->takeANewCard($cards);
                $nbCard++;
            }

            $game->cards = serialize($cards);
            $this->nextPlayer($game);
            $game->save();
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
