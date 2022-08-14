<?php
namespace App\Managers;

use App\Events\NextPlayer;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Game;
use App\Models\User;
use Auth;

class GameManager
{
    private $game;

    public function create()
    {
        $this->game = new Game();
    }

    public function load($game)
    {
        $this->game = $game;
    }

    public function loadById($gameId)
    {
        $game = Game::findOrFail($gameId);
        $this->game = $game;
        return $game;
    }

    public function getCardsSummary(): array
    {
        $cardsSummary = [];
        foreach (Card::CARDS as $landscape) {
            $cardsSummary[$landscape] = unserialize($this->game->$landscape);
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
        $updates = unserialize($this->game->map_update);

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

    public function start(Request $request)
    {
        $game = $this->game;
        if ($request->input("same") == "1") {
            $nbPlayers = $request->input("nb_players");
            for ($k= 1; $k <= $nbPlayers; $k++) {
                $field = 'player'.$k.'_id';
                $fieldIcon = 'player'.$k.'_icon';
                $game->$field = $k;//Player par defaut
                $game->$fieldIcon = config("app.icons")[$k-1];
            }
            $game->same = 1;
        }
        $game->status = Game::STATUS_STARTED;

        $cards = unserialize($game->cards);
        shuffle($cards);
        $players = [];
        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $players[] = $game->$field;
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
        $map = json_decode($game->map->map, true);
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

        $game->cards = serialize($cards);
        $game->lemmings_positions = serialize($lemmingsPositions);

        //First player
        $playersId = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $playersId[] = $game->$field;
            }
        }

        //No shuffle cards because first players have less cards...
        $game->player = $playersId[0];
        $game->save();

        return $game;
    }

    public function init($oldGame = null)
    {
        $game = $this->game;
        $game->map_id = 1;
        $game->player1_id = Auth::user()->id;
        $game->player1_icon = config("app.icons")[0];

        if (!empty($oldGame)) {
            for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                $fieldIcon = 'player' . $i . '_icon';
                if (!empty($oldGame->$field)) {
                    $game->$field = $oldGame->$field;
                    $game->$fieldIcon = $oldGame->$fieldIcon;
                }
            }
            $game->map_id = $oldGame->map_id;
            $game->same  = $oldGame->same;
        }
        $game->name = date("Y-m-d H:i:s");
        $game->created_at = date("Y-m-d H:i:s");
        $game->status = Game::STATUS_WAITING;
        $game->winner = 0;

        $cardsInit = Card::where("game_id", "=", 0)->get()->shuffle()->take(config("app.nb_cards"));
        $cards = [];
        $k=0;
        foreach ($cardsInit as $card) {
            $cards[$k] = ['score'=>$card->score, 'landscape'=>$card->landscape, 'playerId'=>0];
            $k++;
        }
        $game->earth = serialize([2]);
        $game->rock = serialize([2]);
        $game->water = serialize([2]);
        $game->forest = serialize([2]);
        $game->desert = serialize([2]);
        $game->lemmings_positions = serialize([]);
        $game->cards = serialize($cards);
        $game->map_update = serialize([]);

        $game->save();

        return $game;
    }

    public function join()
    {
        $game = $this->game;
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
                $fieldIcon = 'player' . $playerId . '_icon';
                if (empty($game->$field) &&  $alreadyJoin == false) {
                    $alreadyJoin = true;
                    $game->$field = Auth::user()->id;
                    $game->$fieldIcon = config("app.icons")[$playerId-1];
                    $game->save();
                }
            }
        }
        return $game;
    }

    public function game()
    {
        $game = $this->game;
        $cards = unserialize($game->cards);
        $lemmingsPositions = unserialize($game->lemmings_positions);
        $cardsSummary = $this->getCardsSummary();

        $nbAvailableCards = 0;
        foreach ($cards as $card) {
            if ($card['playerId'] == Card::STATUS_AVAILABLE) {
                $nbAvailableCards++;
            }
        }

        $infoCards = $nbAvailableCards .'/'.count($cards);
        $playersInformations = $this->getPlayersInformations($cards);
        $mapUpdate = [];
        $map = json_decode($game->map->map, true);
        $this->getMapWithUpdate($map, $mapUpdate);

        $gameReload = 0;
        if (($game->status !== Game::STATUS_STARTED) || (!$game->same && $game->player !== Auth::user()->id)) {
            $gameReload = 1;
        }

        $yourIcon = $this->getYourIcon();
        $iconNumber = $this->getIconCurrentPlayer($yourIcon);
        $playerIdTrash = $this->whichPlayerHasLeaved();
        $maxTime = date("H:i:s", strtotime($this->game->updated_at)+60);

        return compact(
            'maxTime',
            'cards',
            'game',
            'playersInformations',
            'lemmingsPositions',
            'cardsSummary',
            'infoCards',
            'mapUpdate',
            'map',
            'gameReload',
            'iconNumber',
            'playerIdTrash',
            'yourIcon'
        );
    }

    public function update(Request $request)
    {
        $game = $this->game;
        $cardId = (int) $request->input('card_id');
        $cards = unserialize($game->cards);
        $this->moveLemming($request);
        $this->playACard($cards, $cardId);
        $this->updateMap($request);
        $this->hasWinner(unserialize($this->game->lemmings_positions));

        $game->cards = serialize($cards);
        $game->save();

        return $game;
    }

    /**
     * Increase timeout
     * @return mixed
     */
    public function timeout()
    {
        $game = $this->game;
        $game->updated_at = date('Y-m-d G:i:s');
        $game->save();

        return $game;
    }

    /**
     * Renew cards
     * @param Request $request
     * @return Game
     */
    public function renew(Request $request): Game
    {
        $game = $this->game;
        if ($game->same || $game->player == Auth::user()->id) {
            $playerId = Auth::user()->id;
            if ($game->same) {
                $playerId = $game->player;
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
        return $game;
    }

    public function removePlayer($playerId)
    {
        $game = $this->game;
        $playerHasLeavedId = $this->whichPlayerHasLeaved();

        if ($game->player == $playerId && $playerId == $playerHasLeavedId) {
            for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                if (!empty($game->$field) && $game->$field == $playerId) {
                    $this->nextPlayer($game);
                    $game->$field = null;
                    $game->save();
                }
            }
        }
        return $game;
    }

    public function getPlayersInformations($cards): array
    {
        $playersInformations = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            $fieldIcon = 'player'.$i.'_icon';
            if (!empty($this->game->$field)) {
                $nbCards = 0;
                foreach ($cards as $card) {
                    if ($card['playerId'] == $this->game->$field) {
                        $nbCards++;
                    }
                }
                $playersInformations[$this->game->$field] =
                    ['name' => User::find($this->game->$field)->name,
                        'nbCards' => $nbCards, 'icon' => $this->game->$fieldIcon];
            }
        }
        return $playersInformations;
    }

    public function whichPlayerHasLeaved()
    {
        $playerId = 0;
        $now = strtotime("now");
        $lastUpdate = strtotime($this->game->updated_at);
        $diff = abs($lastUpdate - $now);

        if ($diff > 90) {
            $playerId = $this->game->player;
        }
        return $playerId;
    }

    public function getYourIcon()
    {
        $icon = '';
        if ($this->game->same) {
            for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                if ($this->game->player == $this->game->$field) {
                    $fieldIcon = 'player' . $i . '_icon';
                    $icon = $this->game->$fieldIcon;
                }
            }
        } else {
            for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
                $field = 'player' . $i . '_id';
                $fieldIcon = 'player' . $i . '_icon';
                if (Auth::user()->id == $this->game->$field) {
                    $icon = $this->game->$fieldIcon;
                }
            }
        }

        return $icon;
    }

    private function moveLemming($request)
    {
        $game = $this->game;
        $cards = unserialize($game->cards);
        $playersInformations = $this->getPlayersInformations($cards);
        $lemmingsPositions = unserialize($game->lemmings_positions);
        $path = json_decode($request->input('path'), true);

        if ($this->checkMove($playersInformations, $request, $lemmingsPositions, $path)) {
            $map = json_decode($game->map->map, true);
            $finishTiles = [];
            foreach ($map as $tile) {
                if ($tile["finish"]) {
                    $finishTiles[] = $tile["x"]."/".$tile["y"];
                }
            }

            foreach ($playersInformations as $playerId => $playerInfo) {
                for ($numLemming = 1; $numLemming <3; $numLemming++) {
                    if ($request->input('hexa-'.$playerId.'-'.$numLemming.'-x') != '' &&
                        $request->input('hexa-'.$playerId.'-'.$numLemming.'-y') != '') {
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
        } else {
            die('PLEASE-DONT-CHEAT - IP LOGGED');
        }
    }

    private function checkMove($playersInformations, $request, $lemmingsPositions, $path)
    {
        //@Todo: check pushing lemmings
        //@Todo: check maximum tiles
        //Check only the current lemming
        $canMove = true;
        $numLemmingSelected = (int) $request->input("num_lemming");
        foreach ($playersInformations as $playerId => $playerInfo) {
            if ($playerId == $this->game->player) {
                for ($numLemming = 1; $numLemming < 3; $numLemming++) {
                    if ($numLemming == $numLemmingSelected &&
                        $request->input('hexa-' . $playerId . '-' . $numLemming . '-x') != '' &&
                        $request->input('hexa-' . $playerId . '-' . $numLemming . '-y') != '') {
                        $startX = $lemmingsPositions[$playerId][$numLemming]['x'];
                        $startY = $lemmingsPositions[$playerId][$numLemming]['y'];
                        $x = (int)$request->input('hexa-' . $playerId . '-' . $numLemming . '-x');
                        $y = (int)$request->input('hexa-' . $playerId . '-' . $numLemming . '-y');

                        $k = 0;
                        while ($k < count($path)) {
                            $moveX = $path[$k]['x'];
                            $moveY = $path[$k]['y'];
                            $canMove = false;
                            if ((($startX + 1) == $moveX && ($startY + 1) == $moveY) ||
                                (($startX + 1) == $moveX && ($startY - 1) == $moveY) ||
                                (($startX + 1) == $moveX && $startY == $moveY) ||
                                (($startX - 1) == $moveX && ($startY + 1) == $moveY) ||
                                (($startX - 1) == $moveX && ($startY - 1) == $moveY) ||
                                (($startX - 1) == $moveX && $startY == $moveY) ||
                                (($startX) == $moveX && ($startY + 1) == $moveY) ||
                                (($startX) == $moveX && ($startY - 1) == $moveY) ||
                                (($startX) == $moveX && $startY == $moveY)) {
                                $startX = $moveX;
                                $startY = $moveY;

                                $canMove = true;
                            }
                            $k++;
                        }

                        if ($x != $startX || $y != $startY) {
                            $canMove = false;
                        }
                    }
                }
            }
        }

        return $canMove;
    }

    private function playACard(&$cards, $cardId)
    {
        $game = $this->game;
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

    private function updateMap($request)
    {
        $game = $this->game;
        $x = (int) $request->input('changemap-x');
        $y = (int) $request->input('changemap-y');
        $landscape = $request->input('changemap-landscape');
        if (!empty($landscape)) {
            $map = unserialize($game->map_update);
            $map[$x][$y] = $landscape;
            $game->map_update = serialize($map);
        }
    }

    private function hasWinner($lemmingsPositions)
    {
        $game = $this->game;
        $map = json_decode($game->map->map, true);
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
        if (!empty($winnerId)) {
            $game->winner = $winnerId;
            $game->status = Game::STATUS_ENDED;
        } else {
            $this->nextPlayer($game);
        }
    }

    private function renewTheDeck(&$cards)
    {
        $nbCardAvailable = 0;
        foreach ($cards as $card) {
            if ($card['playerId'] == Card::STATUS_AVAILABLE) {
                $nbCardAvailable++;
            }
        }

        //@TODO Shuffle

        if ($nbCardAvailable == 0) {
            foreach ($cards as $cardIdTmp => $card) {
                if ($card['playerId'] == Card::STATUS_PLAYED) {
                    $cards[$cardIdTmp]['playerId'] = Card::STATUS_AVAILABLE;
                }
            }
        }

        return $cards;
    }

    private function takeANewCard(&$cards, $game)
    {
        $this->renewTheDeck($cards);
        $cardAffected = false;
        foreach ($cards as $cardIdTmp => $card) {
            if ($card['playerId'] == Card::STATUS_AVAILABLE && $cardAffected == false) {
                $playerId = Auth::user()->id;
                if ($game->same) {
                    $playerId = $game->player;
                }
                $cards[$cardIdTmp]['playerId'] = $playerId;
                $cardAffected = true;
            }
        }
    }

    private function nextPlayer(&$game)
    {
        $nextPlayerEvent = new NextPlayer($game->id);
        broadcast($nextPlayerEvent)->toOthers();

        $playersIds = [];
        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player' . $i . '_id';
            if (!empty($game->$field)) {
                $playersIds[] = $game->$field;
            }
        }

        $nextPlayerId = -1;
        $currentPlayer = $game->player;
        foreach ($playersIds as $playerId) {
            if ($nextPlayerId == 0) {
                $nextPlayerId = $playerId;
            }
            if ($playerId == $currentPlayer && $nextPlayerId == -1) {
                $nextPlayerId = 0;
            }
        }
        if ($nextPlayerId == 0) {
            $nextPlayerId = $playersIds[0];
        }
        $game->player = $nextPlayerId;
    }

    /**
     * Use for test moves
     * @return void
     */
    public function forceMove()
    {
        $lemmingsPositions = unserialize($this->game->lemmings_positions);
        $lemmingsPositions[4][1]["x"]=6;
        $lemmingsPositions[4][1]["y"]=3;
        $lemmingsPositions[4][2]["x"]=5;
        $lemmingsPositions[4][2]["y"]=3;
        $this->game->lemmings_positions = serialize($lemmingsPositions);
        $this->game->save();
    }
}
