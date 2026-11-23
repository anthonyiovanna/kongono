<?php

declare(strict_types=1);

namespace Kongono\Database;

use Closure;

/**
 * Multi-connection registry supporting named connections, lazy factory closures,
 * ReadWriteConnection replica pools, and deterministic persistent-worker sticky resets.
 */
final class ConnectionManager
{
    /** @var array<string, ConnectionInterface> */
    private array $resolved = [];

    /** @var array<string, Closure(): ConnectionInterface> */
    private array $factories = [];

    public function __construct(
        private string $defaultName = 'default',
    ) {}

    /** @param ConnectionInterface|(Closure(): ConnectionInterface) $connection */
    public function register(string $name, ConnectionInterface|Closure $connection): self
    {
        $clean = trim($name);
        if ('' === $clean) {
            throw new DatabaseException('Database connection name cannot be empty.');
        }

        if ($connection instanceof ConnectionInterface) {
            $this->resolved[$clean] = $connection;
            unset($this->factories[$clean]);
        } else {
            $this->factories[$clean] = $connection;
            unset($this->resolved[$clean]);
        }

        return $this;
    }

    public function setDefault(string $name): self
    {
        $clean = trim($name);
        if (!$this->has($clean)) {
            throw new DatabaseException(sprintf('Cannot set unknown database connection "%s" as default.', $name));
        }
        $this->defaultName = $clean;

        return $this;
    }

    public function defaultName(): string
    {
        return $this->defaultName;
    }

    public function has(string $name): bool
    {
        return isset($this->resolved[$name]) || isset($this->factories[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_values(array_unique([...array_keys($this->resolved), ...array_keys($this->factories)]));
        sort($names);

        return $names;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $target = $name ?? $this->defaultName;

        if (isset($this->resolved[$target])) {
            return $this->resolved[$target];
        }

        if (isset($this->factories[$target])) {
            $conn = ($this->factories[$target])();
            $this->resolved[$target] = $conn;

            return $conn;
        }

        throw new DatabaseException(sprintf('Database connection "%s" is not registered.', $target));
    }

    public function table(string $table, ?string $connection = null, ?string $alias = null): QueryBuilder
    {
        return $this->connection($connection)->table($table, $alias);
    }

    /** Reset sticky-after-write state across all resolved ReadWriteConnection instances. */
    public function resetSticky(): void
    {
        foreach ($this->resolved as $conn) {
            if ($conn instanceof ReadWriteConnection) {
                $conn->resetSticky();
            }
        }
    }
}
