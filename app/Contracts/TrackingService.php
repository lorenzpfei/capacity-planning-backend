<?php

namespace App\Contracts;

use DateTime;
use Illuminate\Database\Eloquent\Collection;

interface TrackingService
{
    /**
     * Updates tasks in database with tracking data by given tasks collection
     *
     * @param Collection $tasks Collection of Task Entity
     * @param string $prefix Task Prefix (e.g. "ev:" for Everhour)
     * @return void
     */
    public function importTrackingDataForTasks(Collection $tasks, string $prefix = 'ev:'): void;

    /**
     * Get aggregated timeoffs for users and write them into db
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     */
    public function importTimeoffs(DateTime $from, DateTime $to): array;
}
