<?php

namespace App\Providers;

use App\Contracts\TrackingService;
use App\Services\Tracking\EverhourTrackingApi;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class TrackingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TrackingService::class, function ($app) {
            return match (config('services.provider.tracking')) {
                'everhour' => new EverhourTrackingApi(),
                default => throw new RuntimeException('The tracking service driver is invalid.'),
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
