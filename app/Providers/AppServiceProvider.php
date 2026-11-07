<?php

namespace App\Providers;

use App\Models\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Register application services. */
    public function register(): void
    {
        // Resolve the store profile once per request. A fresh request reads the
        // newest saved record, so edits are reflected without clearing a cache.
        $this->app->scoped('shop.settings', static function () {
            return Settings::query()->orderByDesc('id')->first() ?? new Settings();
        });
    }

    /** Bootstrap application services. */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('*', static function ($view) {
            $view->with('shopSettings', app('shop.settings'));
        });
    }
}
