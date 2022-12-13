<?php

namespace App\Services\Feature;

use App\Contracts\TaskService;
use App\Contracts\TrackingService;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Task;
use App\Models\Timeoff;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;

class WorkloadService
{
    private TaskService $taskService;

    private Collection $tasks;

    private Collection $timeoffs;

    private array $contracts;

    public function __construct(TaskService $taskService, TrackingService $trackingService)
    {
        $this->taskService = $taskService;
    }

    public function getWorkloadForDepartment(int $departmentId, DateTime $from, DateTime $to)
    {
        $users = Department::find($departmentId)?->users;
        $this->tasks = new Collection();
        $this->timeoffs = new Collection();
        $this->contracts = [];
        foreach ($users as $user) {
            $this->tasks = $this->tasks->merge(Task::
                where('assigned_user_id', $user->id)
                ->where(function ($query) use ($from, $to) {
                    $query->whereBetween('due', [$from, $to])
                        ->orWhere('start', [$from, $to]);
                })
                ->get()->all());
            $this->timeoffs = $this->timeoffs->merge(Timeoff::where('user_id', $user->id)->get()->all());
            $this->contracts[$user->id] = Contract::where('user_id', $user->id)->get();
        }
        $amountOfDays = (int)$from->diff($to)->format('%a');
        $i = 0;
        $days = [];
        while ($i < $amountOfDays) {
            $date = strtotime("+" . $i . " day", strtotime($from->format('Y-m-d')));
            $date = date_timestamp_set(new DateTime(), $date);
            $activeContracts = new Collection();

            /** @var Collection $collection */
            foreach($this->contracts as $collection)
            {
                //use first Contract of user which to date is null else use latest to date in timerange
                $active = $collection
                    ->first()
                    ->where('from', '<=', $date->format('Y-m-d'))
                    ->whereNull('to')->get()->first();

                if($active === null)
                {
                    $active = $collection
                        ->where('from', '<=', $date->format('Y-m-d'))
                        ->sortByDesc('to')->first();
                }
                $activeContracts->push($active);
            }
            $days[] = $this->calculateDay($date, $activeContracts);
            $i++;
        }
    }

    public function calculateDay(DateTime $day, Collection $activeContracts){

        dd($day, $activeContracts); //todo: Debug entfernen
    }

    public function getActiveContactForUser(User $user, DateTime $date) //todo: move into another service
    {
        return Contract::where('user_id', $user->id)
            ->where('from', '>=', $date->format('Y-m-d'))
            ->where(function ($query) use ($date) {
                $query->whereNotNull('to')
                    ->orWhere('to', '<=', $date->format('Y-m-d'));
            })
            ->get();
    }
}
