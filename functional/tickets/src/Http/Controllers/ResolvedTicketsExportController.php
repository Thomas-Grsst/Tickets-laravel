<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Models\Ticket;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResolvedTicketsExportController
{
    /** @var list<string> */
    private const EXPORTED_COLUMNS = [
        'id',
        'title',
        'status',
        'priority',
        'resolved_at',
    ];

    public function __invoke(): StreamedResponse
    {
        $month = now();

        $resolvedThisMonth = Ticket::query()
            ->select(self::EXPORTED_COLUMNS)
            ->whereBetween('resolved_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->orderBy('resolved_at');

        return response()->streamDownload(
            function () use ($resolvedThisMonth): void {
                $output = fopen('php://output', 'wb');

                fputcsv($output, self::EXPORTED_COLUMNS);

                foreach ($resolvedThisMonth->lazyById() as $ticket) {
                    fputcsv($output, [
                        $ticket->id,
                        $ticket->title,
                        $ticket->status->value,
                        $ticket->priority->value,
                        $ticket->resolved_at->toDateTimeString(),
                    ]);
                }

                fclose($output);
            },
            "resolved-tickets-{$month->format('Y-m')}.csv",
            ['Content-Type' => 'text/csv'],
        );
    }
}
