<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandOutcome;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultData;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandFailureResultTest extends TestCase
{
    public function testFailureGrammarAndEveryResultFactoryShapeAreExact(): void
    {
        $data = new class implements CommandResultData {};
        $success = CommandResult::succeeded($data);
        self::assertSame(CommandOutcome::Succeeded, $success->outcome());
        self::assertNull($success->failure());
        self::assertSame($data, $success->data());

        foreach ([CommandFailureCategory::InvalidInput, CommandFailureCategory::Precondition, CommandFailureCategory::Denied] as $category) {
            $failure = new CommandFailure($category, 'KGN-TEST-1001');
            $result = CommandResult::rejected($failure);
            self::assertSame(CommandOutcome::Rejected, $result->outcome());
            self::assertSame($failure, $result->failure());
            self::assertNull($result->data());
        }

        foreach ([CommandFailureCategory::Capability, CommandFailureCategory::Infrastructure] as $category) {
            $failure = new CommandFailure($category, 'KGN-TEST-CAPABILITY-1002');
            self::assertSame(CommandOutcome::Failed, CommandResult::failed($failure)->outcome());
        }

        $cancelled = new CommandFailure(CommandFailureCategory::Cancelled, 'KGN-TEST-1003');
        self::assertSame(CommandOutcome::Cancelled, CommandResult::cancelled($cancelled)->outcome());
        self::assertSame(['succeeded', 'rejected', 'failed', 'cancelled'], array_column(CommandOutcome::cases(), 'value'));
        self::assertSame(['invalid_input', 'precondition', 'denied', 'capability', 'infrastructure', 'cancelled'], array_column(CommandFailureCategory::cases(), 'value'));
    }

    public function testInvalidFailureIdentifiersAndFactoryCategoriesAreRejected(): void
    {
        foreach (['', 'KGN-console-1001', 'KGN-CONSOLE', 'KGN-CONSOLE-001', str_repeat('A', 97)] as $identifier) {
            try {
                new CommandFailure(CommandFailureCategory::InvalidInput, $identifier);
                self::fail('Expected an invalid diagnostic identifier to be rejected.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1004', $exception->diagnosticId());
                if ('' !== $identifier) {
                    self::assertStringNotContainsString($identifier, $exception->getMessage());
                }
            }
        }

        $invalidFactories = [
            static fn(): CommandResult => CommandResult::rejected(new CommandFailure(CommandFailureCategory::Capability, 'KGN-TEST-1001')),
            static fn(): CommandResult => CommandResult::failed(new CommandFailure(CommandFailureCategory::Denied, 'KGN-TEST-1001')),
            static fn(): CommandResult => CommandResult::cancelled(new CommandFailure(CommandFailureCategory::Infrastructure, 'KGN-TEST-1001')),
        ];
        foreach ($invalidFactories as $operation) {
            $this->expectConsoleFailure($operation);
        }
    }

    /** @param callable(): CommandResult $operation */
    private function expectConsoleFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected an invalid result shape to be rejected.');
        } catch (InvalidConsoleValueException $exception) {
            self::assertSame('KGN-CONSOLE-1004', $exception->diagnosticId());
        }
    }
}
