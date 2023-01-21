<?php

namespace App\Console\Commands;

use App\Contracts\TaskService;
use App\Models\User;
use Illuminate\Console\Command;

class ImportTasks extends Command
{
    private TaskService $taskService;
    public function __construct(TaskService $taskService)
    {
        parent::__construct();
        $this->taskService = $taskService;
    }

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

        try {
            $amount = $this->taskService->importTasksForUser($user);
            $this->info(sprintf('Successfully imported %d tasks', $amount));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::INVALID;
        }
        return Command::SUCCESS;
    }
}
