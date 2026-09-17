<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\TicketImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketImportShowController
{
    public function __invoke(Request $request, TicketImport $ticketImport): JsonResponse
    {
        abort_unless($request->user()->can(TicketPermission::ViewAllTickets->value), 403);

        return response()->json([
            'id' => $ticketImport->id,
            'status' => $ticketImport->status,
            'rows_read' => $ticketImport->rows_read,
            'tickets_created' => $ticketImport->tickets_created,
            'rejections' => $ticketImport->rejections,
        ]);
    }
}
