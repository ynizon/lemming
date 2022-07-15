<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Card extends Model
{
    public $timestamps = false;
    public const LANDSCAPES = ['none','water','forest','rock','earth','desert','out', 'finish'];
}
