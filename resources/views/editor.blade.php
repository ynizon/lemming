@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <form method="POST" action="/saveMap/{{$themap->id}}">
                        <h1>{{__('Map Editor')}}</h1>
                        {{ Session::get('message') }}
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {!! session('error') !!}
                            </div>
                        @endif
                        <input type="name" name="name" id="name" value="{{$themap->name}}" /><br/>
                        <br/>
                        {{__('Choose your landscape')}} :<br/>
                        <ul class="list-horizontal">
                            <li>
                                <div id="hexa-out" class="cursor hexagone hexa-editor" onclick="window.game.game.editTile('out')">
                                    <div class="hexagonemain hex-out"></div>
                                </div>
                            </li>
                            <li>
                                <div id="hexa-meadow" class="cursor hexagone hexa-editor" onclick="window.game.game.editTile('meadow')">
                                    <div class="hexagonemain hex-meadow"></div>
                                </div>
                            </li>
                            @foreach (Card::CARDS as $card)
                                <li>
                                    <div id="hexa-{{$card}}" class="cursor hexagone hexa-editor"  onclick="window.game.game.editTile('{{$card}}')">
                                        <div class="hexagonemain hex-{{$card}}"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <br style="clear:both"/>
                        <ul class="list-horizontal">
                            <li>
                                <div class="cursor" style="font-size:30px" onclick="window.game.game.editTileFinishStart('start')">
                                    {{config("app.start")}}
                                </div>
                            </li>
                            <li>
                                <div class="cursor" style="font-size:30px" onclick="window.game.game.editTileFinishStart('finish')">
                                    {{config("app.finish")}}
                                </div>
                            </li>
                        </ul>
                        <br style="clear:both"/>
                        <br/>

                        <select class="form-select myselect" name="published" id="published">
                            <option @if ($themap->published == 0) selected @endif value="0">{{__('Draft')}}</option>
                            <option @if ($themap->published == 1) selected @endif value="1">{{__('Published')}}</option>
                        </select>
                        <br/><br/>

                        <input type="hidden" name="map_size" id="map_size" value="@if (empty(Cookie::get('map_size'))) 35 @else {{Cookie::get('map_size')}} @endif" />
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" id="editor" name="editor" value="1" />
                        <input type="hidden" id="map_id" value="{{$themap->id}}" />
                        <input type="hidden" id="changemap-x" name="changemap-x" value="" />
                        <input type="hidden" id="changemap-y" name="changemap-y" value="" />
                        <input type="hidden" id="changemap-landscape" name="changemap-landscape" value="" />
                        <input type="hidden" id="changemap-status" name="changemap-status" value="" />

                        <a href="/resetMap/{{$themap->id}}" id="reset" class="btn btn-secondary">{{__('Reset')}}</a>
                        &nbsp;&nbsp;
                        <input type="submit" id="save" class="btn btn-primary" value="{{__('Save')}}" />
                        &nbsp;&nbsp;
                        <a href="/exportMap/{{$themap->id}}" id="reset" class="btn btn-secondary">{{__('Exporter')}}</a>
                        <br/><br/>
                        <i class="fa fa-info"></i>&nbsp;{{__('You need to have 2 start points and at least 3 finish points')}}
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8 scroll">
            @include('game/game-vars', ['game' => $game, 'gameReload'=>0, 'maxTime'=>0, 'iconNumber'=>'', "editor"=>1])
            @include('game/game-map', ['cards' => [], 'game' => $game])
        </div>
    </div>
</div>
@endsection
