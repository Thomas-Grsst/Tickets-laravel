<?php

use Functional\Tickets\Http\Controllers\ResolvedTicketsExportController;
use Functional\Tickets\Rest\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Rest::resource('tickets', TicketsController::class);

    Route::get('tickets/exports/resolved-this-month', ResolvedTicketsExportController::class)
        ->name('tickets.exports.resolved-this-month');
});
