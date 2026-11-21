<?php

declare(strict_types=1);

namespace Kongono\Cli\Mcp;

use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Http\App;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Throwable;

/**
 * Builds a capability-governed Kongono MCP Server exposing live ApplicationGraph,
 * OpenAPI 3.1 spec, route table, secret-scrubbed source browsing, and Oshie lessons.
 *
 * Reusable across both stdio (`php bin/kongo mcp`) and HTTP (`POST /mcp`).
 */
final class KongonoMcpFactory
{
    /** @var list<string> */
    public const array DEFAULT_TOOLS = [
        'kongono_graph',
        'kongono_openapi',
        'kongono_routes',
        'kongono_list_files',
        'kongono_read_file',
        'kongono_oshie_lesson',
    ];

    public static function forApp(string $appRoot, ?App $loadedApp = null): McpServer
    {
        $app = $loadedApp ?? self::tryLoadApp($appRoot);
        $context = null !== $app
            ? new ProjectContext($appRoot, $app->graph())
            : ProjectContext::discover($appRoot);

        $principal = new AgentPrincipal(
            id: 'kongono-mcp-client',
            role: 'mcp-reader',
            allowedTools: self::DEFAULT_TOOLS,
        );

        $server = new McpServer(
            serverName: 'kongono-framework',
            serverVersion: '1.0.0',
            principal: $principal,
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_graph',
                description: 'Return the live ApplicationGraph JSON (routes, controllers, views, services, middleware) for this Kongono app.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($context): string {
                if (null === $context->graph) {
                    return '{"error":"No live ApplicationGraph available."}';
                }

                return $context->graph->toJson(pretty: true);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_openapi',
                description: 'Generate the live OpenAPI 3.1.0 JSON specification directly from registered routes and PHP 8.5 DTO validation attributes.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($app): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }

                return (new OpenApiGenerator())->generateJson($app);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_routes',
                description: 'List all registered HTTP routes (methods, paths, route names, and handlers).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($context): string {
                if (null === $context->graph) {
                    return 'No routes discovered.';
                }
                $lines = [];
                foreach ($context->graph->routes as $route) {
                    $lines[] = sprintf(
                        '%s %s [%s] -> %s',
                        implode('|', $route->methods),
                        $route->path,
                        $route->name ?? '-',
                        $route->handler,
                    );
                }

                return implode("\n", $lines);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_list_files',
                description: 'List relative paths of source, template, and config files in this Kongono project (excluding vendor, var, and .git).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of files to return (default 100, max 250).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($context): string {
                $limit = is_int($args['limit'] ?? null) ? min(250, max(1, $args['limit'])) : 100;

                return implode("\n", $context->listFiles($limit));
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_read_file',
                description: 'Read a project file safely within the app root with automatic secret scrubbing (blocks .env secret files).',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['path'],
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Relative path inside the Kongono app root (e.g. src/Http/NoteController.php).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($context): string {
                $path = is_string($args['path'] ?? null) ? trim($args['path']) : '';
                if ('' === $path) {
                    throw new \InvalidArgumentException('Parameter "path" is required.');
                }
                $base = strtolower(basename(str_replace('\\', '/', $path)));
                if (str_starts_with($base, '.env') && '.env.example' !== $base) {
                    throw new \InvalidArgumentException('Reading environment secret files (.env) via MCP is forbidden.');
                }

                return $context->readFileSafe($path);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_oshie_lesson',
                description: 'Fetch a built-in Kongono v1 Oshie tutorial lesson (e.g. routing, views, validation, database, ai, security) or list all lessons when topic is omitted.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'topic' => [
                            'type' => 'string',
                            'description' => 'Lesson topic ID (routing, views, validation, database, ai, security). Omit to list all topics.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($context): string {
                $oshie = new OshieTutor();
                $topic = is_string($args['topic'] ?? null) ? trim($args['topic']) : '';
                if ('' === $topic) {
                    $lines = [];
                    foreach ($oshie->listLessons() as $id => $meta) {
                        $lines[] = sprintf('%s: %s — %s', $id, $meta['title'], $meta['summary']);
                    }

                    return implode("\n", $lines);
                }

                return $oshie->teachLesson($topic, $context);
            },
        );

        return $server;
    }

    private static function tryLoadApp(string $appRoot): ?App
    {
        $resolved = realpath($appRoot);
        if (false === $resolved) {
            return null;
        }
        $bootstrap = $resolved . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return null;
        }

        try {
            $app = require $bootstrap;

            return $app instanceof App ? $app : null;
        } catch (Throwable) {
            return null;
        }
    }
}
