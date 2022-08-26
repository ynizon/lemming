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
                <span class="rotate">{{config("app.wait")}}</span>
                {{__('Waiting the other player')}} :
                @foreach ($playersInformations as $playerId => $playerInfo)
                    @if ($playerId == $game->player)
                        <span class="icon-player">{{$playerInfo['icon']}}</span>
                    @endif
                @endforeach
            @endif
        @endif
    </div>
@endif

@include('game/game-vars', ['game' => $game, 'gameReload'=>$gameReload, 'maxTime'=>$maxTime, "editor"=>0])

@if ($game->status == Game::STATUS_WAITING)
    @if ($game->same || $game->player1_id == Auth::user()->id)
        <br/>
        <div>
            {{__("Map")}} :
            <select id="change_map" class="form-select myselect" onchange="window.game.game.changeMap(this.value)">
                @foreach ($game->maps() as $map)
                    <option @if ($game->map->id == $map->id) selected @endif value="{{$map->id}}">{{__($map->name)}}</option>
                @endforeach
            </select>

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
    <img alt="winner" class="winner" src="/images/winner{{$winnerNumber}}.png">
    <br/>
@endif
<br/>
<div class="playerinfos">
    <table width="100%">
        <thead>
            <tr>
                <td>
                    <i onclick="$('#settings').toggleClass('hidden')" class="fa fa-gear cursor hidden"></i>
                </td>
                <td>
                    {{__('Players')}}
                </td>
                <td>
                    {{__('card(s)')}}
                </td>
                <td>
                    {{__('Lemmings')}}
                </td>
                <td>
                    {{__('Last card played')}}
                </td>
                <td>
                    &nbsp;
                </td>
            </tr>
        </thead>
        <tbody>
            <tr class="hidden" id="settings">
                <td colspan="5">
                    {{__('Map size')}} :
                    <select id="map_size" onchange="window.game.game.saveSettings()">
                        <option value="35" @if (35 == Cookie::get('map_size')) selected @endif>35 px</option>
                        <option value="30" @if (30 == Cookie::get('map_size')) selected @endif>30 px</option>
                        <option value="25" @if (25 == Cookie::get('map_size')) selected @endif>25 px</option>
                    </select>
                </td>
            </tr>
            @foreach ($playersInformations as $playerId => $playerInfo)
                <tr class="player{{$loop->iteration}} @if ($playerId == $game->player) player-selected @endif">
                    <td>
                        @if ($playerId == $game->player)
                            {{config("app.next")}}
                        @endif
                    </td>
                    <td>
                        <span class="icon-player" id="icon-{{$loop->iteration-1}}">{{$playerInfo['icon']}}</span>
                        {{$playerInfo['name']}}
                    </td>
                    <td>
                        {{$playerInfo['nbCards']}}
                    </td>
                    <td>
                        @if ($game->status == Game::STATUS_STARTED || $game->status == Game::STATUS_ENDED)
                            @if (($game->same && $playerId == $game->player) || (!$game->same && $playerId == Auth::user()->id))
                                <input id="current_icon" type="hidden" value="{{$playerInfo['icon']}}" />
                                <span class="lemming cursor" id="lemming1"
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
                                &nbsp;<span class="lemming cursor" id="lemming2"
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
                                <span class="lemming"
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
                                &nbsp;<span class="lemming"
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
                        @endif
                    </td>
                    <td>
                        @if ($playerInfo['lastcard_score'] !== '')
                            <span title="{{__("Click to see the last moves")}}" onclick="window.game.game.seeLastMoves()"
                                  title="{{__('Last card played')}}"
                                  class="minicard landscape-{{$playerInfo['lastcard_landscape']}}">{{$playerInfo['lastcard_score']}}</span>
                        @endif
                    </td>
                    <td>
                        <a title="{{__('Remove this player')}}" onclick='window.game.game.removePlayer("/game/{{$game->id}}/removePlayer/{{$playerIdTrash}}")'
                           class="player{{$loop->iteration}} @if ($playerIdTrash != $playerId) hidden @endif"><i class="fa fa-trash cursor"></i></a>
                        &nbsp;
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

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
