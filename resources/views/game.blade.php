@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    @include('game/game-info', ['cards' => $cards, 'game' => $game, 'winnerNumber'=>$winnerNumber])
                </div>
                @if ($game->status != Game::STATUS_WAITING)
                    <div class="col-md-6">
                        @include('game/game-cards', ['cards' => $cards, 'game' => $game])
                    </div>
                @else
                    <div class="col-md-12">
                        <h5>{{__("Rules")}}</h5>
                        <a href="https://youtu.be/WLSg3jQa570" target="_blank">Les règles en vidéo</a>.<br/>
                        Vous devez emmener vos 2 lemmings du {{config("app.start")}} au {{config("app.finish")}}.
                        Pour cela, vous aller jouer des cartes. Chaque carte possède une couleur qui reprend celle
                        du terrain de jeu. Vos lemmings pourront traverser uniquement les terrains de type prairie
                        ou de la couleur indiquée sur votre carte.
                        Si la valeur de votre carte est supérieure à celle du plan de jeu
                        de la même couleur, alors vous pourrez placer une tuile de cette couleur sur le
                        plan et vous avancerez votre pion de cette valeur. En revanche si votre carte est inférieure ou
                        égale, alors vous avancerez votre pion de cette valeur + celle du total de la couleur.
                        A noter que vous pouvez pousser d'autres lemmings moyennant des points de déplacement.
                        Bonne chance à vous.
                        <br/><br/>
                    </div>
                @endif
                <div class="col-md-6">
                    @include('game/game-deck', ['cards' => $cards, 'game' => $game])
                </div>
                @if (!$game->same)
                    <div class="row">
                        <div class="col-md-12">
                            @include('game/game-chat', ['game' => $game])
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-8 scroll">
            @include('game/game-map', ['cards' => $cards, 'game' => $game])
        </div>
    </div>
</div>
@endsection
