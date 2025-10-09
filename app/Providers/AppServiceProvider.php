<?php

namespace App\Providers;

use App\Models\DeliveryRequest;
use App\Models\Logs;
use App\Policies\DeliveryRequestPolicy;
use App\Policies\LogPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    protected $policies = [
        Logs::class => LogPolicy::class,
    ];
}
