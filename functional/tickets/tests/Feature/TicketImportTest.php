<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Actions\Imports\ImportTicketsFromCsv;
use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Jobs\ProcessTicketImport;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Models\TicketImport;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketImportTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('manager')->syncPermissions([TicketPermission::ViewAllTickets->value]);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    public function test_uploading_a_csv_queues_the_import_and_responds_with_202(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->manager);

        $file = UploadedFile::fake()->createWithContent('tickets.csv', "title,description,priority,requester_email\n");

        $response = $this->post('/api/v1/tickets/imports', ['file' => $file]);

        $response->assertStatus(202);
        $this->assertDatabaseHas('ticket_imports', [
            'original_name' => 'tickets.csv',
            'status' => TicketImportStatus::Pending->value,
        ]);

        Queue::assertPushed(ProcessTicketImport::class);
    }

    public function test_processing_creates_valid_rows_and_reports_rejections(): void
    {
        $requester = User::factory()->create(['email' => 'requester@tickets.test']);

        $csv = "title,description,priority,requester_email\n"
            ."VPN down,Cannot connect,high,requester@tickets.test\n"
            .",Missing a title,normal,requester@tickets.test\n"
            ."Printer jam,Paper stuck,low,ghost@tickets.test\n";

        Storage::disk('local')->put('ticket-imports/batch.csv', $csv);

        $ticketImport = TicketImport::factory()->create(['status' => TicketImportStatus::Pending]);

        app(ImportTicketsFromCsv::class)($ticketImport, 'local', 'ticket-imports/batch.csv');

        $ticketImport->refresh();

        $this->assertSame(TicketImportStatus::Completed, $ticketImport->status);
        $this->assertSame(3, $ticketImport->rows_read);
        $this->assertSame(1, $ticketImport->tickets_created);
        $this->assertCount(2, $ticketImport->rejections);

        $this->assertDatabaseHas('tickets', ['title' => 'VPN down', 'requester_id' => $requester->id]);
        $this->assertDatabaseMissing('tickets', ['title' => 'Printer jam']);
    }

    public function test_requester_resolution_does_not_grow_with_the_number_of_rows(): void
    {
        $requester = User::factory()->create(['email' => 'requester@tickets.test']);

        $rows = collect(range(1, 30))
            ->map(fn (int $rowNumber): string => "Ticket {$rowNumber},Body {$rowNumber},low,requester@tickets.test")
            ->implode("\n");

        Storage::disk('local')->put('ticket-imports/large.csv', "title,description,priority,requester_email\n{$rows}\n");

        $ticketImport = TicketImport::factory()->create(['status' => TicketImportStatus::Pending]);

        DB::enableQueryLog();
        app(ImportTicketsFromCsv::class)($ticketImport, 'local', 'ticket-imports/large.csv');
        $selectQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with($query['query'], 'select'));
        DB::disableQueryLog();

        // One lookup for the requester, regardless of how many rows share it — not one per row.
        $this->assertLessThanOrEqual(2, $selectQueries->count());
        $this->assertSame(30, $ticketImport->refresh()->tickets_created);
        $this->assertSame($requester->id, Ticket::first()->requester_id);
    }

    public function test_an_unreadable_file_is_a_technical_failure_that_creates_nothing(): void
    {
        $ticketImport = TicketImport::factory()->create(['status' => TicketImportStatus::Pending]);

        $this->expectException(RuntimeException::class);

        app(ImportTicketsFromCsv::class)($ticketImport, 'local', 'ticket-imports/does-not-exist.csv');
    }
}
