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
                        <li>
                            <a href="{{env('APP_URL')}}/create">{{__('Create a game')}}</a>
                        </li>
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
