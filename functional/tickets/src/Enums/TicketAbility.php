<?php

namespace Functional\Tickets\Enums;

/**
 * The policy abilities a ticket answers to, which are also the method names the
 * access-control perimeters match on.
 */
enum TicketAbility: string
{
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Assign = 'assign';
    case Close = 'close';
}
