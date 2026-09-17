<?php

namespace Functional\Tickets\Importing;

use Illuminate\Support\Collection;

/**
 * The single value every stage takes and returns. Stages never mutate it, so a run that
 * dies mid-pipeline leaves no half-updated payload behind.
 */
readonly class TicketImportPayload
{
    /**
     * @param  Collection<int, TicketImportRow>  $rows
     * @param  Collection<int, TicketImportRejection>  $rejections
     */
    public function __construct(
        public string $path,
        public Collection $rows = new Collection(),
        public Collection $rejections = new Collection(),
        public int $readCount = 0,
        public int $createdCount = 0,
    ) {}

    /**
     * @param  Collection<int, TicketImportRow>  $rows
     */
    public function withRows(Collection $rows): self
    {
        return new self($this->path, $rows->values(), $this->rejections, $this->readCount, $this->createdCount);
    }

    /**
     * @param  Collection<int, TicketImportRejection>  $rejections
     */
    public function withRejections(Collection $rejections): self
    {
        return new self(
            $this->path,
            $this->rows,
            $this->rejections->concat($rejections->all())->values(),
            $this->readCount,
            $this->createdCount,
        );
    }

    public function withReadCount(int $readCount): self
    {
        return new self($this->path, $this->rows, $this->rejections, $readCount, $this->createdCount);
    }

    public function withCreatedCount(int $createdCount): self
    {
        return new self($this->path, $this->rows, $this->rejections, $this->readCount, $createdCount);
    }
}
