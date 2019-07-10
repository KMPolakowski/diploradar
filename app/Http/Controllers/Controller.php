<?php

namespace App\Http\Controllers;

use App\Models\PagePiece;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function showHtmlOfPagePiece(string $id)
    {
        $piece = PagePiece::find($id);

        if (isset($piece)) {
            return $piece->html;
        }
    }
}
