<?php

namespace App\Models;

use App\Models\PagePiece;
use App\Models\EventPerson;
use App\Models\ForeignMinistry;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Event extends Model
{
    protected $table = 'event';

    public function PagePiece()
    {
        return $this->hasMany(PagePiece::class);
    }

    public function Location()
    {
        return $this->belongsTo(Location::class);
    }

    public function EventPerson()
    {
        return $this->hasMany(EventPerson::class);
    }

    public function Person()
    {
        return $this->belongsToMany(Person::class, 'event_persons');
    }
}
