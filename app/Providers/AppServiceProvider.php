<?php

namespace App\Providers;

use App\Models\AssessmentSession;
use App\Models\Result;
use App\Policies\AssessmentSessionPolicy;
use App\Policies\ResultPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(AssessmentSession::class, AssessmentSessionPolicy::class);
        Gate::policy(Result::class, ResultPolicy::class);
    }
}
