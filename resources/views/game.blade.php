<?php
use App\Models\Card;
use App\Models\Game;
?>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="">
                @if ($game->status == Game::STATUS_WAITING)
                    <a href="/start/{{$game->id}}">Start</a>
                @else
                    Game's status: {{$game->status}}
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

                @if (empty($game->winner))
                    @if (!empty($game->player) && $game->player == Auth::user()->id)
                        {{__('Please, choose a card')}}<br/>
                    @endif

                    @foreach ($cards as $card)
                        <ul>
                            @if ($card['player'] == Auth()->user()->id)
                                <li>
                                    {{$card['landscape']}} - {{$card['score']}}
                                </li>
                            @endif
                        </ul>
                    @endforeach

                    @if (!empty($game->player) && $game->player != Auth::user()->id)
                            {{__('Wait other player')}}<br/>
                    @endif
                @endif
            </div>
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
                        width: <?php echo $MAP_WIDTH * $HEX_SIDE * 1.5 + $HEX_SIDE/2; ?>px;
                        height: <?php echo $MAP_HEIGHT * $HEX_SCALED_HEIGHT + $HEX_SIDE; ?>px;
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
                        width: <?php echo $HEX_SCALED_HEIGHT ?>;
                        height: <?php echo $HEX_SCALED_HEIGHT ?>;
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
                    for ($x=0; $x<$MAP_WIDTH; $x++) {
                        for ($y=0; $y<$MAP_HEIGHT; $y++) {
                            // --- Terrain type in this hex
                            $terrain = $map[$x][$y];

                            // --- Image to draw
                            $img = $terrain_images[$terrain];

                            // --- Coordinates to place hex on the screen
                            $tx = $x * $HEX_SIDE * 1.5;
                            $ty = $y * $HEX_SCALED_HEIGHT + ($x % 2) * $HEX_SCALED_HEIGHT / 2;

                            // --- Style values to position hex image in the right location
                            $style = sprintf("left:%dpx;top:%dpx", $tx, $ty);

                            // --- Output the image tag for this hex
                            print "<img src='$img' alt='$terrain' data-landscape='".$terrain."' data-x='".$x."' data-y='".$y."' class='hex' style='zindex:99;$style'>\n";
                        }
                    }
                }
                ?>

                <div id='hexmap' class='hexmap' onclick='handle_map_click(event);'>
                    <?php render_map_to_html(); ?>
                    <img id='highlight' class='hex' src='/images/hex-highlight.png' style='zindex:100;'>
                </div>
            </div>
        </div>
    </div>

    <!--Change player -> reload page -->
    <script>
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
    </script>
</div>
@endsection
