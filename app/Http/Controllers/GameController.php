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

        return redirect("/start/".$game->id."?same=1&nb_players=".$request->input("nb_players"));
    }

    public function start($id, Request $request)
    {
        $game = $this->gameManager->loadById($id);
        if ($game->status == GAME::STATUS_WAITING && Auth()->user()->id == $game->player1_id) {
            $this->gameManager->load($game);
            $game = $this->gameManager->start($request);
            return redirect("/game/".$game->id);
        } else {
            return redirect("/game/".$game->id)->withError("This game is already started.");
        }
    }

    public function game($id, Request $request)
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
}
