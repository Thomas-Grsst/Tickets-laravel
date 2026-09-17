<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAssignedBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_broadcasts_only_to_the_requester_the_technician_and_managers(): void
    {
        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('manager')->syncPermissions([TicketPermission::ViewAllTickets->value]);

        $requester = User::factory()->create();
        $technician = User::factory()->create();
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $stranger = User::factory()->create();

        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        $channels = (new TicketAssigned($ticket, $technician))->broadcastOn();
        $channelNames = collect($channels)->map(fn (Channel $channel): string => $channel->name)->all();

        $this->assertContains("private-users.{$requester->id}.tickets", $channelNames);
        $this->assertContains("private-users.{$technician->id}.tickets", $channelNames);
        $this->assertContains("private-users.{$manager->id}.tickets", $channelNames);
        $this->assertNotContains("private-users.{$stranger->id}.tickets", $channelNames);
    }

    public function test_the_broadcast_name_and_payload_only_carry_the_ticket_id(): void
    {
        $ticket = Ticket::factory()->create();
        $technician = User::factory()->create();

        $event = new TicketAssigned($ticket, $technician);

        $this->assertSame('ticket.assigned', $event->broadcastAs());
        $this->assertSame(['ticket_id' => $ticket->id], $event->broadcastWith());
    }
}
