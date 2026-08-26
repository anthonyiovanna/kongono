<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture;

use Closure;
use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\ChangeExecutionOutcome;
use Kongono\Persistence\PersistenceChange;
use Kongono\Persistence\PersistenceChangeExecutor;
use RuntimeException;

final class RecordingChangeExecutor implements PersistenceChangeExecutor
{
    public int $calls = 0;
    public ?DatabaseExecutor $database = null;
    public ?PersistenceChange $change = null;
    public bool $throw = false;
    public ?Closure $beforeOutcome = null;

    public function __construct(public ChangeExecutionOutcome $outcome = ChangeExecutionOutcome::Applied) {}

    public function execute(DatabaseExecutor $database, PersistenceChange $change): ChangeExecutionOutcome
    {
        ++$this->calls;
        $this->database = $database;
        $this->change = $change;
        if (null !== $this->beforeOutcome) {
            ($this->beforeOutcome)();
        }
        if ($this->throw) {
            throw new RuntimeException('synthetic-provider-message sql=secret parameter=secret');
        }

        return $this->outcome;
    }
}
