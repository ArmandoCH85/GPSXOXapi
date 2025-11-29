<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Kristiansnts\FilamentApiLogin\Services\ExternalAuthService;
use App\Services\KiangelAuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ExternalAuthService::class,
            KiangelAuthService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
