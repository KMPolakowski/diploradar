<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\User;
use PHPHtmlParser\Dom;
use App\Models\ForeignMinistry;
use App\Models\Location;
use DateTimeInterface;
use Exception;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\InnerNode;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

class CreateJSONLDataSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:jsonl:file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create training datatset as JSONL file';

    /**
     * Execute the console command.
     *
     * @param  \App\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $events = Event::with(['Person', 'PagePiece', 'Location'])
            ->whereHas('PagePiece')
            ->get();

        $file = fopen(
            dirname(__DIR__, 3) . '/storage/app/training_set.jsonl',
            'w'
        );

        foreach ($events as $event) {

            $annotations = [];

            foreach ($event->Person as $person) {
                $annotations[] = $this->getAnnotation(
                    'person',
                    $person->name,
                    $event->PagePiece[0]->text,
                    $event->id
                );
            }

            if (isset($event->location)) {
                $annotations[] = $this->getAnnotation(
                    'location',
                    $event->location->name,
                    $event->PagePiece[0]->text,
                    $event->id
                );
            }

            if (isset($event->happening_at)) {
                $annotations[] = $this->getAnnotation(
                    'happening_at',
                    $event->happening_at,
                    $event->PagePiece[0]->text,
                    $event->id
                );
            }

            if (isset($event->published_at)) {
                $annotations[] = $this->getAnnotation(
                    'published_at',
                    $event->published_at,
                    $event->PagePiece[0]->text,
                    $event->id
                );
            }

            $row = [
                "annotations" => $annotations,
                "text_snippet" => [
                    "content" => $event->PagePiece[0]->text
                ]
            ];

            fwrite($file, json_encode($row) . "\n");
        }

        fclose($file);
    }


    private function getOffsets(string $needle, string $haystack, int $eventId): array
    {
        $haystack = html_entity_decode($haystack);

        $startOffset = strpos(
            $haystack,
            $needle
        );

        if (!$startOffset) {
            dump($haystack);
            throw new Exception(
                sprintf('This data is crap. Event id %s sucks as couldn \'t find %s', $eventId, $needle)
            );
        }

        $endOffset = $startOffset + \strlen($needle);

        return [
            $startOffset, $endOffset
        ];
    }

    private function getAnnotation(string $annotationType, string $needle, string $haystack, int $eventId): array
    {
        [$startOffset, $endOffset] = $this->getOffsets($needle, $haystack, $eventId);

        $annotation = [
            "text_extraction" => [
                "text_segment" => [
                    "end_offset" => $endOffset,
                    "start_offset" => $startOffset
                ]
            ],
            "display_name" => $annotationType
        ];

        return $annotation;
    }
}
