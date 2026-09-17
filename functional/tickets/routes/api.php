<?php

use Functional\Tickets\Http\Controllers\ResolvedTicketsExportController;
use Functional\Tickets\Http\Controllers\TicketAttachmentDestroyController;
use Functional\Tickets\Http\Controllers\TicketAttachmentDownloadController;
use Functional\Tickets\Http\Controllers\TicketAttachmentUploadController;
use Functional\Tickets\Http\Controllers\TicketImportShowController;
use Functional\Tickets\Http\Controllers\TicketImportUploadController;
use Functional\Tickets\Rest\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Rest::resource('tickets', TicketsController::class);

    Route::get('tickets/exports/resolved-this-month', ResolvedTicketsExportController::class)
        ->name('tickets.exports.resolved-this-month');

    Route::post('tickets/{ticket}/attachments', TicketAttachmentUploadController::class)
        ->name('tickets.attachments.store');
    Route::get('tickets/{ticket}/attachments/{attachment}/download', TicketAttachmentDownloadController::class)
        ->name('tickets.attachments.download');
    Route::delete('tickets/{ticket}/attachments/{attachment}', TicketAttachmentDestroyController::class)
        ->name('tickets.attachments.destroy');

    Route::post('tickets/imports', TicketImportUploadController::class)
        ->name('tickets.imports.store');
    Route::get('tickets/imports/{ticketImport}', TicketImportShowController::class)
        ->name('tickets.imports.show');
});
