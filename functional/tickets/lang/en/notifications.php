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

    'ticket_escalated' => [
        'subject' => 'Ticket escalated: :title',
        'heading' => 'An overdue ticket has been escalated',
        'intro' => '":title" went past its resolution target and was moved up one priority level.',
        'breached' => 'Missed target: :priority (:hours hours)',
        'raised' => 'New priority: :priority (:hours hours)',
        'outro' => 'Please re-triage it.',
    ],
];
