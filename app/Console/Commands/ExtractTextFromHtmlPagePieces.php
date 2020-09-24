<?php

namespace App\Console\Commands;

use PHPHtmlParser\Dom;
use App\Models\PagePiece;
use Spatie\Crawler\Crawler;
use GuzzleHttp\RequestOptions;
use App\Models\ForeignMinistry;
use Illuminate\Console\Command;
use Spatie\Crawler\CrawlProfile;
use Illuminate\Support\Facades\DB;
use App\Services\HtmlFilterService;
use Spatie\Crawler\CrawlSubdomains;
use Illuminate\Support\Facades\Schema;
use App\Listeners\ForeignMinistryCrawlObserver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Extracts text from html
 */
class ExtractTextFromHtmlPagePieces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extract:html:page_pieces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts text from html';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Dom $dom)
    {
        $pagePieces = PagePiece::all();

        foreach ($pagePieces as $piece) {
            $piece->text = $dom->loadStr($piece->html)->root->text(true);
            $piece->saveOrFail();
        }
    }
}
