<?php

namespace App\Services\Feature;

use App\Models\Contract;
use DateTime;

class UserService
{
    /**
     * Returns active contract for a given date
     *
     * @param int $userId
     * @param DateTime $date
     * @return Contract
     */
    public function getActiveContractForUser(int $userId, DateTime $date)
    {
        return Contract::
        where('user_id', $userId)
            ->where('from', '<=', date('Y-m-d'))
            ->where(function ($query) use ($date) {
                $query->whereNull('to')
                    ->orWhere('to', '>=', $date->format('Y-m-d'));
            })
            ->first();
    }
}
