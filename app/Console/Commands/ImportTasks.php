<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tasks\AsanaTaskApi;
use Illuminate\Console\Command;

class ImportTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:tasks {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tasks from task api for specified user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::find($this->argument('user'));
        if($user === null)
        {
            $this->error('Please provide a correct user id.');
            return Command::INVALID;
        }

        $asanaTaskApi = new AsanaTaskApi($user);
        $amount = $asanaTaskApi->importTasksForUser();
        $this->info(sprintf('Successfully imported %d tasks', $amount));
        return Command::SUCCESS;
    }
}
