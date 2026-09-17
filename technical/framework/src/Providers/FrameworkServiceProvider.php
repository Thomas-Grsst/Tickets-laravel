<?php

namespace Technical\Framework\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class FrameworkServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    public function register(): void
    {
        $this->overrideConfigFrom(__DIR__ . '/../../config/permission.php', 'permission');
    }
}
