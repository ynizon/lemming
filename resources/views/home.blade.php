@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="">
                <meta http-equiv="refresh" content="10;">
                <div class="">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h2>{{__('Create a game')}}</h2>
                    <ul>
                        <li>
                            <a href="{{env('APP_URL')}}/create">{{__('Create an online game')}}</a>
                        </li>
                        <li>
                            <form action="/createAndStart">
                                <input type="submit" class="nobtn" value="{{__("Start the game on the same PC with")}}" />
                                <select name="nb_players" class="form-select" style="width:100px;display:inline;padding-top:2px;">
                                    @for ($i = 2; $i<=Game::NB_MAX_PLAYERS; $i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                {{__("players")}}
                            </form>
                        </li>
                    </ul>

                    @if (count($games)>0)
                        <h2>{{__('Join a game')}}</h2>
                        <ul>
                            @foreach ($games as $game)
                                <li>
                                    <a href="{{env('APP_URL')}}/game/{{ $game->id }}">#{{ $game->id }} - {{ $game->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                        {{ $mygames->links() }}
                    @endif

                    <h2>{{__('My games')}}</h2>
                    <ul>
                        @foreach ($mygames as $game)
                        <li>
                            <a href="{{env('APP_URL')}}/game/{{ $game->id }}">#{{ $game->id }} - {{ $game->name }}</a>
                            &nbsp;
                            <a href="{{env('APP_URL')}}/remove/{{ $game->id }}"><i class="fa fa-trash"></i>&nbsp;&nbsp;{{__('Remove')}}</a>
                        </li>
                        @endforeach
                    </ul>
                    {{ $mygames->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
