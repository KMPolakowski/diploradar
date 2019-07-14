<?php

namespace App\Console\Commands;

use App\User;
use PHPHtmlParser\Dom;
use App\Models\ForeignMinistry;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\InnerNode;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

class UpdateForeignMinistries extends Command
{   
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:foreign_ministries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Foreign Ministries';


    private $samples = [""];
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Dom $dom)
    {
        parent::__construct();

        $this->dom = $dom;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

    }
}
