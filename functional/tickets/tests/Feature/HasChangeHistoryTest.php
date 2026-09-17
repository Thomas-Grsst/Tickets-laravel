<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasChangeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_historized_attribute_writes_a_history_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $ticket->forceFill(['status' => TicketStatus::Assigned])->save();

        $entry = $ticket->changeHistory()->sole();

        $this->assertSame('status', $entry->attribute);
        $this->assertSame('open', $entry->old_value);
        $this->assertSame('assigned', $entry->new_value);
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_changing_an_untracked_attribute_writes_nothing(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Original title']);
        $ticket->update(['title' => 'Updated title']);

        $this->assertSame(0, $ticket->changeHistory()->count());
    }

    public function test_the_author_is_null_when_no_user_is_authenticated(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $ticket->forceFill(['status' => TicketStatus::Assigned])->save();

        $this->assertNull($ticket->changeHistory()->sole()->user_id);
    }

    public function test_the_same_trait_works_on_a_second_model_without_modification(): void
    {
        $comment = Comment::factory()->create(['body' => 'First draft']);
        $comment->update(['body' => 'Revised draft']);

        $entry = $comment->changeHistory()->sole();

        $this->assertSame('body', $entry->attribute);
        $this->assertSame('First draft', $entry->old_value);
        $this->assertSame('Revised draft', $entry->new_value);
    }
}
