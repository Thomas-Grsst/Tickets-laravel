<?php

namespace Functional\Tickets\Importing;

/**
 * One CSV record, carried through the pipeline as it gets normalized and resolved.
 *
 * `lineNumber` is the record's position in the file counting the header as line 1, so a
 * rejection reads the same way the operator's spreadsheet does.
 */
readonly class TicketImportRow
{
    public const COLUMN_REQUESTER_EMAIL = 'requester_email';

    public const COLUMN_TITLE = 'title';

    public const COLUMN_DESCRIPTION = 'description';

    public const COLUMN_PRIORITY = 'priority';

    /** @var list<string> */
    public const COLUMNS = [
        self::COLUMN_REQUESTER_EMAIL,
        self::COLUMN_TITLE,
        self::COLUMN_DESCRIPTION,
        self::COLUMN_PRIORITY,
    ];

    public function __construct(
        public int $lineNumber,
        public string $requesterEmail,
        public string $title,
        public string $description,
        public string $priority,
        public ?int $requesterId = null,
    ) {}

    /**
     * @param  array<string, string>  $columns
     */
    public static function fromColumns(int $lineNumber, array $columns): self
    {
        return new self(
            $lineNumber,
            $columns[self::COLUMN_REQUESTER_EMAIL] ?? '',
            $columns[self::COLUMN_TITLE] ?? '',
            $columns[self::COLUMN_DESCRIPTION] ?? '',
            $columns[self::COLUMN_PRIORITY] ?? '',
        );
    }

    /**
     * @return array<string, string>
     */
    public function toValidatableColumns(): array
    {
        return [
            self::COLUMN_REQUESTER_EMAIL => $this->requesterEmail,
            self::COLUMN_TITLE => $this->title,
            self::COLUMN_DESCRIPTION => $this->description,
            self::COLUMN_PRIORITY => $this->priority,
        ];
    }

    public function withNormalizedColumns(
        string $requesterEmail,
        string $title,
        string $description,
        string $priority,
    ): self {
        return new self($this->lineNumber, $requesterEmail, $title, $description, $priority, $this->requesterId);
    }

    public function withRequesterId(int $requesterId): self
    {
        return new self(
            $this->lineNumber,
            $this->requesterEmail,
            $this->title,
            $this->description,
            $this->priority,
            $requesterId,
        );
    }
}
