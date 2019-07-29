<?php

namespace App\Models;

use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class EventPerson extends Model
{
    public function Event()
    {
        return $this->belongsTo(Event::class);
    }

    public function Person()
    {
        return $this->belongsTo(Person::class);
    }
}
