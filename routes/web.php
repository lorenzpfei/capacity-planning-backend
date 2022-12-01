<?php

use App\Http\Controllers\OAuthController;
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

//todo: Remove debug
Route::get('/test', static function () {
    $everhourTrackingApi = new \App\Services\Tracking\EverhourTrackingApi();
    //$everhourTrackingApi->importTimeoffs('2022-01-01', '2022-31-12');
    //$everhourTrackingApi = new \App\Services\Tracking\EverhourTrackingApi();
    //$everhourTrackingApi->importTrackingDataForTasks($tasks, 'as:');
    //return \App\Services\Tasks\AsanaTaskApi::getAssignedTasksForUser(\App\Models\User::find(1));
});

//todo: move to console
Route::get('/import', static function(){
    //\App\Services\Tasks\AsanaTaskApi::importTasksForUser(\App\Models\User::find(1));
    //$tasks = \App\Services\Tasks\AsanaTaskApi::getAssignedTasksForUser(\App\Models\User::find(1));
    $tasks = \App\Models\Task::all();
    \App\Services\Tracking\EverhourTrackingApi::importTrackingDataForTasks($tasks, 'as:');
});

Route::get('oauth/{provider}', [OAuthController::class, 'redirectToProvider']);
Route::get('oauth/{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
