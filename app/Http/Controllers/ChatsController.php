<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class ChatsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Fetch all messages for a game
     * @param $id
     * @return mixed
     */
    public function fetchMessages($id)
    {
        return Message::where("game_id", "=", $id)->with('user')->orderby("id", "desc")->take(5)->get();
    }

    /**
     * Persist message to database
     *
     * @param Request $request
     * @param $id
     * @return array
     */
    public function sendMessage(Request $request, $id)
    {
        if (!empty($request->input('message'))) {
            $user = Auth::user();

            $message = $user->messages()->create([
                'message' => $request->input('message'),
                'game_id' => $id
            ]);

            broadcast(new MessageSent($user, $message, $id))->toOthers();
        }
        return ['status' => __('Message Sent')];
    }
}
