@php
     use App\Models\Card;
     use App\Models\Game;
@endphp

@if (empty($game->winner))
    <h3 class="padleft">{{__("Your deck")}}</h3>
    <h6>&nbsp;</h6>
    <form method="POST" action="/renew/{{$game->id}}" onsubmit="return checkNbCardsToRenew()">
        @csrf
        <ul class="cards" id="mycard">
            @foreach (Card::CARDS as $landscape)
                @for ($k = 4; $k >= 0; $k--)
                    @foreach ($cards as $cardId => $card)
                        @if ($k == $card['score'] && $card['landscape'] == $landscape && (($card['playerId'] == Auth()->user()->id && !$game->same) || ($card['playerId'] == $game->player && $game->same)))
                            <li>
                                <input type="checkbox" class="chk cursor" value="{{$cardId}}" name="renewCards[]"/>
                                <div class="card landscape-{{$card['landscape']}}"
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
            @if ($game->status == Game::STATUS_STARTED && (($game->same && $game->player == $game->id) || (!$game->same && $game->player == Auth::user()->id)))
                <li>
                    <input type="checkbox" class="chk cursor" onclick="$('.chk').prop('checked',$(this).prop('checked'));"/>
                    <div class="renew">
                        <input type="submit" class="btn btn-primary" value="{{__("Renew your cards")}}" />
                    </div>
                </li>
            @endif
        </ul>
    </form>
@endif

