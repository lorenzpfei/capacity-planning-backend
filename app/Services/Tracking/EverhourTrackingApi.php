<?php

namespace App\Services\Tracking;

use App\Contracts\TrackingService;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class EverhourTrackingApi implements TrackingService
{
    public static function importTrackingDataForTasks(Collection $tasks, string $prefix = 'ev:')
    {
        /** @var Task $task */
        foreach($tasks as $task)
        {
            $everhourTask = self::getEverhourDataForTask($task, $prefix);
            $task->task_total = $everhourTask['time']['total'] ?? null;
            $task->task_users = isset($everhourTask['time']['users']) ?json_encode($everhourTask['time']['users']): null;
            $task->task_estimate = $everhourTask['estimate']['total'] ?? null;
            $task->save();
        }
    }

    public static function getEverhourDataForTask(Task $task, string $prefix = 'ev:')
    {
        $url = 'https://api.everhour.com/tasks/' . $prefix . $task->id;
        $request = Http::withHeaders([
            'X-Api-Key' => config('services.everhour.api_key'),
        ]);
        return $request->get($url)->json();
    }
}
