<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TaskService
{
    /**
     * @param string $from
     * @param string $to
     * @return Collection
     */
    public function getAssignedTasksForUser(string $from, string $to): Collection;
}
