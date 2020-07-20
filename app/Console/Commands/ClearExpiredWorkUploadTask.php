<?php

namespace SingPlus\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;

/**
 * Should be execute daily
 */
class ClearExpiredWorkUploadTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'works:clear-upload-task
                            {before? : the tasks which created before  <before> days will be remove. default value is 2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear 2-step work upload task';

    private $defaultBefore = 2;

    /**
     * @var WorkServiceContract
     */
    private $workService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WorkServiceContract $workService)
    {
        parent::__construct();

        $this->workService = $workService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $before = (int) $this->argument('before');
      if ($before <= 0) {
        $before = $this->defaultBefore;
      }

      $expiredTime = Carbon::today()->subDays($before);

      $this->workService->clearExpiredUploadTask($expiredTime);
    }
}
