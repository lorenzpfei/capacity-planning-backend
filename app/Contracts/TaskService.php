<?php

namespace App\Contracts;

use App\Models\Task;
use App\Models\User;

interface TaskService
{
    /**
     * @param User $user
     * @param string $from
     * @param string $to
     * @return Task[]
     */
    public static function getAssignedTasksForUser(User $user, string $from, string $to): array;
}
