<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class KongoApplicationTest extends TestCase
{
    public function testHumanAndMachineHelpUseOneExactDefinition(): void
    {
        [$humanStatus, $humanOut, $humanError] = self::executeApplication([]);
        [$machineStatus, $machineOut, $machineError] = self::executeApplication(['--json', '--help']);

        self::assertSame(0, $humanStatus);
        self::assertStringContainsString('Command: application:build', $humanOut);
        self::assertStringContainsString('Command: application-graph:inspect', $humanOut);
        self::assertStringContainsString('Command: asset:build', $humanOut);
        self::assertStringContainsString('Command: asset:check', $humanOut);
        self::assertStringContainsString('Command: asset:resolve', $humanOut);
        self::assertStringContainsString('Command: cache:build', $humanOut);
        self::assertStringContainsString('Command: cache:check', $humanOut);
        self::assertStringContainsString('Command: cache:plan', $humanOut);
        self::assertStringContainsString('Command: extension:inspect', $humanOut);
        self::assertStringContainsString('Command: observability:check', $humanOut);
        self::assertStringContainsString('Command: observability:inspect', $humanOut);
        self::assertStringContainsString('Command: security:check', $humanOut);
        self::assertStringContainsString('Command: security:inspect', $humanOut);
        self::assertStringContainsString('Command: view:build', $humanOut);
        self::assertStringContainsString('Command: view:check', $humanOut);
        self::assertStringContainsString('Command: view:inspect', $humanOut);
        self::assertStringContainsString('Mutation: mutation_capable', $humanOut);
        self::assertSame('', $humanError);
        self::assertSame(0, $machineStatus);
        self::assertSame('', $machineError);
        $decoded = json_decode($machineOut, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            self::fail('Machine help must decode to an object.');
        }
        $commands = $decoded['commands'] ?? null;
        $command = is_array($commands) ? ($commands[0] ?? null) : null;
        $inputs = is_array($command) ? ($command['inputs'] ?? null) : null;
        if (!is_array($command) || !is_array($inputs)) {
            self::fail('Machine help must contain one command and its inputs.');
        }
        self::assertSame('kongono.command-help', $decoded['schema']);
        self::assertSame('application-graph:inspect', $command['identifier']);
        self::assertSame(['artifact-path'], array_column($inputs, 'name'));
        self::assertSame([
            'application-graph:inspect',
            'application:build',
            'asset:build',
            'asset:check',
            'asset:resolve',
            'cache:build',
            'cache:check',
            'cache:plan',
            'extension:inspect',
            'observability:check',
            'observability:inspect',
            'security:check',
            'security:inspect',
            'view:build',
            'view:check',
            'view:inspect',
        ], array_column($commands, 'identifier'));
        self::assertCount(16, $commands);
    }

    public function testInvalidRequestsHaveStableExitAndNoRawDisclosure(): void
    {
        [$humanStatus, , $humanError] = self::executeApplication(['sentinel-secret-command']);
        [$machineStatus, $machineOut, $machineError] = self::executeApplication(['--json', 'sentinel-secret-command']);

        self::assertSame(2, $humanStatus);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1001', $humanError);
        self::assertStringNotContainsString('sentinel', $humanError);
        self::assertSame(2, $machineStatus);
        self::assertSame('', $machineError);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1001', $machineOut);
        self::assertStringNotContainsString('sentinel', $machineOut);
    }

    public function testMissingAndConflictingApplyAuthorityRejectAndPreconditionsUseSharedExitMap(): void
    {
        [$missingStatus, , $missingError] = self::executeApplication([
            'application:build',
            '--application-root=sentinel-secret-root',
            '--output-directory=sentinel-secret-output',
        ]);
        [$conflictingStatus, $conflictingOut, $conflictingError] = self::executeApplication([
            '--json',
            'application:build',
            '--plan',
            '--apply',
            '--application-root=sentinel-secret-root',
            '--output-directory=sentinel-secret-output',
        ]);
        [$preconditionStatus, $preconditionOut, $preconditionError] = self::executeApplication([
            '--json',
            'application:build',
            '--plan',
            '--application-root=sentinel-secret-root',
            '--output-directory=sentinel-secret-output',
            '--no-interaction',
        ]);

        self::assertSame(2, $missingStatus);
        self::assertStringContainsString('KGN-CONSOLE-', $missingError);
        self::assertStringNotContainsString('sentinel', $missingError);
        self::assertSame(2, $conflictingStatus);
        self::assertSame('', $conflictingError);
        self::assertStringNotContainsString('sentinel', $conflictingOut);
        self::assertSame(3, $preconditionStatus);
        self::assertSame('', $preconditionError);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $preconditionOut);
        self::assertStringNotContainsString('sentinel', $preconditionOut);
    }

    public function testSequentialCallsRetainNoInvocationOutputState(): void
    {
        [$firstStatus, $firstOut] = self::executeApplication([]);
        [$secondStatus, $secondOut] = self::executeApplication(['--json']);
        [$thirdStatus, $thirdOut] = self::executeApplication([]);

        self::assertSame(0, $firstStatus);
        self::assertSame(0, $secondStatus);
        self::assertSame(0, $thirdStatus);
        self::assertSame($firstOut, $thirdOut);
        self::assertNotSame($firstOut, $secondOut);
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string}
     */
    private static function executeApplication(array $arguments): array
    {
        $stdout = new class implements CommandOutputPort {
            public string $bytes = '';

            public function write(string $bytes): void
            {
                if ('' !== $this->bytes) {
                    throw new \RuntimeException('duplicate write');
                }
                $this->bytes = $bytes;
            }
        };
        $stderr = new class implements CommandOutputPort {
            public string $bytes = '';

            public function write(string $bytes): void
            {
                if ('' !== $this->bytes) {
                    throw new \RuntimeException('duplicate write');
                }
                $this->bytes = $bytes;
            }
        };
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $stdout, $stderr);

        return [$status, $stdout->bytes, $stderr->bytes];
    }
}
