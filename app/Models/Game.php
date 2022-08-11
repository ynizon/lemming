<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public const NB_MAX_PLAYERS = 5;
    public const NB_CARDS_MAX_BY_PLAYER = 6;
    public const STATUS_WAITING = 'waiting';
    public const STATUS_STARTED = 'started';
    public const STATUS_PAUSE = 'pause';
    public const STATUS_ENDED = 'ended';
    public const STATUS = [self::STATUS_PAUSE,self::STATUS_WAITING,self::STATUS_STARTED,self::STATUS_ENDED];

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}
