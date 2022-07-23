<?php
use App\Models\Card;
use App\Models\Game;

?>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div id="info" class="">
                @if ($game->status != Game::STATUS_STARTED)
                    <i class="fa fa-info"></i>{{__("Rules are available in the footer")}}.
                    <br/>
                    {{__("Game's status")}}: {{__($game->status)}}<br/>
                @endif

                @if ($game->status == Game::STATUS_STARTED && $game->winner == 0)
                    @if ($game->player == Auth::user()->id)
                        {{__("It's you turn")}} :<br/>
                        - {{__("Select your lemming")}}<br/>
                        - {{__('Choose a card')}}
                    @else
                        {{__('Waiting the other player')}}
                    @endif
                @endif
            </div>

            @if ($game->status == Game::STATUS_WAITING)
                @if ($game->player1_id == Auth::user()->id)
                    <br/>
                    <div><a class="btn btn-primary" href="/start/{{$game->id}}">{{__("Start the game")}}</a><br/>
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
                    {{__('Game over. You win.')}}<br/>
                    <a href="{{env('APP_URL')}}/replay/{{$game->id}}">{{__('Play again')}}</a>
                </div>
            @endif

            @if ($game->winner != Auth::user()->id && !empty($game->winner))
                <div class="alert alert-danger" role="alert">
                    {{__('Game over. You loose.')}}<br/>
                    {{__('The winner is')}}
                    @if (0 != $game->winner) {{ $playersInformations[$game->winner]['name'] }}. @endif
                </div>
            @endif
            <br/>
            {{__('Players')}}:
            <ul>
            @foreach ($playersInformations as $playerId => $playerInfo)
                <li>
                    <div class="player{{$loop->iteration}}">
                        <span class="icon-player" id="icon-{{$loop->iteration-1}}">{{config("app.icons")[$loop->iteration-1]}}</span>
                        {{$playerInfo['name']}} ({{$playerInfo['nbCards']}} {{__('card(s)')}})
                        @if ($game->status == Game::STATUS_STARTED)
                            @if ($playerId == Auth::user()->id)
                                : <span class="lemming cursor" id="lemming1"
                                        data-lemming = "1"
                                        data-finish = "{{$lemmingsPositions[$playerId][1]["finish"]}}"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-color="player{{$loop->iteration}}"
                                        data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                                >Lemming 1</span>
                                @if ($lemmingsPositions[$playerId][1]["finish"])
                                    🏁
                                @endif
                                - <span class="lemming cursor" id="lemming2"
                                        data-lemming = "2"
                                        data-finish = "{{$lemmingsPositions[$playerId][2]["finish"]}}"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-color="player{{$loop->iteration}}"
                                        data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                                >Lemming 2</span>
                                @if ($lemmingsPositions[$playerId][2]["finish"])
                                    🏁
                                @endif
                            @else
                                : <span class="lemming"
                                        data-color="player{{$loop->iteration}}"
                                        data-lemming = "1"
                                        data-finish = "{{$lemmingsPositions[$playerId][1]["finish"]}}"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                                >Lemming 1</span>
                                @if ($lemmingsPositions[$playerId][1]["finish"])
                                    🏁
                                @endif
                                - <span class="lemming"
                                        data-color="player{{$loop->iteration}}"
                                        data-lemming = "2"
                                        data-finish = "{{$lemmingsPositions[$playerId][2]["finish"]}}"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                                >Lemming 2</span>
                                @if ($lemmingsPositions[$playerId][2]["finish"])
                                    🏁
                                @endif
                            @endif
                        @endif

                        @if ($game->status == Game::STATUS_STARTED)
                            @if ($playerId == $game->player)
                                ⬅️
                            @else
                                ⏳
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
            </ul>
            @if ($game->status == Game::STATUS_STARTED && $game->player == Auth::user()->id)
                <br/>
                @foreach (Card::CARDS as $land)
                    <input type="hidden" id="nb_{{$land}}" value="{{3-$mapUpdate[$land]}}" />
                @endforeach


                <form method="post" onsubmit="return validateCardAndPath()" action="/update/{{$game->id}}">
                    @csrf
                    <input type="hidden" id="game_id" name="game_id" value="{{$game->id}}" />
                    <input type="hidden" id="path" name="path" value="" />
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

                    <input type="button" onclick="window.location.reload();" value="{{__('Restart')}}" class="btn btn-secondary"/>
                    <input type="submit" value="{{__('Validate')}}" class="btn btn-primary"/>
                </form>
            @endif
        </div>
        <div class="col-md-4">
            @if (empty($game->winner))
                <h3 class="padleft">{{__("Your deck")}}</h3>
                <form method="POST" action="/renew/{{$game->id}}" onsubmit="return checkNbCardsToRenew()">
                    @csrf
                    <ul class="cards" id="mycard">
                        @foreach (Card::CARDS as $landscape)
                            @for ($k = 4; $k >= 0; $k--)
                                @foreach ($cards as $cardId => $card)
                                    @if ($k == $card['score'] && $card['landscape'] == $landscape && $card['playerId'] == Auth()->user()->id)
                                        <li>
                                            <input type="checkbox" class="chk cursor" value="{{$cardId}}" name="renewCards[]"/>
                                            <div class="card landscape-{{$card['landscape']}}"
                                                 data-cardid="{{$cardId}}"
                                                 data-score="{{$card['score']}}" data-landscape="{{$card['landscape']}}">
                                                <div class="card-body cursor" alt="{{$card['landscape']}}">
                                                    <h5 class="card-title">{{$card['score']}}</h5>
                                                </div>
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            @endfor
                        @endforeach
                        @if ($game->status == Game::STATUS_STARTED && $game->player == Auth::user()->id)
                            <li>
                                <input type="checkbox" class="chk cursor" onclick="$('.chk').prop('checked',$(this).prop('checked'));"/>
                                <div class="renew">
                                    <input type="submit" class="btn btn-primary" value="{{__("Renew your cards")}}" />
                                </div>
                            </li>
                        @endif
                    </ul>
                </form>
            @endif
        </div>
        <div class="col-md-4">
            <div>
                <div class="padleft">
                    <h3>{{__("Global Deck")}}</h3>
                    <h6>{{$infoCards}} {{__('remaining cards')}}</h6>
                </div>
                <ul class="cards deck">
                    @foreach (Card::CARDS as $landscape)
                        <li>
                            <div class="card landscape-{{$landscape}}">
                                <div class="card-body" alt="{{$landscape}}">
                                    <h5 class="card-title cards-deck" data-origine = "{{$cardsSummary['line_'.$landscape]}}"
                                        data-score = "{{$cardsSummary['total_'.$landscape]}}"
                                        data-min = "{{$cardsSummary['min_'.$landscape]}}"
                                        id="score-{{$landscape}}">
                                        {{$cardsSummary['line_'.$landscape]}}
                                    </h5>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="mygrid">
                <div id='hexmap' class='hexmap'>

                </div>
                <script src="/js/svg.min.js"></script>
                <script src="/js/honeycomb.min.js"></script>
                <script src="/js/utils.js"></script>
                <script>
                    loadGame({{config("app.map_width")}}, {{config("app.map_height")}}, '{!! str_replace("\n",'',$map) !!}');


                    let timer = 10000;
                    @if (!empty(env('PUSHER_APP_ID')))
                                Echo.channel(`game-{{$game->id}}`)
                            .listen('.NextPlayer', (event) => {
                                console.log("public");
                                window.location.reload();
                            });
                        timer = 30000;
                    @endif

                    @if (($game->status !== Game::STATUS_STARTED) || ($game->player !== Auth::user()->id))
                        window.setInterval(function() {
                            window.location.reload();
                        },timer)
                    @endif
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
