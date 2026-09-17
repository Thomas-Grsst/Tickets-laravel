<?php

namespace Technical\Framework\Providers;

use Illuminate\Support\Facades\Broadcast;
use Xefi\LaravelOSDD\LayerServiceProvider;

class FrameworkServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->registerBroadcastAuthenticationRoute();
    }

    /**
     * The `/broadcasting/auth` endpoint every private channel is gated by. It is framework
     * plumbing rather than a business concern, so it lives here while each functional layer
     * keeps its own channel definitions in its own routes/channels.php.
     */
    private function registerBroadcastAuthenticationRoute(): void
    {
        Broadcast::routes();
    }

    public function register(): void
    {
        $this->overrideConfigFrom(__DIR__ . '/../../config/permission.php', 'permission');
    }
}
