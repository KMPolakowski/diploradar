<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\PagePiece;
use Illuminate\Console\Command;

class CreateJSONLForClassification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:jsonl:classification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create training dataset as JSONL file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pieces = PagePiece::with(['Event'])
            ->where('labeled_by', '!=', null)
            ->get();

        $file = fopen(
            dirname(__DIR__, 3) . '/storage/app/training_set_classification.csv',
            'w'
        );

        foreach ($pieces as $piece) {
            $text = html_entity_decode(
                trim($piece->text)
            );

            $type = 0;

            if ($piece->Event instanceof Event) {
                $type = 1;
            }

            fputcsv($file, [$text, $type]);
        }

        fclose($file);
    }
}
