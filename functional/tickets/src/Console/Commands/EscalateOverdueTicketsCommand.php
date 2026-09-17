<?php

namespace Functional\Tickets\Console\Commands;

use Functional\Tickets\Actions\EscalateOverdueTickets;
use Illuminate\Console\Command;

class EscalateOverdueTicketsCommand extends Command
{
    protected $signature = 'tickets:escalate-overdue';

    protected $description = 'Escalate unresolved tickets that have breached their priority\'s SLA';

    public function handle(EscalateOverdueTickets $escalateOverdueTickets): int
    {
        $summary = $escalateOverdueTickets();

        $this->info("Examined {$summary['examined']} ticket(s), escalated {$summary['escalated']}.");

        return self::SUCCESS;
    }
}
