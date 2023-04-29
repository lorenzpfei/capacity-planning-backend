<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkloadController;
use App\Models\Department;
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


Route::middleware(['auth:sanctum'])
    ->controller(UserController::class)
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/oauth', 'getAvailableOAuthProviders')->name('oauth');
        Route::get('/me', 'getLoggedinUserData')->name('me');
        Route::get('/logout', 'logout')->name('logout');
    });

Route::middleware('auth:sanctum')->controller(WorkloadController::class)->group(function () {
    Route::get('/workload/{departmentId}/{from?}/{to?}', 'getWorkloadForDepartment');
});

Route::middleware('auth:sanctum')->get('/departments', function () {
    return Department::all();
});
