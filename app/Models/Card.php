<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Card extends Model
{
    public $timestamps = false;
    public const LANDSCAPES = ['none','water','forest','rock','earth','desert','out', 'finish','start'];
    public const CARDS = ['water','forest','rock','earth','desert'];
    public const STATUS_IN_DASHBOARD = -1;
    public const STATUS_PLAYED = -2;
    public const STATUS_AVAILABLE = 0;
}
