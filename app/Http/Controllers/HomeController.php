<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Map;
use Auth;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $yesterday = date('Y-m-d H:i:s', (time() - 86400));
        Game::where("updated_at", "<", $yesterday)->delete();

        $mygames = Game::where('player1_id', '=', Auth::user()->id)
        ->orWhere('player2_id', '=', Auth::user()->id)
        ->orWhere('player3_id', '=', Auth::user()->id)
        ->orWhere('player4_id', '=', Auth::user()->id)
        ->orWhere('player5_id', '=', Auth::user()->id)
        ->orderBy('created_at', 'desc')->paginate(10);

        $mymaps = Map::where("user_id", "=", Auth::user()->id)->get();

        $games = Game::whereNull('player2_id')
            ->where('player1_id', '!=', Auth::user()->id)
            ->where('status', '=', 'waiting')
            ->orderBy('created_at', 'desc')->paginate(10);

        $game = new Game;
        return view('home', compact('mygames', 'games', 'mymaps', 'game'));
    }
}
