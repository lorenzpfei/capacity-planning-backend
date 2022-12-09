<?php

namespace App\Http\Controllers;

use App\Contracts\TaskService;
use App\Models\User;

class WorkloadController extends Controller
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function test()
    {
        $tasks = $this->taskService->getAssignedTasksForUser(User::find(1)); //todo: move to service
        dd($tasks); //todo: Debug entfernen
    }
}
