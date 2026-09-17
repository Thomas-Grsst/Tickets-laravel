<?php

namespace Functional\Tickets\Enums;

enum TicketPermission: string
{
    case ViewOwnTickets = 'tickets.view-own';
    case ViewAssignedTickets = 'tickets.view-assigned';
    case ViewAllTickets = 'tickets.view-all';
    case CreateTicket = 'tickets.create';
    case AssignTicket = 'tickets.assign';
    case CloseTicket = 'tickets.close';
}
