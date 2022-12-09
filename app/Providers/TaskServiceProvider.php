<?php

namespace App\Providers;

use App\Contracts\TaskService;
use App\Services\Tasks\AsanaTaskApi;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TaskService::class, function ($app) {
            return match (config('services.provider.task')) {
                'asana' => new AsanaTaskApi(),
                'gitlab' => null, //todo implement
                default => throw new RuntimeException('The task service driver is invalid.'),
            };
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
