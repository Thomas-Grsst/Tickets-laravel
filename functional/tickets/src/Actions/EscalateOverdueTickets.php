<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketSlaBreachedNotification;
use Functional\Users\Models\User;
use Illuminate\Support\Facades\Notification;

class EscalateOverdueTickets
{
    /**
     * Detects overdue tickets one priority at a time (four queries, not four thousand) and
     * escalates each once. A ticket already at Critical never escalates again, but keeps
     * being signalled every run since it has nowhere left to go.
     *
     * @return array{examined: int, escalated: int}
     */
    public function __invoke(): array
    {
        $examined = Ticket::query()
            ->whereNull('escalated_at')
            ->whereNotIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->count();

        $escalatedCount = 0;

        foreach (TicketPriority::cases() as $priority) {
            $overdueTickets = Ticket::query()
                ->where('priority', $priority)
                ->whereNull('escalated_at')
                ->whereNotIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
                ->where('created_at', '<=', now()->subHours($priority->slaHours()))
                ->get(['id', 'title', 'priority', 'created_at']);

            if ($overdueTickets->isEmpty()) {
                continue;
            }

            $nextPriority = $priority->escalated();

            if ($nextPriority !== null) {
                Ticket::query()->whereKey($overdueTickets->modelKeys())->update([
                    'priority' => $nextPriority->value,
                    'escalated_at' => now(),
                ]);
            }

            foreach ($overdueTickets as $ticket) {
                $this->notifyManagers($ticket, wasEscalated: $nextPriority !== null);
            }

            $escalatedCount += $overdueTickets->count();
        }

        return ['examined' => $examined, 'escalated' => $escalatedCount];
    }

    private function notifyManagers(Ticket $ticket, bool $wasEscalated): void
    {
        $managers = User::permission(TicketPermission::ViewAllTickets->value)->get();

        Notification::send($managers, new TicketSlaBreachedNotification($ticket, $wasEscalated));
    }
}
