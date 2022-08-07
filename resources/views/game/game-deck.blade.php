@if ($game->status != Game::STATUS_WAITING)
    <br/>
    <div class="padleft">
        <h3>{{__("Global Deck")}}</h3>
        <h6>{{$infoCards}} {{__('remaining cards')}}</h6>
    </div>
    <ul class="cards deck">
        @foreach (Card::CARDS as $landscape)
            <li>
                <div class="card landscape-{{$landscape}}">
                    <div class="card-body nocursor" alt="{{$landscape}}">
                        <h5 class="card-title cards-deck" data-origine = "{{$cardsSummary['line_'.$landscape]}}"
                            data-score = "{{$cardsSummary['total_'.$landscape]}}"
                            data-min = "{{$cardsSummary['min_'.$landscape]}}"
                            id="score-{{$landscape}}">
                            {{$cardsSummary['line_'.$landscape]}}
                        </h5>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
    <br/>
@endif
