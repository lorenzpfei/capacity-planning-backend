<?php

namespace App\Http\Controllers;

use App\Contracts\TaskService;
use App\Contracts\TrackingService;
use App\Services\Feature\WorkloadService;
use DateTime;

class WorkloadController extends Controller
{
    private WorkloadService $workloadService;

    public function __construct(TaskService $taskService, TrackingService $trackingService)
    {
        $this->workloadService = new WorkloadService($taskService, $trackingService);
    }

    /**
     * @throws \Exception
     */
    public function getWorkloadForDepartment(int $departmentId, string $from = '', string $to = '')
    {
        if($from === '')
        {
            $from = date('d.m.Y');
        }
        if($to === '')
        {
            $date = date('d.m.Y');
            $date = strtotime("+35 day", strtotime($date));
            $to = sprintf('%d-%d-%d', date("Y", $date), date("m", $date), date("d", $date));
        }
        $this->workloadService->getWorkloadForDepartment($departmentId, new DateTime($from), new DateTime($to));
    }
}
