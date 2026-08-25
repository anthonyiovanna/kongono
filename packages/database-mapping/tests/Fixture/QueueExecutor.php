<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseExecutor;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\RowStream;
use RuntimeException;

final class QueueExecutor implements DatabaseExecutor
{
    /** @var list<RowStream|CommandResult> */
    private array $results;

    /** @var list<DatabaseStatement> */
    private array $statements = [];

    /** @param list<RowStream|CommandResult> $results */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        $this->statements[] = $statement;
        $result = array_shift($this->results);
        if (!$result instanceof RowStream && !$result instanceof CommandResult) {
            throw new RuntimeException('No queued database result.');
        }

        return $result;
    }

    /** @return list<DatabaseStatement> */
    public function statements(): array
    {
        return [...$this->statements];
    }
}
