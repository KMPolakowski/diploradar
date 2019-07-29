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
            ["id", ">=" ,76],
            [
                "id", "<=", 80
            ]
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
        $crawler = Crawler::create(
            [
                     RequestOptions::ALLOW_REDIRECTS => true,
                     RequestOptions::HEADERS => [
                        "Accept" =>
                        "text/html,application/xhtml+xm…plication/xml;q=0.9,*/*;q=0.8",
                        // "Accept-Encoding" =>
                        // "gzip, deflate, br",
                        // "Accept-Language" =>
                        // "en-US,en;q=0.5",
                        // "Cache-Control" =>
                        // "max-age=0",
                        // "Connection" =>
                        // "keep-alive",
                        // "Cookie" =>
                        // "_trs_uv=jybh0sdi_469_kpvk; _trs_ua_s_1=jye4taop_469_46gd",
                        // "DNT" =>
                        // "1",
                        // "Host" =>
                        // "www.fmprc.gov.cn",
                        // "Upgrade-Insecure-Requests" =>
                        // "1",
                        "User-Agent" =>
                        "Mozilla/5.0 (X11; Ubuntu; Linu…) Gecko/20100101 Firefox/68.0"
                        ],
                        RequestOptions::VERIFY => false
                       ]
                    )
                    ->ignoreRobots()
                    ->setMaximumResponseSize(1024 * 1024 * 2.5)
                    ->setMaximumCrawlCount(5000);

        //Always try to append an EN to URL IF SITE IS NOT IN ENGLISH
        foreach ($this->crawlable as $ministry) {
            dump($ministry->website);

            $crawler
                ->setCrawlObservers([
                    new ForeignMinistryCrawlObserver($ministry)
                ])
                ->setCrawlProfile(new CrawlSubdomains($ministry->website))
                ->startCrawling($ministry->website);
        }
    }
}
