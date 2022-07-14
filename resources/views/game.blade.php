<?php
use App\Models\Card;
?>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="">
                {{$game->status}}
                <a href="/start/{{$game->id}}">Start</a>
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
                    $MAP_WIDTH = 13;
                    $MAP_HEIGHT = 18;
                    $HEX_HEIGHT = 72;

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

                // ----------------------------------------------------------------------
                // --- This is a list of possible terrain types and the
                // --- image to use to render the hex.
                // ----------------------------------------------------------------------
                $terrain_images = [];
                foreach (Card::LANDSCAPES as $landscape) {
                    $terrain_images[$landscape] = "/images/".$landscape.".png";
                }

                // ==================================================================
                function generate_map_data() {
                    // -------------------------------------------------------------
                    // --- Fill the $map array with values identifying the terrain
                    // --- type in each hex.  This example simply randomizes the
                    // --- contents of each hex.  Your code could actually load the
                    // --- values from a file or from a database.
                    // -------------------------------------------------------------
                    global $MAP_WIDTH, $MAP_HEIGHT;
                    global $map, $terrain_images;
                    for ($x=0; $x<$MAP_WIDTH; $x++) {
                        for ($y=0; $y<$MAP_HEIGHT; $y++) {
                            // --- Randomly choose a terrain type from the terrain
                            // --- images array and assign to this coordinate.
                            $map[$x][$y] = array_rand($terrain_images);
                            $map[$x][$y] = "none";
                        }
                    }

                    for ($row = 0 ; $row < 5 ; $row++) {
                        for ($column = 0 ; $column < 4 ; $column++) {
                            $map[$column][$row] = "out";
                        }
                    }
                    $map[4][0] = "out";
                    $map[6][0] = "out";
                    $map[8][0] = "out";
                    $map[10][0] = "out";
                    $map[12][0] = "out";
                    $map[9][0] = "out";
                    $map[10][1] = "out";
                    $map[11][0] = "out";
                    $map[12][1] = "out";
                    $map[11][1] = "out";
                    $map[12][2] = "out";
                    $map[12][3] = "out";
                    $map[4][4] = "out";
                    $map[5][4] = "out";
                    $map[5][5] = "out";
                    $map[6][5] = "out";
                    $map[6][6] = "out";
                    $map[7][6] = "out";
                    $map[7][5] = "out";

                    $map[5][1] = "desert";
                    $map[8][11] = "desert";
                    $map[8][12] = "desert";
                    $map[9][12] = "desert";
                    $map[10][13] = "desert";
                    $map[11][14] = "desert";
                    $map[12][14] = "desert";

                    $map[7][3] = "rock";
                    $map[7][4] = "rock";
                    $map[8][5] = "rock";
                    $map[12][6] = "rock";
                    $map[12][7] = "rock";
                    $map[12][8] = "rock";

                    $map[8][6] = "earth";
                    $map[9][4] = "earth";
                    $map[9][3] = "earth";
                    $map[9][2] = "earth";
                    $map[8][2] = "earth";
                    $map[10][4] = "earth";
                    $map[12][4] = "earth";
                    $map[12][5] = "earth";

                    $map[11][7] = "water";
                    $map[11][8] = "water";
                    $map[10][10] = "water";
                    $map[10][8] = "water";
                    $map[10][9] = "water";
                    $map[9][7] = "water";
                    $map[9][8] = "water";
                    $map[9][9] = "water";
                    $map[9][10] = "water";
                    $map[8][9] = "water";
                    $map[8][8] = "water";

                    $map[12][12] = "water";
                }

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
                            print "<img src='$img' alt='$terrain' class='hex' style='zindex:99;$style'>\n";
                        }
                    }
                }

                // -----------------------------------------------------------------
                // --- Generate the map data
                // -----------------------------------------------------------------
                generate_map_data();
                ?>

                <div id='hexmap' class='hexmap' onclick='handle_map_click(event);'>
                    <?php render_map_to_html(); ?>
                    <img id='highlight' class='hex' src='/images/hex-highlight.png' style='zindex:100;'>
                </div>

                <!--- output a list of all terrain types -->
                <br/>
                <?php
                reset ($terrain_images);
                while (list($type, $img) = each($terrain_images)) {
                    print "<div class='hex-key-element'><img src='$img' alt='$type'><br/>$type</div>";
                }
                ?>
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
