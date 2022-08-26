<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\Game;

class Map extends Model
{
    public $timestamps = false;

    public function games()
    {
        return $this->belongsToMany(Game::class);
    }

    public function exportMap()
    {
        $file = fopen(storage_path("maps/".$this->name.".json"), "w+");
        fputs($file, $this->map);
        fclose($file);
    }
}
