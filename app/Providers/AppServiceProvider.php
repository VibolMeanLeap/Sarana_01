<?php

namespace App\Providers;

use App\Models\Settings;
use App\Models\StoreContactReply;
use App\Services\AdminActionCountService;
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

        // The administrator shell receives small action badges that are also
        // refreshed through the AJAX endpoint without reloading the page.
        View::composer('layout.admin', static function ($view) {
            $service = app(AdminActionCountService::class);
            $view->with('adminActionCounts', $service->get());
            $view->with('adminLatestWebsiteOrderId', $service->latestWebsiteOrderId());
        });

        View::composer('layout.web', static function ($view) {
            $unreadStoreReplyCount = 0;
            $user = auth()->user();
            if ($user && strtolower((string) $user->role?->role_name) === 'customer') {
                $unreadStoreReplyCount = StoreContactReply::query()
                    ->whereIn('sender_type', ['admin', 'system'])
                    ->whereNull('read_at')
                    ->whereHas('contactMessage', fn ($query) => $query->where('user_id', $user->user_id))
                    ->count();
            }

            $view->with('unreadStoreReplyCount', $unreadStoreReplyCount);
        });
    }
}
