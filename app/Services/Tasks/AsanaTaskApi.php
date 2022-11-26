<?php

namespace App\Services\Tasks;

use App\Contracts\TaskService;
use App\Models\User;

class AsanaTaskApi implements TaskService
{

    public function getAssignedTasksForUser(User $user, string $from, string $to): array
    {
        // TODO: Implement getAssignedTasksForUser() method.
    }
}
