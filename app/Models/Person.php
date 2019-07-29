<?php

namespace App\Models;

use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Person extends Model
{
    protected $table = "person";

    public function State()
    {
        return $this->hasOne(State::class);
    }

    public function EventPerson()
    {
        return $this->hasMany(EventPerson::class);
    }
}
