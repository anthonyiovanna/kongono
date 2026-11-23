<?php

declare(strict_types=1);

namespace Kongono\Cli\Mcp;

use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Eval\EvalRunner;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\App;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Throwable;

/**
 * Builds a capability-governed Kongono MCP Server exposing live ApplicationGraph,
 * OpenAPI 3.1 spec, route table, automated security audit, in-process route testing,
 * FrankenPHP deploy previews, secret-scrubbed source browsing, and Oshie lessons.
 *
 * Reusable across both stdio (`php bin/kongo mcp`) and HTTP (`POST /mcp`).
 */
final class KongonoMcpFactory
{
    /** @var list<string> */
    public const array DEFAULT_TOOLS = [
        'kongono_graph',
        'kongono_openapi',
        'kongono_sdk_generate',
        'kongono_routes',
        'kongono_audit',
        'kongono_eval',
        'kongono_migrate_lint',
        'kongono_test_route',
        'kongono_deploy_preview',
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
            maxToolCalls: 64,
        );

        $server = new McpServer(
            serverName: 'kongono-framework',
            serverVersion: '1.17.0',
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
                name: 'kongono_sdk_generate',
                description: 'Generate a typed TypeScript (ts), Swift (swift), Kotlin (kotlin), or Dart (dart) client SDK directly from this Kongono app routes and DTOs.',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['language'],
                    'properties' => [
                        'language' => [
                            'type' => 'string',
                            'description' => 'Target language: ts, swift, kotlin, or dart.',
                        ],
                        'client_name' => [
                            'type' => 'string',
                            'description' => 'Optional generated API client class name (default KongonoClient).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($app): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                $lang = is_string($args['language'] ?? null) ? $args['language'] : 'ts';
                $clientName = is_string($args['client_name'] ?? null) && '' !== trim($args['client_name'])
                    ? trim($args['client_name'])
                    : 'KongonoClient';

                return (new SdkGenerator())->generate($app, $lang, $clientName);
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
                name: 'kongono_audit',
                description: 'Run the graph-driven Kongono security & architecture audit (0-Symfony supply chain, auth rate limits, payment idempotency, raw HTML sinks, .env hygiene, plus optional in-process red-team --attack probes).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'attack' => [
                            'type' => 'boolean',
                            'description' => 'Run in-process red-team attack simulations (SSRF, PromptGuard canary, path traversal, headers).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot, $app): string {
                $report = (new SecurityAuditor())->audit($appRoot, $app, attack: (bool) ($args['attack'] ?? false));

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_eval',
                description: 'Run the built-in 12-vector adversarial prompt-injection & PromptGuard canary red-team evaluation battery.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function (): string {
                $report = (new EvalRunner())->runRedTeamBattery();

                return json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_migrate_lint',
                description: 'Lint database migrations in migrations/*.php for zero-downtime Postgres/SQL deployment hazards (locking indexes, ADD COLUMN NOT NULL without DEFAULT, DROP COLUMN/TABLE).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot): string {
                $report = (new MigrationLinter())->lintDirectory($appRoot . DIRECTORY_SEPARATOR . 'migrations');

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_test_route',
                description: 'Execute a real in-process HTTP request against this Kongono App without starting a server, returning status, headers, secret-scrubbed body, and active ops count.',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['path'],
                    'properties' => [
                        'method' => [
                            'type' => 'string',
                            'description' => 'HTTP method (GET, HEAD, OPTIONS, POST, PUT, PATCH, DELETE). Defaults to GET.',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Request path (e.g. /health or /api/notes).',
                        ],
                        'json_body' => [
                            'type' => 'object',
                            'description' => 'Optional JSON payload for mutating requests.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($app): string {
                static $inFlight = false;
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                if ($inFlight) {
                    throw new \InvalidArgumentException('Recursive kongono_test_route invocation is forbidden.');
                }
                $method = strtoupper(is_string($args['method'] ?? null) ? trim($args['method']) : 'GET');
                if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                    throw new \InvalidArgumentException(sprintf('Mutating HTTP method "%s" is forbidden under mcp-reader role (read-only GET/HEAD/OPTIONS only).', $method));
                }
                $path = is_string($args['path'] ?? null) ? trim($args['path']) : '/';
                if ('' === $path || !str_starts_with($path, '/')) {
                    throw new \InvalidArgumentException('Parameter "path" must start with "/".');
                }
                $pathOnly = explode('#', explode('?', $path, 2)[0], 2)[0];
                $decoded = str_replace(["\0", '\\'], ['', '/'], rawurldecode(rawurldecode($pathOnly)));
                $collapsed = (string) preg_replace('#/+#', '/', '/' . trim($decoded, '/'));
                $lowerCanonical = strtolower($collapsed);
                if ('/mcp' === $lowerCanonical || str_starts_with($lowerCanonical, '/mcp/') || '/_kongono' === $lowerCanonical || str_starts_with($lowerCanonical, '/_kongono/')) {
                    throw new \InvalidArgumentException('Recursive or internal Dev Studio calls via kongono_test_route are forbidden.');
                }

                $inFlight = true;
                try {
                    $req = Request::create($method, $path);
                    $res = $app->handle($req);
                } finally {
                    $inFlight = false;
                }
                $headers = [];
                foreach ($res->getHeaders() as $name => $values) {
                    $headers[$name] = SecretScrubber::scrub(implode(', ', $values));
                }
                $rawBody = $res instanceof Response ? $res->bodyString() : (string) $res->getBody();

                return json_encode([
                    'method' => $method,
                    'path' => $path,
                    'status' => $res->getStatusCode(),
                    'headers' => $headers,
                    'body' => substr(SecretScrubber::scrub($rawBody), 0, 4000),
                    'active_ops_after' => $app->kernel()->activeOperationCount(),
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_deploy_preview',
                description: 'Preview zero-downtime FrankenPHP production deployment files (Dockerfile, Caddyfile, docker-compose.prod.yml, provision.sh, deploy.sh).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'domain' => ['type' => 'string', 'description' => 'Production domain (e.g. app.example.com).'],
                        'postgres' => ['type' => 'boolean', 'description' => 'Include PostgreSQL 17 service.'],
                        'redis' => ['type' => 'boolean', 'description' => 'Include Redis 7 service.'],
                        'worker' => ['type' => 'boolean', 'description' => 'Include async queue worker container.'],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot): string {
                $domain = is_string($args['domain'] ?? null) && '' !== trim($args['domain']) ? trim($args['domain']) : 'app.example.com';
                $files = (new DeployBlueprintGenerator())->generate(
                    slug: basename($appRoot),
                    domain: $domain,
                    postgres: (bool) ($args['postgres'] ?? true),
                    redis: (bool) ($args['redis'] ?? true),
                    worker: (bool) ($args['worker'] ?? false),
                );

                return json_encode([
                    'domain' => $domain,
                    'files' => array_keys($files),
                    'dockerfile' => $files['Dockerfile'],
                    'caddyfile' => $files['Caddyfile'],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
