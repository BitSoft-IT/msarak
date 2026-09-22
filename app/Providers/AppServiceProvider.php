<?php

namespace App\Providers;

use App\Models\AssessmentSession;
use App\Policies\AssessmentSessionPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(AssessmentSession::class, AssessmentSessionPolicy::class);
    }
}
