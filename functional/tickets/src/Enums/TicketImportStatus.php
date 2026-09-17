<?php

namespace Functional\Tickets\Enums;

enum TicketImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
