<?php

namespace App\Services\Tracking;

use App\Contracts\TrackingService;
use App\Models\Task;
use App\Models\Timeoff;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class EverhourTrackingApi implements TrackingService
{
    /**
     * Updates tasks in database with everhour tracking data by given tasks collection
     *
     * @param Collection $tasks Collection of Task Entity
     * @param string $prefix Everhour Task Prefix (e.g. ev:)
     * @return void
     */
    public function importTrackingDataForTasks(Collection $tasks, string $prefix = 'ev:')
    {
        $firstElement = true;

        /** @var Task $task */
        foreach ($tasks as $task) {
            $everhourTask = $this->getEverhourDataForTask($task, $prefix);

            //set correct everhour user id
            if ($firstElement) {
                $this->setTaskUserId(User::find($task->assigned_user_id), $everhourTask['assignees'][0]['userId']);
                $firstElement = false;
            }

            $task->tracking_total = $everhourTask['time']['total'] ?? null;
            if(isset($everhourTask['time']['users'])) {
                $users = $everhourTask['time']['users'];
                $task->tracking_users = json_encode($users);

                //sort array desc and keep keys
                arsort($users, SORT_NUMERIC);
                $task->tracking_highest_time_user_id = User::firstWhere('tracking_user_id', array_keys($users)[0])?->id;
            }
            $task->tracking_estimate = $everhourTask['estimate']['total'] ?? null;
            $task->save();
        }
    }

    /**
     * Gets everhour tracking data
     *
     * @param Task $task Single Task
     * @param string $prefix Everhour Task Prefix (e.g. ev:)
     * @return array|mixed Everhour data or everhour error data
     */
    public function getEverhourDataForTask(Task $task, string $prefix = 'ev:')
    {
        $url = 'https://api.everhour.com/tasks/' . $prefix . $task->id;
        $request = Http::withHeaders([
            'X-Api-Key' => config('services.everhour.api_key'),
        ]);
        return $request->get($url)->json();
    }

    /**
     * Get aggregated timeoffs for users and write them into db
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return array amount of upserts and users
     */
    public function importTimeoffs(DateTime $from, DateTime $to): array
    {
        $url = 'https://api.everhour.com/resource-planner/assignments?from=' . $from->format('Y-m-d') . '&to=' . $to->format('Y-m-d');
        $request = Http::withHeaders([
            'X-Api-Key' => config('services.everhour.api_key'),
        ]);

        //Format data
        $aggregatedTimeoffs = [];
        foreach ($request->get($url)->json() as $timeoff) {
            $time = $timeoff['time'] ?? null;
            $timeOffPeriod = $timeoff['timeOffPeriod'] ?? null;
            switch ($timeOffPeriod)
            {
                case 'full-day':
                    $timeOffPeriod = 1.0;
                    break;
                case 'half-and-quarter-of-day':
                    $timeOffPeriod = 0.75;
                    break;
                case 'half-day':
                    $timeOffPeriod = 0.5;
                    break;
                case 'quarter-of-day':
                    $timeOffPeriod = 0.25;
                    break;
            }

            //Format array to be upserted
            $dbData = [
                'reason' => $timeoff['timeOffType']['name'],
                'paid' => $timeoff['timeOffType']['paid'],
                'start' => $timeoff['startDate'],
                'end' => $timeoff['endDate'],
                'type' => $timeoff['timeOffDurationType'],
                'time' => $time,
                'time_off_period' => $timeOffPeriod
            ];
            $aggregatedTimeoffs[$timeoff['user']['id']][] = $dbData;
        }

        //Set assigned users and upsert timeoffs
        $upsertAmount = 0;
        foreach ($aggregatedTimeoffs as $key => $aggregatedTimeoff) {
            $user = User::where('tracking_user_id', '=', $key)->first();
            if ($user !== null) {
                foreach($aggregatedTimeoff as $aggegatedKey => $singleTimeoff)
                {
                    $aggregatedTimeoff[$aggegatedKey]['user_id'] = $user->id;
                    $aggregatedTimeoff[$aggegatedKey]['id'] = $user->id . '_' . $singleTimeoff['start'];
                }
                Timeoff::where('start', '>=', $from->format('Y-m-d'))
                ->where('end', '<=', $to->format('Y-m-d'))
                ->delete();
                $upsertAmount = Timeoff::upsert($aggregatedTimeoff,
                    ['id'],
                    ['id', 'user_id', 'reason', 'paid', 'start', 'end', 'type', 'time', 'time_off_period']
                );
            }
        }
        return [
            'upserts' => $upsertAmount,
            'users' => count($aggregatedTimeoffs)
        ];
    }

    /**
     * Set tracking user id for given user
     *
     * @param User $user
     * @param int $trackingUserId
     * @return void
     */
    private function setTaskUserId(User $user, int $trackingUserId)
    {
        $user->tracking_user_id = $trackingUserId;
        $user->save();
    }
}
