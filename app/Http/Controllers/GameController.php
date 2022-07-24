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
    public function start($id, Request $request)
    {
        $game = Game::findOrFail($id);
        if ($game->status == GAME::STATUS_WAITING && Auth()->user()->id == $game->player1_id){
            if ($request->input("same") == "1") {
                $nbPlayers = $request->input("nb_players");
                for ($k= 1; $k <= $nbPlayers; $k++){
                    $field = 'player'.$k.'_id';
                    $game->$field = $k;//Player par defaut
                }
                $game->same = 1;
            }
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
        $cardsSummary = $game->getCardsSummary();

        $nbAvailableCards = 0;
        foreach ($cards as $card) {
            if ($card['playerId'] == Card::STATUS_AVAILABLE) {
                $nbAvailableCards++;
            }
        }

        $infoCards = $nbAvailableCards .'/'.count($cards);
        $playersInformations = $game->getPlayersInformations($cards);

        $mapUpdate = [];
        $map = json_decode($game->map->map, true);
        $game->getMapWithUpdate($map, $mapUpdate);

        return view('game',compact('cards','game', 'playersInformations', 'lemmingsPositions',
            'cardsSummary', 'infoCards', 'mapUpdate', 'map'));
    }

    public function create(){
        $game = new Game();
        $game->init();

        return redirect("/game/".$game->id);
    }

    public function replay($id){
        $oldGame = Game::findOrFail($id);
        $game = new Game();
        $game->init($oldGame);

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

        if (!empty($request->input('path')) && (Auth::user()->id == $game->player || $game->same)){
            $cardId = (int) $request->input('card_id');
            $cards = unserialize($game->cards);
            $this->moveLemming($game, $request);
            $this->playACard($game, $cards, $cardId);
            $this->updateMap($game, $request);
            $this->hasWinner($game, unserialize($game->lemmings_positions));

            $game->cards = serialize($cards);
            $game->save();
        }

        return redirect("/game/".$game->id);
    }

    private function moveLemming(&$game, $request) {
        //@TODO Check le path (hack possible ?)
        $path = $request->input('path');

        $map = json_decode($game->map->map,true);
        $finishTiles = [];
        foreach ($map as $tile) {
            if ($tile["finish"]) {
                $finishTiles[] = $tile["x"]."/".$tile["y"];
            }
        }

        $lemmingsPositions = unserialize($game->lemmings_positions);
        $cards = unserialize($game->cards);
        $playersInformations = $game->getPlayersInformations($cards);
        foreach ($playersInformations as $playerId => $playerInfo) {
            for ($numLemming = 1; $numLemming <3; $numLemming++) {
                if ($request->input('hexa-'.$playerId.'-'.$numLemming.'-x') != '' &&
                    $request->input('hexa-'.$playerId.'-'.$numLemming.'-y') != '' ) {
                    $x = (int)$request->input('hexa-' . $playerId . '-' . $numLemming . '-x');
                    $y = (int)$request->input('hexa-' . $playerId . '-' . $numLemming . '-y');
                    $finish = 0;
                    if (in_array($x.'/'.$y, $finishTiles)) {
                        $finish = 1;
                    }
                    $lemmingsPositions[$playerId][$numLemming] = ["x" => $x, "y" => $y, "finish" => $finish];
                }
            }
        }

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
        $map = json_decode($game->map->map,true);
        $finishTiles = [];
        foreach ($map as $tile) {
            if ($tile["finish"]) {
                $finishTiles[] = $tile["x"]."/".$tile["y"];
            }
        }

        $winnerId = 0;
        foreach ($lemmingsPositions as $playerId => $lemmings) {
            $lemming1 = $lemmings[1]['x']."/".$lemmings[1]['y'];
            $lemming2 = $lemmings[2]['x']."/".$lemmings[2]['y'];

            if ($winnerId == 0 && in_array($lemming1, $finishTiles) && in_array($lemming2, $finishTiles)) {
                $winnerId = $playerId;
            }
        }
        if (!empty($winnerId)){
            $game->winner = $winnerId;
            $game->status = Game::STATUS_ENDED;
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
            foreach ($cards as $cardIdTmp=>$card) {
                if ($card['playerId'] == Card::STATUS_PLAYED){
                    $cards[$cardIdTmp]['playerId'] = Card::STATUS_AVAILABLE;
                }
            }
        }

        return $cards;
    }

    private function takeANewCard(&$cards, $game){
        $this->renewTheDeck($cards);
        $cardAffected = false;
        foreach ($cards as $cardIdTmp =>$card){
            if ($card['playerId'] == Card::STATUS_AVAILABLE && $cardAffected == false){
                $playerId = Auth::user()->id;
                if ($game->same) {
                    $playerId = $game->player;
                }
                $cards[$cardIdTmp]['playerId'] = $playerId;
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
        if ($game->same || $game->player == Auth::user()->id) {
            $playerId = Auth::user()->id;
            if ($game->same) {
                $playerId = Auth::user()->id;
            }
            $cards = unserialize($game->cards);
            $cardsId = $request->input("renewCards");
            if (!empty($cardsId)) {
                foreach ($cards as $cardId => $card) {
                    if ($card['playerId'] == $playerId && in_array($cardId, $cardsId)) {
                        $cards[$cardId]['playerId'] = Card::STATUS_PLAYED;
                    }
                }
            }

            $nbCard = 0;
            foreach ($cards as $cardId => $card) {
                if ($card['playerId'] == $playerId) {
                    $nbCard++;
                }
            }

            while ($nbCard < Game::NB_CARDS_MAX_BY_PLAYER) {
                $this->takeANewCard($cards, $game);
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
