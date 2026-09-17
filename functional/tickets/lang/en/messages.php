<?php

return [
    'transitions' => [
        'illegal' => 'A ticket cannot move from :from to :target.',
    ],

    'status' => [
        'open' => 'Open',
        'assigned' => 'Assigned',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    'console' => [
        'escalate' => [
            'scanning' => 'Scanning unresolved tickets for SLA breaches…',
            'summary' => 'Overdue: :overdue — escalated: :escalated, flagged at Critical: :flagged.',
            'columns' => [
                'id' => 'Ticket',
                'title' => 'Title',
                'priority' => 'Priority (after run)',
                'age' => 'Age (h)',
                'outcome' => 'Outcome',
            ],
            'outcome' => [
                'escalated' => 'Escalated',
                'flagged' => 'Flagged (already Critical)',
            ],
        ],
    ],

    'attachments' => [
        'title' => 'Attachments',
        'empty' => 'No file attached to this ticket yet.',
        'upload' => 'Attach file',
        'delete' => 'Remove',
        'kind' => [
            'image' => 'Image',
            'document' => 'Document',
            'archive' => 'Archive',
        ],
        'success' => [
            'uploaded' => 'File attached.',
            'deleted' => 'Attachment removed.',
        ],
        'error' => [
            'unsupported_type' => 'Files of type :type cannot be attached to a ticket.',
        ],
    ],

    'import' => [
        'rejection' => [
            'column_count' => 'The row has :found column(s) where the header declares :expected.',
            'unknown_requester' => 'No user is registered with the address :email.',
        ],
        'console' => [
            'unreadable' => 'No readable CSV file at :path.',
            'queued' => 'Import of :path queued — the report is written to the log once it runs.',
            'summary' => 'Rows read: :read — tickets created: :created, rows rejected: :rejected.',
            'columns' => [
                'line' => 'Line',
                'reason' => 'Rejection reason',
            ],
        ],
    ],

    'mcp' => [
        'error' => [
            'ticket_not_visible' => 'No ticket [:id] is visible to you. Search first, then use an id the search returned.',
        ],
    ],

    'change_history' => [
        'author_kind' => [
            'user' => 'User',
            'system' => 'System',
        ],
    ],

    'nav' => [
        'tickets' => 'Tickets',
        'new_ticket' => 'New ticket',
        'logout' => 'Log out',
    ],

    'auth' => [
        'email' => 'Email',
        'password' => 'Password',
        'login' => 'Log in',
        'failed' => 'These credentials do not match our records.',
    ],

    'list' => [
        'empty' => 'No tickets match these filters.',
        'columns' => [
            'title' => 'Title',
            'requester' => 'Requester',
            'assigned_technician' => 'Technician',
            'status' => 'Status',
            'priority' => 'Priority',
            'comments' => 'Comments',
            'created_at' => 'Created',
        ],
        'unassigned' => 'Unassigned',
        'filters' => [
            'status' => 'Status',
            'priority' => 'Priority',
            'all' => 'All',
        ],
    ],

    'form' => [
        'create_title' => 'New ticket',
        'edit_title' => 'Edit ticket',
        'title' => 'Title',
        'description' => 'Description',
        'priority' => 'Priority',
        'submit_create' => 'Create ticket',
        'submit_update' => 'Save changes',
        'back' => 'Back to list',
        'current_status' => 'Current status',
        'actions' => [
            'assign' => 'Assign to me',
            'unassign' => 'Unassign',
            'start_progress' => 'Start progress',
            'resolve' => 'Resolve',
            'reopen' => 'Reopen',
            'close' => 'Close',
        ],
        'success' => [
            'created' => 'Ticket created.',
            'updated' => 'Ticket updated.',
            'transitioned' => 'Ticket moved to :status.',
        ],
        'error' => [
            'transition' => 'This transition is not allowed from the current status.',
        ],
        'comments' => 'Comments',
        'comment_placeholder' => 'Write a comment…',
        'add_comment' => 'Add comment',
    ],
];
