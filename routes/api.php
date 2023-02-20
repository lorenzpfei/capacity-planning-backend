<?php

use App\Http\Controllers\WorkloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user()->makeHidden(['login_token', 'login_refresh_token', 'task_user_id', 'task_token', 'task_refresh_token', 'tracking_user_id', 'tracking_refresh_token', 'tracking_token']);
});

Route::middleware('auth:sanctum')->controller(WorkloadController::class)->group(function () {
    Route::get('/workload/{departmentId}/{from?}/{to?}', 'getWorkloadForDepartment');
});

