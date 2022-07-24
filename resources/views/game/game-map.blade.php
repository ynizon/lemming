@php
    use App\Models\Card;
    use App\Models\Game;
@endphp

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

        @if (($game->status !== Game::STATUS_STARTED) || (!$game->same && $game->player !== Auth::user()->id))
        window.setInterval(function() {
            window.location.reload();
        },timer)
        @endif
    </script>
</div>
