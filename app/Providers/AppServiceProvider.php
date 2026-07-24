<?php

namespace App\Providers;

use App\Models\User;
use App\Services\MailerService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.header', 'partials.navbar', 'welcome'], function ($view) {
            $userId = session('user_id');
            $headerUser = null;
            $notificationCount = 0;

            if ($userId) {
                $headerUser = User::query()
                    ->select('id', 'name', 'email', 'role', 'profile_pic')
                    ->find($userId);

                $notificationCount = app(NotificationService::class)->unreadCount($userId);
            }

            $view->with([
                'headerUser' => $headerUser,
                'notificationCount' => $notificationCount,
            ]);
        });
    }
}
