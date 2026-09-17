<?php

use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Technical\Framework\Models\ChangeHistory;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', ['--model' => Ticket::class])->daily();
Schedule::command('model:prune', ['--model' => ChangeHistory::class])->daily();
