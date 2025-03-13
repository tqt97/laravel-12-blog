<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        /**
         * Eloquent strict mode
         *
         * Prevents lazy loading
         * Prevents silently discarding attributes.
         * Prevents accessing missing attributes.
         */
        Model::shouldBeStrict();
    }
}
