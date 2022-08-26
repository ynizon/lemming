<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Auth;
use Illuminate\Http\Request;
use App\Managers\GameManager;

class GameController extends Controller
{
    private $gameManager;

    public function __construct(GameManager $gameManager)
    {
        $this->gameManager = $gameManager;
    }

    public function replay($id)
    {
        $oldGame = Game::findOrFail($id);
        $this->gameManager->create();
        $game = $this->gameManager->init($oldGame);

        return redirect("/game/".$game->id);
    }

    public function create()
    {
        $this->gameManager->create();
        $game = $this->gameManager->init();

        return redirect("/game/".$game->id);
    }

    public function createAndStart(Request $request)
    {
        $this->gameManager->create();
        $game = $this->gameManager->init();

        return redirect("/start/".$game->id."?same=1&map_id=".$request->input("map_id").
            "&nb_players=".$request->input("nb_players"));
    }

    public function start($id, Request $request)
    {
        $game = $this->gameManager->loadById($id);
        if ($game->status == GAME::STATUS_WAITING) {
            $this->gameManager->load($game);
            $game = $this->gameManager->start($request);
            return redirect("/game/".$game->id);
        } else {
            return redirect("/game/".$game->id)->withError("This game is already started.");
        }
    }

    public function game($id)
    {
        $this->gameManager->loadById($id);
        $gameVars = $this->gameManager->game();

        return view('game', $gameVars);
    }

    public function join($id)
    {
        $this->gameManager->loadById($id);
        $game = $this->gameManager->join();
        return redirect("/game/".$game->id);
    }

    public function timeout($id)
    {
        $this->gameManager->loadById($id);
        $game = $this->gameManager->timeout();
        return date("H:i:s", strtotime($game->updated_at)+120);
    }

    public function reload($id, $playerId = '')
    {
        $this->gameManager->loadById($id);
        return $this->gameManager->reload($playerId);
    }

    public function update($id, Request $request)
    {
        $game = $this->gameManager->loadById($id);

        if (!empty($request->input('path')) && (Auth::user()->id == $game->player || $game->same)) {
            $this->gameManager->load($game);
            $game = $this->gameManager->update($request);
        }

        return redirect("/game/".$game->id);
    }

    public function renew($id, Request $request)
    {
        $this->gameManager->loadById($id);
        $game = $this->gameManager->renew($request);

        return redirect("/game/".$game->id);
    }

    public function delete($id)
    {
        $game = $this->gameManager->loadById($id);
        if ($game->player1_id == Auth::user()->id) {
            $game->delete();
        }
        return redirect('/home');
    }

    public function removePlayer($id, $playerId)
    {
        $this->gameManager->loadById($id);
        $game = $this->gameManager->removePlayer($playerId);

        return redirect("/game/".$game->id);
    }

    public function changeMap($id, Request $request)
    {
        $this->gameManager->loadById($id);
        $this->gameManager->changeMap($request);
    }

    public function saveSettings(Request $request)
    {
        $this->gameManager->saveSettings($request);
    }
}
