<?php

namespace App\Http\Controllers;

use App\Models\Game;
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
        $yesterday = date('Y-m-d H:i:s', ( time() - 86400) );
        DB::delete("delete from games where created_at < ?", [$yesterday]);

        $mygames = Game::where('player1_id','=',Auth::user()->id)
        ->orderBy('created_at','desc')->paginate(10);

        $games = Game::whereNull('player2_id')
            ->where('player1_id','!=',Auth::user()->id)
            ->orderBy('created_at','desc')->paginate(10);

        return view('home',compact('mygames', 'games'));
    }
}
