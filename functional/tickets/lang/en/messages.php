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

    'attachments' => [
        'title' => 'Attachments',
        'empty' => 'No files attached yet.',
        'upload' => 'Upload',
        'delete' => 'Delete',
        'uploaded_by' => 'Uploaded by :name',
        'success' => [
            'uploaded' => 'File uploaded.',
            'deleted' => 'File deleted.',
        ],
    ],
];
