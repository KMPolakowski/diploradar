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

            $text = html_entity_decode(
                trim($event->PagePiece[0]->text)
            );

            // dump($text);
            // dump($event->id);

            foreach ($event->Person as $person) {
                $annotations[] = $this->getAnnotation(
                    'person',
                    $person->name,
                    $text,
                    $event->id
                );
            }

            if (isset($event->location)) {
                $annotations[] = $this->getAnnotation(
                    'location',
                    $event->location->name,
                    $text,
                    $event->id
                );
            }

            if (isset($event->happening_at)) {
                $annotations[] = $this->getAnnotation(
                    'happening_at',
                    $event->happening_at,
                    $text,
                    $event->id
                );
            }

            if (isset($event->published_at)) {
                $annotations[] = $this->getAnnotation(
                    'published_at',
                    $event->published_at,
                    $text,
                    $event->id
                );
            }

            $row = [
                "annotations" => $annotations,
                "text_snippet" => [
                    "content" => $text
                ]
            ];

            fwrite($file, json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK) . "\n");
        }

        fclose($file);
    }


    private function getOffsets(string $needle, string $haystack, int $eventId): array
    {
        $needle = trim($needle);

        $startOffset = mb_strpos(
            $haystack,
            $needle,
            null,
            "UTF-8"
        );

        // dump($needle);
        // dump($startOffset);

        if (!\is_int($startOffset)) {
            dump($haystack);
            dump($needle);
            throw new Exception(
                sprintf('This data is crap. Event id %s sucks as couldn \'t find |%s|', $eventId, $needle)
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
