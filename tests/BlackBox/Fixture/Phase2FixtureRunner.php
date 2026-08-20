<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use RuntimeException;
use Throwable;

/** Owns the closed CLI grammar and newline-delimited JSON fixture protocol. */
final class Phase2FixtureRunner
{
    private const int INVALID_INVOCATION = 64;
    private const int EXECUTION_FAILURE = 70;

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        $configuration = $this->configuration($arguments);
        if (null === $configuration || 'cli' !== PHP_SAPI) {
            $this->write($stderr, "Invalid Phase 2 fixture invocation\n");

            return self::INVALID_INVOCATION;
        }

        try {
            $application = Phase2FixtureApplication::boot($configuration['bootstrap']);
            foreach ($configuration['scenarios'] as $scenario) {
                $request = self::scenario($scenario);
                $delivery = $application->deliver($request['method'], $request['uri'], $configuration['delivery']);
                $result = $delivery->result();
                $activeOperations = $application->activeOperationCount();
                if (0 !== $activeOperations) {
                    throw new RuntimeException('Fixture Kernel operation cleanup is incomplete.');
                }

                $line = json_encode([
                    'scenario' => $scenario,
                    'status' => $result->statusCode(),
                    'headers' => $result->headers(),
                    'body' => $result->body(),
                    'permission_evaluations' => $delivery->permissionEvaluationCount(),
                    'request_calls' => $delivery->requestCount(),
                    'send_calls' => $delivery->sendCount(),
                    'close_calls' => $delivery->closeCount(),
                    'active_operations' => $activeOperations,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $this->write($stdout, $line . "\n");
            }
            $application->shutdown();

            return 0;
        } catch (Throwable) {
            try {
                $this->write($stderr, "Phase 2 fixture execution failed\n");
            } catch (Throwable) {
            }

            return self::EXECUTION_FAILURE;
        }
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @return null|array{bootstrap: TrustedDeveloperBootstrapPermission, delivery: AdapterControlledDeliveryPermission, scenarios: list<string>}
     */
    private function configuration(array $arguments): ?array
    {
        if (!array_is_list($arguments) || count($arguments) < 2 || count($arguments) > 17) {
            return null;
        }
        /** @var list<string> $validatedArguments */
        $validatedArguments = [];
        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                return null;
            }
            $validatedArguments[] = $argument;
        }

        $permissions = match ($validatedArguments[0]) {
            'production' => [TrustedDeveloperBootstrapPermission::Denied, AdapterControlledDeliveryPermission::Denied],
            'developer-denied' => [TrustedDeveloperBootstrapPermission::Allowed, AdapterControlledDeliveryPermission::Denied],
            'developer-trusted' => [TrustedDeveloperBootstrapPermission::Allowed, AdapterControlledDeliveryPermission::Allowed],
            default => null,
        };
        if (null === $permissions) {
            return null;
        }

        $scenarios = array_slice($validatedArguments, 1);
        foreach ($scenarios as $scenario) {
            if (!in_array($scenario, ['matched-a', 'matched-b', 'unmatched', 'generic-failure', 'diagnostic-failure'], true)) {
                return null;
            }
        }

        return ['bootstrap' => $permissions[0], 'delivery' => $permissions[1], 'scenarios' => $scenarios];
    }

    /** @return array{method: 'GET', uri: string} */
    private static function scenario(string $scenario): array
    {
        return match ($scenario) {
            'matched-a' => ['method' => 'GET', 'uri' => 'https://fixture.kongono.test/hello/alpha'],
            'matched-b' => ['method' => 'GET', 'uri' => 'https://fixture.kongono.test/hello/beta'],
            'unmatched' => ['method' => 'GET', 'uri' => 'https://fixture.kongono.test/missing'],
            'generic-failure' => ['method' => 'GET', 'uri' => 'https://fixture.kongono.test/fail/generic'],
            'diagnostic-failure' => ['method' => 'GET', 'uri' => 'https://fixture.kongono.test/fail/diagnostic'],
            default => throw new RuntimeException('Fixture scenario is invalid.'),
        };
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $contents): void
    {
        $offset = 0;
        while ($offset < strlen($contents)) {
            $written = fwrite($stream, substr($contents, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('Fixture output write failed.');
            }
            $offset += $written;
        }
    }
}
