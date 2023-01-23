<?php

namespace App\Console\Commands;

use App\Contracts\TrackingService;
use App\Models\Task;
use App\Services\Tracking\EverhourTrackingApi;
use Illuminate\Console\Command;

class ImportTrackings extends Command
{
    private TrackingService $trackingService;
    public function __construct(TrackingService $trackingService)
    {
        parent::__construct();
        $this->trackingService = $trackingService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:trackings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracking data for all tasks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tasks = Task::all();
        $this->trackingService->importTrackingDataForTasks($tasks, 'as:');
        $this->info('Imported tracking data for given tasks.');
        return Command::SUCCESS;
    }
}
