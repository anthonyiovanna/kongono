<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** External acceptance for the fixture-runnable Phase 2 framework boundary. */
final class Phase2RunnableWebFrameworkTest extends TestCase
{
    private const array RESULT_KEYS = [
        'scenario',
        'status',
        'headers',
        'body',
        'permission_evaluations',
        'request_calls',
        'send_calls',
        'close_calls',
        'active_operations',
    ];

    public function testInvalidInvocationFailsWithOnlyTheFixedError(): void
    {
        $result = $this->execute([]);

        self::assertSame(64, $result['exit']);
        self::assertSame('', $result['stdout']);
        self::assertSame("Invalid Phase 2 fixture invocation\n", $result['stderr']);
    }

    public function testMatchedSuccessUsesTheExactMiddlewareOrder(): void
    {
        $result = $this->successful('production', 'matched-a');

        self::assertSame(200, $result['status']);
        self::assertSame("Kongono Phase 2 fixture\n", $result['body']);
        self::assertSame([
            'Cache-Control' => ['no-store'],
            'Content-Type' => ['text/plain; charset=utf-8'],
            'X-Content-Type-Options' => ['nosniff'],
            'X-Kongono-Fixture-Trace' => [
                'outer-before',
                'inner-before',
                'handler',
                'inner-after',
                'outer-after',
            ],
        ], $result['headers']);
        $this->assertDeliveryCounts($result, 0);
    }

    public function testUnmatchedResponseIsDeterministicAndMiddlewareWrapped(): void
    {
        $result = $this->successful('production', 'unmatched');

        self::assertSame(404, $result['status']);
        self::assertSame("Not Found\n", $result['body']);
        self::assertSame([
            'Cache-Control' => ['no-store'],
            'Content-Type' => ['text/plain; charset=utf-8'],
            'X-Content-Type-Options' => ['nosniff'],
            'X-Kongono-Fixture-Trace' => ['inner-after', 'outer-after'],
        ], $result['headers']);
        $this->assertDeliveryCounts($result, 0);
    }

    public function testNativeFailureRemainsGenericWithBothPermissions(): void
    {
        $result = $this->successful('developer-trusted', 'generic-failure');

        $this->assertGenericFailure($result, 1);
        self::assertStringNotContainsString('fixture-generic-failure-sentinel', $this->serialized($result));
        self::assertStringNotContainsString(RuntimeException::class, $this->serialized($result));
    }

    public function testSupportedDiagnosticStaysGenericAndSkipsEvidenceUnderBootstrapDenial(): void
    {
        $result = $this->successful('production', 'diagnostic-failure');

        $this->assertGenericFailure($result, 0);
        self::assertStringNotContainsString('KGN-KERNEL-9001', $this->serialized($result));
    }

    public function testSupportedDiagnosticStaysGenericUnderDeliveryDenial(): void
    {
        $result = $this->successful('developer-denied', 'diagnostic-failure');

        $this->assertGenericFailure($result, 1);
        self::assertStringNotContainsString('KGN-KERNEL-9001', $this->serialized($result));
    }

    public function testSupportedDiagnosticRendersOnlyWithBothPermissions(): void
    {
        $result = $this->successful('developer-trusted', 'diagnostic-failure');

        self::assertSame(500, $result['status']);
        self::assertSame([
            'Cache-Control' => ['no-store'],
            'Content-Security-Policy' => ["default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'"],
            'Content-Type' => ['text/html; charset=utf-8'],
            'X-Content-Type-Options' => ['nosniff'],
        ], $result['headers']);
        self::assertIsString($result['body']);
        foreach ([
            'KGN-KERNEL-9001',
            'Phase 2 fixture diagnostic failure',
            'Inspect the deterministic Phase 2 fixture',
            'Run Phase 2 fixture diagnostic route',
            'Trusted fixture output requires both typed permissions',
        ] as $expected) {
            self::assertStringContainsString($expected, $result['body']);
        }
        $this->assertDeliveryCounts($result, 1);
    }

    public function testRequestAndInvocationValuesNeverEnterTrustedOutput(): void
    {
        $result = $this->successful('developer-trusted', 'diagnostic-failure');
        $serialized = $this->serialized([
            'headers' => $result['headers'],
            'body' => $result['body'],
        ]);

        foreach ([
            'https://fixture.kongono.test',
            '/fail/diagnostic',
            'developer-trusted',
            'diagnostic-failure',
            'fixture-kernel-diagnostic-failure-sentinel',
            'fixture-generic-failure-sentinel',
            'alpha',
            'beta',
            'C:\\Users\\',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $serialized);
        }
    }

    public function testExactFiveOperationSequenceHasNoStateLeakage(): void
    {
        $process = $this->execute(['production', 'matched-a', 'unmatched', 'matched-b', 'generic-failure', 'matched-a']);

        self::assertSame(0, $process['exit']);
        self::assertSame('', $process['stderr']);
        $results = $this->decodeLines($process['stdout']);
        self::assertSame(['matched-a', 'unmatched', 'matched-b', 'generic-failure', 'matched-a'], array_column($results, 'scenario'));
        self::assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertDeliveryCounts($result, 0);
        }
        self::assertSame($results[0], $results[4]);
        self::assertSame($results[0]['body'], $results[2]['body']);
        self::assertSame($results[0]['headers'], $results[2]['headers']);
        self::assertSame("Internal Server Error\n", $results[3]['body']);
        self::assertStringNotContainsString('fixture-generic-failure-sentinel', $process['stdout']);
    }

    /** @return iterable<string, array{string, string, int}> */
    public static function deliveryCases(): iterable
    {
        yield 'success' => ['production', 'matched-a', 0];
        yield 'unmatched' => ['production', 'unmatched', 0];
        yield 'generic failure' => ['developer-trusted', 'generic-failure', 1];
        yield 'bootstrap denial' => ['production', 'diagnostic-failure', 0];
        yield 'delivery denial' => ['developer-denied', 'diagnostic-failure', 1];
        yield 'trusted diagnostic' => ['developer-trusted', 'diagnostic-failure', 1];
    }

    #[DataProvider('deliveryCases')]
    public function testEverySuccessAndFailureDeliveryClosesOnceWithNoActiveOperation(
        string $mode,
        string $scenario,
        int $permissionEvaluations,
    ): void {
        $this->assertDeliveryCounts($this->successful($mode, $scenario), $permissionEvaluations);
    }

    public function testIndependentProcessesAreByteDeterministic(): void
    {
        $arguments = ['developer-trusted', 'matched-b', 'diagnostic-failure'];
        $first = $this->execute($arguments);
        $second = $this->execute($arguments);

        self::assertSame(0, $first['exit']);
        self::assertSame($first, $second);
    }

    /** @return array<string, mixed> */
    private function successful(string $mode, string $scenario): array
    {
        $process = $this->execute([$mode, $scenario]);
        self::assertSame(0, $process['exit']);
        self::assertSame('', $process['stderr']);
        $results = $this->decodeLines($process['stdout']);
        self::assertCount(1, $results);

        return $results[0];
    }

    /** @param array<string, mixed> $result */
    private function assertGenericFailure(array $result, int $permissionEvaluations): void
    {
        self::assertSame(500, $result['status']);
        self::assertSame([
            'Cache-Control' => ['no-store'],
            'Content-Type' => ['text/plain; charset=utf-8'],
            'X-Content-Type-Options' => ['nosniff'],
        ], $result['headers']);
        self::assertSame("Internal Server Error\n", $result['body']);
        $this->assertDeliveryCounts($result, $permissionEvaluations);
    }

    /** @param array<string, mixed> $result */
    private function assertDeliveryCounts(array $result, int $permissionEvaluations): void
    {
        self::assertSame($permissionEvaluations, $result['permission_evaluations']);
        self::assertSame(1, $result['request_calls']);
        self::assertSame(1, $result['send_calls']);
        self::assertSame(1, $result['close_calls']);
        self::assertSame(0, $result['active_operations']);
    }

    /**
     * @param list<string> $arguments
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private function execute(array $arguments): array
    {
        $command = [PHP_BINARY, __DIR__ . '/Fixture/run.php', ...$arguments];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            dirname(__DIR__, 2),
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            throw new RuntimeException('Unable to start the Phase 2 fixture process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if (false === $stdout || false === $stderr) {
            throw new RuntimeException('Unable to read the Phase 2 fixture process output.');
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /** @return list<array<string, mixed>> */
    private function decodeLines(string $stdout): array
    {
        self::assertNotSame('', $stdout);
        self::assertStringEndsWith("\n", $stdout);
        $decoded = [];
        foreach (explode("\n", substr($stdout, 0, -1)) as $line) {
            try {
                $value = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $failure) {
                self::fail('Fixture output must be valid JSON: ' . $failure->getMessage());
            }
            self::assertIsArray($value);
            $record = $this->stringKeyedRecord($value);
            self::assertSame(self::RESULT_KEYS, array_keys($record));
            $decoded[] = $record;
        }

        return $decoded;
    }

    /**
     * @param array<array-key, mixed> $value
     * @return array<string, mixed>
     */
    private function stringKeyedRecord(array $value): array
    {
        $record = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                self::fail('Fixture output record keys must be strings.');
            }
            $record[$key] = $item;
        }

        return $record;
    }

    /** @param array<string, mixed> $result */
    private function serialized(array $result): string
    {
        return json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
