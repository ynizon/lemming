@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <form method="POST" action="/saveMap/{{$themap->id}}">
                        <h1>{{__('Map Editor')}}</h1>
                        <input type="name" name="name" id="name" value="{{$themap->name}}" /><br/>
                        <br/>
                        {{__('Choose your landscape')}} :<br/>
                        <ul class="list-horizontal">
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
                        <br/>

                        <select class="form-select myselect" name="published">
                            <option @if ($themap->published == 0) selected @endif value="0">{{__('Draft')}}</option>
                            <option @if ($themap->published == 1) selected @endif value="1">{{__('Published')}}</option>
                        </select>
                        <br/><br/>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                        <input type="hidden" id="editor" name="editor" value="1" />
                        <input type="hidden" id="map_id" value="{{$themap->id}}" />
                        <input type="hidden" id="changemap-x" name="changemap-x" value="" />
                        <input type="hidden" id="changemap-y" name="changemap-y" value="" />
                        <input type="hidden" id="changemap-landscape" name="changemap-landscape" value="" />

                        <a href="/resetMap/{{$themap->id}}" id="reset" class="btn btn-secondary">{{__('Reset')}}</a>
                        &nbsp;&nbsp;
                        <input type="submit" id="save" class="btn btn-primary" value="{{__('Save')}}" />
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