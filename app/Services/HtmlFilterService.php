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
        $this->strongKeywords = [
            "MINISTER", "PRESIDENT", "AMBASSADOR", "DEPUTY", "CONSUL", "FOREIGN OFFICER",
            "REPRESENTATIVE", "COUNTERPART", "SIGNED"
        ];
        $this->weakKeywords = [
            "SPOKE", "MET", "MEETING", "MEETS", "SIGNED", "SIGNING", "PLANNED", "TALKED", "VISITED",
            "HOLDS"
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
        
        dump($url->getPath());


        // TX Would be nice

        $foreignMinistryPage = new ForeignMinistryPage;

        $foreignMinistryPage->foreign_ministry_id = $foreignMinistryId;
        $foreignMinistryPage->url = $url->getPath();
        $foreignMinistryPage->save();

        $pagePieces = [];
        
        foreach ($this->extractInterestingNodes($response) as $node) {
            $html = $node->outerHtml();

            $alreadyExisting = PagePiece::
                whereHas("ForeignMinistryPage.ForeignMinistry")
                    ->where("html", $html)
                    ->first();

            if ($alreadyExisting) {
                continue;
            }

            $pagePiece = new PagePiece;
            $pagePiece->html = $html;
            $pagePieces[] = $pagePiece;
        }

        $foreignMinistryPage->PagePiece()->saveMany($pagePieces);
    }

    private function extractInterestingNodes(ResponseInterface $response) : array
    {
        $document = $this->dom->loadStr((string) $response->getBody());

        // $textHolders = $document->getElementsByTag("p, h, h1, h2, h3");
        $textHolders = $document->getElementsByTag("p");

        $interestingNodes = [];

        foreach ($textHolders as $p) {
            if ($this->isInteresting($p)) {
                $interestingNodes[] = $this->getParent($p, 3);
            }
        }

        // $interestingPieces = $this->handleDuplicateTextFromSamePage($interestingTags);
        /**
         * Problems:
         *  > Duplicate nodes on same page => for each parent node get the deepest children and check if these children
         *  have been already filtered out, on duplicates reject the node that is bigger and thus further away from these
         *  children
         *
         *  > linking elements (shortcuts) from different pages => ignore nodes that are "a" tags or contain an href
         *
         *  > Crawling of same page multiple times
         */
        

        return $interestingNodes;
    }

    private function handleDuplicateTextFromSamePage(array $alreadyFilteredTags) : array
    {
        $deepestChildren = [];

        foreach ($alreadyFilteredTags as $parentNode) {
            $deepestChildren[] = $this->getDeepestChildrenAndTheirDepth($parentNode->getChildren());
        }
        
        for ($i = 0; $i < count($deepestChildren); $i++) {
            for ($y = $i+1; $y < count($deepestChildren-1); $y++) {
                //compare objects
                // dump($deepestChildren[$i]->innerHtml());
                // dump($deepestChildren[$y]->innerHtml());
                dump($deepestChildren[$i] == $deepestChildren[$y]);

                if ($deepestChildren[$i] == $deepestChildren[$y]) {
                    if ($deepestChildren[$i][0] > $deepestChildren[$y][0]) {
                        $alreadyFilteredTags[$i] = null;
                    } else {
                        $alreadyFilteredTags[$y] = null;
                    }
                }
            }
        }
        
        return array_filter($alreadyFilteredTags);
    }

    private function getDeepestChildrenAndTheirDepth(array $nodes, ?int $depth = 0, ?array $children) : array
    {
        $depths = [];

        if (!$node->hasChildren()) {
            return null;
        }

        /**
         * Iterate,
         */

        foreach ($children as $child) {
            if ($this->getDeepestChildrenAndTheirDepth($child->getChildren())) {
            }
        }
    }

    private function isInteresting($node) : bool
    {
        // TODO: If there are two texts that contain n amount of same keywords then
        // reject the shorter text.
        
        $nodeText = strtoupper($node->text);
        
        $foundStrongKeywords = [];
        $foundWeakKeywords = [];

        foreach ($this->strongKeywords as $keyword) {
            if (substr_count($nodeText, $keyword) > 0 && !in_array($keyword, $foundStrongKeywords)) {
                $foundStrongKeywords[] = $keyword;
            }
        }

        foreach ($this->weakKeywords as $keyword) {
            if (substr_count($nodeText, $keyword) > 0 && !in_array($keyword, $foundWeakKeywords)) {
                $foundWeakKeywords[] = $keyword;
            }
        }
        
        if (count($foundStrongKeywords) < 2 && count($foundWeakKeywords) < 1) {
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
