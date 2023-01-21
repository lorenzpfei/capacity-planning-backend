<?php

namespace App\Console\Commands;

use App\Contracts\TrackingService;
use App\Services\Tracking\EverhourTrackingApi;
use Illuminate\Console\Command;

class ImportTimeoffs extends Command
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
    protected $signature = 'import:timeoffs {from?} {to?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import timeoffs for a specified range. Default: Current year';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $from = $this->argument('from') ?? sprintf('%d-01-01', date("Y"));
        $to = $this->argument('to') ?? sprintf('%d-12-31', date("Y"));

        $returnData = $this->trackingService->importTimeoffs(date_create($from), date_create($to));
        $this->info(sprintf('Successfully imported %d timeoffs for %d users', $returnData['upserts'], $returnData['users']));
        return Command::SUCCESS;
    }
}
