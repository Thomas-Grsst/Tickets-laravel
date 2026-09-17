<?php

use Functional\Tickets\Http\Controllers\AttachmentDownloadController;
use Functional\Tickets\Http\Controllers\ResolvedTicketsExportController;
use Functional\Tickets\Http\Controllers\TicketAttachmentUploadController;
use Functional\Tickets\Rest\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Rest::resource('tickets', TicketsController::class);

    Route::get('tickets/exports/resolved-this-month', ResolvedTicketsExportController::class)
        ->name('tickets.exports.resolved-this-month');

    Route::post('tickets/{ticket}/attachments', TicketAttachmentUploadController::class)
        ->name('tickets.attachments.store');

    Route::get('attachments/{attachment}/download', AttachmentDownloadController::class)
        ->name('attachments.download');
});
