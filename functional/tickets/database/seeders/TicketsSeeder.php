<?php

namespace Functional\Tickets\Database\Seeders;

use DateTimeInterface;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class TicketsSeeder extends Seeder
{
    public function run(): void
    {
        $requesters  = User::factory()->count(6)->create();
        $technicians = User::factory()->count(4)->create();
        $managers    = User::factory()->count(2)->create();

        $commentAuthors = $requesters->concat($technicians)->concat($managers);

        foreach (TicketStatus::cases() as $status) {
            foreach (TicketPriority::cases() as $priority) {
                $ticket = $this->createTicket($status, $priority, $requesters, $technicians);

                Comment::factory()
                    ->count(faker()->number(1, 4))
                    ->for($ticket)
                    ->recycle($commentAuthors)
                    ->create();
            }
        }

        Ticket::factory()
            ->count(15)
            ->recycle($requesters)
            ->create()
            ->each(fn (Ticket $ticket) => Comment::factory()
                ->count(faker()->number(1, 3))
                ->for($ticket)
                ->recycle($commentAuthors)
                ->create());
    }

    /**
     * @param Collection<int, User> $requesters
     * @param Collection<int, User> $technicians
     */
    private function createTicket(
        TicketStatus $status,
        TicketPriority $priority,
        Collection $requesters,
        Collection $technicians,
    ): Ticket {
        $factory = Ticket::factory()->for($requesters->random(), 'requester');

        if ($status !== TicketStatus::Open) {
            $factory = $factory->assignedToTechnician($technicians->random());
        }

        return $factory->create([
            'status'      => $status,
            'priority'    => $priority,
            'resolved_at' => $this->resolvedAtFor($status),
        ]);
    }

    private function resolvedAtFor(TicketStatus $status): ?DateTimeInterface
    {
        if (! in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], strict: true)) {
            return null;
        }

        return faker()->dateTime('-20 days', 'now');
    }
}
