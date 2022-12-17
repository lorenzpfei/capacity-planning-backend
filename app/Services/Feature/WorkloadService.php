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
            $dayInWeek = (int)date('w', $date);
            $date = date_timestamp_set(new DateTime(), $date);
            $activeContracts = new Collection();

            if ($dayInWeek === 6 || $dayInWeek === 0) {
                $i++;
                continue;
            }

            /** @var Collection $collection */
            foreach ($this->contracts as $collection) { //todo: fix this: just get contracts in timerange instead of last. This would cause problems
                //use first Contract of user which to date is null else use latest to date in timerange
                $active = $collection
                    ->first()
                    ->where('from', '<=', $date->format('Y-m-d'))
                    ->whereNull('to')->get()->first();

                if ($active === null) {
                    $active = $collection
                        ->where('from', '<=', $date->format('Y-m-d'))
                        ->sortByDesc('to')->first();
                }
                $activeContracts->push($active);
            }
            $days[$date->format('Y-m-d')] = $this->calculateDay($date, $activeContracts);
            $i++;
        }
        dd($days); //todo: Debug entfernen
    }

    /**
     * @throws \Exception
     */
    public function calculateDay(DateTime $date, Collection $activeContracts)
    {
        $day = new \stdClass();
        $day->hoursContract = 0;
        $day->hoursTimeoff = 0;
        $day->hoursTask = 0;

        /** @var Contract $contract */
        foreach ($activeContracts as $contract) {
            $day->hoursContract += $contract->hours_per_week / 5;
        }

        $timeoffsInPeriod = $this->timeoffs->where('start', '<=', $date->format('Y-m-d'))
            ->where('end', '>=', $date->format('Y-m-d'));

        /** @var Timeoff $timeoff */
        foreach ($timeoffsInPeriod as $timeoff) { //todo: own function and test only hourly timeoffs
            $day->hoursTimeoff += $this->getTimeoff($timeoff, $activeContracts);
        }

        $tasks = $this->tasks->whereNull('from');
        $tasks = $tasks->merge($this->tasks->whereNotNull('from')->where('from', '>=', $date->format('Y-m-d')));


        foreach ($tasks as $task) {
            $leftTaskTrackingTime = $task->tracking_estimate - $task->tracking_total;
            if ($leftTaskTrackingTime <= 0) {
                continue;
            }
            $timestampForDate = strtotime($date->format('Y-m-d'));
            if (strtotime($task->start) > $timestampForDate) //begin of working time for task in future
            {
                continue;
            }

            if($task->id === 1203472609060347)
            {
               // dump('total tracked '.$task->tracking_total / 3600); //todo: Debug entfernen
            }

            $weekdaysToWorkOn = $this->getWeekdayDifference(new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due))));

            //Check timeoffs
            $timeoffsInPeriod = Timeoff::whereBetween('start', [new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due)))])
                ->orWhereBetween('end', [new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due)))])->get();

            //loop through and remove days from weekdays

            foreach ($timeoffsInPeriod as $timeoff) {
                if ($timeoff->time !== null) {
                    $weekdays = $this->getWeekdayDifference(new DateTime(date('Y-m-d', strtotime($timeoff->start))), new DateTime(date('Y-m-d', strtotime($timeoff->end))));
                    $dailyTimeoff = ((int)$timeoff->time) / $weekdays / 3600;
                    dd($dailyTimeoff); //todo: Debug entfernen
                    $weekdaysToWorkOn -= $dailyTimeoff;
                } else {
                    $dailyTimeoff = ((float)$timeoff->time_off_period);
                    $weekdaysToWorkOn -= $dailyTimeoff;
                }
            }

            //use workdays to get the left time divided by those
            $leftWorktimeForDate = $day->hoursContract - $day->hoursTimeoff - $day->hoursTask;
            $taskHoursForDay = 0;
            if($leftWorktimeForDate > 0 && $weekdaysToWorkOn > 0)
            {
                $taskHoursForDay = $leftTaskTrackingTime / $weekdaysToWorkOn / 3600;
            }
            //if daily task worktime is more than worktime left, just use the remaining worktime as task worktime
            if($leftWorktimeForDate < $taskHoursForDay)
            {
                $taskHoursForDay = $leftWorktimeForDate;
            }
            $day->hoursTask += $taskHoursForDay;

            //decrease today`s left worktime locally to ensure next days do not use it again
            $this->tasks->firstWhere('id', $task->id)->tracking_total += $taskHoursForDay;
            if($task->id === 1203472609060347)
            {
               // dump('taskTimeToday '.$taskHoursForDay/3600); //todo: Debug entfernen
                //dump('$weekdaysToWorkOn '.$weekdaysToWorkOn); //todo: Debug entfernen
                //dump('$leftTaskTrackingTime '.$leftTaskTrackingTime/3600); //todo: Debug entfernen

                echo '<br><br><br>';
            }
        }

        return $day;
    }

    // This function takes a date and returns the remaining number of days by that date, with weekends removed
    private function getWeekdayDifference(\DateTime $startDate, \DateTime $endDate)
    {
        $days = 0;
        while ($startDate->getTimestamp() <= $endDate->getTimestamp()) {
            $days += $startDate->format('N') < 6 ? 1 : 0;
            $startDate = $startDate->setTimestamp(strtotime('+1 day', $startDate->getTimestamp())); //set to next day
        }

        return $days;
    }


    /**
     * @throws \Exception
     */
    private function getTimeoff(Timeoff $timeoff, Collection $activeContracts)
    {
        $start = new DateTime($timeoff->start);
        $end = new DateTime($timeoff->end);
        $amountOfDays = ((int)$start->diff($end)->format('%a')) + 1;
        if ($timeoff->time !== null) {
            //timeoff in hours
            $time = $timeoff->time / 60 / 60;
            if ($timeoff->start !== $timeoff->end) {
                $time = $timeoff->time / 60 / 60 / $amountOfDays;
            }
            return $time;
        }

        //timeoff in days
        $belongingContractHours = $activeContracts->firstWhere('user_id', $timeoff->user_id)->hours_per_week / 5;
        return $belongingContractHours * $timeoff->time_off_period;
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
