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

    'ticket_needs_handling' => [
        'subject' => 'Ticket needs handling: :title',
        'heading' => 'A ticket is on its way to a technician',
        'intro' => '":title" has just been assigned and needs watching until it is resolved.',
        'priority' => 'Priority: :priority',
        'sla' => 'Resolution target: :hours hours',
        'outro' => 'Nothing to do unless the target slips.',
    ],

    'urgent_channel' => [
        'line' => 'Urgent channel: ":title" is :priority and must be resolved within :hours hours.',
    ],

    'immediate_alert' => [
        'line' => 'Immediate alert: ":title" is :priority — paging :recipients manager(s) right now.',
    ],
];
