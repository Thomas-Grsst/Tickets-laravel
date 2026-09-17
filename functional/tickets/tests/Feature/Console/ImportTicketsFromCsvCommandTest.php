<?php

namespace Functional\Tickets\Tests\Feature\Console;

use Functional\Tickets\Jobs\ImportTicketsFromCsvFile;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\WritesTicketImportCsv;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTicketsFromCsvCommandTest extends TestCase
{
    use RefreshDatabase;
    use WritesTicketImportCsv;

    #[Test]
    public function it_queues_the_import_instead_of_running_it_in_the_console_process(): void
    {
        Queue::fake();

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
        ]);

        $this->artisan('tickets:import-csv', ['path' => $path])->assertSuccessful();

        Queue::assertPushed(ImportTicketsFromCsvFile::class);
    }

    #[Test]
    public function it_imports_the_file_once_the_queued_job_runs(): void
    {
        User::factory()->create(['email' => 'first@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
        ]);

        $this->artisan('tickets:import-csv', ['path' => $path])->assertSuccessful();

        $this->assertSame(1, Ticket::query()->count());
    }

    #[Test]
    public function it_fails_immediately_when_the_path_points_at_nothing(): void
    {
        Queue::fake();

        $this->artisan('tickets:import-csv', ['path' => sys_get_temp_dir() . '/no-such-file.csv'])
            ->assertFailed();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_reports_the_rejections_without_writing_anything_on_a_dry_run(): void
    {
        User::factory()->create(['email' => 'first@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            $this->importCsvLine('ghost@example.test', 'Unknown requester'),
        ]);

        $this->artisan('tickets:import-csv', ['path' => $path, '--dry-run' => true])
            ->expectsOutputToContain('ghost@example.test')
            ->assertSuccessful();

        $this->assertSame(0, Ticket::query()->count());
    }
}
