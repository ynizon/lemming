@if (($game->status == Game::STATUS_STARTED && $game->winner == 0) || $game->status != Game::STATUS_STARTED)
    <div id="info" class="alert-success">
        @if ($game->status != Game::STATUS_STARTED)
            @if ($game->status != Game::STATUS_ENDED)
                <i class="fa fa-info"></i>{{__("Rules are available in the footer")}}.
                <br/>
            @endif
            {{__("Game's status")}}: {{__($game->status)}}<br/>
        @endif

        @if ($game->status == Game::STATUS_STARTED && $game->winner == 0)
            @if ($game->same || $game->player == Auth::user()->id)
                {{__("It's you turn")}} :<br/>
                - {{__("Select your lemming (1 or 2)")}}<br/>
                - {{__('Choose a card')}}<br/>
                - {{__('Move your lemming on the map')}}
            @else
                {{__('Waiting the other player')}}
            @endif
        @endif
    </div>
@endif

<input type="hidden" id="max_time" value="{{$maxTime}}" />
<input type="hidden" id="game_id" value="{{$game->id}}" />
<input type="hidden" id="game_status" value="{{$game->status}}" />
<input type="hidden" id="game_pusher_id" value="{{$game->status}}" />
<input type="hidden" id="game_player" value="{{$game->player}}" />
<input type="hidden" id="game_lastmoves" value="{{$game->player_lastmoves}}" />
<input type="hidden" id="game_reload" value="{{ $gameReload }}" />
<input type="hidden" id="icon_start" value="{{ config("app.start") }}" />
<input type="hidden" id="icon_finish" value="{{ config("app.finish") }}" />

@if ($game->status == Game::STATUS_WAITING)
    @if ($game->same || $game->player1_id == Auth::user()->id)
        <br/>
        <div>
            <a class="btn btn-primary" href="/start/{{$game->id}}">{{__("Start the game")}}</a>
        </div>
    @else
        @if (!in_array(Auth::user()->id, [$game->player1_id, $game->player2_id, $game->player3_id, $game->player4_id]))
            <br/>
            <div><a class="btn btn-primary" href="/join/{{$game->id}}">{{__("Join the game")}}</a><br/>
            </div>
        @endif
    @endif
@endif

@if ($game->winner == Auth::user()->id)
    <div class="alert alert-success" role="alert">
        {{__('You win')}}<br/>
        <a href="{{env('APP_URL')}}/replay/{{$game->id}}">{{__('Play again')}}</a>
    </div>
@endif

@if ($game->winner != Auth::user()->id && !empty($game->winner))
    <div class="alert alert-danger" role="alert">
        {{__('You loose')}}<br/>
        {{__('The winner is')}}
        @if (0 != $game->winner) {{ $playersInformations[$game->winner]['name'] }}. @endif
        <br/><a href="/replay/{{$game->id}}">{{__('Play again')}}</a>
    </div>
@endif
<br/>
<div class="playerinfos">
    <h5>{{__('Players')}}:</h5>
    <ul>
        @foreach ($playersInformations as $playerId => $playerInfo)
            <li>
                <div class="player{{$loop->iteration}}">
                    <span class="icon-player" id="icon-{{$loop->iteration-1}}">{{$playerInfo['icon']}}</span>
                    {{$playerInfo['name']}} - {{$playerInfo['nbCards']}} {{__('card(s)')}}
                    @if ($game->status == Game::STATUS_STARTED)
                        @if (($game->same && $playerId == $game->player) || (!$game->same && $playerId == Auth::user()->id))
                            <input id="current_icon" type="hidden" value="{{$playerInfo['icon']}}" />
                            : <span class="lemming cursor" id="lemming1"
                                    data-lemming = "1"
                                    data-finish = "{{$lemmingsPositions[$playerId][1]["finish"]}}"
                                    data-player = "{{$playerId}}"
                                    data-content = "{{$playerInfo['icon']}}"
                                    data-color = "player{{$loop->iteration}}"
                                    data-x = "{{$lemmingsPositions[$playerId][1]["x"]}}"
                                    data-y = "{{$lemmingsPositions[$playerId][1]["y"]}}"
                            >Lemming 1</span>
                            @if ($lemmingsPositions[$playerId][1]["finish"])
                                {{config("app.finish")}}
                            @endif
                            - <span class="lemming cursor" id="lemming2"
                                    data-lemming = "2"
                                    data-finish = "{{$lemmingsPositions[$playerId][2]["finish"]}}"
                                    data-player = "{{$playerId}}"
                                    data-content = "{{$playerInfo['icon']}}"
                                    data-color = "player{{$loop->iteration}}"
                                    data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                    data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                            >Lemming 2</span>
                            @if ($lemmingsPositions[$playerId][2]["finish"])
                                {{config("app.finish")}}
                            @endif
                        @else
                            : <span class="lemming"
                                    data-color="player{{$loop->iteration}}"
                                    data-lemming = "1"
                                    data-finish = "{{$lemmingsPositions[$playerId][1]["finish"]}}"
                                    data-player = "{{$playerId}}"
                                    data-content="{{$playerInfo['icon']}}"
                                    data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                    data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                            >Lemming 1</span>
                            @if ($lemmingsPositions[$playerId][1]["finish"])
                                {{config("app.finish")}}
                            @endif
                            - <span class="lemming"
                                    data-color="player{{$loop->iteration}}"
                                    data-lemming = "2"
                                    data-finish = "{{$lemmingsPositions[$playerId][2]["finish"]}}"
                                    data-player = "{{$playerId}}"
                                    data-content="{{$playerInfo['icon']}}"
                                    data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                    data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                            >Lemming 2</span>
                            @if ($lemmingsPositions[$playerId][2]["finish"])
                                {{config("app.finish")}}
                            @endif
                        @endif
                        @if ($lemmingsPositions[$playerId][1]["finish"] && $lemmingsPositions[$playerId][2]["finish"])
                            {{config("app.winner")}}
                        @endif

                        @if ($playerId == $game->player)
                            {{config("app.next")}}
                        @else
                            {{config("app.wait")}}
                        @endif
                    @endif

                    @if ($playerInfo['lastcard_score'] !== '')
                        <span title="{{__("Show last moves")}}" onclick="window.game.game.seeLastMoves()"
                              title="{{__('Last card played')}}"
                              class="minicard landscape-{{$playerInfo['lastcard_landscape']}}">{{$playerInfo['lastcard_score']}}</span>
                    @endif
                    &nbsp;&nbsp;<a onclick='window.game.game.removePlayer("/game/{{$game->id}}/removePlayer/{{$playerIdTrash}}")'
                       class="@if ($playerIdTrash != $playerId) hidden @endif"><i class="fa fa-trash cursor"></i></a>
                </div>
            </li>
        @endforeach
    </ul>
</div>
<div id="tile-hover"><div class="hexagone"><div class="hexagonemain"></div></div></div>
<input type="hidden" id="icon_number" value="{{$iconNumber}}" />
<input type="hidden" id="is_started" value="@if ($game->status == Game::STATUS_STARTED) 1 @else 0 @endif" />
<input type="hidden" id="is_your_turn" value="@if ($game->player == Auth()->user()->id || $game->same) 1 @else 0 @endif" />
@if ($game->status == Game::STATUS_STARTED && ($game->same || $game->player == Auth::user()->id))
    <br/>
    @foreach (Card::CARDS as $land)
        <input type="hidden" id="nb_{{$land}}" value="{{3-$mapUpdate[$land]}}" />
    @endforeach

    <form class="forminfo" method="post" onsubmit="return window.game.game.validateCardAndPath()" action="/update/{{$game->id}}">
        @csrf
        <input type="hidden" id="game_id" name="game_id" value="{{$game->id}}" />
        <input type="hidden" id="num_lemming" name="num_lemming" value="" />
        <input type="hidden" id="path" name="path" value="" />
        <input type="hidden" id="full_path" name="full_path" value="" />
        <input type="hidden" id="card_id" name="card_id" value="" />
        @foreach ($playersInformations as $playerId => $playerInfo)
            <input type="hidden" id="hexa-{{$playerId}}-1-x" name="hexa-{{$playerId}}-1-x" value="" />
            <input type="hidden" id="hexa-{{$playerId}}-1-y" name="hexa-{{$playerId}}-1-y" value="" />
            <input type="hidden" id="hexa-{{$playerId}}-2-x" name="hexa-{{$playerId}}-2-x" value="" />
            <input type="hidden" id="hexa-{{$playerId}}-2-y" name="hexa-{{$playerId}}-2-y" value="" />
        @endforeach
        <input type="hidden" id="changemap-x" name="changemap-x" value="" />
        <input type="hidden" id="changemap-y" name="changemap-y" value="" />
        <input type="hidden" id="changemap-landscape" name="changemap-landscape" value="" />

        <input type="button" onclick="window.game.game.changeCards()" value="{{__('Renew my cards')}}" class="clicker btn btn-primary"/>
        <input type="button" onclick="window.location.reload();" value="{{__('Restart')}}" class="clicker btn btn-secondary"/>
        <input type="submit" id="btnConfirm" value="{{__('Validate')}}" class="btn btn-primary clicker"/>
    </form>
@endif
