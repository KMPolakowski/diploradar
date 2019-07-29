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

class RemoveDuplicatePagePieces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:duplicate:page_pieces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Foreign Ministries';

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
        $pagePieces = PagePiece::with("ForeignMinistryPage")
            ->offset(0)
            ->limit(500)
            ->get();

        $pagePiecesToCompare = PagePiece::with("ForeignMinistryPage")->get();

        foreach ($pagePieces as $pagePiece) {

            dump($pagePiece->id);

            $pNodes = $dom
                ->loadStr($pagePiece->html)
                ->find("p")
                ->toArray();

            $paragraphs = [];

            foreach ($pNodes as $pNode) {
                $paragraphs[] = $pNode->text();
            }

            foreach ($pagePiecesToCompare as $comparedPagePiece) {
                if (
                    $comparedPagePiece->id === $pagePiece->id || ($comparedPagePiece->ForeignMinistryPage->foreign_ministry_id
                        !== $pagePiece->ForeignMinistryPage->foreign_ministry_id)
                ) {
                    continue;
                }

                $pNodes = $dom->loadStr($comparedPagePiece->html)->find("p")->toArray();

                $paragraphsToCompare = [];

                foreach ($pNodes as $pNode) {
                    $paragraphsToCompare[] = $pNode->text();
                }

                $matchingSentences =
                    array_filter(
                        array_intersect(
                            $paragraphsToCompare,
                            $paragraphs
                        ),
                        function ($sentence) {
                            if (strlen($sentence) >= 30) return true;
                        }
                    );

                $compareArray = [
                    strlen($comparedPagePiece->text) => $comparedPagePiece,
                    strlen($pagePiece->text) => $pagePiece
                ];

                $smallerWordsCount = min(
                    array_keys($compareArray)
                );

                if (count($matchingSentences) >= 2) {
                    dump($compareArray[max(array_keys($compareArray))]->text);
                    print_r($compareArray[$smallerWordsCount]->text);
                    dump($matchingSentences);

                    $compareArray[$smallerWordsCount]->delete();
                }
            }
        }
    }
}
