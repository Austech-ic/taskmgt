<?php

namespace App\Providers;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        // Older MySQL/MariaDB builds common on shared hosting cap index length.
        Schema::defaultStringLength(191);
        Paginator::defaultView('partials.pagination');

        if (str_starts_with((string) config('app.url'), 'https://') && ! $this->app->runningInConsole()) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.*', 'partials.*'], function ($view) {
            static $pages = null;
            if ($pages === null) {
                try {
                    $pages = is_installed() ? Page::where('is_published', true)->orderBy('position')->get(['title', 'slug', 'placement']) : collect();
                } catch (Throwable) {
                    $pages = collect();
                }
            }
            $view->with('navPages', $pages);
        });
    }
}
