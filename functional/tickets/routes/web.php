<?php

use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Livewire\TicketList;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/tickets', TicketList::class)->name('tickets.index');
    Route::get('/tickets/create', TicketForm::class)->name('tickets.create');
    Route::get('/tickets/{ticket}/edit', TicketForm::class)->name('tickets.edit');
});
