<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Auth;
use Illuminate\Http\Request;
use App\Managers\GameManager;

class EditorController extends Controller
{
    private $gameManager;

    public function __construct(GameManager $gameManager)
    {
        $this->gameManager = $gameManager;
    }

    public function removeMap($mapId)
    {
        $this->gameManager->loadMap($mapId);
        $this->gameManager->removeMap($mapId);
        return redirect("/home");
    }

    public function createNewMap()
    {
        $map = $this->gameManager->createNewMap();
        return redirect("/editor/".$map->id);
    }

    public function editor($mapId)
    {
        if ($mapId <= 2) {
            abort(403);
        } else {
            $themap = $this->gameManager->loadMap($mapId);
            $map = $this->gameManager->editor($mapId);
            $game = new Game();
            $game->id = 0;
            $game->map_id = $mapId;
            $game->status = Game::STATUS_STARTED;
            $game->player_lastmoves = json_encode([]);
            $game->same = 1;
            return view('editor', compact('map', 'game', 'themap'));
        }
    }

    public function saveMap($mapId, Request $request)
    {
        $map = $this->gameManager->loadMap($mapId);
        if ($map->user_id == Auth::user()->id) {
            $this->gameManager->saveMap($request);
            if (!empty($request->input("editor"))) {
                return redirect("/editor/".$mapId);
            }
        }
    }

    public function resetMap($mapId)
    {
        $map = $this->gameManager->loadMap($mapId);
        if ($map->user_id == Auth::user()->id && $mapId > 2) {
            $this->gameManager->resetMap();
            return redirect("/editor/".$mapId);
        }
    }
}
