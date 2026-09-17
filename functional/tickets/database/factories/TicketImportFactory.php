<?php

namespace Functional\Tickets\Database\Factories;

use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Tickets\Models\TicketImport;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketImport>
 */
class TicketImportFactory extends Factory
{
    protected $model = TicketImport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploaded_by' => User::factory(),
            'original_name' => faker()->words(2).'.csv',
            'status' => TicketImportStatus::Pending,
        ];
    }
}
