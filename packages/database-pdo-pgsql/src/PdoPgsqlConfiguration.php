<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseSession;
use Kongono\Database\Exception\DatabaseConnectionException;
use Kongono\Database\Exception\InvalidDatabaseValueException;
use PDO;
use Throwable;

/** Holds explicit adapter configuration without getters, stringification, or ambient lookup. */
#[Api(ApiStatus::Experimental)]
final readonly class PdoPgsqlConfiguration
{
    public function __construct(
        #[\SensitiveParameter]
        private string $host,
        private int $port,
        #[\SensitiveParameter]
        private string $database,
        #[\SensitiveParameter]
        private string $username,
        #[\SensitiveParameter]
        private string $password,
    ) {
        if (
            1 !== preg_match('/^[A-Za-z0-9_.:-]{1,255}$/D', $host)
            || $port < 1
            || $port > 65_535
            || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,127}$/D', $database)
            || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,127}$/D', $username)
            || strlen($password) > 4_096
            || str_contains($password, "\0")
        ) {
            throw InvalidDatabaseValueException::validation();
        }
    }

    /** @internal Used only by the explicit connector; no native handle crosses this method. */
    public function createSession(): DatabaseSession
    {
        try {
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $this->host, $this->port, $this->database),
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            );
            if ('pgsql' !== $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                throw DatabaseConnectionException::failed();
            }

            return new PdoPgsqlSession($pdo);
        } catch (DatabaseConnectionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw DatabaseConnectionException::failed($exception);
        }
    }
}
