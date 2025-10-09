<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Define the delete-logs gate
        Gate::define('delete-logs', function (User $user) {
            return $user->user_type === 'admin'; // Adjust condition based on your requirements
        });
    }
}

