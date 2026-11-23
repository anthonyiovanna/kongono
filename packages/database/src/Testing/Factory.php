<?php

declare(strict_types=1);

namespace Kongono\Database\Testing;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;

/**
 * Typed, immutable, zero-ActiveRecord test & seeder fixture factory.
 *
 * Example:
 *   $rows = Factory::for('orders', fn (int $n) => ['code' => "ORD-{$n}", 'status' => 'open'])
 *       ->count(3)
 *       ->sequence(['tier' => 'free'], ['tier' => 'pro'])
 *       ->state(['currency' => 'USD'])
 *       ->create($db);
 */
final class Factory
{
    private int $count = 1;

    /** @var list<array<string, mixed>|Closure(int, array<string, mixed>): array<string, mixed>> */
    private array $states = [];

    /** @var list<array<string, mixed>> */
    private array $sequenceStates = [];

    /** @param Closure(int): array<string, mixed> $definition Receives 1-based sequence index */
    private function __construct(
        private readonly string $table,
        private readonly Closure $definition,
    ) {}

    /** @param Closure(int): array<string, mixed> $definition */
    public static function for(string $table, Closure $definition): self
    {
        if ('' === trim($table)) {
            throw new DatabaseException('Factory table name cannot be empty.');
        }

        return new self($table, $definition);
    }

    public function count(int $count): self
    {
        if ($count < 1) {
            throw new DatabaseException('Factory count must be at least 1.');
        }
        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    /**
     * Layer an associative override or dynamic state callback on top of generated rows.
     *
     * @param array<string, mixed>|Closure(int, array<string, mixed>): array<string, mixed> $override
     */
    public function state(array|Closure $override): self
    {
        $clone = clone $this;
        $clone->states[] = $override;

        return $clone;
    }

    /**
     * Cycle round-robin across one or more state maps as rows are generated.
     *
     * @param array<string, mixed> ...$states
     */
    public function sequence(array ...$states): self
    {
        $clone = clone $this;
        $clone->sequenceStates = array_values($states);

        return $clone;
    }

    /**
     * Generate rows in memory without touching any database.
     *
     * @return list<array<string, mixed>>
     */
    public function make(): array
    {
        $rows = [];
        $seqCount = count($this->sequenceStates);

        for ($i = 1; $i <= $this->count; ++$i) {
            $row = ($this->definition)($i);
            if ($seqCount > 0) {
                $row = [...$row, ...$this->sequenceStates[($i - 1) % $seqCount]];
            }
            foreach ($this->states as $state) {
                $patch = $state instanceof Closure ? $state($i, $row) : $state;
                $row = [...$row, ...$patch];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function makeOne(): array
    {
        return $this->count(1)->make()[0];
    }

    /**
     * Generate rows and insert them into `$db` using parameterized SQL, returning the generated rows.
     *
     * @return list<array<string, mixed>>
     */
    public function create(ConnectionInterface $db): array
    {
        $rows = $this->make();
        if (1 === count($rows)) {
            $db->table($this->table)->insert($rows[0]);
        } elseif ([] !== $rows) {
            $db->table($this->table)->insertMany($rows);
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function createOne(ConnectionInterface $db): array
    {
        return $this->count(1)->create($db)[0];
    }
}
