<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit\Exception;

use Kongono\ConsoleRuntime\Exception\CommandExecutionException;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommandExecutionExceptionTest extends TestCase
{
    public function testPrimaryCleanupAndCombinedShapesPreserveExactIdentities(): void
    {
        $primary = new RuntimeException('primary-hostile-secret');
        $cleanup = [new RuntimeException('cleanup-one-secret'), new RuntimeException('cleanup-two-secret')];
        foreach ([
            [$primary, [], 'KGN-CONSOLE-RUNTIME-1004'],
            [null, $cleanup, 'KGN-CONSOLE-RUNTIME-1005'],
            [$primary, $cleanup, 'KGN-CONSOLE-RUNTIME-1006'],
        ] as [$primaryFailure, $cleanupFailures, $diagnostic]) {
            $exception = CommandExecutionException::forFailures($primaryFailure, $cleanupFailures);
            self::assertSame($primaryFailure, $exception->primaryFailure());
            self::assertSame($cleanupFailures, $exception->cleanupFailures());
            self::assertSame($diagnostic, $exception->diagnosticId());
            if (null !== $primaryFailure) {
                self::assertSame($primaryFailure, $exception->getPrevious());
            } else {
                self::assertNotSame([], $cleanupFailures);
                foreach ($cleanupFailures as $firstCleanupFailure) {
                    self::assertSame($firstCleanupFailure, $exception->getPrevious());
                    break;
                }
            }
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertStringNotContainsString('secret', (string) $exception);
        }
    }

    public function testCleanupInputIsDetachedAndInvalidShapesAreRejected(): void
    {
        $failure = new RuntimeException('failure');
        $input = [$failure];
        $exception = CommandExecutionException::forFailures(null, $input);
        $input[0] = new RuntimeException('changed');
        self::assertSame([$failure], $exception->cleanupFailures());

        foreach ([[null, []], [null, [new \stdClass()]], [null, array_fill(0, 65, $failure)]] as [$primary, $cleanups]) {
            try {
                CommandExecutionException::forFailures($primary, $cleanups);
                self::fail('Expected invalid failure evidence.');
            } catch (InvalidCommandRuntimeValueException $invalid) {
                self::assertSame('KGN-CONSOLE-RUNTIME-1001', $invalid->diagnosticId());
            }
        }
    }
}
