<?php

declare(strict_types=1);

namespace Kongono\Cli\Mcp;

use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Eval\EvalRunner;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\Diagnostics\IncidentReplayer;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Impact\AppSnapshot;
use Kongono\Cli\Impact\ImpactAnalyzer;
use Kongono\Cli\Knowledge\KongonoKnowledgeIndex;
use Kongono\Cli\Perf\PerfProver;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Proof\MasterProver;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Cli\Security\TenantIsolationProver;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Cli\Upgrade\UpgradeEngine;
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
        'kongono_inspect_schema',
        'kongono_audit',
        'kongono_diagnose',
        'kongono_prove_all',
        'kongono_policy_check',
        'kongono_incident_replay',
        'kongono_perf_probe',
        'kongono_prove_isolation',
        'kongono_impact',
        'kongono_shadow_diff',
        'kongono_upgrade_plan',
        'kongono_eval',
        'kongono_migrate_lint',
        'kongono_test_route',
        'kongono_deploy_preview',
        'kongono_list_files',
        'kongono_read_file',
        'kongono_oshie_lesson',
        'kongono_hantei_diagnose',
        'kongono_chie_preview',
        'kongono_search_docs',
        'kongono_zokeibi_registry',
    ];

    public static function forApp(
        string $appRoot,
        ?App $loadedApp = null,
        ?AiClient $aiOverride = null,
        bool $allowMutatingReplay = false,
        ?bool $allowBootstrap = null,
    ): McpServer {
        $bootstrapAllowed = $allowBootstrap ?? !ProjectContext::isSafeModeEnabled();
        $app = $loadedApp ?? ($bootstrapAllowed ? self::tryLoadApp($appRoot) : null);
        $context = null !== $app
            ? new ProjectContext($appRoot, $app->graph(), $app)
            : ProjectContext::discover($appRoot, allowBootstrap: $bootstrapAllowed);

        $tryAi = static function (string $companion) use ($aiOverride): ?AiClient {
            if (null !== $aiOverride) {
                return $aiOverride;
            }

            try {
                return AiClient::fromEnvForCompanion($companion);
            } catch (AiException) {
                return null;
            }
        };

        $principal = new AgentPrincipal(
            id: 'kongono-mcp-client',
            role: 'mcp-reader',
            allowedTools: self::DEFAULT_TOOLS,
            maxToolCalls: 64,
        );

        $server = new McpServer(
            serverName: 'kongono-framework',
            serverVersion: '1.20.1',
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
                name: 'kongono_inspect_schema',
                description: 'Inspect live database schema metadata (tables, columns, types, nullability, indexes) strictly without reading row data.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static fn(): string => json_encode($context->inspectSchema(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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
                name: 'kongono_diagnose',
                description: 'Run Kongono\'s 17-rule framework-native self-inspector & full-stack contract prover (middleware order, live DI container resolution, compiled .kgn.html syntax, Link/Form <-> Router & Form <-> Input DTO proof, Controller <-> View var typos, Live 2.5 AST & k-show scope, CSRF, missing views/components, route params, duplicate routes, pending migrations, and persistent-worker static leaks) returning 5W1H JSON.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot, $app): string {
                $report = (new KongonoAppInspector())->inspect($appRoot, $app);

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_prove_all',
                description: 'Run ALL 5 deterministic Kongono proof engines in one shot (17-rule Full-Stack Contracts, Architecture Policy, Multi-Tenant Isolation, Persistent-Worker Leak & N+1, and PHP 8.5 Upgrade Hygiene) returning a unified Readiness Certificate JSON + Route Capability Matrix.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot, $app): string {
                return json_encode((new MasterProver())->proveAll($appRoot, $app), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_policy_check',
                description: 'Verify Kongono\'s built-in security invariants and app/invariants.php architectural boundary rules against the live ApplicationGraph and src/ namespaces.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot, $app): string {
                $report = (new PolicyVerifier())->verify($appRoot, $app, requireApp: true);

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_incident_replay',
                description: 'List recent recorded KGN-... production incidents or replay a specific sanitized incident reference in-process against the local app to confirm a fix.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'reference' => [
                            'type' => 'string',
                            'description' => 'Optional incident Reference ID (e.g. KGN-20261123-7A3F9C12). Omit to list recent incidents.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot, $app, $allowMutatingReplay): string {
                $replayer = new IncidentReplayer();
                $store = $replayer->resolveStore($appRoot, $app);
                $ref = is_string($args['reference'] ?? null) ? trim($args['reference']) : '';
                if ('' === $ref) {
                    $list = array_map(static fn($r): array => $r->toArray(), $store->recent(20));

                    return json_encode(['incidents' => $list], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                $record = $store->find($ref);
                if (null === $record) {
                    return json_encode(['error' => sprintf('Incident "%s" not found.', $ref)], JSON_THROW_ON_ERROR);
                }
                if (!$allowMutatingReplay && !in_array(strtoupper($record->method), ['GET', 'HEAD', 'OPTIONS'], true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Replaying mutating HTTP %s incident "%s" is forbidden under mcp-reader role (read-only GET/HEAD/OPTIONS only).',
                        strtoupper($record->method),
                        $ref,
                    ));
                }

                return json_encode($replayer->replay($app, $record), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_perf_probe',
                description: 'Run Kongono\'s persistent-worker leak & SQL N+1 prover across safe GET routes in-process (proves zero leaked ops, zero global mutation, bounded memory, zero N+1 SQL loops, and latency budgets).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'iterations' => [
                            'type' => 'integer',
                            'description' => 'Measured requests per route after warmup (default: 3).',
                        ],
                        'max_ms' => [
                            'type' => 'number',
                            'description' => 'P95 latency budget in milliseconds (default: 250).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($app): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                $iters = is_int($args['iterations'] ?? null) ? $args['iterations'] : 3;
                $maxMs = is_numeric($args['max_ms'] ?? null) ? (float) $args['max_ms'] : 250.0;
                $report = (new PerfProver())->prove($app, iterations: $iters, maxMs: $maxMs);

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_prove_isolation',
                description: 'Run Kongono\'s automated Multi-Tenant Isolation & BOLA/IDOR Prover against protected routes and app/isolation.php cross-tenant probes.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot, $app, $allowMutatingReplay): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }

                return json_encode(
                    (new TenantIsolationProver())->prove($appRoot, $app, readOnly: !$allowMutatingReplay),
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                );
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_impact',
                description: 'Compute deterministic Semantic Blast-Radius diff against a baseline snapshot (detects security regressions, removed routes, and breaking DTO changes).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'against' => [
                            'type' => 'string',
                            'description' => 'Relative path to baseline snapshot JSON (default: storage/kongono-snapshot.json).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot, $app): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                $rel = is_string($args['against'] ?? null) && '' !== trim($args['against'])
                    ? trim($args['against'])
                    : 'storage/kongono-snapshot.json';
                $basePath = self::resolveSafeJsonPath($appRoot, $rel);
                if (null === $basePath) {
                    return '{"error":"Path traversal, symlink, or non-JSON snapshot path not allowed."}';
                }
                if (!is_file($basePath)) {
                    return json_encode(['error' => sprintf('Snapshot "%s" not found. Run `kongo snapshot` first.', $rel)], JSON_THROW_ON_ERROR);
                }
                $decoded = json_decode((string) file_get_contents($basePath), true);
                if (!is_array($decoded)) {
                    return '{"error":"Invalid baseline snapshot JSON."}';
                }
                /** @var array<string, mixed> $decoded */
                $diff = (new ImpactAnalyzer())->diff(AppSnapshot::fromArray($decoded), AppSnapshot::capture($appRoot, $app));

                return json_encode($diff, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_shadow_diff',
                description: 'Prove deterministic behavioral equivalence against a recorded golden shadow baseline (status, JSON shape, HTML outline, SQL query count).',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'against' => [
                            'type' => 'string',
                            'description' => 'Relative path to shadow baseline JSON (default: storage/kongono-shadow.json).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot, $app): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                $rel = is_string($args['against'] ?? null) && '' !== trim($args['against'])
                    ? trim($args['against'])
                    : 'storage/kongono-shadow.json';
                $basePath = self::resolveSafeJsonPath($appRoot, $rel);
                if (null === $basePath) {
                    return '{"error":"Path traversal, symlink, or non-JSON baseline path not allowed."}';
                }
                if (!is_file($basePath)) {
                    return json_encode(['error' => sprintf('Shadow baseline "%s" not found. Run `kongo shadow:record` first.', $rel)], JSON_THROW_ON_ERROR);
                }
                $decoded = json_decode((string) file_get_contents($basePath), true);
                if (!is_array($decoded)) {
                    return '{"error":"Invalid shadow baseline JSON."}';
                }
                /** @var array<string, mixed> $decoded */
                $report = (new ShadowDiffProver())->diff($decoded, $app);

                return json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_upgrade_plan',
                description: 'Preview syntax-verified Kongono PHP 8.5 & template upgrade codemods without modifying files.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ),
            static function () use ($appRoot): string {
                return json_encode((new UpgradeEngine())->run($appRoot, apply: false), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

        $testRouteGuard = new class {
            public bool $inFlight = false;
        };

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_test_route',
                description: 'Execute a read-only in-process HTTP request (GET, HEAD, or OPTIONS) against this Kongono App without starting a server, returning status, headers, secret-scrubbed body, and active ops count.',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['path'],
                    'properties' => [
                        'method' => [
                            'type' => 'string',
                            'enum' => ['GET', 'HEAD', 'OPTIONS'],
                            'description' => 'Read-only HTTP method (GET, HEAD, or OPTIONS). Defaults to GET.',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Request path (e.g. /health or /api/notes).',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($app, $testRouteGuard): string {
                if (null === $app) {
                    return '{"error":"No runnable Kongono App instance loaded."}';
                }
                if ($testRouteGuard->inFlight) {
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
                if (
                    '/mcp' === $lowerCanonical || str_starts_with($lowerCanonical, '/mcp/')
                    || '/_kongono' === $lowerCanonical || str_starts_with($lowerCanonical, '/_kongono/')
                    || '/_dev' === $lowerCanonical || str_starts_with($lowerCanonical, '/_dev/')
                    || '/_ops' === $lowerCanonical || str_starts_with($lowerCanonical, '/_ops/')
                ) {
                    throw new \InvalidArgumentException('Recursive or internal Dev/Ops Studio calls via kongono_test_route are forbidden.');
                }

                $testRouteGuard->inFlight = true;
                try {
                    $req = Request::create($method, $path);
                    $res = $app->handle($req);
                } finally {
                    $testRouteGuard->inFlight = false;
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

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_hantei_diagnose',
                description: 'Run Hantei (判定) against an error log, stack trace, or Kongono diagnostic code using secret-scrubbed project/schema inspection.',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['diagnostic'],
                    'properties' => [
                        'diagnostic' => [
                            'type' => 'string',
                            'description' => 'Error message, stack trace, or KGN-* diagnostic text to analyze.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($context, $tryAi): string {
                $diagnostic = is_string($args['diagnostic'] ?? null) ? trim($args['diagnostic']) : '';
                if ('' === $diagnostic) {
                    throw new \InvalidArgumentException('Parameter "diagnostic" is required.');
                }
                $hantei = new HanteiDebugger($tryAi('hantei'));
                $res = $hantei->diagnoseWithTelemetry($diagnostic, $context);

                return json_encode([
                    'diagnosis' => $res['diagnosis'],
                    'tools_used' => $res['tools_used'],
                    'telemetry' => $res['telemetry']?->toArray(),
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_chie_preview',
                description: 'Run Chie (知恵) in strictly read-only diff-preview mode (never modifies disk) to propose PHP 8.5 Kongono changes with unified diffs.',
                parametersSchema: [
                    'type' => 'object',
                    'required' => ['instruction'],
                    'properties' => [
                        'instruction' => [
                            'type' => 'string',
                            'description' => 'Feature or code change to plan against this Kongono project.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($context, $tryAi): string {
                $instruction = is_string($args['instruction'] ?? null) ? trim($args['instruction']) : '';
                if ('' === $instruction) {
                    throw new \InvalidArgumentException('Parameter "instruction" is required.');
                }
                $ai = $tryAi('chie');
                if (null === $ai) {
                    return json_encode([
                        'summary' => 'Chie preview requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).',
                        'diffs' => [],
                    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }

                $plan = (new ChieBuilder($ai))->build($instruction, $context, apply: false);
                $diffs = [];
                foreach ($plan['changes'] as $change) {
                    $before = null;
                    try {
                        $before = $context->readFileSafe($change['path']);
                    } catch (Throwable) {
                        $before = null;
                    }
                    $diffs[] = [
                        'path' => $change['path'],
                        'action' => $change['action'],
                        'explanation' => $change['explanation'],
                        'diff' => DiffRenderer::render($change['path'], $before, SecretScrubber::scrub($change['content'])),
                    ];
                }

                return json_encode([
                    'summary' => $plan['summary'],
                    'diffs' => $diffs,
                    'next_steps' => $plan['next_steps'],
                    'telemetry' => $plan['telemetry']->toArray(),
                    'apply_command' => sprintf('php vendor/bin/kongo chie %s --apply --verify', escapeshellarg($instruction)),
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_search_docs',
                description: 'Search Kongono v1.20 verified framework reference documentation and bundled workflow skills for exact namespaces, attributes, and code patterns.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Framework topic, attribute, class, or workflow query'],
                        'limit' => ['type' => 'integer', 'description' => 'Maximum matching sections to return (1-5, default 3)'],
                    ],
                    'required' => ['query'],
                ],
            ),
            static function (array $args): string {
                $query = is_string($args['query'] ?? null) ? trim($args['query']) : '';
                if ('' === $query) {
                    return json_encode(['error' => 'Missing required string parameter "query".'], JSON_THROW_ON_ERROR);
                }
                $limit = is_int($args['limit'] ?? null) ? max(1, min(5, $args['limit'])) : 3;
                $hits = (new KongonoKnowledgeIndex())->search($query, $limit);

                return json_encode([
                    'query' => $query,
                    'count' => count($hits),
                    'results' => $hits,
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        $server->registerTool(
            new ToolDefinition(
                name: 'kongono_zokeibi_registry',
                description: 'Inspect the app\'s Zokeibi UI (kongono/zokeibi) design tokens (zokeibi.json), installed <x-ui.*> components, full component prop schemas, and turnkey SaaS blocks so AI agents never hallucinate UI tags or prop enums.',
                parametersSchema: [
                    'type' => 'object',
                    'properties' => [
                        'component' => [
                            'type' => 'string',
                            'description' => 'Optional Zokeibi component ID (e.g. button, command-palette, combobox) to return its full prop schema and example.',
                        ],
                    ],
                ],
            ),
            static function (array $args) use ($appRoot): string {
                $root = rtrim($appRoot, '\\/');
                $zokeibiJsonPath = $root . DIRECTORY_SEPARATOR . 'zokeibi.json';
                $zokeibiConfig = null;
                if (is_file($zokeibiJsonPath)) {
                    $decodedCfg = json_decode((string) file_get_contents($zokeibiJsonPath), true);
                    if (is_array($decodedCfg)) {
                        $zokeibiConfig = $decodedCfg;
                    }
                }

                $installed = [];
                $uiDir = $root . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'ui';
                if (is_dir($uiDir)) {
                    $entries = scandir($uiDir);
                    if (is_array($entries)) {
                        foreach ($entries as $entry) {
                            if (str_ends_with($entry, '.kgn.html')) {
                                $installed[] = substr($entry, 0, -strlen('.kgn.html'));
                            }
                        }
                    }
                    sort($installed);
                }

                $registryCandidates = [
                    $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'kongono' . DIRECTORY_SEPARATOR . 'zokeibi' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'registry.json',
                    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'registry.json',
                    dirname($root) . DIRECTORY_SEPARATOR . 'zokeibi' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'registry.json',
                    dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'zokeibi' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'registry.json',
                ];
                $registryData = null;
                foreach ($registryCandidates as $candidate) {
                    if (is_file($candidate)) {
                        $decoded = json_decode((string) file_get_contents($candidate), true);
                        if (is_array($decoded) && 'zokeibi' === ($decoded['name'] ?? null)) {
                            $registryData = $decoded;
                            break;
                        }
                    }
                }

                $filterComp = is_string($args['component'] ?? null) ? strtolower(trim($args['component'])) : '';
                if (null === $registryData) {
                    return json_encode([
                        'installed_package' => false,
                        'hint' => 'Zokeibi UI is optional and not installed in this app yet. Install via `composer require kongono/zokeibi && php vendor/bin/zokeibi init`.',
                        'zokeibi_json' => $zokeibiConfig,
                        'installed_components' => $installed,
                    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }

                $rawComponents = is_array($registryData['components'] ?? null) ? $registryData['components'] : [];
                if ('' !== $filterComp) {
                    if (!isset($rawComponents[$filterComp]) || !is_array($rawComponents[$filterComp])) {
                        return json_encode([
                            'error' => sprintf('Unknown Zokeibi component "%s".', $filterComp),
                            'available' => array_keys($rawComponents),
                        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    }

                    return json_encode([
                        'version' => $registryData['version'] ?? '1.0.0',
                        'installed_in_app' => in_array($filterComp, $installed, true),
                        'component' => $rawComponents[$filterComp],
                    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }

                $summary = [];
                foreach ($rawComponents as $id => $spec) {
                    if (!is_string($id) || !is_array($spec)) {
                        continue;
                    }
                    $props = is_array($spec['props'] ?? null) ? $spec['props'] : [];
                    $enumMap = [];
                    foreach ($props as $propName => $propDef) {
                        if (is_string($propName) && is_array($propDef) && isset($propDef['enum']) && is_array($propDef['enum'])) {
                            $enumMap[$propName] = $propDef['enum'];
                        }
                    }
                    $summary[$id] = [
                        'tag' => $spec['tag'] ?? ('x-ui.' . $id),
                        'category' => $spec['category'] ?? 'primitives',
                        'js_mode' => $spec['js_mode'] ?? '0 B JS',
                        'props' => array_keys($props),
                        'enums' => $enumMap,
                        'example' => $spec['example'] ?? '',
                    ];
                }

                return json_encode([
                    'installed_package' => true,
                    'version' => $registryData['version'] ?? '1.0.0',
                    'themes' => $registryData['themes'] ?? [],
                    'radius_presets' => $registryData['radius_presets'] ?? [],
                    'zokeibi_json' => $zokeibiConfig,
                    'installed_components' => $installed,
                    'component_count' => count($summary),
                    'blocks' => ['saas-shell', 'auth-login', 'settings-billing', 'pricing-grid', 'empty-onboarding', 'error-page', 'analytics-dashboard', 'api-keys-audit'],
                    'components' => $summary,
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        );

        return $server;
    }

    private static function resolveSafeJsonPath(string $appRoot, string $relativePath): ?string
    {
        $trimmed = trim($relativePath);
        if (
            '' === $trimmed
            || str_contains($trimmed, '..')
            || str_contains($trimmed, "\0")
            || 1 === preg_match('/[\x00-\x1f\x7f]/', $trimmed)
            || str_starts_with($trimmed, '/')
            || str_starts_with($trimmed, '\\')
            || 1 === preg_match('/\A[a-zA-Z]:/', $trimmed)
        ) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $trimmed), '/');
        if (!str_ends_with(strtolower($normalized), '.json') || self::isDeniedJsonRelativePath($normalized)) {
            return null;
        }

        $realRoot = realpath($appRoot);
        if (false === $realRoot) {
            return null;
        }
        $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Walk every path segment rejecting empty/dot segments, Windows device/trailing-dot tricks, and intermediate/leaf symlinks
        $walk = $realRoot;
        foreach (explode('/', $normalized) as $segment) {
            if (
                '' === $segment
                || '.' === $segment
                || str_ends_with($segment, '.')
                || str_ends_with($segment, ' ')
                || 1 === preg_match('/\A(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|\z)/i', $segment)
            ) {
                return null;
            }
            $walk .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($walk)) {
                return null;
            }
        }

        $candidate = $walk;
        if (!file_exists($candidate)) {
            // Verify nearest existing ancestor is strictly contained inside appRoot
            $probeDir = dirname($candidate);
            while (!file_exists($probeDir) && $probeDir !== dirname($probeDir)) {
                $probeDir = dirname($probeDir);
            }
            $realDir = realpath($probeDir);
            if (false === $realDir || ($realDir !== $realRoot && !str_starts_with($realDir . DIRECTORY_SEPARATOR, $prefix))) {
                return null;
            }

            return $candidate;
        }

        $real = realpath($candidate);
        if (false === $real || !is_file($real) || !str_starts_with($real, $prefix)) {
            return null;
        }

        $canonicalRel = str_replace('\\', '/', substr($real, strlen($prefix)));
        if (!str_ends_with(strtolower($canonicalRel), '.json') || self::isDeniedJsonRelativePath($canonicalRel)) {
            return null;
        }

        return $real;
    }

    private static function isDeniedJsonRelativePath(string $relativePath): bool
    {
        $lower = strtolower($relativePath);
        $base = strtolower(basename($relativePath));

        return in_array($base, ['auth.json', 'credentials.json', 'service-account.json', 'secrets.json', 'config.json'], true)
            || ((str_starts_with($base, '.env') || str_ends_with($base, '.env')) && '.env.example' !== $base)
            || '.git' === $lower
            || str_starts_with($lower, '.git/')
            || str_contains($lower, '/.git/')
            || str_starts_with($lower, '.ssh/')
            || str_contains($lower, '/.ssh/')
            || str_starts_with($lower, '.aws/')
            || str_contains($lower, '/.aws/')
            || str_starts_with($lower, '.docker/')
            || str_contains($lower, '/.docker/')
            || str_starts_with($lower, 'var/cache/')
            || str_starts_with($lower, 'var/storage/')
            || str_starts_with($lower, 'storage/dev/')
            || str_starts_with($lower, 'storage/incidents/')
            || 1 === preg_match('#/(?:var/(?:cache|storage)|storage/(?:dev|incidents))/#', '/' . $lower);
    }

    private static function tryLoadApp(string $appRoot): ?App
    {
        $resolved = realpath($appRoot);
        if (false === $resolved || !is_dir($resolved)) {
            return null;
        }
        $appDir = $resolved . DIRECTORY_SEPARATOR . 'app';
        $bootstrap = $appDir . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_link($appDir) || is_link($bootstrap) || !is_file($bootstrap)) {
            return null;
        }
        $realBootstrap = realpath($bootstrap);
        $prefix = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $realBootstrap || !str_starts_with($realBootstrap, $prefix)) {
            return null;
        }

        try {
            $app = require $realBootstrap;

            return $app instanceof App ? $app : null;
        } catch (Throwable) {
            return null;
        }
    }
}
