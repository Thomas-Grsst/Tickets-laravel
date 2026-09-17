<?php

namespace Functional\Tickets\Mcp;

use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

/**
 * The single shape a ticket takes on the MCP surface, so the tools that hand a ticket back
 * to a calling agent cannot drift from one another.
 */
class TicketSummary
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Ticket $ticket): array
    {
        /** @var ?User $requester */
        $requester = $ticket->requester;

        /** @var ?User $technician */
        $technician = $ticket->assignedTechnician;

        return [
            'id' => $ticket->getKey(),
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'requester' => $this->participant($requester),
            'assigned_technician' => $this->participant($technician),
            'created_at' => $ticket->created_at->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
        ];
    }

    /**
     * @return ?array<string, mixed>
     */
    private function participant(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
