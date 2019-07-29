<?php

namespace App\Models;

use App\Models\Event;
use App\Models\State;
use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Location extends Model
{
    protected $table = "location";

    public function State()
    {
        return $this->belongsTo(State::class);
    }

    public function Event()
    {
        return $this->hasMany(Event::class);
    }
}
