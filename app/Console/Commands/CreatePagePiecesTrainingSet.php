<?php

namespace App\Console\Commands;

use App\User;
use Exception;
use App\Models\Event;
use DateTimeInterface;
use PHPHtmlParser\Dom;
use App\Models\Location;
use App\Models\PagePiece;
use App\Models\ForeignMinistry;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\InnerNode;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

/**
 * Create training datatset as csv file
 */
class CreatePagePiecesTrainingSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:page_piece:training_set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create training datatset as csv file';

    /**
     * Execute the console command.
     *
     * @param  \App\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $ministries = ForeignMinistry::all();

        $csv = fopen(
            dirname(__DIR__, 3) . '/storage/app/page_pieces.csv',
            'w'
        );

        foreach ($ministries as $ministry) {

            $query =
                <<<SQL
SELECT 
    `pp0`.`id`, `pp0`.`text`
FROM
    page_piece AS `pp0`
        INNER JOIN
    foreign_ministry_page AS `fmp0` ON `fmp0`.`id` = `pp0`.`foreign_ministry_page_id`
        INNER JOIN
    foreign_ministries AS `fm0` ON `fm0`.`id` = `fmp0`.`foreign_ministry_id`
WHERE 
	1=1
		AND `pp0`.`labeled_by` IS NULL
        AND LENGTH(`pp0`.`text`) < 3000
        AND `fm0`.`id` = ?
LIMIT 15
OFFSET 0
;
SQL;

            $pieces = DB::select($query, [$ministry->id]);

            foreach ($pieces as $piece) {
                $fileName = sprintf('%s.txt', $piece->id);

                $file = fopen(
                    dirname(__DIR__, 3) . '/storage/app/page_pieces/' . $fileName,
                    'w'
                );

                $text = html_entity_decode(
                    trim($piece->text)
                );


                fwrite($file, $text);
                fputcsv($csv, ['gs://diploradar/page_pieces/' . $fileName]);

                fclose($file);
            }
        }

        fclose($csv);
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

        if (!\is_int($startOffset)) {
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
