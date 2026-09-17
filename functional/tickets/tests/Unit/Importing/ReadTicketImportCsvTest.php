<?php

namespace Functional\Tickets\Tests\Unit\Importing;

use Closure;
use Functional\Tickets\Exceptions\TicketImportFileUnreadableException;
use Functional\Tickets\Exceptions\TicketImportHeaderMismatchException;
use Functional\Tickets\Importing\Stages\ReadTicketImportCsv;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Tests\Concerns\WritesTicketImportCsv;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadTicketImportCsvTest extends TestCase
{
    use WritesTicketImportCsv;

    #[Test]
    public function it_reads_every_record_and_numbers_it_from_the_header(): void
    {
        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            $this->importCsvLine('second@example.test', 'VPN down'),
        ]);

        $payload = $this->read($path);

        $this->assertSame(2, $payload->rows->count());
        $this->assertSame(2, $payload->readCount);
        $this->assertSame([2, 3], $payload->rows->map(
            static fn (TicketImportRow $row): int => $row->lineNumber,
        )->all());
        $this->assertSame('Printer down', $payload->rows->first()->title);
    }

    #[Test]
    public function it_rejects_a_record_whose_column_count_disagrees_with_the_header(): void
    {
        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            'second@example.test,Only three columns',
        ]);

        $payload = $this->read($path);

        $this->assertSame(1, $payload->rows->count());
        $this->assertSame(2, $payload->readCount);
        $this->assertSame(3, $payload->rejections->first()->lineNumber);
        $this->assertStringContainsString('2 column(s)', $payload->rejections->first()->reason);
    }

    #[Test]
    public function it_ignores_blank_lines_without_counting_them_as_rows(): void
    {
        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
            '',
            $this->importCsvLine('second@example.test', 'VPN down'),
        ]);

        $payload = $this->read($path);

        $this->assertSame(2, $payload->readCount);
        $this->assertSame([2, 4], $payload->rows->map(
            static fn (TicketImportRow $row): int => $row->lineNumber,
        )->all());
    }

    #[Test]
    public function it_aborts_when_the_file_cannot_be_read(): void
    {
        $this->expectException(TicketImportFileUnreadableException::class);

        $this->read(sys_get_temp_dir() . '/no-such-ticket-import.csv');
    }

    #[Test]
    public function it_aborts_when_the_header_is_missing_a_column(): void
    {
        $path = $this->writeImportCsv([
            'requester_email,title,description',
            'first@example.test,Printer down,Something broke',
        ]);

        $this->expectException(TicketImportHeaderMismatchException::class);

        $this->read($path);
    }

    #[Test]
    public function it_hands_the_read_rows_to_the_next_stage(): void
    {
        $path = $this->writeImportCsv([
            ...$this->importCsvHeader(),
            $this->importCsvLine('first@example.test', 'Printer down'),
        ]);

        $reached = false;

        (new ReadTicketImportCsv)->handle(
            new TicketImportPayload($path),
            function (TicketImportPayload $passed) use (&$reached): TicketImportPayload {
                $reached = true;

                return $passed;
            },
        );

        $this->assertTrue($reached);
    }

    private function read(string $path): TicketImportPayload
    {
        return (new ReadTicketImportCsv)->handle(
            new TicketImportPayload($path),
            $this->identityStage(),
        );
    }

    private function identityStage(): Closure
    {
        return static fn (TicketImportPayload $passed): TicketImportPayload => $passed;
    }
}
