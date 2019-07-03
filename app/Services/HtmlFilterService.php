<?php

namespace App\Services;

use PHPHtmlParser\Dom;
use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Psr\Http\Message\UriInterface;
use App\Models\ForeignMinistryPage;
use PHPHtmlParser\Dom\AbstractNode;
use Psr\Http\Message\ResponseInterface;

class HtmlFilterService
{
    public function __construct(Dom $dom)
    {
        $this->dom = $dom;
        $this->filterKeywords = [
            "MINISTER", "MEETING", "VISIT", "CONVERSATION", "MEETS", "MET",
            "MEET", "AMBASSADOR", "ANNOUNCEMENT", "INVITE",
        ];
    }

    public function handleCrawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null,
        int $foreignMinistryId
    ) : void {
        $foreignMinistryPage = ForeignMinistryPage::with("ForeignMinistry")
            ->where("url", (string) $url)
            ->whereHas("ForeignMinistry", function ($query) use ($foreignMinistryId) {
                $query->where("id", $foreignMinistryId);
            })
            ->first();

        if ($foreignMinistryPage instanceof ForeignMinistryPage) {
            return;
        }

        $foreignMinistryPage = new ForeignMinistryPage;

        $foreignMinistryPage->foreign_ministry_id = $foreignMinistryId;
        $foreignMinistryPage->url = $url->getPath();
        
        $interestingPieces = $this->extractInterestingPagePieces($response);

        $foreignMinistryPage->save();
    }

    private function extractInterestingPagePieces(ResponseInterface $response) : array
    {
        $document = $this->dom->loadStr((string) $response->getBody());

        // $textHolders = $document->getElementsByTag("p, h, h1, h2, h3");
        $textHolders = $document->getElementsByTag("p");

        $interestingTags = [];

        foreach ($textHolders as $p) {
            if ($this->isInteresting($p)) {
                $this->handleDuplicateTextFromSamePage($p, $interestingTags);
            }
        }

        /**
         * Problems:
         *  > Duplicate nodes on same page => for each parent node get the deepest children and check if these children
         *  have been already filtered out, on duplicates reject the node that is bigger and thus further away from these
         *  children
         *  
         *  > linking elements (shortcuts) from different pages => ignore nodes that are "a" tags or contain an href
         *  > Crawling of same page multiple times
         */


        return $interestingPieces;
    }

    private function handleDuplicateTextFromSamePage(AbstractNode $node, array &$alreadyFilteredTags) : void
    {
        $wordsFromNode = explode(" ", $node->text);

        foreach($alreadyFilteredTags as $tag)
        {
            $wordsFromTag = explode(" ", $tag->text);

            if(count(\array_intersect($wordsFromNode, $wordsFromTag)) > )
        }

    }

    private function isInteresting($node) : bool
    {
        // TODO: If there are two texts that contain n amount of same keywords then
        // reject the shorter text.
        
        $nodeText = strtoupper($node->text);
        
        $foundKeywords = [];

        foreach ($this->filterKeywords as $keyword) {
            if (substr_count($nodeText, $keyword) > 0 && !in_array($keyword, $foundKeywords)) {
                $foundKeywords[] = $keyword;
            }
        }
        
        if (count($foundKeywords) < 2) {
            return false;
        }

        return true;

        // dump("------------------------------------");
        // dump($foundKeywords);
        // dump("------------------------------------");
    }

    private function getParent($node, int $parentDepth)
    {
        if ($parentDepth === 0 || !$node->parent instanceof AbstractNode) {
            return $node;
        }

        return $this->getParent($node->parent, $parentDepth-1);
    }
}
