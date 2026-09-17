<?php

namespace Functional\Tickets\Providers;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Database\Seeders\TicketAccessSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Listeners\DeleteStoredAttachmentFile;
use Functional\Tickets\Listeners\DeleteTicketAttachments;
use Functional\Tickets\Listeners\NotifyTechnicianOfTicketAssignment;
use Functional\Tickets\Livewire\TicketAttachments;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
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

        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'tickets');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tickets');

        $this->registerListeners();

        Livewire::component('tickets.attachments', TicketAttachments::class);

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );
    }

    /**
     * The one place the layer's side effects are wired, so a reader never has to open a
     * model to discover what reacts to it.
     */
    private function registerListeners(): void
    {
        Event::listen(TicketAssigned::class, NotifyTechnicianOfTicketAssignment::class);

        Attachment::deleted(static function (Attachment $attachment): void {
            app(DeleteStoredAttachmentFile::class)($attachment);
        });

        Ticket::forceDeleting(static function (Ticket $ticket): void {
            app(DeleteTicketAttachments::class)($ticket);
        });
    }

    /**
     * The package discovers controls by scanning app/Access/Controls, a path no OSDD
     * layer has, so the control is handed to the registry explicitly instead.
     */
    public function register(): void
    {
        $this->overrideConfigFrom(__DIR__ . '/../../config/tickets.php', 'tickets');

        (new Access())->addControl(new TicketControl());
    }
}
