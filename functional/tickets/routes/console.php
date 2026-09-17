<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tickets:escalate-overdue')->daily();
