<?php

namespace App\Http\Controllers;

use App\Services\Feature\UserService;
use App\Services\Feature\WorkloadService;
use DateTime;

class WorkloadController extends Controller
{
    private WorkloadService $workloadService;

    public function __construct(UserService $userService)
    {
        $this->workloadService = new WorkloadService($userService);
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
        return $this->workloadService->getWorkloadForDepartment($departmentId, new DateTime($from), new DateTime($to));
    }
}
