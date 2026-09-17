<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketCommentsTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_ties_a_comment_to_its_ticket_and_its_author(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $comment = Comment::factory()->for($ticket)->create(['author_id' => $author->getKey()]);

        $this->assertTrue($comment->ticket->is($ticket));
        $this->assertTrue($comment->author->is($author));
        $this->assertTrue($ticket->comments->contains($comment));
    }

    #[Test]
    public function it_returns_the_comments_of_a_ticket_the_requester_can_see(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        Comment::factory()->count(2)->for($ticket)->create(['author_id' => $requester->getKey()]);
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/search', [
            'search' => ['includes' => [['relation' => 'comments']]],
        ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.comments');
    }

    #[Test]
    public function it_stores_a_comment_created_alongside_its_ticket(): void
    {
        $requester = $this->createRequester();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'Badge reader rejects my card',
                    'description' => 'It beeps twice and the door stays locked.',
                    'priority' => 'normal',
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    'comments' => [[
                        'operation' => 'create',
                        'attributes' => ['body' => 'Happens only at the north entrance.'],
                        'relations' => [
                            'author' => ['operation' => 'attach', 'key' => $requester->getKey()],
                        ],
                    ]],
                ],
            ]],
        ])->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'body' => 'Happens only at the north entrance.',
            'author_id' => $requester->getKey(),
        ]);
    }
}
