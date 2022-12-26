<?php

namespace App\Services\Feature;

use App\Contracts\TaskService;
use App\Contracts\TrackingService;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Task;
use App\Models\Timeoff;
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

    /**
     * @throws \Exception
     */
    public function getWorkloadForDepartment(int $departmentId, DateTime $from, DateTime $to)
    {
        $users = Department::find($departmentId)?->users;
        $this->tasks = new Collection();
        $this->timeoffs = new Collection();
        $this->contracts = [];
        foreach ($users as $user) {
            $this->tasks = $this->tasks->merge(Task::
            where('assigned_user_id', $user->id)
                ->whereNull('completed')
                ->where(function ($query) use ($from) {
                    $query->whereNull('start')
                        ->orWhere('start', '>=', $from->format('Y-m-d'));
                })
                ->get()->all());
            $this->timeoffs = $this->timeoffs->merge(Timeoff::where('user_id', $user->id)->get()->all());
            $this->contracts[$user->id] = Contract::where('user_id', $user->id)->get();
        }
        $today = new DateTime();
        $amountOfDays = (int)$today->diff($to)->format('%a');

        $i = 0;
        $days = [];
        $trackedHoursInWeek = 0;
        $contractHoursInWeek = 0;
        while ($i < $amountOfDays) {
            $date = strtotime("+" . $i . " day", strtotime($today->format('Y-m-d')));
            $dayInWeek = (int)date('w', $date);
            $date = date_timestamp_set(new DateTime(), $date);
            $activeContracts = new Collection();

            if ($dayInWeek === 6 || $dayInWeek === 0) {
                $i++;
                $trackedHoursInWeek = 0; //prepare for week
                $contractHoursInWeek = 0;
                continue;
            }

            /** @var Collection $collection */
            foreach ($this->contracts as $collection) {
                //use first Contract of user which to date is null else use latest to date in timerange
                $activeContract = $this->getActiveContactForUser($collection->first()->user_id, $date);
                $activeContracts->push($activeContract);
            }

            $day = $this->calculateDay($date, $activeContracts, $trackedHoursInWeek, $contractHoursInWeek);
            $trackedHoursInWeek += $day->hoursTask + $day->hoursTimeoff;
            $contractHoursInWeek += $day->hoursContract;
            if (strtotime($from->format('Y-m-d')) <= strtotime($date->format('Y-m-d'))) {
                $days[$date->format('Y-m-d')] = $day;
            }
            $i++;
        }
        return $days;
    }

    /**
     * @param DateTime $date
     * @param Collection $activeContracts
     * @param int $trackedHoursInWeek
     * @param int $contractHoursInWeek
     * @return \stdClass
     * @throws \Exception
     */
    public function calculateDay(DateTime $date, Collection $activeContracts, int $trackedHoursInWeek, int $contractHoursInWeek)
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
        foreach ($timeoffsInPeriod as $timeoff) { //todo: own function
            $day->hoursTimeoff += $this->getTimeoff($timeoff, $activeContracts);
        }

        $tasks = $this->tasks->whereNull('start')->whereNull('completed');
        $tasks = $tasks->merge($this->tasks->whereNotNull('start')->where('start', '>=', $date->format('Y-m-d')))->whereNull('completed');

        foreach ($tasks as $task) {
            $taskHoursForDay = $this->getTaskHours($task, $date, $day);

            //if task is too much for daily worktime, just estimate the remaining worktime for a time to work on this task for today
            $weeklyTaskTime = $trackedHoursInWeek + $day->hoursTask + $taskHoursForDay;
            $weeklyContractTime = $contractHoursInWeek + $day->hoursContract;

            if ($weeklyTaskTime > $weeklyContractTime) {
                $taskHoursForDay = $weeklyContractTime - $weeklyTaskTime;
            }

            $day->hoursTask += $taskHoursForDay;

            //decrease today`s left worktime locally to ensure next days do not use it again
            $this->tasks->firstWhere('id', $task->id)->tracking_total += $taskHoursForDay * 3600;
        }

        return $day;
    }

    private function getTaskHours(Task $task, DateTime $date, \stdClass $day)
    {
        $leftTaskTrackingTime = $task->tracking_estimate - $task->tracking_total;
        if ($leftTaskTrackingTime <= 0) {
            return 0;
        }
        $timestampForDate = strtotime($date->format('Y-m-d'));
        if (strtotime($task->start) > $timestampForDate) //begin of working time for task in future
        {
            return 0;
        }

        $weekdaysToWorkOn = $this->getWeekdayDifference(new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due))));

        //Check timeoffs
        $timeoffsInPeriod = Timeoff::whereBetween('start', [new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due)))])
            ->orWhereBetween('end', [new DateTime(date('Y-m-d', $timestampForDate)), new DateTime(date('Y-m-d', strtotime($task->due)))])->get();

        //loop through and remove days from weekdays

        foreach ($timeoffsInPeriod as $timeoff) {
            if ($timeoff->time !== null) {
                $dailyTimeoff = ((int)$timeoff->time) / 3600 / $day->hoursContract;
                $weekdaysToWorkOn -= $dailyTimeoff;
            } else {
                $dailyTimeoff = ((float)$timeoff->time_off_period);
                $weekdaysToWorkOn -= $dailyTimeoff;
            }
        }

        //use workdays to get the left time divided by those
        $leftWorktimeForDate = $day->hoursContract - $day->hoursTimeoff - $day->hoursTask;
        $taskHoursForDay = 0;
        $weekdaysToWorkOn = ($weekdaysToWorkOn > 0) ? $weekdaysToWorkOn : 1;
        if ($leftWorktimeForDate > 0) {
            $taskHoursForDay = $leftTaskTrackingTime / $weekdaysToWorkOn / 3600;
            if ($day->hoursTimeoff > 0) {
                //multiple task time with worktime factor for today to split the task time correctly
                $taskHoursForDay *= 1 - $day->hoursTimeoff / $day->hoursContract;
            }
        }
        //if daily task worktime is more than worktime left, just use the remaining worktime as task worktime
        if ($leftWorktimeForDate < $taskHoursForDay) {
            $taskHoursForDay = $leftWorktimeForDate;
        }
        return $taskHoursForDay;
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

    public function getActiveContactForUser(int $userId, DateTime $date) //todo: move into another service
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
