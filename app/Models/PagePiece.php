<?php

namespace App\Models;

use App\Models\Event;
use App\Models\ForeignMinistryPage;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class PagePiece extends Model
{
    protected $table = "page_piece";

    public function ForeignMinistryPage()
    {
        return $this->belongsTo(ForeignMinistryPage::class);
    }

    public function Event()
    {
        return $this->belongsTo(Event::class);
    }
}
