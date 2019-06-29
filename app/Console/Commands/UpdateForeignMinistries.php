<?php

namespace App\Console\Commands;

use App\User;
use PHPHtmlParser\Dom;
use App\Models\ForeignMinistry;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\InnerNode;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

class UpdateForeignMinistries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:foreign_ministries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Foreign Ministries';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Dom $dom)
    {
        parent::__construct();

        $this->baseUrl = "https://en.wikipedia.org";
        $this->listUrl = "/wiki/Ministry_of_Foreign_Affairs";

        $this->dom = $dom;
    }

    /**
     * Execute the console command.
     *
     * @param  \App\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $this->dom->load($this->baseUrl . $this->listUrl);

        $listings = $this->dom->find("ul")->toArray();

        foreach ($listings as $listing) {
            if (!$listing instanceof InnerNode || !$listing->hasChildren()) {
                continue;
            }
            
            foreach ($listing->getChildren() as $row) {
                if (!$row instanceof InnerNode) {
                    continue;
                }

                $content = $row->find("a");
    
                if ($content->count() === 0) {
                    continue;
                }
                
                $parsedUrl = parse_url($content->href);

                $url = $this->baseUrl . $content->href;

                if (isset($parsedUrl["scheme"])) {
                    $url = $content->href;
                }

                $this->extractDataFromPage($url);
            }
        }
    }

    private function extractDataFromPage(string $url)
    {
        $page = $this->dom->load($url);

        $infoBox = $page->getElementsByClass("infobox", 0);
                
        if ($infoBox->count() === 0) {
            return;
        }

        $infoBox = $infoBox->find("tbody")->find("tr");


        $minister = null;
        $headquarters = null;
        $webSite = null;
        $rowName = null;

        foreach ($infoBox as $rows) {
            if (!$rows instanceof InnerNode || !$rows->hasChildren()) {
                continue;
            }

            $th = $rows->find("th", 0);

            if (is_null($th)) {
                continue;
            }

            $rowName = strtoupper($th->text);

            switch ($rowName) {
                case "WEBSITE": $this->extractRowValue($webSite, $rows, "href");
                break;

                case
                 \levenshtein($rowName, "MINISTER RESPONSIBLE") <= 3 || \levenshtein($rowName, "AGENCY EXECUTIVE") <= 3:
                  $this->extractRowValue($minister, $rows, "text");
                
                break;

                case "HEADQUARTERS": $this->extractRowValue($headquarters, $rows, "text");
                break;
            }
        }

        $foreignMinistry = ForeignMinistry::where('wikipage_url', $url)
        ->first();

        dump([$rowName, $webSite, $minister, $headquarters]);

        if (!isset($foreignMinistry)) {
            $foreignMinistry = new ForeignMinistry;
        }

        $foreignMinistry->website = $webSite ?? $foreignMinistry->website;
        $foreignMinistry->headquarters = $headquarters ?? $foreignMinistry->headquarters;
        $foreignMinistry->minister = $minister ?? $foreignMinistry->minister;
        $foreignMinistry->wikipage_url = $url ?? $foreignMinistry->wikipage_url;

        $foreignMinistry->saveOrFail();
    }

    private function extractRowValue(?string &$assignTo, HtmlNode $node, string $valueType) : void
    {
        $td = $node->find("td", 0);
        
        if (is_null($td) || $td->count() === 0) {
            return;
        }

        $as = $td->find("a");

        if ($as->count() === 0) {
            return;
        }
    
        if ($as->innerHtml() !== null) {
            $assignTo = $as->innerHtml();
        }

        $extractedAs = [];

        foreach ($as as $a) {
            if ($a->{$valueType} !== null) {
                $extractedAs[] = $a->{$valueType};
            }
        }

        $assignTo = implode(", ", $extractedAs);
    }
}
