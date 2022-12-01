<?php

namespace App\Services\Tracking;

use App\Contracts\TrackingService;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class EverhourTrackingApi implements TrackingService
{
    public function importTrackingDataForTasks(Collection $tasks, string $prefix = 'ev:')
    {
        $firstElement = true;

        /** @var Task $task */
        foreach ($tasks as $key => $task) {
            $everhourTask = $this->getEverhourDataForTask($task, $prefix);

            //set correct everhour user id
            if ($firstElement) {
                $this->setTaskUserId(User::firstWhere('task_user_id', '=', $task->task_user_id), $everhourTask['assignees'][0]['userId']);
            }
            $firstElement = false;

            $task->task_total = $everhourTask['time']['total'] ?? null;
            $task->task_users = isset($everhourTask['time']['users']) ? json_encode($everhourTask['time']['users']) : null;
            $task->task_estimate = $everhourTask['estimate']['total'] ?? null;
            $task->save();
        }
    }

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
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function importTimeoffs(string $from = '', string $to = ''): bool
    {
        $url = 'https://api.everhour.com/resource-planner/assignments?from=' . $from . '&to=' . $to;
        $request = Http::withHeaders([
            'X-Api-Key' => config('services.everhour.api_key'),
        ]);

        $aggregatedTimeoffs = [];
        foreach($request->get($url)->json() as $timeoff) {
            $time = $timeoff['time'] ?? null;
            $timeOffPeriod = $timeoff['timeOffPeriod'] ?? null;

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

        foreach ($aggregatedTimeoffs as $key => $aggregatedTimeoff)
        {
            User::where('tracking_user_id', '=', $key)?->update(['timeoffs' => json_encode($aggregatedTimeoff)]);
        }
        return true;
    }

    private function setTaskUserId(User $user, int $trackingUserId)
    {
        $user->tracking_user_id = $trackingUserId;
        $user->save();
    }
}
