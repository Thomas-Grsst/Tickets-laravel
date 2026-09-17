<?php

namespace Functional\Tickets\Enums;

/**
 * The policy abilities a ticket answers to, which are also the method names the
 * access-control perimeters match on — except `viewAny`, which the package rewrites to
 * `view` before any perimeter sees it, so no perimeter ever matches on it.
 */
enum TicketAbility: string
{
    case ViewAny = 'viewAny';
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Assign = 'assign';
    case Close = 'close';
}
