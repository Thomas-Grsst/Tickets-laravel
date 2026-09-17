<?php

namespace Functional\Tickets\Mcp\Servers;

use Functional\Tickets\Mcp\Resources\TicketRulesResource;
use Functional\Tickets\Mcp\Tools\CreateTicketTool;
use Functional\Tickets\Mcp\Tools\SearchTicketsTool;
use Functional\Tickets\Mcp\Tools\TransitionTicketTool;
use Functional\Tickets\Mcp\Tools\ViewTicketTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * The AI-facing door onto the ticketing domain, standing beside the REST API and the web UI
 * rather than in front of either: every tool reaches the same models, the same access-control
 * perimeters and the same transition actions those two already go through.
 */
#[Name('Tickets')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Consult and open support tickets on behalf of the authenticated user.

    Every call is scoped to what that user may see: a requester reaches only the tickets they
    opened, a technician also reaches the ones assigned to them, and a manager reaches all of
    them. A ticket outside that perimeter is reported as invisible.

    Read the `tickets://rules` resource before opening a ticket or moving one: it lists the
    required fields, the accepted priorities and the transitions that are legal from each
    status.
    MARKDOWN)]
class TicketsServer extends Server
{
    /** @var list<class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        SearchTicketsTool::class,
        ViewTicketTool::class,
        CreateTicketTool::class,
        TransitionTicketTool::class,
    ];

    /** @var list<class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [
        TicketRulesResource::class,
    ];
}
