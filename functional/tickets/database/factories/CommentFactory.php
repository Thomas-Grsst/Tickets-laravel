<?php

namespace Functional\Tickets\Database\Factories;

use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = faker()->dateTime('-60 days', 'now');

        return [
            'ticket_id'  => Ticket::factory(),
            'author_id'  => User::factory(),
            'body'       => faker()->paragraphs(1),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
