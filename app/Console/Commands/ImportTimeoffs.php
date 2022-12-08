<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTimeoffs extends Command
{
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
        $to = $this->argument('to') ?? sprintf('%d-31-12', date("Y"));
        $everhourTrackingApi = new \App\Services\Tracking\EverhourTrackingApi();
        $returnData = $everhourTrackingApi->importTimeoffs(date_create($from), date_create($to));
        $this->info(sprintf('Successfully imported %d timeoffs for %d users', $returnData['upserts'], $returnData['users']));
        return Command::SUCCESS;
    }
}
