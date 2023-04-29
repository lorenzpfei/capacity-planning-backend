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
     * @param User $user
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @return Collection Task Collection
     */
    public function getAssignedTasksForUser(User $user, DateTime $from = null, DateTime $to = null): Collection;


    /**
     * Get asana tasks for specified user and upsert them
     *
     * @param User $user User to import the tasks for
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @return array Task amount
     */
    public function importTasksForUser(User $user, DateTime $from = null, DateTime $to = null): array; //todo: specify array
}
