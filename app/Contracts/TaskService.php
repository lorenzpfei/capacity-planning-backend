<?php

namespace App\Contracts;

use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;

interface TaskService
{
    /**
     * Get tasks by user
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return Collection Task Collection
     */
    public function getAssignedTasksForUser(DateTime $from, DateTime $to): Collection;
}
