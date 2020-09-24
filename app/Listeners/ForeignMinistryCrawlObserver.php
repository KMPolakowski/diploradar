<?php

namespace App\Listeners;

use PHPHtmlParser\Dom;
use App\Models\ForeignMinistry;
use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;
use App\Services\HtmlFilterService;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class ForeignMinistryCrawlObserver extends CrawlObserver
{
    private $uri;

    private $filterService;
    
    public function __construct(ForeignMinistry $ministry)
    {
        $this->crawledMinistry = $ministry;
        $this->filterService = app(HtmlFilterService::class);
    }

      /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     */
    public function willCrawl(UriInterface $url)
    {
        dump("will visit " . $url->__toString());
    }

    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ) {
        dump("visiting " . $url->__toString());

        $this->filterService->handleCrawled(
            $url,
            $response,
            $foundOnUrl,
            $this->crawledMinistry->id
        );
    }

    /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ) {
        dump($requestException->getMessage());
    }
}
