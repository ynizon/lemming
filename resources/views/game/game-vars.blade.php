<input type="hidden" id="max_time" value="{{$maxTime}}" />
<input type="hidden" id="game_id" value="{{$game->id}}" />
<input type="hidden" id="game_status" value="{{$game->status}}" />
<input type="hidden" id="game_pusher_id" value="{{env('PUSHER_APP_ID')}}" />
<input type="hidden" id="game_player" value="{{$game->player}}" />
<input type="hidden" id="game_lastmoves" value="{{$game->player_lastmoves}}" />
<input type="hidden" id="game_reload" value="{{ $gameReload }}" />
<input type="hidden" id="icon_start" value="{{ config("app.start") }}" />
<input type="hidden" id="icon_finish" value="{{ config("app.finish") }}" />
<input type="hidden" id="is_your_turn" value="@if ($game->player == Auth()->user()->id || $game->same) 1 @else 0 @endif" />
<input type="hidden" id="icon_number" value="{{$iconNumber}}" />
<input type="hidden" id="emojis" value="{{config("app.emojis")}}" />
<input type="hidden" id="same" value="{{ $game->same }}" />
<input type="hidden" id="is_started" value="@if ($game->status == Game::STATUS_STARTED) 1 @else 0 @endif" />
<div id="tile-hover"><div class="hexagone"><div class="hexagonemain"></div></div></div>
<input type="hidden" id="editor" name="editor" value="{{$editor}}" />
