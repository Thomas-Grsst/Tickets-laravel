<?php

namespace Functional\Tickets\Tests\Feature\Jobs;

use Functional\Tickets\Actions\ImportTicketsFromCsv;
use Functional\Tickets\Exceptions\TicketImportFileUnreadableException;
use Functional\Tickets\Jobs\ImportTicketsFromCsvFile;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\WritesTicketImportCsv;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTicketsFromCsvFileTest extends TestCase
{
    use RefreshDatabase;
    use WritesTicketImportCsv;

    #[Test]
    public function it_imports_the_file_and_logs_the_report(): void
    {
        $logger = Log::spy();
        User::factory()->create(['email' => 'first@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            $this->importCsvLine('ghost@example.test', 'Unknown requester'),
        ]);

        $this->runJob($path);

        $this->assertSame(1, Ticket::query()->count());

        $logger->shouldHaveReceived('info')->withArgs(static function (string $message, array $logContext): bool {
            return $logContext['rows_read'] === 2
                && $logContext['tickets_created'] === 1
                && $logContext['rows_rejected'] === 1
                && $logContext['rejections'][0]['line_number'] === 3;
        })->once();
    }

    #[Test]
    public function it_fails_instead_of_logging_a_report_when_the_file_cannot_be_read(): void
    {
        $logger = Log::spy();

        $this->assertThrows(
            fn () => $this->runJob(sys_get_temp_dir() . '/no-such-ticket-import.csv'),
            TicketImportFileUnreadableException::class,
        );

        $logger->shouldNotHaveReceived('info');
    }

    private function runJob(string $path): void
    {
        (new ImportTicketsFromCsvFile($path))->handle(app(ImportTicketsFromCsv::class));
    }
}
