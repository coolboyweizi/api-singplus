<?php

namespace SingPlus\Console\Commands;

use Illuminate\Console\Command;
use SingPlus\Services\MusicService;

class CategoryAdjust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adjust:category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adjust catetory (languate & style) total number. Only affect incremental data';

    /**
     * @var MusicService
     */
    private $musicService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MusicService $musicService)
    {
        parent::__construct();

        $this->musicService = $musicService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $this->musicService->adjustCategoryTotalNumber();
    }
}
