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

Route::get('/test', static function () {
    return \App\Services\Tasks\AsanaTaskApi::getAssignedTasksForUser(\App\Models\User::find(1));
});

Route::get('oauth/{provider}', [OAuthController::class, 'redirectToProvider']);
Route::get('oauth/{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
