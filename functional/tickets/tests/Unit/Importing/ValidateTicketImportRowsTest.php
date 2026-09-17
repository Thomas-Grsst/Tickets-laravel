<?php

namespace Functional\Tickets\Tests\Unit\Importing;

use Functional\Tickets\Importing\Stages\ValidateTicketImportRows;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportRow;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidateTicketImportRowsTest extends TestCase
{
    #[Test]
    public function it_keeps_a_row_whose_columns_all_hold(): void
    {
        $payload = $this->validate([$this->validRow(2)]);

        $this->assertSame(1, $payload->rows->count());
        $this->assertTrue($payload->rejections->isEmpty());
    }

    #[Test]
    public function it_rejects_a_malformed_email_without_dropping_the_other_rows(): void
    {
        $payload = $this->validate([
            $this->validRow(2),
            new TicketImportRow(3, 'not-an-address', 'VPN down', 'Cannot connect', 'high'),
            $this->validRow(4),
        ]);

        $this->assertSame(2, $payload->rows->count());
        $this->assertSame([3], $payload->rejections->map(
            static fn (TicketImportRejection $rejection): int => $rejection->lineNumber,
        )->all());
        $this->assertStringContainsString('email', $payload->rejections->first()->reason);
    }

    #[Test]
    public function it_rejects_a_row_with_no_title(): void
    {
        $payload = $this->validate([new TicketImportRow(9, 'first@example.test', '', 'Broken', 'low')]);

        $this->assertTrue($payload->rows->isEmpty());
        $this->assertSame(9, $payload->rejections->first()->lineNumber);
    }

    #[Test]
    public function it_rejects_a_priority_outside_the_enum(): void
    {
        $payload = $this->validate([
            new TicketImportRow(5, 'first@example.test', 'Printer down', 'Broken', 'urgentissime'),
        ]);

        $this->assertTrue($payload->rows->isEmpty());
        $this->assertSame(5, $payload->rejections->first()->lineNumber);
    }

    #[Test]
    public function it_reports_every_broken_column_of_the_same_row_in_one_rejection(): void
    {
        $payload = $this->validate([new TicketImportRow(4, '', '', '', '')]);

        $this->assertSame(1, $payload->rejections->count());
        $this->assertSame(
            4,
            substr_count($payload->rejections->first()->reason, 'required'),
            'One rejection should carry the reason for each broken column.',
        );
    }

    private function validRow(int $lineNumber): TicketImportRow
    {
        return new TicketImportRow($lineNumber, 'first@example.test', 'Printer down', 'Nothing prints', 'normal');
    }

    /**
     * @param  list<TicketImportRow>  $rows
     */
    private function validate(array $rows): TicketImportPayload
    {
        return (new ValidateTicketImportRows)->handle(
            new TicketImportPayload('irrelevant.csv', new Collection($rows)),
            static fn (TicketImportPayload $passed): TicketImportPayload => $passed,
        );
    }
}
