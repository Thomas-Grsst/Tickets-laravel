<?php

namespace Functional\Tickets\Tests\Concerns;

/**
 * The import reads a path on disk, so tests hand it a real file rather than a fake disk.
 */
trait WritesTicketImportCsv
{
    /** @var list<string> */
    private array $writtenImportPaths = [];

    /**
     * @param  list<string>  $lines
     */
    protected function writeImportCsv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ticket-import-') . '.csv';

        file_put_contents($path, implode("\n", $lines)."\n");

        $this->writtenImportPaths[] = $path;

        return $path;
    }

    /**
     * @return list<string>
     */
    protected function importCsvHeader(): array
    {
        return ['requester_email,title,description,priority'];
    }

    protected function importCsvLine(string $email, string $title, string $priority = 'normal'): string
    {
        return "{$email},{$title},Something broke,{$priority}";
    }

    protected function tearDown(): void
    {
        foreach ($this->writtenImportPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }
}
