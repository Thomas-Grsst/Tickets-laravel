<?php

namespace Functional\Tickets\Mcp\Resources;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;

/**
 * Tells the agent what a ticket needs, from the same source of truth the create tool and
 * the Livewire form validate against — an enum's own cases, not a copy of them.
 */
#[Description('The fields and allowed values required to create a ticket.')]
class TicketInputRulesResource extends Resource
{
    protected string $mimeType = 'application/json';

    public function handle(Request $request): Response
    {
        return Response::json([
            'fields' => [
                'title' => ['type' => 'string', 'required' => true, 'max' => 255],
                'description' => ['type' => 'string', 'required' => true],
                'priority' => [
                    'type' => 'string',
                    'required' => true,
                    'allowed' => array_column(TicketPriority::cases(), 'value'),
                ],
            ],
            'statuses' => array_column(TicketStatus::cases(), 'value'),
        ]);
    }
}
