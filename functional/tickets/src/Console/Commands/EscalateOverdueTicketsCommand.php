<?php

namespace Functional\Tickets\Console\Commands;

use Functional\Tickets\Actions\EscalateOverdueTickets;
use Functional\Tickets\Escalations\OverdueTicketsEscalation;
use Functional\Tickets\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class EscalateOverdueTicketsCommand extends Command
{
    protected $signature = 'tickets:escalate';

    protected $description = 'Escalate unresolved tickets that went past the SLA target carried by their priority';

    /**
     * The scan and the escalation are one batched statement each, so there is no per-item
     * loop to narrate. The operator gets the announcement before the work, the full list
     * of touched tickets after it, and a closing count that proves the run completed.
     */
    public function handle(EscalateOverdueTickets $escalateOverdueTickets): int
    {
        $this->components->info(__('tickets::messages.console.escalate.scanning'));

        $escalation = $escalateOverdueTickets();

        if ($escalation->overdueCount() > 0) {
            $this->table(
                [
                    __('tickets::messages.console.escalate.columns.id'),
                    __('tickets::messages.console.escalate.columns.title'),
                    __('tickets::messages.console.escalate.columns.priority'),
                    __('tickets::messages.console.escalate.columns.age'),
                    __('tickets::messages.console.escalate.columns.outcome'),
                ],
                $this->buildTableRows($escalation),
            );
        }

        $this->components->info(__('tickets::messages.console.escalate.summary', [
            'overdue'   => $escalation->overdueCount(),
            'escalated' => $escalation->escalatedTickets->count(),
            'flagged'   => $escalation->cappedTickets->count(),
        ]));

        return self::SUCCESS;
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function buildTableRows(OverdueTicketsEscalation $escalation): array
    {
        return array_merge(
            $this->describeTickets($escalation->escalatedTickets, 'escalated'),
            $this->describeTickets($escalation->cappedTickets, 'flagged'),
        );
    }

    /**
     * @param Collection<int, Ticket> $tickets
     *
     * @return list<array<int, mixed>>
     */
    private function describeTickets(Collection $tickets, string $outcome): array
    {
        return $tickets
            ->map(static fn (Ticket $ticket): array => [
                (string) $ticket->getKey(),
                $ticket->title,
                __("tickets::messages.priority.{$ticket->priority->value}"),
                (string) (int) $ticket->created_at->diffInHours(now()),
                __("tickets::messages.console.escalate.outcome.{$outcome}"),
            ])
            ->values()
            ->all();
    }
}
