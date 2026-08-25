<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseValue;
use Kongono\Database\Exception\CombinedDatabaseFailureException;
use Kongono\Database\Exception\DatabaseCleanupException;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\Exception\InvalidDatabaseStateException;
use Kongono\Database\RowStream;
use PDO;
use PDOStatement;
use Throwable;

/** Adapts one native forward-only result and releases it exactly once. */
#[Api(ApiStatus::Internal)]
final class PdoPgsqlRowStream implements RowStream
{
    private bool $closed = false;

    /** @param list<DatabaseColumn> $columns @param Closure(self): void $release */
    public function __construct(
        private ?PDOStatement $statement,
        private readonly array $columns,
        private readonly Closure $release,
    ) {}

    public function columns(): array
    {
        return [...$this->columns];
    }

    public function fetch(): ?DatabaseRow
    {
        if ($this->closed || null === $this->statement) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        try {
            $row = $this->statement->fetch(PDO::FETCH_NUM);
            if (false === $row) {
                $this->close();

                return null;
            }
            if (!is_array($row) || !array_is_list($row) || count($row) !== count($this->columns)) {
                throw DatabaseResultException::failed();
            }
            $values = [];
            foreach ($row as $ordinal => $value) {
                $values[] = $this->decode($ordinal, $value);
            }

            return new DatabaseRow($this->columns, $values);
        } catch (InvalidDatabaseStateException|DatabaseCleanupException|CombinedDatabaseFailureException $exception) {
            throw $exception;
        } catch (Throwable $primary) {
            try {
                $this->close();
            } catch (Throwable $cleanup) {
                throw new CombinedDatabaseFailureException($primary, $cleanup);
            }
            if ($primary instanceof DatabaseResultException) {
                throw $primary;
            }

            throw DatabaseResultException::failed($primary);
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        if (null === $this->statement) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        try {
            if (!$this->statement->closeCursor()) {
                throw DatabaseCleanupException::failed();
            }
        } catch (DatabaseCleanupException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw DatabaseCleanupException::failed($exception);
        }
        $this->statement = null;
        $this->closed = true;
        ($this->release)($this);
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    private function decode(int $ordinal, mixed $value): DatabaseValue
    {
        if (null === $this->statement) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        $type = strtolower($this->metadataType($this->statement->getColumnMeta($ordinal)));
        if (null === $value) {
            return DatabaseValue::null();
        }

        return match ($type) {
            'bool', 'boolean' => DatabaseValue::boolean($this->boolean($value)),
            'int2', 'int4', 'int8' => DatabaseValue::integer($this->integer($value)),
            'float4', 'float8' => DatabaseValue::float($this->float($value)),
            'text', 'varchar', 'bpchar', 'name' => DatabaseValue::text($this->string($value)),
            'bytea' => DatabaseValue::binary($this->binary($value)),
            default => throw DatabaseResultException::failed(),
        };
    }

    private function metadataType(mixed $metadata): string
    {
        if (!is_array($metadata)) {
            throw DatabaseResultException::failed();
        }
        $type = $metadata['native_type'] ?? null;
        if (!is_string($type)) {
            throw DatabaseResultException::failed();
        }

        return $type;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ('t' === $value || '1' === $value) {
            return true;
        }
        if ('f' === $value || '0' === $value) {
            return false;
        }

        throw DatabaseResultException::failed();
    }

    private function integer(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (!is_string($value) || 1 !== preg_match('/^-?(?:0|[1-9][0-9]*)$/D', $value)) {
            throw DatabaseResultException::failed();
        }
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if (!is_int($integer)) {
            throw DatabaseResultException::failed();
        }

        return $integer;
    }

    private function float(mixed $value): float
    {
        if (!is_float($value) && !is_int($value) && !is_string($value)) {
            throw DatabaseResultException::failed();
        }
        if (is_string($value) && !is_numeric($value)) {
            throw DatabaseResultException::failed();
        }
        $float = (float) $value;
        if (!is_finite($float)) {
            throw DatabaseResultException::failed();
        }

        return $float;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw DatabaseResultException::failed();
        }

        return $value;
    }

    private function binary(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (!is_resource($value)) {
            throw DatabaseResultException::failed();
        }
        $bytes = stream_get_contents($value, 16_777_217);
        if (false === $bytes || strlen($bytes) > 16_777_216) {
            throw DatabaseResultException::failed();
        }

        return $bytes;
    }
}
