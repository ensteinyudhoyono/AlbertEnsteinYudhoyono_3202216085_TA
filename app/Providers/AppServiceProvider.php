<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use App\Services\WhatsAppService;
use App\Notifications\Channels\WhatsAppChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');

        // Register WhatsApp notification channel
        $this->app->make('Illuminate\Notifications\ChannelManager')
            ->extend('whatsapp', function ($app) {
                return new WhatsAppChannel($app->make(WhatsAppService::class));
            });
    }
}
