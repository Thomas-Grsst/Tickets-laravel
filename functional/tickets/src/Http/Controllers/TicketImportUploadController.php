<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Jobs\ProcessTicketImport;
use Functional\Tickets\Models\TicketImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketImportUploadController
{
    private const DISK = 'local';

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->can(TicketPermission::ViewAllTickets->value), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $validated['file']->store('ticket-imports', self::DISK);

        $ticketImport = TicketImport::create([
            'uploaded_by' => $request->user()->id,
            'original_name' => $validated['file']->getClientOriginalName(),
            'status' => TicketImportStatus::Pending,
        ]);

        ProcessTicketImport::dispatch($ticketImport, self::DISK, $path);

        return response()->json(['id' => $ticketImport->id, 'status' => $ticketImport->status], 202);
    }
}
