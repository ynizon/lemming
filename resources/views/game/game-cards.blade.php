@if (empty($game->winner))
    <br/>
    <h3>
        <span id="your_icon" @if ($game->player != Auth()->user()->id && !$game->same) class="rotate" @endif>
            {{$yourIcon}}
        </span>

        {{__("Your deck")}}
    </h3>
    <h6 style="padding-left:40px">(max = 6)</h6>
    <form method="POST" action="/renew/{{$game->id}}" onsubmit="return checkNbCardsToRenew()">
        @csrf
        <ul class="cards" id="mycard">
            @foreach (Card::CARDS as $landscape)
                @for ($k = 4; $k >= 0; $k--)
                    @foreach ($cards as $cardId => $card)
                        @if ($k == $card['score'] && $card['landscape'] == $landscape && (($card['playerId'] == Auth()->user()->id && !$game->same) || ($card['playerId'] == $game->player && $game->same)))
                            <li style="width: max-content;">
                                <input type="checkbox" class="chk cursor changecard hidden" value="{{$cardId}}" name="renewCards[]"/>
                                <div class="card yourcard landscape-{{$card['landscape']}} "
                                     data-cardid="{{$cardId}}"
                                     data-score="{{$card['score']}}" data-landscape="{{$card['landscape']}}">
                                    <div class="card-body cursor" alt="{{$card['landscape']}}">
                                        <h5 class="card-title">{{$card['score']}}</h5>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                @endfor
            @endforeach
            @if ($game->status == Game::STATUS_STARTED && (($game->same) || (!$game->same && $game->player == Auth::user()->id)))
                <li class="changecard hidden">
                    <input type="checkbox" class="chk cursor" onclick="$('.chk').prop('checked',$(this).prop('checked'));"/>
                    <div class="renew">
                        <input type="submit" id="renew_cards" class="btn btn-primary" value="{{__("Renew selected cards")}}" />
                    </div>
                </li>
            @endif
        </ul>
    </form>
@endif

