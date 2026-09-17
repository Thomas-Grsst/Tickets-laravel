<?php

namespace Functional\Tickets\Tests\Unit\Importing;

use Functional\Tickets\Importing\Stages\NormalizeTicketImportRows;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRow;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NormalizeTicketImportRowsTest extends TestCase
{
    #[Test]
    public function it_trims_every_column_and_lowercases_the_email_and_the_priority(): void
    {
        $payload = $this->normalize(new TicketImportRow(
            2,
            '  First.User@Example.TEST ',
            '  Printer down  ',
            '  Nothing prints  ',
            ' High ',
        ));

        $row = $payload->rows->first();

        $this->assertSame('first.user@example.test', $row->requesterEmail);
        $this->assertSame('Printer down', $row->title);
        $this->assertSame('Nothing prints', $row->description);
        $this->assertSame('high', $row->priority);
    }

    #[Test]
    public function it_keeps_the_line_number_and_the_row_count_untouched(): void
    {
        $payload = $this->normalize(new TicketImportRow(7, 'first@example.test', 'Printer down', 'Broken', 'low'));

        $this->assertSame(1, $payload->rows->count());
        $this->assertSame(7, $payload->rows->first()->lineNumber);
    }

    private function normalize(TicketImportRow $row): TicketImportPayload
    {
        return (new NormalizeTicketImportRows)->handle(
            new TicketImportPayload('irrelevant.csv', new Collection([$row])),
            static fn (TicketImportPayload $passed): TicketImportPayload => $passed,
        );
    }
}
