<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Kristiansnts\FilamentApiLogin\Services\ExternalAuthService;
use App\Services\KiangelAuthService;
use App\Models\Event;
use App\Observers\EventObserver;

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
        Event::observe(EventObserver::class);
    }
}
