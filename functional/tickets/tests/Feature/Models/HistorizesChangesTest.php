<?php

namespace Functional\Tickets\Tests\Feature\Models;

use Functional\Tickets\Enums\ChangeAuthorKind;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\ChangeHistory;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HistorizesChangesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_old_and_new_value_of_a_historized_attribute(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $ticket->update(['status' => TicketStatus::Assigned]);

        $this->assertDatabaseHas('change_histories', [
            'historizable_type' => Ticket::class,
            'historizable_id' => $ticket->getKey(),
            'attribute' => 'status',
            'old_value' => TicketStatus::Open->value,
            'new_value' => TicketStatus::Assigned->value,
        ]);
    }

    #[Test]
    public function it_records_one_row_per_changed_attribute_and_none_for_the_untouched_ones(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Low,
        ]);
        $technician = User::factory()->create();

        $ticket->update([
            'status' => TicketStatus::Assigned,
            'assigned_technician_id' => $technician->getKey(),
        ]);

        $this->assertSame(
            ['assigned_technician_id', 'status'],
            $ticket->changeHistories()->pluck('attribute')->sort()->values()->all(),
        );
        $this->assertDatabaseHas('change_histories', [
            'attribute' => 'assigned_technician_id',
            'old_value' => null,
            'new_value' => (string) $technician->getKey(),
        ]);
    }

    #[Test]
    public function it_records_nothing_when_an_attribute_outside_the_declared_list_changes(): void
    {
        $ticket = Ticket::factory()->create();

        $ticket->update(['title' => 'A brand new title']);

        $this->assertDatabaseCount('change_histories', 0);
    }

    #[Test]
    public function it_records_nothing_when_a_historized_attribute_is_rewritten_with_the_same_value(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $ticket->update(['status' => TicketStatus::Open]);

        $this->assertDatabaseCount('change_histories', 0);
    }

    #[Test]
    public function it_records_nothing_when_the_model_is_created(): void
    {
        Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->assertDatabaseCount('change_histories', 0);
    }

    #[Test]
    public function it_historizes_a_second_model_through_the_very_same_trait(): void
    {
        $comment = Comment::factory()->create(['body' => 'The printer is offline.']);

        $comment->update(['body' => 'The printer is back online.']);

        $this->assertDatabaseHas('change_histories', [
            'historizable_type' => Comment::class,
            'historizable_id' => $comment->getKey(),
            'attribute' => 'body',
            'old_value' => 'The printer is offline.',
            'new_value' => 'The printer is back online.',
        ]);
    }

    #[Test]
    public function it_attributes_the_change_to_the_authenticated_user(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        $this->actingAs($author);
        $ticket->update(['priority' => TicketPriority::High]);

        $history = $ticket->changeHistories()->sole();

        $this->assertSame($author->getKey(), $history->author_id);
        $this->assertSame(ChangeAuthorKind::User, $history->author_kind);
        $this->assertTrue($author->is($history->author));
    }

    #[Test]
    public function it_attributes_the_change_to_the_system_when_no_user_is_authenticated(): void
    {
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        $this->assertGuest();
        $ticket->update(['priority' => TicketPriority::High]);

        $history = $ticket->changeHistories()->sole();

        $this->assertNull($history->author_id);
        $this->assertSame(ChangeAuthorKind::System, $history->author_kind);
    }

    /**
     * The hook rides the model's `updated` event, so a builder-level update that never
     * hydrates a model is outside its reach. E1's escalation command works that way on
     * purpose, which is why its priority raises leave no journal row — pinned here so the
     * boundary is a known, tested property rather than a surprise.
     */
    #[Test]
    public function it_leaves_no_trace_of_a_bulk_update_that_never_hydrates_a_model(): void
    {
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        Ticket::query()->whereKey($ticket->getKey())->update(['priority' => TicketPriority::High]);

        $this->assertSame(TicketPriority::High, $ticket->refresh()->priority);
        $this->assertDatabaseCount('change_histories', 0);
    }

    #[Test]
    public function it_prunes_history_rows_past_the_retention_window_and_keeps_the_recent_ones(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $ticket->update(['status' => TicketStatus::Assigned]);
        ChangeHistory::query()->update(['created_at' => now()->subDays(400)]);

        $ticket->update(['status' => TicketStatus::InProgress]);
        (new ChangeHistory())->pruneAll();

        $this->assertDatabaseCount('change_histories', 1);
        $this->assertDatabaseHas('change_histories', ['new_value' => TicketStatus::InProgress->value]);
    }
}
