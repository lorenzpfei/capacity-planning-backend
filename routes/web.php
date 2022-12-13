<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\WorkloadController;
use App\Providers\TaskServiceProvider;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', static function () {
    return view('welcome');
});

Route::controller(WorkloadController::class)->group(function () {
    Route::get('/workload/{departmentId}/{from?}/{to?}', 'getWorkloadForDepartment');
    //Route::post('/workload', 'store');
});

Route::get('oauth/{provider}', [OAuthController::class, 'redirectToProvider']);
Route::get('oauth/{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
