<?php
namespace App\Managers;

use App\Events\Reload;
use App\Models\Map;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Game;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Cookie;

class GameManager
{
    private $game;
    private $map;

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

    public function changeMap(Request $request)
    {
        $mapId = (int) $request->input("map");
        $map = Map::findOrFail($mapId);
        $game = $this->game;
        $game->map_id = $map->id;
        $game->save();

        $changeMapEvent = new Reload($game->id);
        broadcast($changeMapEvent)->toOthers();
    }

    public function removeMap($mapId)
    {
        $map = $this->map;
        if ($map->user_id == Auth::user()->id) {
            $map->delete();
        }
    }

    public function createNewMap()
    {
        $map = new Map();
        $map->map = Map::find("2")->map;
        $map->user_id = Auth::user()->id;
        $map->save();
        $map->name = "Map #". $map->id;
        $map->save();

        return $map;
    }

    public function loadMap($mapId)
    {
        $this->map = Map::find($mapId);
        return $this->map;
    }

    public function editor($mapId)
    {
        $map = Map::findOrFail($mapId);
        $map = json_decode($map->map, true);
        $map = json_encode($map);

        return $map;
    }

    public function resetMap()
    {
        $emptyMap = Map::find(2);
        $this->map->map = $emptyMap->map;
        $this->map->save();
    }

    public function saveMap($request)
    {
        $x = (int) $request->input('x');
        $y = (int) $request->input('y');
        $land = $request->input('landscape');
        $name = $request->input('name');
        $status = $request->input('status');
        $published = (int) $request->input('published');

        $map = json_decode($this->map->map, true);
        if (!empty($land)) {
            $k = 0;
            foreach ($map as $tile) {
                if ($tile["y"] == $y && $tile["x"] == $x) {
                    $map[$k]["landscape"] = $land;
                    $map[$k]["start"] = false;
                    $map[$k]["finish"] = false;
                    if ($land == 'out') {
                        $map[$k]["picture"] = "none";
                    } else {
                        $map[$k]["picture"] = "/images/".$land.".png";
                    }
                    if ($status == 'start') {
                        $map[$k]["start"] = true;
                    }
                    if ($status == 'finish') {
                        $map[$k]["finish"] = true;
                    }
                }
                $k++;
            }

            $this->map->map = json_encode($map);
        }
        $this->map->name = $name;

        $nbStart = 0;
        $nbFinish = 0;
        foreach ($map as $tile) {
            if ($status == 'start') {
                $nbStart++;
            }
            if ($status == 'finish') {
                $nbFinish++;
            }
        }
        if ($nbStart != 2 && $nbFinish < 3) {
            $published = 0;
        }

        $this->map->published = $published;
        $this->map->save();
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
        $map = Map::where("published", "=", 1)->where("id", "=", $request->input("map_id"))->first();
        if (!empty($map)) {
            $game->map_id = $map->id;
        }
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

        // Shuffle player orders
        // No shuffle cards because first players have less cards...
        $playersId = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $playersId[] = $game->$field;
            }
        }
        shuffle($playersId);
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $game->$field = $playersId[$i-1];
            }
        }

        // Set cards
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

        //Set First player
        $playersId = [];
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player'.$i.'_id';
            if (!empty($game->$field)) {
                $playersId[] = $game->$field;
            }
        }
        $game->player = $playersId[0];
        $game->save();

        $startEvent = new Reload($game->id);
        broadcast($startEvent)->toOthers();

        return $game;
    }

    public function init($oldGame = null)
    {
        $game = $this->game;
        $game->map_id = 1;
        $game->player1_id = Auth::user()->id;
        $game->player1_icon = config("app.icons")[0];

        if (!empty($oldGame)) {
            $oldPlayerId = $game->player1_id;
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
        $game->player_lastmoves = json_encode([]);
        for ($k = 1; $k <= Game::NB_MAX_PLAYERS; $k++) {
            $fieldLastCard = 'player'.$k.'_lastcard';
            $game->$fieldLastCard = serialize(['score'=> '', 'landscape'=>'']);
        }

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

        $joinEvent = new Reload($game->id);
        broadcast($joinEvent)->toOthers();

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
        $maxTime = date("H:i:s", strtotime($this->game->updated_at)+120);
        $winnerNumber = $this->getWinnerNumber();

        return compact(
            'winnerNumber',
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

    public function reload($playerId)
    {
        $game = $this->game;
        $return = '';
        if ($game->player != $playerId || $playerId == '') {
            $return = 'reload';
        }
        return response()->json($return);
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
            $fieldCard = 'player'.$i.'_lastcard';
            if (!empty($this->game->$field)) {
                $nbCards = 0;
                foreach ($cards as $card) {
                    if ($card['playerId'] == $this->game->$field) {
                        $nbCards++;
                    }
                }

                $lastCard = unserialize($this->game->$fieldCard);

                $playersInformations[$this->game->$field] =
                    ['name' => User::find($this->game->$field)->name,
                        'nbCards' => $nbCards, 'icon' => $this->game->$fieldIcon,
                        'lastcard_landscape' => $lastCard['landscape'],
                        'lastcard_score' => $lastCard['score']
                        ];
            }
        }
        return $playersInformations;
    }

    private function getWinnerNumber()
    {
        $winnerNumber = 0;
        for ($i = 1; $i<= Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player' . $i . '_id';
            if ($this->game->winner == $this->game->$field) {
                $winnerNumber = $i;
            }
        }
        return $winnerNumber;
    }

    public function whichPlayerHasLeaved()
    {
        $playerId = 0;
        $now = strtotime("now");
        $lastUpdate = strtotime($this->game->updated_at);
        $diff = abs($lastUpdate - $now);

        if ($diff > 210) {
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
            $game->player_lastmoves = json_encode($path);
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

        $findPlayer = false;
        $currentPlayer = 1;
        for ($i = 1; $i<=Game::NB_MAX_PLAYERS; $i++) {
            $field = 'player' . $i . '_id';
            if (!empty($game->$field) && $game->player == $game->$field) {
                $findPlayer = true;
            }
            if (!$findPlayer) {
                $currentPlayer++;
            }
        }

        $fieldLastCard = "player".$currentPlayer."_lastcard";
        $game->$fieldLastCard = serialize(["score"=>$currentScore, "landscape"=>$landscape]);
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

            $endEvent = new Reload($game->id);
            broadcast($endEvent)->toOthers();
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
        $nextPlayerEvent = new Reload($game->id);
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

    public function saveSettings(Request $request)
    {
        Cookie::queue('map_size', $request->input("map_size"));
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
