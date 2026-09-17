<?php

namespace Functional\Tickets\Tests\Feature\Importing;

use Functional\Tickets\Importing\Stages\ResolveTicketImportRequesters;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Users\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolveTicketImportRequestersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_attaches_the_requester_id_of_every_known_address(): void
    {
        $requester = User::factory()->create(['email' => 'first@example.test']);

        $payload = $this->resolve([$this->rowFor(2, 'first@example.test')]);

        $this->assertSame($requester->getKey(), $payload->rows->first()->requesterId);
        $this->assertTrue($payload->rejections->isEmpty());
    }

    #[Test]
    public function it_rejects_an_address_nobody_owns_and_keeps_the_rest(): void
    {
        User::factory()->create(['email' => 'first@example.test']);

        $payload = $this->resolve([
            $this->rowFor(2, 'first@example.test'),
            $this->rowFor(3, 'ghost@example.test'),
        ]);

        $this->assertSame(1, $payload->rows->count());
        $this->assertSame([3], $payload->rejections->map(
            static fn (TicketImportRejection $rejection): int => $rejection->lineNumber,
        )->all());
        $this->assertStringContainsString('ghost@example.test', $payload->rejections->first()->reason);
    }

    #[Test]
    public function it_resolves_the_whole_batch_in_a_single_query(): void
    {
        $emails = ['first@example.test', 'second@example.test', 'third@example.test'];

        foreach ($emails as $email) {
            User::factory()->create(['email' => $email]);
        }

        $rows = [];

        for ($rowPosition = 0; $rowPosition < 60; $rowPosition++) {
            $rows[] = $this->rowFor($rowPosition + 2, $emails[$rowPosition % count($emails)]);
        }

        $queryCount = 0;
        DB::listen(static function (QueryExecuted $executedQuery) use (&$queryCount): void {
            $queryCount++;
        });

        $payload = $this->resolve($rows);

        $this->assertSame(60, $payload->rows->count());
        $this->assertSame(
            1,
            $queryCount,
            'Reference resolution must not grow with the number of rows.',
        );
    }

    #[Test]
    public function it_runs_no_query_at_all_when_every_row_was_already_rejected(): void
    {
        $queryCount = 0;
        DB::listen(static function (QueryExecuted $executedQuery) use (&$queryCount): void {
            $queryCount++;
        });

        $this->resolve([]);

        $this->assertSame(0, $queryCount);
    }

    private function rowFor(int $lineNumber, string $email): TicketImportRow
    {
        return new TicketImportRow($lineNumber, $email, 'Printer down', 'Nothing prints', 'normal');
    }

    /**
     * @param  list<TicketImportRow>  $rows
     */
    private function resolve(array $rows): TicketImportPayload
    {
        return app(ResolveTicketImportRequesters::class)->handle(
            new TicketImportPayload('irrelevant.csv', new Collection($rows)),
            static fn (TicketImportPayload $passed): TicketImportPayload => $passed,
        );
    }
}
