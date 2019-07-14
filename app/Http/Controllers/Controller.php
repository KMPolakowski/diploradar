<?php

namespace App\Http\Controllers;

use App\Models\PagePiece;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function showHtmlOfPagePiece(int $id)
    {
        $piece = PagePiece::find($id);

        if (isset($piece)) {
            return "<a href='/page_piece/" . (string) ($id+1) . "' > NEXT </a>" .
            $piece->html;
        }
    }
}
