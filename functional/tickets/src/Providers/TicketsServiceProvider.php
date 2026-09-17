<?php

namespace Functional\Tickets\Providers;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Database\Seeders\TicketAccessSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class TicketsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([TicketAccessSeeder::class, TicketsSeeder::class]);
        }

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );
    }

    /**
     * The package discovers controls by scanning app/Access/Controls, a path no OSDD
     * layer has, so the control is handed to the registry explicitly instead.
     */
    public function register(): void
    {
        (new Access())->addControl(new TicketControl());
    }
}
