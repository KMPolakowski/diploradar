<?php

namespace App\Console\Commands;

use Spatie\Crawler\Crawler;
use GuzzleHttp\RequestOptions;
use App\Models\ForeignMinistry;
use Illuminate\Console\Command;
use Spatie\Crawler\CrawlProfile;
use App\Services\HtmlFilterService;
use Spatie\Crawler\CrawlSubdomains;
use App\Listeners\ForeignMinistryCrawlObserver;

class CrawlForeignMinistries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:foreign_ministries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawls Foreign Ministries';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->crawlable = ForeignMinistry::where(
            [
            ["website", "!=", null],
            // ["id", ">", 7]
            ]
        )->get();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $crawler = Crawler::create([RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::HEADERS => ["Accept-Language" => "en-US,en;q=0.5",
            "User-Agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:67.0) Gecko/20100101 Firefox/67.0"
            ]])
            ->ignoreRobots()
            ->setMaximumDepth(100)
            ->setMaximumResponseSize(1024 * 1024 * 2.5)
            ->setDelayBetweenRequests(50);

        //Always try to append an EN to URL IF SITE IS NOT IN ENGLISH
        foreach ($this->crawlable as $ministry) {
            if (!strpos($ministry->website, "/en")) {
                $suffix = "/en";

                if (substr($ministry->website, -1) === "/") {
                    $suffix = "en";
                }

                $ministry->website .= $suffix;
            }

            dump("---------MINISTRY----------");
            dump($ministry->id);

            $crawler
                ->setCrawlObservers([
                    new ForeignMinistryCrawlObserver($ministry)
                ])
                ->setCrawlProfile(new CrawlSubdomains($ministry->website))
                ->startCrawling($ministry->website);
        }
    }
}
