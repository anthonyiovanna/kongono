<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseValue;
use Kongono\Database\DatabaseValueKind;
use Kongono\Database\Exception\CombinedDatabaseFailureException;
use Kongono\Database\Exception\DatabaseBindingException;
use Kongono\Database\Exception\DatabaseCleanupException;
use Kongono\Database\Exception\DatabaseExecutionException;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\ResultExpectation;
use Kongono\Database\RowStream;
use PDO;
use PDOStatement;
use Throwable;

/** Performs native prepare, exact binding, and bounded result-shape admission. */
#[Api(ApiStatus::Internal)]
final class PdoPgsqlExecution
{
    /** @param Closure(PdoPgsqlRowStream): void $release */
    public static function execute(PDO $pdo, DatabaseStatement $statement, Closure $release): RowStream|CommandResult
    {
        try {
            $native = $pdo->prepare($statement->sql());
        } catch (Throwable $exception) {
            throw DatabaseExecutionException::failed($exception);
        }

        try {
            foreach ($statement->parameters() as $parameter) {
                [$value, $type] = self::binding($parameter->value());
                if (!$native->bindValue(':' . $parameter->name(), $value, $type)) {
                    throw DatabaseBindingException::failed();
                }
            }
        } catch (DatabaseBindingException $exception) {
            self::failAfterDiscard($native, $exception);
        } catch (Throwable $exception) {
            self::failAfterDiscard($native, DatabaseBindingException::failed($exception));
        }

        try {
            if (!$native->execute()) {
                throw DatabaseExecutionException::failed();
            }
        } catch (DatabaseExecutionException $exception) {
            self::failAfterDiscard($native, $exception);
        } catch (Throwable $exception) {
            self::failAfterDiscard($native, DatabaseExecutionException::failed($exception));
        }

        $columnCount = $native->columnCount();
        if (ResultExpectation::Rows === $statement->expectation()) {
            if ($columnCount < 1 || $columnCount > 4_096) {
                self::failAfterDiscard($native, DatabaseResultException::failed());
            }

            return new PdoPgsqlRowStream($native, self::columns($native, $columnCount), $release);
        }
        if (0 !== $columnCount) {
            self::failAfterDiscard($native, DatabaseResultException::failed());
        }
        $affectedRows = $native->rowCount();
        self::discard($native);

        return new CommandResult($affectedRows);
    }

    /** @return array{0: null|bool|int|string, 1: int} */
    private static function binding(DatabaseValue $value): array
    {
        return match ($value->kind()) {
            DatabaseValueKind::Null => [null, PDO::PARAM_NULL],
            DatabaseValueKind::Boolean => [self::booleanValue($value), PDO::PARAM_BOOL],
            DatabaseValueKind::Integer => [self::integerValue($value), PDO::PARAM_INT],
            DatabaseValueKind::Float => [self::floatText($value), PDO::PARAM_STR],
            DatabaseValueKind::Text => [self::stringValue($value), PDO::PARAM_STR],
            DatabaseValueKind::Binary => [self::stringValue($value), PDO::PARAM_LOB],
        };
    }

    private static function booleanValue(DatabaseValue $value): bool
    {
        $native = $value->value();
        if (!is_bool($native)) {
            throw DatabaseBindingException::failed();
        }

        return $native;
    }

    private static function integerValue(DatabaseValue $value): int
    {
        $native = $value->value();
        if (!is_int($native)) {
            throw DatabaseBindingException::failed();
        }

        return $native;
    }

    private static function stringValue(DatabaseValue $value): string
    {
        $native = $value->value();
        if (!is_string($native)) {
            throw DatabaseBindingException::failed();
        }

        return $native;
    }

    private static function floatText(DatabaseValue $value): string
    {
        $native = $value->value();
        if (!is_float($native)) {
            throw DatabaseBindingException::failed();
        }

        return sprintf('%.17g', $native);
    }

    /** @return list<DatabaseColumn> */
    private static function columns(PDOStatement $statement, int $count): array
    {
        $columns = [];
        try {
            for ($ordinal = 0; $ordinal < $count; ++$ordinal) {
                $columns[] = new DatabaseColumn($ordinal, self::metadataString($statement->getColumnMeta($ordinal), 'name'));
            }
        } catch (DatabaseResultException $exception) {
            self::failAfterDiscard($statement, $exception);
        } catch (Throwable $exception) {
            self::failAfterDiscard($statement, DatabaseResultException::failed($exception));
        }

        return $columns;
    }

    private static function metadataString(mixed $metadata, string $key): string
    {
        if (!is_array($metadata)) {
            throw DatabaseResultException::failed();
        }
        $value = $metadata[$key] ?? null;
        if (!is_string($value)) {
            throw DatabaseResultException::failed();
        }

        return $value;
    }

    private static function discard(PDOStatement $statement): void
    {
        try {
            if (!$statement->closeCursor()) {
                throw DatabaseCleanupException::failed();
            }
        } catch (DatabaseCleanupException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw DatabaseCleanupException::failed($exception);
        }
    }

    private static function failAfterDiscard(PDOStatement $statement, Throwable $primary): never
    {
        try {
            self::discard($statement);
        } catch (Throwable $cleanup) {
            throw new CombinedDatabaseFailureException($primary, $cleanup);
        }

        throw $primary;
    }
}
