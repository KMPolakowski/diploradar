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
            "MINISTER", "MINISTRY", "PRESIDENT", "AMBASSADOR", "DEPUTY",
            "REPRESENTATIVE", "COUNTERPART"
        ];
        
        $this->weakKeywords = [
            "SPOKE", "MET", "MEETING", "MEETS", "SIGNED", "SIGNING", "TALKED", "VISITED",
            "HOLDS", "CALLED", "CALLS", "CALL", "DISCUSS", "DISCUSSED"
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
            $foreignMinistryPage->PagePiece()->save($pagePiece);
        }
    }

    private function extractInterestingNodes(ResponseInterface $response) : array
    {
        $document = $this->dom->loadStr(
            (string) $response->getBody()
        );

        // $textHolders = $document->getElementsByTag("p, h, h1, h2, h3");
        $textHolders = $document->getElementsByTag("p");

        // dump($textHolders->innerHtml());

        $interestingNodes = [];

        foreach ($textHolders as $p) {
            if ($this->isInteresting($p)) {
                $parents = $this->getParentIfEnglish($p, 3);
                if ($parents !== null) {
                    $interestingNodes[] = $parents;
                }
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
        

        return array_filter($interestingNodes);
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
                // dump($deepestChildren[$i]->innerPLANNEDHtml());
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

        //remove stuff that contains not english characters
        if (!$this->isEnglish($nodeText)) {
            // dump($nodeText);
            // dump("blah");
            return false;
        }

        $words = explode(
            " ",
            $nodeText
        );

        $foundStrongKeywords = array_unique(
            array_intersect(
                $this->strongKeywords,
                $words
            )
        );

        $foundWeakKeywords = array_unique(
            array_intersect(
                $this->weakKeywords,
                $words
            )
        );
        
        if (count($foundStrongKeywords) < 2 || count($foundWeakKeywords) < 1) {
            return false;
        }

        return true;
    }

    private function isEnglish(string $text) : bool
    {
        $words = explode(" ", $text);

        $amountOfNonEnglishWords = count(array_filter($words, function ($word) {
            return preg_match("/[^A-Za-z0-9#$%^*()+=\-!–\[\]\';,´’.\/{}|“” " . '":<>?~\\\\]/', $word);
        }));

        // dump($amountOfNonEnglishWords);

        return $amountOfNonEnglishWords <= 2;
    }

    private function getParentIfEnglish($node, int $parentDepth)
    {
        if ($parentDepth < 3 && !$this->areChildrenInEnglish($node)) {
            return null;
        } elseif ($parentDepth === 0 || !$node->parent instanceof AbstractNode) {
            return $node;
        }

        return $this->getParentIfEnglish($node->parent, $parentDepth-1);
    }

    private function areChildrenInEnglish(AbstractNode $node) : bool
    {
        foreach ($node->find("p") as $child) {
            if (!$this->isEnglish($child->text)) {
                return false;
            }
        }

        return true;
    }
}
