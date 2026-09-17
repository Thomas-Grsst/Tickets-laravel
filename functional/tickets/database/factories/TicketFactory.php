<?php

namespace Functional\Tickets\Database\Factories;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = faker()->dateTime('-90 days', '-1 day');

        return [
            'requester_id'           => User::factory(),
            'assigned_technician_id' => null,
            'title'                  => faker()->sentences(1),
            'description'            => faker()->paragraphs(2),
            'status'                 => TicketStatus::Open,
            'priority'               => faker()->randomElement(TicketPriority::cases()),
            'resolved_at'            => null,
            'created_at'             => $createdAt,
            'updated_at'             => $createdAt,
        ];
    }

    public function assignedToTechnician(?User $technician = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_technician_id' => $technician ?? User::factory(),
            'status'                 => TicketStatus::Assigned,
        ]);
    }
}
