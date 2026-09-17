<?php

namespace Functional\Tickets\Providers;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Console\Commands\EscalateOverdueTicketsCommand;
use Functional\Tickets\Database\Seeders\TicketAccessSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Listeners\NotifyTechnicianOfTicketAssignment;
use Functional\Tickets\Notifications\Channels\ImmediateAlertChannel;
use Functional\Tickets\Notifications\Channels\UrgentChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class TicketsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([TicketAccessSeeder::class, TicketsSeeder::class]);
            $this->commands([EscalateOverdueTicketsCommand::class]);
        }

        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'tickets');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tickets');

        $this->registerListeners();
        $this->registerNotificationChannels();

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }

    /**
     * The one place the layer's side effects are wired, so a reader never has to open a
     * model to discover what reacts to it.
     */
    private function registerListeners(): void
    {
        Event::listen(TicketAssigned::class, NotifyTechnicianOfTicketAssignment::class);
    }

    /**
     * Registers the priority-driven notification channels the strategy resolver can hand
     * out. `urgent` and `immediate-alert` simulate an external system with a structured log.
     */
    private function registerNotificationChannels(): void
    {
        Notification::extend('urgent', fn () => new UrgentChannel);
        Notification::extend('immediate-alert', fn () => new ImmediateAlertChannel);
    }

    /**
     * The package discovers controls by scanning app/Access/Controls, a path no OSDD
     * layer has, so the control is handed to the registry explicitly instead.
     */
    public function register(): void
    {
        (new Access)->addControl(new TicketControl);
    }
}
