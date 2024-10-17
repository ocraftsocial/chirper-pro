<?php

namespace App\Providers;

use App\Models\Chirp;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // Define policies here if you have any
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define your gate here
        Gate::define('view-chirp', function ($user, Chirp $chirp) {
            return $user->id === $chirp->user_id; // Check if the user is the author
        });
    }
}
