@php
    use App\Models\Card;
    use App\Models\Game;
@endphp

<div class="mygrid">
    <div id='hexmap' class='hexmap'>

    </div>

    <script src="/js/utils.js"></script>

    <script>
        let mapWidth = {{config("app.map_width")}};
        let mapHeight = {{config("app.map_height")}};
        let mapTiles = '{!! str_replace("\n",'',$map) !!}';
        let gameId = '{{ $game->id }}';

        document.addEventListener("DOMContentLoaded", function(){
            loadGame(mapWidth, mapHeight, mapTiles, gameId);

            let timer = 10000;
            @if (!empty(env('PUSHER_APP_ID')))
            Echo.channel(`game-{{$game->id}}`)
                .listen('.NextPlayer', (event) => {
                    console.log("public");
                    window.location.reload();
                });
            timer = 30000;
            @endif

            @if (($game->status !== Game::STATUS_STARTED) || (!$game->same && $game->player !== Auth::user()->id))
            window.setInterval(function() {
                window.location.reload();
            },timer)
            @endif
        });
    </script>
</div>
