<?php

namespace Functional\Tickets\Rest\Controllers;

use Functional\Tickets\Rest\Resources\TicketResource;
use Technical\Framework\Rest\Controllers\Controller;

class TicketsController extends Controller
{
    /** @var class-string<TicketResource> */
    public static $resource = TicketResource::class;
}
