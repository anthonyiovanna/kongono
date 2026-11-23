<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Context\ProjectContext;

/**
 * Shared read-only, secret-scrubbed inspection toolset for Chie and Hantei:
 * `list_files`, `read_file` (with optional line ranges), `search_code`, `inspect_graph`, and `inspect_schema`.
 */
final readonly class CompanionTools
{
    /** @var list<string> */
    public const array NAMES = [
        'list_files',
        'read_file',
        'search_code',
        'inspect_graph',
        'inspect_schema',
    ];

    /** @return list<ToolDefinition> */
    public static function definitions(): array
    {
        return [
            new ToolDefinition(
                'list_files',
                'List relative file paths in the Kongono project (excluding vendor, var, and .git).',
            ),
            new ToolDefinition(
                'read_file',
                'Read a secret-scrubbed file (or specific line range) from the project.',
                [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'Relative file path from project root'],
                        'start_line' => ['type' => 'integer', 'description' => 'Optional 1-based start line number'],
                        'end_line' => ['type' => 'integer', 'description' => 'Optional 1-based end line number (max 300 lines)'],
                    ],
                    'required' => ['path'],
                ],
            ),
            new ToolDefinition(
                'search_code',
                'Search readable project source/test files for a case-insensitive literal substring (returns file:line matches, secret-scrubbed).',
                [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Literal text to search for'],
                        'directory' => ['type' => 'string', 'description' => 'Optional relative directory prefix (e.g. "src" or "tests")'],
                        'limit' => ['type' => 'integer', 'description' => 'Maximum matches to return (1-100, default 40)'],
                    ],
                    'required' => ['query'],
                ],
            ),
            new ToolDefinition(
                'inspect_graph',
                'Inspect the live ApplicationGraph JSON (routes, services, middleware, views).',
            ),
            new ToolDefinition(
                'inspect_schema',
                'Inspect live database schema metadata (tables, columns, types, nullability, indexes — strictly metadata, zero row data).',
            ),
        ];
    }

    public static function execute(ToolCall $call, ProjectContext $context): string
    {
        return match ($call->name) {
            'list_files' => implode("\n", $context->listFiles()),
            'inspect_graph' => null !== $context->graph ? $context->graph->toJson() : '{"graph":null}',
            'inspect_schema' => json_encode($context->inspectSchema(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'read_file' => self::execReadFile($call, $context),
            'search_code' => self::execSearchCode($call, $context),
            default => 'Error: unknown tool.',
        };
    }

    private static function execReadFile(ToolCall $call, ProjectContext $context): string
    {
        $path = $call->arguments['path'] ?? null;
        if (!is_string($path) || '' === trim($path)) {
            return 'Error: missing string "path" argument.';
        }

        $startLine = $call->arguments['start_line'] ?? null;
        $endLine = $call->arguments['end_line'] ?? null;
        if (is_int($startLine) || is_int($endLine)) {
            $from = is_int($startLine) ? max(1, $startLine) : 1;
            $to = is_int($endLine) ? max($from, $endLine) : ($from + 199);

            return $context->readFileLines($path, $from, $to);
        }

        return $context->readFileSafe($path);
    }

    private static function execSearchCode(ToolCall $call, ProjectContext $context): string
    {
        $query = $call->arguments['query'] ?? null;
        if (!is_string($query) || '' === trim($query)) {
            return 'Error: missing string "query" argument.';
        }

        $dir = is_string($call->arguments['directory'] ?? null) ? $call->arguments['directory'] : null;
        $limit = is_int($call->arguments['limit'] ?? null) ? $call->arguments['limit'] : 40;
        $hits = $context->searchCode($query, $dir, $limit);

        if ([] === $hits) {
            return sprintf('No matches found for "%s".', $query);
        }

        return implode("\n", array_map(
            static fn(array $h): string => sprintf('%s:%d: %s', $h['file'], $h['line'], $h['text']),
            $hits,
        ));
    }
}
