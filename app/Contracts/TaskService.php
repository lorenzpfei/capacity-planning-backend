<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TaskService
{
    /**
     * @param User $user
     * @param string $from
     * @param string $to
     * @return Collection
     */
    public static function getAssignedTasksForUser(User $user, string $from, string $to): Collection;
}
