<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql\Tests;

use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseConnector;
use Kongono\Database\Exception\CombinedDatabaseFailureException;
use Kongono\Database\Exception\DatabaseCleanupException;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\Exception\ExceptionInterface;
use Kongono\DatabasePdoPgsql\PdoPgsqlConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConnector;
use Kongono\DatabasePdoPgsql\PdoPgsqlRowStream;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SensitiveParameter;

final class PdoPgsqlBoundaryTest extends TestCase
{
    public function testConnectorUsesExplicitConfigurationAndNeutralContract(): void
    {
        $configuration = new PdoPgsqlConfiguration('127.0.0.1', 5432, 'postgres', 'postgres', '');
        $connector = new PdoPgsqlConnector($configuration);

        self::assertInstanceOf(DatabaseConnector::class, $connector);
        self::assertSame([], (new ReflectionClass($configuration))->getMethods(\ReflectionMethod::IS_PUBLIC)[0]->getAttributes(SensitiveParameter::class));
    }

    public function testEveryCredentialBearingConstructorParameterIsSensitive(): void
    {
        $constructor = (new ReflectionClass(PdoPgsqlConfiguration::class))->getConstructor();
        self::assertNotNull($constructor);
        $sensitive = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(SensitiveParameter::class)) {
                $sensitive[] = $parameter->getName();
            }
        }
        self::assertSame(['host', 'database', 'username', 'password'], $sensitive);
    }

    public function testConfigurationValidationDoesNotDiscloseRejectedValues(): void
    {
        $sentinel = 'KONGONO_CONFIG_SENTINEL';
        try {
            new PdoPgsqlConfiguration($sentinel . ';', 5432, 'postgres', 'postgres', $sentinel);
            self::fail('Invalid configuration was accepted.');
        } catch (ExceptionInterface $exception) {
            self::assertStringNotContainsString($sentinel, $exception->getMessage() . (string) $exception);
        }
    }

    public function testPublicAdapterSignaturesExposeNoNativePdoType(): void
    {
        foreach ([PdoPgsqlConfiguration::class, PdoPgsqlConnector::class] as $type) {
            $reflection = new ReflectionClass($type);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $signature = (string) $method->getReturnType();
                foreach ($method->getParameters() as $parameter) {
                    $signature .= ' ' . (string) $parameter->getType();
                }
                self::assertStringNotContainsString('PDO', $signature);
                self::assertStringNotContainsString('PDOStatement', $signature);
            }
        }
    }

    public function testResultFailurePreservesLaterNativeCleanupFailure(): void
    {
        $statement = self::createStub(PDOStatement::class);
        $statement->method('fetch')->willReturn(['unsupported']);
        $statement->method('getColumnMeta')->willReturn(['native_type' => 'json']);
        $statement->method('closeCursor')->willReturn(false);
        $stream = new PdoPgsqlRowStream($statement, [new DatabaseColumn(0, 'value')], static function (): void {});

        try {
            $stream->fetch();
            self::fail('Unsupported result and cleanup failures were not reported.');
        } catch (CombinedDatabaseFailureException $exception) {
            self::assertInstanceOf(DatabaseResultException::class, $exception->primaryFailure());
            self::assertInstanceOf(DatabaseCleanupException::class, $exception->cleanupFailure());
            self::assertSame($exception->primaryFailure(), $exception->getPrevious());
        }
    }

    public function testExhaustionCleanupFailureIsNotRetried(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->expects(self::once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn(false);
        $statement->expects(self::once())->method('closeCursor')->willReturn(false);
        $stream = new PdoPgsqlRowStream(
            $statement,
            [new DatabaseColumn(0, 'value')],
            static function (): void {
                self::fail('Release callback was called after failed cleanup.');
            },
        );

        try {
            $stream->fetch();
            self::fail('Exhaustion cleanup failure was not reported.');
        } catch (DatabaseCleanupException) {
            self::assertFalse($stream->isClosed());
        }
    }
}
