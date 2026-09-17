<?php

namespace Functional\Tickets\Tests\Feature\Importing;

use Functional\Tickets\Actions\ImportTicketsFromCsv;
use Functional\Tickets\Importing\TicketImportPipeline;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportReport;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\WritesTicketImportCsv;
use Functional\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTicketsFromCsvTest extends TestCase
{
    use RefreshDatabase;
    use WritesTicketImportCsv;

    #[Test]
    public function it_creates_every_ticket_of_a_clean_file(): void
    {
        $this->createRequesters(['first@example.test', 'second@example.test', 'third@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down', 'high'),
            $this->importCsvLine('second@example.test', 'VPN down', 'critical'),
            $this->importCsvLine('third@example.test', 'Mailbox full', 'low'),
        ]);

        $report = $this->import($path);

        $this->assertSame(3, $report->rowsRead);
        $this->assertSame(3, $report->ticketsCreated);
        $this->assertSame(0, $report->rowsRejected());
        $this->assertSame(3, Ticket::query()->count());
    }

    #[Test]
    public function it_imports_the_valid_rows_and_reports_exactly_the_five_it_refused(): void
    {
        $this->createRequesters(['first@example.test', 'second@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            $this->importCsvLine('not-an-address', 'Bad email'),
            $this->importCsvLine('second@example.test', 'VPN down'),
            $this->importCsvLine('ghost@example.test', 'Unknown requester'),
            $this->importCsvLine('first@example.test', ''),
            $this->importCsvLine('second@example.test', 'Bad priority', 'urgentissime'),
            'first@example.test,Too few columns',
            $this->importCsvLine('first@example.test', 'Mailbox full'),
        ]);

        $report = $this->import($path);

        $this->assertSame(8, $report->rowsRead);
        $this->assertSame(3, $report->ticketsCreated);
        $this->assertSame(5, $report->rowsRejected());
        $this->assertSame(3, Ticket::query()->count());

        $this->assertSame([3, 5, 6, 7, 8], $report->rejections->map(
            static fn (TicketImportRejection $rejection): int => $rejection->lineNumber,
        )->all());

        $reasons = $report->rejections->mapWithKeys(
            static fn (TicketImportRejection $rejection): array => [$rejection->lineNumber => $rejection->reason],
        );

        $this->assertStringContainsString('email', $reasons->get(3));
        $this->assertStringContainsString('ghost@example.test', $reasons->get(5));
        $this->assertStringContainsString('title', $reasons->get(6));
        $this->assertStringContainsString('priority', $reasons->get(7));
        $this->assertStringContainsString('column(s)', $reasons->get(8));
    }

    #[Test]
    public function it_aborts_the_whole_import_when_the_database_goes_away_mid_run(): void
    {
        $this->createRequesters(['first@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'First'),
            $this->importCsvLine('first@example.test', 'Second'),
            $this->importCsvLine('first@example.test', 'Third'),
            $this->importCsvLine('first@example.test', 'Fourth'),
        ]);

        $this->failTicketCreationAfter(2);

        $this->assertThrows(fn (): TicketImportReport => $this->import($path), QueryException::class);

        $this->assertSame(
            2,
            Ticket::query()->count(),
            'Rows committed before the failure survive: the boundary is the row, not the batch.',
        );
    }

    #[Test]
    public function it_writes_nothing_on_a_dry_run_while_still_reporting_the_rejections(): void
    {
        $this->createRequesters(['first@example.test']);

        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            $this->importCsvLine('ghost@example.test', 'Unknown requester'),
        ]);

        $report = app(ImportTicketsFromCsv::class)($path, TicketImportPipeline::DRY_RUN_STAGES);

        $this->assertSame(2, $report->rowsRead);
        $this->assertSame(0, $report->ticketsCreated);
        $this->assertSame(1, $report->rowsRejected());
        $this->assertSame(0, Ticket::query()->count());
    }

    /**
     * A driver-level failure raised from the `creating` event is the closest a test can get
     * to the connection dropping under the import: it is the same exception type Eloquent
     * would surface, and it happens between two rows rather than before the first.
     */
    private function failTicketCreationAfter(int $successfulCreations): void
    {
        $attempts = 0;

        Ticket::creating(static function () use (&$attempts, $successfulCreations): void {
            $attempts++;

            if ($attempts > $successfulCreations) {
                throw new QueryException(
                    'mysql',
                    'insert into `tickets` (`title`) values (?)',
                    [],
                    new PDOException('SQLSTATE[HY000] [2006] MySQL server has gone away'),
                );
            }
        });
    }

    /**
     * @param  list<string>  $emails
     */
    private function createRequesters(array $emails): void
    {
        foreach ($emails as $email) {
            User::factory()->create(['email' => $email]);
        }
    }

    private function import(string $path): TicketImportReport
    {
        return app(ImportTicketsFromCsv::class)($path);
    }
}
