<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseRow;
use Kongono\Database\RowStream;
use Throwable;

final class MemoryRowStream implements RowStream
{
    private int $offset = 0;
    private int $closeCount = 0;

    /**
     * @param list<DatabaseColumn> $columns
     * @param list<DatabaseRow> $rows
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $rows,
        private readonly ?Throwable $fetchFailure = null,
        private readonly ?Throwable $closeFailure = null,
    ) {}

    public function columns(): array
    {
        return [...$this->columns];
    }

    public function fetch(): ?DatabaseRow
    {
        if (null !== $this->fetchFailure) {
            throw $this->fetchFailure;
        }
        if (!isset($this->rows[$this->offset])) {
            return null;
        }

        return $this->rows[$this->offset++];
    }

    public function close(): void
    {
        ++$this->closeCount;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closeCount > 0;
    }

    public function closeCount(): int
    {
        return $this->closeCount;
    }
}
