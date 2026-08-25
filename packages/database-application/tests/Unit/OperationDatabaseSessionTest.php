<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Unit;

use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseConnector;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\ResultExpectation;
use Kongono\Database\TransactionOptions;
use Kongono\DatabaseApplication\Exception\DatabaseSessionUnavailableException;
use Kongono\DatabaseApplication\OperationDatabaseSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OperationDatabaseSessionTest extends TestCase
{
    public function testOpensOnceDelegatesExactlyAndClosesOnce(): void
    {
        $capabilities = new DatabaseCapabilities([]);
        $statement = new DatabaseStatement('SELECT 1', [], ResultExpectation::Completion);
        $result = new CommandResult(1);
        $options = new TransactionOptions();
        $transaction = self::createStub(DatabaseTransaction::class);
        $delegate = $this->createMock(DatabaseSession::class);
        $delegate->expects(self::once())->method('capabilities')->willReturn($capabilities);
        $delegate->expects(self::once())->method('execute')->with($statement)->willReturn($result);
        $delegate->expects(self::once())->method('begin')->with($options)->willReturn($transaction);
        $delegate->expects(self::once())->method('close');
        $connector = $this->createMock(DatabaseConnector::class);
        $connector->expects(self::once())->method('openSession')->willReturn($delegate);

        $session = new OperationDatabaseSession($connector);
        self::assertSame($capabilities, $session->capabilities());
        self::assertSame($result, $session->execute($statement));
        self::assertSame($transaction, $session->begin($options));
        $session->close();
        $session->close();
        self::assertTrue($session->isClosed());

        $this->expectException(DatabaseSessionUnavailableException::class);
        $session->execute($statement);
    }

    public function testFailingCleanupRetiresBeforeFailureAndIsNeverRetried(): void
    {
        $cleanup = new RuntimeException('provider_cleanup_secret');
        $delegate = $this->createMock(DatabaseSession::class);
        $delegate->expects(self::once())->method('close')->willThrowException($cleanup);
        $connector = self::createStub(DatabaseConnector::class);
        $connector->method('openSession')->willReturn($delegate);
        $session = new OperationDatabaseSession($connector);

        try {
            $session->close();
            self::fail('The delegate cleanup failure was not transparent.');
        } catch (RuntimeException $failure) {
            self::assertSame($cleanup, $failure);
        }
        self::assertTrue($session->isClosed());
        $session->close();

        try {
            $session->capabilities();
            self::fail('A retired wrapper remained available.');
        } catch (DatabaseSessionUnavailableException $failure) {
            self::assertStringNotContainsString('provider_cleanup_secret', (string) $failure);
        }
    }
}
