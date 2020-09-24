<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CreateJSONLDataSet;
use App\Console\Commands\CrawlForeignMinistries;
use App\Console\Commands\CreateCSVForClassification;
use App\Console\Commands\UpdateForeignMinistries;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Console\Commands\RemoveDuplicatePagePieces;
use App\Console\Commands\CreatePagePiecesTrainingSet;
use App\Console\Commands\CreateJSONLForClassification;
use App\Console\Commands\ExtractTextFromHtmlPagePieces;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateForeignMinistries::class,
        CrawlForeignMinistries::class,
        ExtractTextFromHtmlPagePieces::class,
        RemoveDuplicatePagePieces::class,
        CreateJSONLDataSet::class,
        CreateCSVForClassification::class,
        CreatePagePiecesTrainingSet::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
