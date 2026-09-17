<?php

return [
    'ticket_assigned' => [
        'subject' => 'Ticket assigned to you: :title',
        'heading' => 'A ticket has been assigned to you',
        'intro' => 'You are now the technician in charge of ":title".',
        'priority' => 'Priority: :priority',
        'sla' => 'Resolution target: :hours hours',
        'outro' => 'Thanks for taking care of it.',
    ],

    'ticket_sla_breached' => [
        'subject' => 'SLA breached: :title',
        'heading' => 'A ticket has breached its SLA',
        'escalated' => 'Ticket ":title" was overdue and has been escalated to :priority priority.',
        'still_critical' => 'Ticket ":title" is overdue and already at the highest priority.',
        'priority' => 'Priority: :priority',
        'age_hours' => 'Age: :hours hours',
        'outro' => 'Please review it.',
    ],
];
