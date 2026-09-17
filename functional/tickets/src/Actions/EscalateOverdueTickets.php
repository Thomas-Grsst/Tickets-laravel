<?php

namespace Functional\Tickets\Actions;

use Carbon\CarbonInterface;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Escalations\OverdueTicketsEscalation;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketEscalatedNotification;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class EscalateOverdueTickets
{
    public function __invoke(): OverdueTicketsEscalation
    {
        [$escalatable, $capped] = $this->findOverdueTickets()->partition(
            static fn (Ticket $ticket): bool => $ticket->priority->escalatesTo() !== null,
        );

        if ($escalatable->isEmpty()) {
            return new OverdueTicketsEscalation($escalatable->values(), $capped->values());
        }

        $escalatedAt = now();
        $managers = $this->findManagers();

        $escalatable
            ->groupBy(static fn (Ticket $ticket): string => $ticket->priority->value)
            ->each(function (Collection $tickets, string $priority) use ($escalatedAt, $managers): void {
                $this->escalate($tickets, TicketPriority::from($priority), $escalatedAt, $managers);
            });

        return new OverdueTicketsEscalation($escalatable->values(), $capped->values());
    }

    /**
     * One statement for the whole table: the SLA window lives on the priority column, so
     * each priority contributes its own `created_at <= now - slaHours` branch to a single
     * OR group instead of a round trip per priority — and never a PHP-side date compare
     * per row.
     *
     * The clock starts at `escalated_at` once a ticket has been escalated, which both
     * restarts the SLA at the new priority and makes a second run in the same window a
     * no-op.
     *
     * @return Collection<int, Ticket>
     */
    private function findOverdueTickets(): Collection
    {
        return Ticket::query()
            ->whereNotIn('status', [TicketStatus::Resolved, TicketStatus::Closed])
            ->where(static function (Builder $query): void {
                foreach (TicketPriority::cases() as $priority) {
                    $query->orWhere(static function (Builder $branch) use ($priority): void {
                        $branch
                            ->where('priority', $priority)
                            ->whereRaw(
                                'coalesce(escalated_at, created_at) <= ?',
                                [now()->subHours($priority->slaHours())],
                            );
                    });
                }
            })
            ->get();
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @param Collection<int, User>   $managers
     */
    private function escalate(
        Collection $tickets,
        TicketPriority $breachedPriority,
        CarbonInterface $escalatedAt,
        Collection $managers,
    ): void {
        $raisedPriority = $breachedPriority->escalatesTo();

        if ($raisedPriority === null) {
            return;
        }

        Ticket::query()
            ->whereKey($tickets->pluck('id')->all())
            ->update(['priority' => $raisedPriority, 'escalated_at' => $escalatedAt]);

        $tickets->each(static function (Ticket $ticket) use ($raisedPriority, $breachedPriority, $escalatedAt, $managers): void {
            $ticket
                ->forceFill(['priority' => $raisedPriority, 'escalated_at' => $escalatedAt])
                ->syncOriginal();

            Notification::send($managers, new TicketEscalatedNotification($ticket, $breachedPriority));
        });
    }

    /**
     * The escalation asks someone to re-triage the ticket, so it goes to the people who
     * hold the right to do it — never to a role name.
     *
     * @return Collection<int, User>
     */
    private function findManagers(): Collection
    {
        return User::query()
            ->permission(TicketPermission::AssignTicket->value)
            ->get();
    }
}
