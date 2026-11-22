<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

/**
 * Deterministically converts a captured DevInspector request trace into a ready-to-run
 * PHPUnit 13 regression test using Kongono's fluent TestClient.
 */
final readonly class TestExporter
{
    /**
     * @param array{
     *     id: int,
     *     method: string,
     *     path: string,
     *     query?: string,
     *     headers?: array<string, string>,
     *     body?: string,
     *     status: int
     * } $trace
     */
    public static function exportPhpUnitTest(array $trace, ?string $testMethodName = null): string
    {
        $method = strtoupper($trace['method']);
        $path = $trace['path'];
        $query = $trace['query'] ?? '';
        $fullUri = '' !== $query ? $path . '?' . $query : $path;
        $status = $trace['status'];
        $headers = $trace['headers'] ?? [];
        $body = $trace['body'] ?? '';

        $fnName = $testMethodName ?? self::deriveMethodName($method, $path, $trace['id']);
        if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $fnName)) {
            throw new \InvalidArgumentException(sprintf('Invalid PHPUnit test method name "%s".', $fnName));
        }

        // Neutralize PHP open/close tags and newlines so single-line comments cannot break out into executable PHP
        $safeCommentMethod = self::sanitizeComment($method);
        $safeCommentUri = self::sanitizeComment($fullUri);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'use Kongono\\Http\\Testing\\TestClient;',
            '',
            sprintf('// Auto-exported from Kongono Dev Studio trace #%d (%s %s -> HTTP %d)', $trace['id'], $safeCommentMethod, $safeCommentUri, $status),
            sprintf('public function %s(): void', $fnName),
            '{',
            '    $client = new TestClient($this->app);',
        ];

        $chain = '    $client';
        foreach ($headers as $name => $value) {
            if ('[REDACTED]' === $value || in_array(strtolower($name), ['host', 'content-length', 'content-type'], true)) {
                continue;
            }
            $chain .= sprintf("\n        ->withHeader(%s, %s)", var_export($name, true), var_export($value, true));
        }

        $contentType = '';
        foreach ($headers as $name => $value) {
            if ('content-type' === strtolower($name)) {
                $contentType = strtolower($value);
                break;
            }
        }

        $decodedJson = '' !== $body && str_contains($contentType, 'application/json')
            ? json_decode($body, true)
            : null;

        if ('GET' === $method) {
            $call = sprintf('->get(%s)', var_export($fullUri, true));
        } elseif ('DELETE' === $method) {
            $call = sprintf('->delete(%s)', var_export($fullUri, true));
        } elseif ('POST' === $method && is_array($decodedJson)) {
            $call = sprintf('->postJson(%s, %s)', var_export($fullUri, true), self::inlineExport($decodedJson));
        } elseif ('PATCH' === $method && is_array($decodedJson)) {
            $call = sprintf('->patchJson(%s, %s)', var_export($fullUri, true), self::inlineExport($decodedJson));
        } elseif ('PUT' === $method && is_array($decodedJson)) {
            $call = sprintf('->putJson(%s, %s)', var_export($fullUri, true), self::inlineExport($decodedJson));
        } else {
            $call = sprintf(
                '->request(%s, %s, body: %s)',
                var_export($method, true),
                var_export($fullUri, true),
                var_export($body, true),
            );
        }

        $assertion = match ($status) {
            200 => '->assertOk()',
            201 => '->assertCreated()',
            204 => '->assertNoContent()',
            default => sprintf('->assertStatus(%d)', $status),
        };

        $lines[] = $chain . "\n        " . $call . "\n        " . $assertion . ';';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    private static function deriveMethodName(string $method, string $path, int $id): string
    {
        $cleanMethod = (string) preg_replace('/[^a-zA-Z0-9]+/', '', $method);
        if ('' === $cleanMethod) {
            $cleanMethod = 'Request';
        }
        $slug = (string) preg_replace('/[^a-zA-Z0-9]+/', ' ', trim($path, '/'));
        $parts = array_values(array_filter(explode(' ', $slug), static fn(string $p): bool => '' !== $p));
        $camel = implode('', array_map(static fn(string $p): string => ucfirst(strtolower($p)), $parts));

        return 'testReplay' . ucfirst(strtolower($cleanMethod)) . ('' !== $camel ? $camel : 'Root') . 'Trace' . abs($id);
    }

    private static function sanitizeComment(string $value): string
    {
        $noControls = (string) preg_replace('/[\x00-\x1f\x7f]/', ' ', $value);

        return str_replace(['?>', '<?'], ['?_>', '<_?'], $noControls);
    }

    /** @param array<array-key, mixed> $value */
    private static function inlineExport(array $value): string
    {
        return (string) preg_replace('/\s+/', ' ', var_export($value, true));
    }
}
