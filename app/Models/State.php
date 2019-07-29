<?php

namespace App\Models;

use App\Models\Location;
use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class State extends Model
{
    protected $table = "state";

    public function Location()
    {
        return $this->hasMany(Location::class);
    }
}
