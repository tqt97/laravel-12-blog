<?php

namespace App\Providers;

use App\Services\Cache\CacheService;
use App\Services\Cache\CacheServiceInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(
            CacheServiceInterface::class,
            CacheService::class
        );

        // $this->app->bind(PermissionRepositoryInterface::class, function ($app) {
        //     $permissionRepository = $app->make(PermissionRepository::class);
        //     $cacheService = $app->make(CacheServiceInterface::class);

        //     return config('repository_pattern.use_cached')
        //         ? new CachedPermissionRepository($permissionRepository, $cacheService)
        //         : $permissionRepository;
        // });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
