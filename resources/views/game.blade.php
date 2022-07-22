<?php
use App\Models\Card;
use App\Models\Game;

?>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div id="info" class="alert-success">
                <i class="fa fa-info"></i>{{__("Rules are available in the footer")}}.
                <br/>
                @if ($game->status != Game::STATUS_STARTED)
                    {{__("Game's status")}}: {{__($game->status)}}<br/>
                @endif

                @if ($game->status == Game::STATUS_STARTED && $game->player == Auth::user()->id)
                    - {{__("Select your lemming")}}<br/>
                    - {{__('Choose a card')}}
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
                    {{__('It was')}}
                    @if (0 != $game->winner)) {{ $playersName[$game->winner] }}. @endif
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
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-color="player{{$loop->iteration}}"
                                        data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                                >Lemming 1</span>
                                - <span class="lemming cursor" id="lemming2"
                                        data-lemming = "2"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-color="player{{$loop->iteration}}"
                                        data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                                >Lemming 2</span>
                            @else
                                : <span class="lemming"
                                        data-color="player{{$loop->iteration}}"
                                        data-lemming = "1"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                                >Lemming 1</span>
                                - <span class="lemming"
                                        data-color="player{{$loop->iteration}}"
                                        data-lemming = "2"
                                        data-player = "{{$playerId}}"
                                        data-content="{{config("app.icons")[$loop->iteration-1]}}"
                                        data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                        data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                                >Lemming 2</span>
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
                <form method="POST" action="/renew/{{$game->id}}">
                    @csrf
                    <ul class="cards">
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
                <script src="/js/utils.js"></script>
                <div id='hexmap' class='hexmap' style="width:100%;height:1000px;">

                </div>
                <script src="/js/svg.min.js"></script>
                <script src="/js/honeycomb.min.js"></script>
                <script>
                    const draw = SVG(document.getElementById('hexmap'))
                    const Hex = Honeycomb.extendHex({
                        size: 35,
                        mydraw: null,

                        addMarker() {
                            if (this.text !== '') {
                                this.mydraw.text(this.text).font({ fill: this.color })
                                    .addClass('x-'+this.x+'_y-'+this.y)
                                    .move(this.coordX+3, this.coordY+10);
                            }
                        },

                        render(draw) {
                            const { x, y } = this.toPoint()
                            const corners = this.corners()
                            this.mydraw = draw;
                            this.start = false;
                            this.finish = false;
                            this.landscape = "none";
                            this.picture = "/images/meadow.png";
                            this.text = "";
                            this.color=  "#000000";
                            this.coordX = x;
                            this.coordY = y;

                            this.draw = draw
                                .polygon(corners.map(({ x, y }) => `${x},${y}`))
                                .fill(this.picture)
                                .stroke({ width: 1, color: '#fff' })
                                .addClass('poly-x-'+this.x+'_y-'+this.y)
                                .translate(x, y);
                        },
                        highlight() {
                            this.draw
                                // stop running animation
                                .stop(true, true)
                                .fill({ opacity: 1, color: 'aquamarine' })
                                .animate(1000)
                                .fill({ opacity: 0, color: 'none' })
                        }
                    })
                    const Grid = Honeycomb.defineGrid(Hex);

                    const grid = Grid.rectangle({
                        width: {{config("app.map_width")}},
                        height: {{config("app.map_height")}},
                        // render each hex, passing the draw instance
                        onCreate(hex) {
                            hex.render(draw);
                        }
                    })

                    // For create new Map see utils.js
                    // var deserializedGrid=createOriginalMap()
                    let deserializedGrid = JSON.parse('{!! str_replace("\n",'',$map) !!}');

                    deserializedGrid.forEach((hexa, index) => {
                        let coord = {x: hexa.x, y:hexa.y};
                        grid.get(coord).landscape = hexa.landscape;
                        grid.get(coord).picture = hexa.picture;
                        grid.get(coord).finish = hexa.finish;
                        grid.get(coord).start = hexa.start;
                        grid.get(coord).text = hexa.text;
                        grid.get(coord).draw.fill(grid.get(coord).picture) ;
                    });
                </script>
            </div>
        </div>
    </div>

    <!--Change player -> reload page -->
    <script>
        initCards();
        initMap();
        initLemmings();

        /*
        let timer = 2000;
        @if (!empty(env('PUSHER_APP_ID')))
            Echo.channel(`game-{{$game->id}}`)
                .listen('.NextPlayer', (event) => {
                    console.log("public");
                    window.location.reload();
                });
            timer = 30000;
        @endif

        window.setInterval(function() {
            let isFocused = (document.activeElement === document.getElementById('question'));
            if (!isFocused){
                jQuery.ajax('{{env('APP_URL')}}/whoplay/{{$game->id}}').done(function(response) {
                    if (response.player == <?php echo Auth::user()->id;?>){
                        window.location.reload();
                    }
                })
            }
        },timer)
        */
    </script>
</div>
@endsection
