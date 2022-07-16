<?php
use App\Models\Card;
use App\Models\Game;

?>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            Game's status: {{$game->status}}
            @if ($game->status == Game::STATUS_WAITING)
                @if ($game->player1_id == Auth::user()->id)
                    <a class="btn btn-primary" href="/start/{{$game->id}}">Start the game</a>
                @else
                    @if (!in_array(Auth::user()->id, [$game->player1_id, $game->player2_id, $game->player3_id, $game->player4_id]))
                        <a class="btn btn-primary" href="/join/{{$game->id}}">Join the game</a>
                    @endif
                @endif

            @endif
            <br/>
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
            Players (Select Your Lemming):
            <ul>
            @foreach ($playersName as $playerId => $player)
                <li>
                    <div class="player{{$loop->iteration}}">
                        {{$player}}
                        @if ($game->status == Game::STATUS_STARTED && $playerId == Auth::user()->id)
                            : <span class="lemming" id="lemming1"
                                    data-lemming = "1"
                                    data-color="player{{$loop->iteration}}"
                                    data-x="{{$lemmingsPositions[$playerId][1]["x"]}}"
                                    data-y="{{$lemmingsPositions[$playerId][1]["y"]}}"
                            >Lemming 1</span>
                            - <span class="lemming" id="lemming2"
                                    data-lemming = "2"
                                    data-color="player{{$loop->iteration}}"
                                    data-x="{{$lemmingsPositions[$playerId][2]["x"]}}"
                                    data-y="{{$lemmingsPositions[$playerId][2]["y"]}}"
                            >Lemming 2</span>
                        @endif
                    </div>
                </li>
            @endforeach
            </ul>
            @if ($game->status == Game::STATUS_STARTED && $game->player == Auth::user()->id)
                <br/>
                <form method="post" onsubmit="return validateCardAndPath()" action="/update/{{$game->id}}">
                    @csrf
                    <input type="hidden" id="path" name="path" value="" />
                    <input type="hidden" id="hexa-x" name="hexa-x" value="" />
                    <input type="hidden" id="hexa-y" name="hexa-y" value="" />
                    <input type="hidden" id="card_id" name="card_id" value="" />
                    <input type="hidden" id="lemming_number" name="lemming_number" value="" />
                    <input type="submit" value="Validate" class="btn btn-primary"/>
                </form>
            @endif
        </div>
        <div class="col-md-4">
            @if (empty($game->winner))
                @if (!empty($game->player) && $game->player == Auth::user()->id)
                    {{__('Please, choose a card')}}<br/>
                @endif

                <h3>Your deck</h3>
                <ul class="cards">
                @foreach (Card::LANDSCAPES as $landscape)
                    @if ($landscape != 'none' && $landscape != 'out' && $landscape != 'finish')
                        @for ($k = 4; $k >= 0; $k--)
                            @foreach ($cards as $cardId => $card)
                                @if ($k == $card['score'] && $card['landscape'] == $landscape && $card['playerId'] == Auth()->user()->id)
                                    <li>
                                        <div class="card landscape-{{$card['landscape']}}" style="width: 10rem;"
                                             data-cardid="{{$cardId}}"
                                             data-score="{{$card['score']}}" data-landscape="{{$card['landscape']}}">
                                            <div class="card-body" alt="{{$card['landscape']}}">
                                                <h5 class="card-title">{{$card['score']}}</h5>
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        @endfor
                    @endif
                @endforeach
                </ul>
            @endif
        </div>
        <div class="col-md-4">
            <div>
                <h3>Global Deck ({{$infoCards}})</h3>
                <ul class="deck">
                    @foreach (Card::LANDSCAPES as $landscape)
                        @if ($landscape != 'none' && $landscape != 'out' && $landscape != 'finish')
                            <li>
                                <div class="card landscape-{{$landscape}}" style="width: 10rem;">
                                    <div class="card-body" alt="{{$landscape}}">
                                        <h5 class="card-title card-deck" data-origine = "{{$cardsSummary['line_'.$landscape]}}"
                                            data-score = "{{$cardsSummary['total_'.$landscape]}}"
                                            data-min = "{{$cardsSummary['min_'.$landscape]}}"
                                            id="score-{{$landscape}}">
                                            {{$cardsSummary['line_'.$landscape]}}
                                        </h5>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            @if (!empty($game->player) && $game->player != Auth::user()->id)
                {{__('Wait other player')}}<br/>
            @endif
        </div>
    </div>
    <br/>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="mygrid">
                <style type="text/css">
                    <?php
                    // --- Define some constants
                    global $MAP_WIDTH, $MAP_HEIGHT;
                    global $HEX_HEIGHT, $HEX_SCALED_HEIGHT, $HEX_SIDE;
                    global $map, $terrain_images;
                    $MAP_WIDTH = config("app.map_width");
                    $MAP_HEIGHT = config("app.map_height");
                    $HEX_HEIGHT = config("app.hex_height");
                    $terrain_images = $game->getLandscapesPictures();
                    $map = unserialize($game->map);

                    // --- Use this to scale the hexes smaller or larger than the actual graphics
                    $HEX_SCALED_HEIGHT = $HEX_HEIGHT * 1.0;
                    $HEX_SIDE = $HEX_SCALED_HEIGHT / 2;
                    ?>
                    .hexmap {
                        min-width: <?php echo $MAP_WIDTH * $HEX_SIDE * 1.5 + $HEX_SIDE/2; ?>px;
                        min-height: <?php echo $MAP_HEIGHT * $HEX_SCALED_HEIGHT + $HEX_SIDE; ?>px;
                        position: relative;
                        background: #000;
                    }

                    .hex-key-element {
                        width: <?php echo $HEX_HEIGHT * 1.5; ?>px;
                        height: <?php echo $HEX_HEIGHT * 1.5; ?>px;
                        border: 1px solid #fff;
                        float: left;
                        text-align: center;
                    }

                    .hex {
                        position: absolute;
                        width: <?php echo $HEX_SCALED_HEIGHT ?>px;
                        height: <?php echo $HEX_SCALED_HEIGHT ?>px;
                    }
                </style>
                <script type="text/javascript">
                    var hex_height = <?php echo $HEX_SCALED_HEIGHT; ?>;
                    var hex_side = <?php echo $HEX_SIDE; ?>;
                </script>
                <script src="/js/utils.js"></script>

                <?php
                // ==================================================================

                function render_map_to_html() {
                    // -------------------------------------------------------------
                    // --- This function renders the map to HTML.  It uses the $map
                    // --- array to determine what is in each hex, and the
                    // --- $terrain_images array to determine what type of image to
                    // --- draw in each cell.
                    // -------------------------------------------------------------
                    global $MAP_WIDTH, $MAP_HEIGHT;
                    global $HEX_HEIGHT, $HEX_SCALED_HEIGHT, $HEX_SIDE;
                    global $map, $terrain_images;

                    // -------------------------------------------------------------
                    // --- Draw each hex in the map
                    // -------------------------------------------------------------
                    $nbHexa = 0;
                    for ($x=0; $x<$MAP_WIDTH; $x++) {
                        for ($y=0; $y<$MAP_HEIGHT; $y++) {
                            //Event (start / end)
                            $event = '';
                            if ($x == 4 && $y <= 3){
                                $event = 'start';
                            }

                            // --- Terrain type in this hex
                            $terrain = $map[$x][$y];

                            // --- Image to draw
                            $img = $terrain_images[$terrain];

                            // --- Coordinates to place hex on the screen
                            $tx = $x * $HEX_SIDE * 1.5;
                            $ty = $y * $HEX_SCALED_HEIGHT + ($x % 2) * $HEX_SCALED_HEIGHT / 2 -30;

                            // --- Style values to position hex image in the right location
                            $style = sprintf("left:%dpx;top:%dpx", $tx, $ty);

                            // --- Output the image tag for this hex
                            print "<div id='hexa$nbHexa' alt='$terrain' data-landscape='".$terrain."' data-x='".$x."'
                            data-y='".$y."' data-event='$event' class='hex hex-$img' style='$style'
                            ></div>".PHP_EOL;
                            $nbHexa++;
                        }
                    }
                }
                ?>

                <div id='hexmap' class='hexmap'>
                    <?php render_map_to_html(); ?>
                </div>
            </div>
        </div>
    </div>

    <!--Change player -> reload page -->
    <script>
        initCards();
        initLemmings();
        initMap();

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
