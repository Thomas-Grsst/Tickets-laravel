<?php

namespace Functional\Tickets\Mcp\Resources;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Mcp\Tools\CreateTicketTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

/**
 * What an agent would otherwise have to guess before its first call: which fields a ticket
 * needs, which priorities exist and what each one promises, and which lifecycle move is legal
 * from which status. Every line is read back from the enums and from the create tool's own
 * schema, so the document cannot describe rules the application no longer enforces.
 */
#[Name('ticket-rules')]
#[Uri('tickets://rules')]
#[MimeType('application/json')]
#[Description('The rules for filling out a ticket: the required fields, the accepted priorities with the SLA each one promises, and the legal lifecycle transitions.')]
class TicketRulesResource extends Resource
{
    public function handle(CreateTicketTool $createTicket): ResponseFactory
    {
        $createSchema = JsonSchema::object($createTicket->schema(...))->toArray();

        return Response::structured([
            'required_fields' => $createSchema['required'] ?? [],
            'priorities' => $this->priorities(),
            'transitions' => $this->transitions(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function priorities(): array
    {
        return array_map(static fn (TicketPriority $priority): array => [
            'value' => $priority->value,
            'sla_hours' => $priority->slaHours(),
        ], TicketPriority::cases());
    }

    /**
     * @return array<string, list<string>>
     */
    private function transitions(): array
    {
        $transitions = [];

        foreach (TicketStatus::cases() as $status) {
            $transitions[$status->value] = array_map(
                static fn (TicketStatus $target): string => $target->value,
                $status->allowedTransitions(),
            );
        }

        return $transitions;
    }
}
