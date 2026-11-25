<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use FilesystemIterator;
use Kongono\Core\Container;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Http\App;
use Kongono\Http\Live\LiveMiddleware;
use Kongono\Http\Request;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Security\Middleware\CsrfMiddleware;
use Kongono\Security\Principal;
use Kongono\View\TemplateCompiler;
use ParseError;
use PhpToken;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;
use Throwable;

/**
 * Kongono Self-Inspector (`kongo diagnose`, `kongo lsp`, `kongo check`, & MCP `kongono_diagnose`).
 *
 * Knows Kongono deeper than any external IDE — inspects live ApplicationGraph, middleware order,
 * live DI container bindings, route parameter bindings, persistent-runtime static property leaks,
 * `.kgn.html` TemplateCompiler compile+parse syntax & gotchas, Kongono Live 2.5 AST rules,
 * CSRF form wiring, missing views/components, duplicate routes, and pending migrations.
 */
final readonly class KongonoAppInspector
{
    /** @var list<string> */
    private const array IGNORED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'storage', '.cache'];

    /**
     * Run all 14 Kongono-native checks against an application root and optional live booted App.
     *
     * @return array{
     *     score: int,
     *     passed: bool,
     *     app_root: string,
     *     routes_checked: int,
     *     views_checked: int,
     *     php_files_checked: int,
     *     fixable_count: int,
     *     counts: array{error: int, warning: int},
     *     findings: list<array{
     *         code: string,
     *         severity: 'error'|'warning',
     *         who: string,
     *         what: string,
     *         why: string,
     *         where: string,
     *         file: ?string,
     *         line: ?int,
     *         fixable: bool,
     *         how_to_fix: list<string>,
     *         code_fix: ?string,
     *         command: ?string,
     *         did_you_mean: list<string>
     *     }>
     * }
     */
    public function inspect(string $appRoot, ?App $app = null): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');
        /** @var list<AppFinding> $findings */
        $findings = [];

        $viewsDir = $root . DIRECTORY_SEPARATOR . 'views';
        $availableViews = $this->collectLogicalViews($viewsDir);
        $prover = new FullStackContractProver();

        /** @var array{uses_live: bool, post_forms_missing_csrf: list<array{file: string, line: int, action: string}>} $viewFlags */
        $viewFlags = ['uses_live' => false, 'post_forms_missing_csrf' => []];
        /** @var array<string, array{rel: string, raw: string}> $viewSources */
        $viewSources = [];
        $viewsChecked = $this->inspectViews($viewsDir, $root, $availableViews, $findings, $viewFlags, $viewSources);

        /** @var array<string, list<array{keys: list<string>, file: string, line: int}>> $passedViewVars */
        $passedViewVars = [];
        $phpFilesChecked = $this->inspectPhpSources($root, $availableViews, $findings, $prover, $passedViewVars);

        // Rule 17: Controller <-> View variable typo proof
        $prover->proveViewVariables($passedViewVars, $viewSources, $findings);

        $routesChecked = 0;
        if (null !== $app) {
            $routesChecked = $this->inspectLiveApp($app, $root, $viewFlags, $findings);
            // Rules 15 & 16: Full-stack Link/Form <-> Router & Form <-> Input DTO contract proof
            foreach ($viewSources as $vs) {
                $prover->proveTemplateAgainstApp($vs['rel'], $vs['raw'], $root, $app, $findings);
            }
        }

        return $this->finalizeReport($root, $routesChecked, $viewsChecked, $phpFilesChecked, $findings);
    }

    /**
     * Fast single-buffer inspection used by `kongo lsp` for live unsaved editor buffers.
     *
     * @return list<AppFinding>
     */
    public function inspectBuffer(string $appRoot, string $relativeFile, string $content, ?App $app = null): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');
        $rel = ltrim(str_replace('\\', '/', $relativeFile), '/');
        $availableViews = $this->collectLogicalViews($root . DIRECTORY_SEPARATOR . 'views');
        $prover = new FullStackContractProver();

        /** @var list<AppFinding> $findings */
        $findings = [];
        /** @var array{uses_live: bool, post_forms_missing_csrf: list<array{file: string, line: int, action: string}>} $viewFlags */
        $viewFlags = ['uses_live' => false, 'post_forms_missing_csrf' => []];

        if (str_ends_with($rel, '.kgn.html')) {
            $this->inspectSingleView($rel, $content, $availableViews, $findings, $viewFlags);
            if (null !== $app) {
                if ($this->hasCsrfMiddleware($app)) {
                    $this->emitMissingFormCsrfFindings($viewFlags['post_forms_missing_csrf'], $findings);
                }
                $prover->proveTemplateAgainstApp($rel, $content, $root, $app, $findings);
            }
        } elseif (str_ends_with($rel, '.php')) {
            $this->inspectSinglePhpFile($rel, $content, $availableViews, $findings);
        }

        if (null !== $app) {
            /** @var list<AppFinding> $liveFindings */
            $liveFindings = [];
            $this->inspectLiveApp($app, $root, ['uses_live' => $viewFlags['uses_live'], 'post_forms_missing_csrf' => []], $liveFindings);
            foreach ($liveFindings as $lf) {
                if ($lf->file === $rel) {
                    $findings[] = $lf;
                }
            }
        }

        return $findings;
    }

    /**
     * @param list<AppFinding> $findings
     * @return array{
     *     score: int,
     *     passed: bool,
     *     app_root: string,
     *     routes_checked: int,
     *     views_checked: int,
     *     php_files_checked: int,
     *     fixable_count: int,
     *     counts: array{error: int, warning: int},
     *     findings: list<array{
     *         code: string,
     *         severity: 'error'|'warning',
     *         who: string,
     *         what: string,
     *         why: string,
     *         where: string,
     *         file: ?string,
     *         line: ?int,
     *         fixable: bool,
     *         how_to_fix: list<string>,
     *         code_fix: ?string,
     *         command: ?string,
     *         did_you_mean: list<string>
     *     }>
     * }
     */
    private function finalizeReport(
        string $root,
        int $routesChecked,
        int $viewsChecked,
        int $phpFilesChecked,
        array $findings,
    ): array {
        $errors = 0;
        $warnings = 0;
        $fixableCount = 0;
        $penalty = 0;

        foreach ($findings as $finding) {
            if ('error' === $finding->severity) {
                ++$errors;
                $penalty += 15;
            } else {
                ++$warnings;
                $penalty += 5;
            }
            if ($finding->fixable) {
                ++$fixableCount;
            }
        }

        return [
            'score' => max(0, 100 - $penalty),
            'passed' => 0 === $errors,
            'app_root' => str_replace('\\', '/', $root),
            'routes_checked' => $routesChecked,
            'views_checked' => $viewsChecked,
            'php_files_checked' => $phpFilesChecked,
            'fixable_count' => $fixableCount,
            'counts' => [
                'error' => $errors,
                'warning' => $warnings,
            ],
            'findings' => array_map(static fn(AppFinding $f): array => $f->toArray(), $findings),
        ];
    }

    private function hasCsrfMiddleware(App $app): bool
    {
        foreach ($app->graph()->middleware as $mwClass) {
            if (CsrfMiddleware::class === $mwClass || str_ends_with($mwClass, '\\CsrfMiddleware')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{uses_live: bool, post_forms_missing_csrf: list<array{file: string, line: int, action: string}>} $viewFlags
     * @param list<AppFinding> $findings
     */
    private function inspectLiveApp(App $app, string $root, array $viewFlags, array &$findings): int
    {
        $graph = $app->graph();
        $middleware = $graph->middleware;

        // Rule 1: Middleware Ordering Invariants (CsrfMiddleware MUST come AFTER SessionMiddleware; LiveMiddleware required when views use Live 2.5)
        $sessionIdx = null;
        $csrfIdx = null;
        $hasLive = false;
        foreach ($middleware as $idx => $mwClass) {
            if (SessionMiddleware::class === $mwClass || str_ends_with($mwClass, '\\SessionMiddleware')) {
                $sessionIdx = $idx;
            }
            if (CsrfMiddleware::class === $mwClass || str_ends_with($mwClass, '\\CsrfMiddleware')) {
                $csrfIdx = $idx;
            }
            if (LiveMiddleware::class === $mwClass || str_ends_with($mwClass, '\\LiveMiddleware')) {
                $hasLive = true;
            }
        }

        if (null !== $csrfIdx && null === $sessionIdx) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-MW-CSRF-NO-SESSION',
                severity: 'error',
                who: 'Kongono Middleware Pipeline (`app/bootstrap.php`)',
                what: '`CsrfMiddleware` is mounted without `SessionMiddleware`.',
                why: 'Signed CSRF tokens bind to the active session ID. Without `SessionMiddleware`, browser POST forms fail CSRF verification.',
                where: 'app/bootstrap.php (middleware registration)',
                howToFix: [
                    'Mount `new SessionMiddleware(...)` BEFORE `new CsrfMiddleware(...)` in `$app->middleware(...)`.',
                ],
                codeFix: "\$app->middleware(\n    new SessionMiddleware(new CacheSessionStore(\$cache)),\n    new CsrfMiddleware(signer: \$signer),\n);",
                file: 'app/bootstrap.php',
                line: 1,
            );
        } elseif (null !== $csrfIdx && $csrfIdx < $sessionIdx) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-MW-ORDER',
                severity: 'error',
                who: 'Kongono Middleware Pipeline (`app/bootstrap.php`)',
                what: '`CsrfMiddleware` is registered BEFORE `SessionMiddleware` (wrong order).',
                why: 'Kongono runs middleware in registration order. `SessionMiddleware` must run first so the session cookie and ID exist before `CsrfMiddleware` validates `_csrf`.',
                where: sprintf('app/bootstrap.php (middleware #%d vs #%d)', $csrfIdx, $sessionIdx),
                howToFix: [
                    'Swap the order in `$app->middleware(...)` so `SessionMiddleware` comes immediately before `CsrfMiddleware`.',
                ],
                codeFix: "\$app->middleware(\n    new SessionMiddleware(...),\n    new CsrfMiddleware(...),\n);",
                file: 'app/bootstrap.php',
                line: 1,
            );
        }

        if ($viewFlags['uses_live'] && !$hasLive) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-LIVE-MW-MISSING',
                severity: 'error',
                who: 'Kongono Live 2.5 Runtime',
                what: 'Your `.kgn.html` templates use Live 2.5 (`@liveScript` / `@state` / `k-on:`), but `LiveMiddleware` is not mounted.',
                why: 'Without `LiveMiddleware`, `/_kongono/live.js` returns 404 and interactive signals (`@state`, `k-on:click`, `k-show`, `k-model`) will do nothing in the browser.',
                where: 'app/bootstrap.php (middleware registration)',
                howToFix: [
                    'Add `new LiveMiddleware()` inside `$app->middleware(...)` in `app/bootstrap.php`.',
                ],
                codeFix: "use Kongono\\Http\\Live\\LiveMiddleware;\n\n\$app->middleware(\n    new LiveMiddleware(),\n);",
                file: 'app/bootstrap.php',
                line: 1,
            );
        }

        // Rule 13: Browser POST <form> missing `_csrf` when CsrfMiddleware is mounted
        if (null !== $csrfIdx) {
            $this->emitMissingFormCsrfFindings($viewFlags['post_forms_missing_csrf'], $findings);
        }

        // Rule 8, Rule 10, & Rule 11: Route Parameter Reflection, Duplicate Routes, & Live DI Container Verification
        $routes = $app->router()->routes();
        $container = $app->container();
        $serviceIds = array_map(static fn($s): string => $s->id, $graph->services);
        /** @var array<string, string> $seenRoutes */
        $seenRoutes = [];
        /** @var array<string, true> $checkedControllers */
        $checkedControllers = [];

        foreach ($routes as $route) {
            $ref = $this->reflectHandler($route->handler);
            $handlerLoc = $this->reflectionLocation($ref, $root);

            // Rule 10: Duplicate route registrations
            foreach ($route->methods as $method) {
                $sig = strtoupper($method) . ' ' . $route->path;
                if (isset($seenRoutes[$sig])) {
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-DUPLICATE-ROUTE',
                        severity: 'error',
                        who: 'Kongono Router',
                        what: sprintf('Duplicate route `%s` is registered more than once.', $sig),
                        why: sprintf('`%s` is already handled by `%s`, so `%s` will be silently shadowed and never reached.', $sig, $seenRoutes[$sig], $route->handlerLabel()),
                        where: null !== $handlerLoc['file'] && null !== $handlerLoc['line']
                            ? sprintf('%s:%d (%s)', $handlerLoc['file'], $handlerLoc['line'], $route->handlerLabel())
                            : $route->handlerLabel(),
                        howToFix: [
                            sprintf('Remove or rename one of the duplicate `%s` route definitions.', $sig),
                        ],
                        file: $handlerLoc['file'],
                        line: $handlerLoc['line'],
                    );
                } else {
                    $seenRoutes[$sig] = $route->handlerLabel();
                }
            }

            // Rule 8: Route `{placeholder}` mismatch vs controller scalar parameter names
            if (null !== $ref && preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/', $route->path, $pm) > 0) {
                $placeholders = $pm[1];
                $handlerParams = $this->extractParamNames($ref);
                foreach ($placeholders as $placeholder) {
                    if (!in_array($placeholder, $handlerParams['all'], true) && [] !== $handlerParams['builtins']) {
                        $closest = $this->suggestClosest($placeholder, $handlerParams['builtins']);
                        $findings[] = new AppFinding(
                            code: 'KGN-DIAG-ROUTE-PARAM',
                            severity: 'warning',
                            who: sprintf('Kongono Route Binding (%s)', $route->handlerLabel()),
                            what: sprintf('Route `%s` defines `{%s}`, but handler `%s` has no `$%s` parameter.', $route->path, $placeholder, $route->handlerLabel(), $placeholder),
                            why: sprintf('Kongono binds URL placeholders by exact parameter name. Handler declares scalar parameter(s): %s.', implode(', ', array_map(static fn(string $p): string => '$' . $p, $handlerParams['builtins']))),
                            where: null !== $handlerLoc['file'] && null !== $handlerLoc['line']
                                ? sprintf('%s:%d (%s)', $handlerLoc['file'], $handlerLoc['line'], $route->handlerLabel())
                                : $route->handlerLabel(),
                            howToFix: [
                                [] !== $closest
                                    ? sprintf('Rename parameter `$%s` to `$%s` (or change the route placeholder to `{%s}`).', $closest[0], $placeholder, $closest[0])
                                    : sprintf('Add parameter `string|int $%s` to `%s`, or read it via `$request->routeParam(\'%s\')`.', $placeholder, $route->handlerLabel(), $placeholder),
                            ],
                            didYouMean: array_map(static fn(string $p): string => '$' . $p, $closest),
                            file: $handlerLoc['file'],
                            line: $handlerLoc['line'],
                        );
                    }
                }
            }

            // Rule 11a: Controller constructor DI resolution check against live Container
            if (is_array($route->handler)) {
                $controllerClass = $route->handler[0];
                if (!isset($checkedControllers[$controllerClass])) {
                    $checkedControllers[$controllerClass] = true;
                    $this->inspectControllerDi($controllerClass, $container, $serviceIds, $root, $findings);
                }
            }

            // Rule 11b: Route action parameter DI checks (Session without SessionMiddleware; unbound interface)
            if (null !== $ref) {
                $this->inspectActionParametersDi(
                    ref: $ref,
                    handlerLabel: $route->handlerLabel(),
                    handlerLoc: $handlerLoc,
                    container: $container,
                    serviceIds: $serviceIds,
                    hasSessionMiddleware: null !== $sessionIdx,
                    findings: $findings,
                );
            }
        }

        // Rule 14: Pending Database Migrations check
        $this->inspectPendingMigrations($app, $root, $findings);

        return count($routes);
    }

    /**
     * Rule 11a: Recursively verifies a controller class can actually be constructed from the live Container.
     *
     * @param list<string>     $serviceIds
     * @param list<AppFinding> $findings
     */
    private function inspectControllerDi(
        string $controllerClass,
        Container $container,
        array $serviceIds,
        string $root,
        array &$findings,
    ): void {
        if ($container->hasBinding($controllerClass)) {
            return;
        }

        if (!class_exists($controllerClass)) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-DI-UNRESOLVABLE',
                severity: 'error',
                who: 'Kongono DI Container',
                what: sprintf('Route controller class `%s` does not exist or cannot be autoloaded.', $controllerClass),
                why: 'Dispatching this route will fail because PHP cannot load the controller class.',
                where: $controllerClass,
                howToFix: [
                    sprintf('Verify the class name, namespace, and PSR-4 autoloading for `%s`.', $controllerClass),
                ],
            );

            return;
        }

        $refClass = new ReflectionClass($controllerClass);
        $classLoc = $this->classLocation($refClass, $root);
        if (!$refClass->isInstantiable()) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-DI-UNRESOLVABLE',
                severity: 'error',
                who: 'Kongono DI Container',
                what: sprintf('Route controller `%s` is not instantiable (abstract class or private constructor) and has no container binding.', $controllerClass),
                why: 'Kongono\'s container cannot autowire non-instantiable classes unless you register an explicit factory.',
                where: null !== $classLoc['file'] && null !== $classLoc['line']
                    ? sprintf('%s:%d', $classLoc['file'], $classLoc['line'])
                    : $controllerClass,
                howToFix: [
                    sprintf('Make `%s` concrete with a public constructor, or bind a factory in `app/bootstrap.php`.', $controllerClass),
                ],
                file: $classLoc['file'],
                line: $classLoc['line'],
            );

            return;
        }

        $failure = $this->checkClassAutowire($refClass, $container, [$controllerClass => true], 0);
        if (null !== $failure) {
            $similar = '' !== $failure['dependency'] ? $this->suggestClosest($failure['dependency'], $serviceIds) : [];
            $where = null !== $classLoc['file'] && null !== $classLoc['line']
                ? sprintf('%s:%d (%s)', $classLoc['file'], $classLoc['line'], $controllerClass)
                : $controllerClass;

            $findings[] = new AppFinding(
                code: 'KGN-DIAG-DI-UNRESOLVABLE',
                severity: 'error',
                who: sprintf('Kongono DI Container (%s)', $controllerClass),
                what: $failure['what'],
                why: $failure['why'],
                where: $where,
                howToFix: [
                    $failure['fix'],
                ],
                codeFix: $failure['code_fix'],
                didYouMean: $similar,
                file: $classLoc['file'],
                line: $classLoc['line'],
            );
        }
    }

    /**
     * @param ReflectionClass<object> $refClass
     * @param array<string, true>     $visited
     * @return array{what: string, why: string, fix: string, code_fix: ?string, dependency: string}|null
     */
    private function checkClassAutowire(
        ReflectionClass $refClass,
        Container $container,
        array $visited,
        int $depth,
    ): ?array {
        if ($depth > 8) {
            return null;
        }
        $ctor = $refClass->getConstructor();
        if (null === $ctor) {
            return null;
        }

        $owner = $refClass->getName();
        foreach ($ctor->getParameters() as $param) {
            if ($param->isDefaultValueAvailable() || $param->allowsNull() || $param->isVariadic()) {
                continue;
            }
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType) {
                return [
                    'what' => sprintf('`%s::__construct()` parameter `$%s` has no concrete class type and no default value.', $owner, $param->getName()),
                    'why' => 'Kongono\'s autowiring container requires a single class/interface type (or an explicit binding in `app/bootstrap.php`) to construct this service.',
                    'fix' => sprintf('Bind `%s` explicitly with `$app->container()->singleton(%s::class, fn($c) => new %s(...))` in `app/bootstrap.php`.', $owner, $refClass->getShortName(), $refClass->getShortName()),
                    'code_fix' => sprintf('$app->container()->singleton(\\%s::class, static fn($c) => new \\%s(/* ... */));', $owner, $owner),
                    'dependency' => '',
                ];
            }

            if ($type->isBuiltin()) {
                return [
                    'what' => sprintf('`%s::__construct()` requires scalar parameter `%s $%s`, which the DI container cannot autowire.', $owner, $type->getName(), $param->getName()),
                    'why' => 'Kongono only autowires class/interface dependencies automatically; scalar values (config strings, secrets, flags) must be passed via a container binding.',
                    'fix' => sprintf('Register `%s` in `app/bootstrap.php` using `$app->container()->singleton(...)` and pass `$%s` explicitly.', $owner, $param->getName()),
                    'code_fix' => sprintf('$app->container()->singleton(\\%s::class, static fn($c) => new \\%s(%s: /* value */));', $owner, $owner, $param->getName()),
                    'dependency' => '',
                ];
            }

            $depName = $type->getName();
            if ($container->hasBinding($depName)) {
                continue;
            }

            if (isset($visited[$depName])) {
                return [
                    'what' => sprintf('Circular DI dependency detected: `%s` cycles back to `%s`.', $owner, $depName),
                    'why' => 'When resolving this controller at runtime, `Container::get()` will throw `ContainerException: Circular dependency detected`.',
                    'fix' => sprintf('Break the constructor cycle between `%s` and `%s` by extracting shared logic into a third service.', $owner, $depName),
                    'code_fix' => null,
                    'dependency' => $depName,
                ];
            }

            if (interface_exists($depName) || !class_exists($depName)) {
                $short = str_contains($depName, '\\') ? substr($depName, (int) strrpos($depName, '\\') + 1) : $depName;

                return [
                    'what' => sprintf('`%s` depends on `%s` (`$%s`), which is not bound in the DI container.', $owner, $depName, $param->getName()),
                    'why' => 'Interfaces and missing classes cannot be autowired automatically. Hitting this route will throw `NotFoundException` at runtime.',
                    'fix' => sprintf('Bind `%s` to a concrete implementation in `app/bootstrap.php`.', $short),
                    'code_fix' => sprintf('$app->container()->singleton(\\%s::class, Concrete%s::class);', $depName, $short),
                    'dependency' => $depName,
                ];
            }

            $depRef = new ReflectionClass($depName);
            if (!$depRef->isInstantiable()) {
                return [
                    'what' => sprintf('`%s` depends on non-instantiable class `%s` (`$%s`), which has no container binding.', $owner, $depName, $param->getName()),
                    'why' => 'Abstract classes and classes with private constructors must be explicitly bound in `app/bootstrap.php`.',
                    'fix' => sprintf('Register a factory for `%s` via `$app->container()->singleton(...)` in `app/bootstrap.php`.', $depName),
                    'code_fix' => sprintf('$app->container()->singleton(\\%s::class, static fn($c) => /* factory */);', $depName),
                    'dependency' => $depName,
                ];
            }

            $nextVisited = $visited;
            $nextVisited[$depName] = true;
            $nested = $this->checkClassAutowire($depRef, $container, $nextVisited, $depth + 1);
            if (null !== $nested) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * Rule 11b: Checks route action method parameters for missing `SessionMiddleware` or unbound interfaces.
     *
     * @param array{file: ?string, line: ?int} $handlerLoc
     * @param list<string>                     $serviceIds
     * @param list<AppFinding>                 $findings
     */
    private function inspectActionParametersDi(
        ReflectionFunctionAbstract $ref,
        string $handlerLabel,
        array $handlerLoc,
        Container $container,
        array $serviceIds,
        bool $hasSessionMiddleware,
        array &$findings,
    ): void {
        $where = null !== $handlerLoc['file'] && null !== $handlerLoc['line']
            ? sprintf('%s:%d (%s)', $handlerLoc['file'], $handlerLoc['line'], $handlerLabel)
            : $handlerLabel;

        foreach ($ref->getParameters() as $param) {
            if ($param->isDefaultValueAvailable() || $param->allowsNull()) {
                continue;
            }
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            $typeName = $type->getName();

            // Built-in request-injected types
            if (Request::class === $typeName || ServerRequestInterface::class === $typeName || Principal::class === $typeName) {
                continue;
            }

            if (Session::class === $typeName) {
                if (!$hasSessionMiddleware) {
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-DI-UNRESOLVABLE',
                        severity: 'error',
                        who: sprintf('Kongono Session Injection (%s)', $handlerLabel),
                        what: sprintf('Handler `%s` requires `Session $%s`, but `SessionMiddleware` is not mounted.', $handlerLabel, $param->getName()),
                        why: 'Kongono injects `Session` from the request attribute attached by `SessionMiddleware`. Without it, this route throws `CoreException: Session attribute is missing`.',
                        where: $where,
                        howToFix: [
                            'Mount `new SessionMiddleware(new CacheSessionStore($cache))` inside `$app->middleware(...)` in `app/bootstrap.php`.',
                        ],
                        codeFix: "\$app->middleware(\n    new SessionMiddleware(new CacheSessionStore(\$cache)),\n);",
                        file: $handlerLoc['file'],
                        line: $handlerLoc['line'],
                    );
                }
                continue;
            }

            if (interface_exists($typeName) && !$container->hasBinding($typeName)) {
                $short = str_contains($typeName, '\\') ? substr($typeName, (int) strrpos($typeName, '\\') + 1) : $typeName;
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-DI-UNRESOLVABLE',
                    severity: 'error',
                    who: sprintf('Kongono DI Container (%s)', $handlerLabel),
                    what: sprintf('Route handler `%s` requires interface `%s` (`$%s`), which is not bound in the container.', $handlerLabel, $typeName, $param->getName()),
                    why: 'Kongono cannot autowire an unbound interface when dispatching this route.',
                    where: $where,
                    howToFix: [
                        sprintf('Bind `%s` to a concrete class in `app/bootstrap.php` using `$app->container()->singleton(...)`.', $short),
                    ],
                    codeFix: sprintf('$app->container()->singleton(\\%s::class, Concrete%s::class);', $typeName, $short),
                    didYouMean: $this->suggestClosest($typeName, $serviceIds),
                    file: $handlerLoc['file'],
                    line: $handlerLoc['line'],
                );
            }
        }
    }

    /**
     * Rule 13 helper: emits findings for browser POST forms missing `_csrf`.
     *
     * @param list<array{file: string, line: int, action: string}> $forms
     * @param list<AppFinding>                                     $findings
     */
    private function emitMissingFormCsrfFindings(array $forms, array &$findings): void
    {
        foreach ($forms as $form) {
            $findings[] = new AppFinding(
                code: 'KGN-DIAG-FORM-NO-CSRF',
                severity: 'warning',
                who: 'Kongono CsrfMiddleware',
                what: sprintf('POST `<form>`%s has no hidden `name="_csrf"` input while `CsrfMiddleware` is active.', '' !== $form['action'] ? sprintf(' (`action="%s"`)', $form['action']) : ''),
                why: 'Submitting a browser POST form without a signed `_csrf` token will be rejected by `CsrfMiddleware` with HTTP 403.',
                where: sprintf('%s:%d', $form['file'], $form['line']),
                howToFix: [
                    'Add `<input type="hidden" name="_csrf" value="{{ $csrf }}">` inside the `<form>` element.',
                ],
                codeFix: '<input type="hidden" name="_csrf" value="{{ $csrf }}">',
                file: $form['file'],
                line: $form['line'],
            );
        }
    }

    /**
     * Rule 14: Checks whether `migrations/*.php` has unapplied migrations against the bound DB connection.
     *
     * @param list<AppFinding> $findings
     */
    private function inspectPendingMigrations(App $app, string $root, array &$findings): void
    {
        if (!$app->container()->hasBinding(ConnectionInterface::class)) {
            return;
        }
        $migrationsDir = $root . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($migrationsDir)) {
            return;
        }
        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
        if (false === $files || [] === $files) {
            return;
        }
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            try {
                $loaded = require $file;
                if ($loaded instanceof MigrationInterface) {
                    $migrations[] = $loaded;
                }
            } catch (Throwable) {
                continue;
            }
        }
        if ([] === $migrations) {
            return;
        }

        try {
            $db = $app->container()->get(ConnectionInterface::class);
            if (!$db instanceof ConnectionInterface) {
                return;
            }
            $rows = (new Migrator($db))->status($migrations);
            $pending = [];
            foreach ($rows as $row) {
                if (!$row['applied']) {
                    $pending[] = $row['id'];
                }
            }
            if ([] !== $pending) {
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-MIGRATIONS-PENDING',
                    severity: 'warning',
                    who: 'Kongono Database Migrator',
                    what: sprintf('%d pending database migration(s) have not been applied yet: %s.', count($pending), implode(', ', array_slice($pending, 0, 5))),
                    why: 'Routes querying tables or columns introduced by pending migrations will fail with SQL errors (`no such table` / `no such column`).',
                    where: 'migrations/',
                    howToFix: [
                        'Run `php vendor/bin/kongo migrate` to apply pending migrations (or `--pretend` to preview SQL first).',
                    ],
                    command: 'php vendor/bin/kongo migrate',
                    file: 'migrations/' . $pending[0] . '.php',
                    line: 1,
                );
            }
        } catch (Throwable) {
            // Database may be intentionally offline during static CI runs; skip silently.
        }
    }

    /**
     * Inspects `.kgn.html` templates for Kongono-specific compiler, syntax, Live 2.5, CSRF, and component issues.
     *
     * @param list<string>                                                                                          $availableViews
     * @param list<AppFinding>                                                                                      $findings
     * @param array{uses_live: bool, post_forms_missing_csrf: list<array{file: string, line: int, action: string}>} $viewFlags
     * @param array<string, array{rel: string, raw: string}>                                                        $viewSources
     */
    private function inspectViews(
        string $viewsDir,
        string $root,
        array $availableViews,
        array &$findings,
        array &$viewFlags,
        array &$viewSources,
    ): int {
        if (!is_dir($viewsDir)) {
            return 0;
        }

        $checked = 0;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.kgn.html')) {
                continue;
            }
            $raw = file_get_contents($file->getPathname());
            if (false === $raw) {
                continue;
            }

            ++$checked;
            $rel = $this->relativePath($file->getPathname(), $root);
            if (str_starts_with($rel, 'views/') && str_ends_with($rel, '.kgn.html')) {
                $logical = substr($rel, strlen('views/'), -strlen('.kgn.html'));
                $viewSources[$logical] = ['rel' => $rel, 'raw' => $raw];
            }
            $this->inspectSingleView($rel, $raw, $availableViews, $findings, $viewFlags);
        }

        return $checked;
    }

    /**
     * @param list<string>                                                                                          $availableViews
     * @param list<AppFinding>                                                                                      $findings
     * @param array{uses_live: bool, post_forms_missing_csrf: list<array{file: string, line: int, action: string}>} $viewFlags
     */
    private function inspectSingleView(
        string $rel,
        string $raw,
        array $availableViews,
        array &$findings,
        array &$viewFlags,
    ): void {
        // Strip template comments preserving line numbers
        $source = preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
            $raw,
        ) ?? $raw;

        if (str_contains($source, '@liveScript') || str_contains($source, '@state(') || str_contains($source, 'k-on:')) {
            $viewFlags['uses_live'] = true;
        }

        // Rule 2: Kongono Live 2.5 zero-eval AST rejects JS assignment (`k-on:click="open = true"` instead of `set(open, true)`)
        if (preg_match_all('/\bk-on:[a-zA-Z0-9._-]+\s*=\s*"([^"]+)"/', $source, $liveMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($liveMatches as $m) {
                $expr = trim($m[1][0]);
                if (1 === preg_match('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*=(?!=)\s*([^;]+)/', $expr, $am)) {
                    $var = $am[1];
                    $val = trim($am[2]);
                    $line = $this->lineNumber($source, $m[0][1]);
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-LIVE-ASSIGN',
                        severity: 'error',
                        who: 'Kongono Live 2.5 AST Compiler',
                        what: sprintf('Invalid Live 2.5 assignment `%s = %s` inside `%s`.', $var, $val, $rel),
                        why: 'Kongono Live 2.5 uses a strict zero-eval AST (for strict CSP) that executes action calls like `set(var, value)` or `toggle(var)`, NOT JavaScript `=` assignments.',
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: [
                            sprintf('Replace `%s = %s` with `set(%s, %s)` (or `toggle(%s)` for booleans).', $var, $val, $var, $val, $var),
                        ],
                        codeFix: sprintf('k-on:click="set(%s, %s)"', $var, $val),
                        file: $rel,
                        line: $line,
                        fixable: true,
                    );
                }
            }
        }

        // Rule 3: Kongono Live 2.5 `k-show` on the SAME element as `@state(...)` (child scope hides outer variable!)
        if (preg_match_all('/<[a-zA-Z][a-zA-Z0-9-]*\b((?:=>|[^>])*\bk-show\s*=(?:=>|[^>])*@state\s*\((?:=>|[^>])*|(?:=>|[^>])*@state\s*\((?:=>|[^>])*\bk-show\s*=(?:=>|[^>])*)>/s', $source, $scopeMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($scopeMatches as $m) {
                $line = $this->lineNumber($source, $m[0][1]);
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-LIVE-SHOW-SCOPE',
                    severity: 'error',
                    who: 'Kongono Live 2.5 Scope Engine',
                    what: '`k-show` and `@state(...)` are declared on the exact same HTML element.',
                    why: 'Live 2.5 evaluates `k-show` inside the element\'s own child `@state` scope, where variables from the parent island are `undefined` — causing the element to stay permanently hidden.',
                    where: sprintf('%s:%d', $rel, $line),
                    howToFix: [
                        'Move `k-show="..."` onto an outer wrapper `<div k-show="...">` and keep `@state(...)` on the inner element.',
                    ],
                    codeFix: "<div k-show=\"cat === 'all'\">\n  <section @state(['view' => 'preview'])>...</section>\n</div>",
                    file: $rel,
                    line: $line,
                );
            }
        }

        // Rule 4: Laravel muscle-memory `name="_token"` instead of Kongono `name="_csrf"`
        if (preg_match_all('/<input\b[^>]*\bname\s*=\s*["\']_token["\'][^>]*>/i', $source, $tokMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($tokMatches as $m) {
                $line = $this->lineNumber($source, $m[0][1]);
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-CSRF-FIELD',
                    severity: 'error',
                    who: 'Kongono CsrfMiddleware',
                    what: 'Form uses `name="_token"` instead of Kongono\'s `name="_csrf"`.',
                    why: 'Kongono\'s `CsrfMiddleware` validates the `_csrf` form field (or `X-CSRF-Token` header), so submitting `_token` will return HTTP 403.',
                    where: sprintf('%s:%d', $rel, $line),
                    howToFix: [
                        'Rename `name="_token"` to `name="_csrf"` on the hidden CSRF input.',
                    ],
                    codeFix: '<input type="hidden" name="_csrf" value="{{ $csrf }}">',
                    file: $rel,
                    line: $line,
                    fixable: true,
                );
            }
        }

        // Collect browser POST <form> elements missing `_csrf` (evaluated in Rule 13 if CsrfMiddleware is mounted)
        $withoutCode = preg_replace_callback(
            '/<(pre|code)\b[^>]*>.*?<\/\1>/is',
            static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
            $source,
        ) ?? $source;
        if (preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $withoutCode, $formMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($formMatches as $fm) {
                $attrs = $fm[1][0];
                $body = $fm[2][0];
                if (1 !== preg_match('/\bmethod\s*=\s*["\']?post\b/i', $attrs)) {
                    continue;
                }
                // Skip JS/Live-intercepted forms (they send X-CSRF-Token headers)
                if (1 === preg_match('/\b(?:k-on:submit|onsubmit|data-ajax)\b/i', $attrs)) {
                    continue;
                }
                $action = '';
                if (1 === preg_match('/\baction\s*=\s*["\']([^"\']*)["\']/i', $attrs, $actM)) {
                    $action = trim($actM[1]);
                }
                // Skip API-exempt or external URL forms
                if (str_starts_with($action, '/api/') || str_starts_with($action, 'http://') || str_starts_with($action, 'https://')) {
                    continue;
                }
                // Skip if form already has `_csrf`, `_token` (covered by Rule 4), `@csrf`, or `@include(`
                if (
                    1 === preg_match('/\bname\s*=\s*["\'](?:_csrf|_token)["\']/i', $body)
                    || str_contains($body, '@csrf')
                    || str_contains($body, '@include(')
                    || str_contains($body, 'X-CSRF-Token')
                ) {
                    continue;
                }
                $viewFlags['post_forms_missing_csrf'][] = [
                    'file' => $rel,
                    'line' => $this->lineNumber($withoutCode, $fm[0][1]),
                    'action' => $action,
                ];
            }
        }

        // Rule 5: TemplateCompiler directive gotchas (multi-@if on one line, inline @section with `)`, unsupported @for)
        $hadCompilerGotcha = false;
        $lines = explode("\n", $source);
        foreach ($lines as $idx => $lineText) {
            $lineNo = $idx + 1;
            if (preg_match_all('/@if\s*\(/', $lineText) > 1) {
                $hadCompilerGotcha = true;
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-TEMPLATE-MULTI-IF',
                    severity: 'error',
                    who: 'Kongono TemplateCompiler',
                    what: 'Multiple `@if (...)` directives appear on the same line.',
                    why: '`TemplateCompiler` compiles `@if (...)` greedily per line, so two `@if (...)` blocks on one line swallow everything between them and cause a PHP ParseError.',
                    where: sprintf('%s:%d', $rel, $lineNo),
                    howToFix: [
                        'Place each `@if (...)` on its own line, or use inline ternary `{{ $cond ? \'a\' : \'b\' }}`.',
                    ],
                    file: $rel,
                    line: $lineNo,
                );
            }

            if (1 === preg_match('/@section\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"][^\'"]*\)[^\'"]*[\'"]\s*\)/', $lineText)) {
                $hadCompilerGotcha = true;
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-TEMPLATE-SECTION-PAREN',
                    severity: 'error',
                    who: 'Kongono TemplateCompiler',
                    what: 'Single-line `@section(\'...\', \'...\')` string contains parentheses `(...)`.',
                    why: 'The single-line `@section` parser stops at the first `)`, truncating the PHP string literal and throwing a ParseError at runtime.',
                    where: sprintf('%s:%d', $rel, $lineNo),
                    howToFix: [
                        'Use block `@section(\'title\') ... @endsection` or remove `)` from the single-line `@section` argument.',
                    ],
                    codeFix: "@section('title')\n  Page Title (Beta)\n@endsection",
                    file: $rel,
                    line: $lineNo,
                );
            }

            if (1 === preg_match('/@for\s*\(/', $lineText)) {
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-TEMPLATE-FOR',
                    severity: 'error',
                    who: 'Kongono TemplateCompiler',
                    what: 'Unsupported `@for (...)` directive used in `.kgn.html` template.',
                    why: 'Kongono\'s template compiler supports `@foreach` and `@forelse` (not `@for`), so `@for` will render literally in HTML.',
                    where: sprintf('%s:%d', $rel, $lineNo),
                    howToFix: [
                        'Replace `@for ($i = 0; $i < $n; $i++)` with `@foreach (range(1, $n) as $i)`.',
                    ],
                    codeFix: "@foreach (range(1, \$count) as \$i)\n  ...\n@endforeach",
                    file: $rel,
                    line: $lineNo,
                );
            }
        }

        // Rule 12: Whole-app `.kgn.html` compile + Zend PHP 8.5 parser verification (zero code execution!)
        if (!$hadCompilerGotcha) {
            try {
                $compiled = (new TemplateCompiler())->compile($raw);
                PhpToken::tokenize($compiled, TOKEN_PARSE);
            } catch (ParseError $e) {
                $mappedLine = max(1, $e->getLine() - 4);
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-TEMPLATE-SYNTAX',
                    severity: 'error',
                    who: 'Kongono TemplateCompiler (PHP 8.5 Parser)',
                    what: sprintf('Compiled template `%s` has a PHP syntax error near line %d: %s.', $rel, $mappedLine, $e->getMessage()),
                    why: 'Rendering this `.kgn.html` view will crash with a `ParseError` — typically caused by an unclosed `@if`/`@foreach`/`@forelse` or malformed `{{ ... }}` / `@php` expression.',
                    where: sprintf('%s:%d', $rel, $mappedLine),
                    howToFix: [
                        sprintf('Inspect `%s` around line %d for unclosed directives (`@endif`, `@endforeach`, `@endforelse`) or invalid PHP syntax.', $rel, $mappedLine),
                    ],
                    file: $rel,
                    line: $mappedLine,
                );
            }
        }

        // Rule 6b: Missing `@extends('...')` / `@include('...')` targets inside `.kgn.html`
        if ([] !== $availableViews && preg_match_all('/@(extends|include)\s*\(\s*[\'"]([a-zA-Z0-9_\/.-]+)[\'"]/', $source, $incMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($incMatches as $m) {
                $directive = $m[1][0];
                $tpl = ltrim($m[2][0], '/');
                if (!in_array($tpl, $availableViews, true)) {
                    $line = $this->lineNumber($source, $m[0][1]);
                    $similar = $this->suggestClosest($tpl, $availableViews);
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-MISSING-VIEW',
                        severity: 'error',
                        who: 'Kongono ViewEngine Contract Check',
                        what: sprintf('Template directive `@%s(\'%s\')` references missing view `views/%s.kgn.html`.', $directive, $tpl, $tpl),
                        why: 'At runtime this template will throw `ViewException: Template not found`.',
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: [
                            [] !== $similar
                                ? sprintf('Replace `%s` with `%s` (closest existing template).', $tpl, $similar[0])
                                : sprintf('Create `views/%s.kgn.html` or fix the `@%s(...)` target.', $tpl, $directive),
                        ],
                        didYouMean: $similar,
                        file: $rel,
                        line: $line,
                    );
                }
            }
        }

        // Rule 7: Missing `<x-...>` component templates (skip inside `<pre>`/`<code>` examples)
        if (preg_match_all('/<x-([a-zA-Z0-9._-]+)(?=[\s\/>])/', $withoutCode, $compMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            $reported = [];
            foreach ($compMatches as $m) {
                $tag = $m[1][0];
                if (isset($reported[$tag])) {
                    continue;
                }
                $logical = 'components/' . str_replace('.', '/', $tag);
                if (!in_array($logical, $availableViews, true)) {
                    $reported[$tag] = true;
                    $line = $this->lineNumber($withoutCode, $m[0][1]);
                    $isZokeibi = str_starts_with(strtolower($tag), 'ui.');
                    $zokeibiId = $isZokeibi ? strtolower(substr($tag, 3)) : null;
                    $similar = $this->suggestClosest($logical, $availableViews);

                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-MISSING-COMPONENT',
                        severity: 'error',
                        who: $isZokeibi ? 'Zokeibi UI / Kongono ViewEngine' : 'Kongono ViewEngine',
                        what: sprintf('Component tag `<x-%s>` has no template at `views/%s.kgn.html`.', $tag, $logical),
                        why: 'Rendering this page will throw `ViewException: Template not found` because the component file does not exist.',
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: null !== $zokeibiId
                            ? [
                                sprintf('Install Zokeibi component `<x-ui.%s>` with `php vendor/bin/zokeibi add %s`.', $zokeibiId, $zokeibiId),
                                'Or verify the component spelling with `php vendor/bin/zokeibi list`.',
                            ]
                            : [
                                sprintf('Create `views/%s.kgn.html` or fix the component tag name.', $logical),
                            ],
                        command: null !== $zokeibiId ? sprintf('php vendor/bin/zokeibi add %s', $zokeibiId) : null,
                        didYouMean: array_map(static fn(string $v): string => '<x-' . str_replace('/', '.', substr($v, strlen('components/'))) . '>', array_values(array_filter($similar, static fn(string $v): bool => str_starts_with($v, 'components/')))),
                        file: $rel,
                        line: $line,
                    );
                }
            }
        }
    }

    /**
     * Scans `src/` and `app/` PHP files for missing `$views->render('...')` targets, passed view vars, and mutable static properties.
     *
     * @param list<string>                                                            $availableViews
     * @param list<AppFinding>                                                        $findings
     * @param array<string, list<array{keys: list<string>, file: string, line: int}>> $passedViewVars
     */
    private function inspectPhpSources(
        string $root,
        array $availableViews,
        array &$findings,
        FullStackContractProver $prover,
        array &$passedViewVars,
    ): int {
        $checked = 0;
        $dirs = [
            $root . DIRECTORY_SEPARATOR . 'src',
            $root . DIRECTORY_SEPARATOR . 'app',
        ];

        foreach ($dirs as $scanDir) {
            if (!is_dir($scanDir)) {
                continue;
            }

            $dirIt = new RecursiveDirectoryIterator($scanDir, FilesystemIterator::SKIP_DOTS);
            $filter = new RecursiveCallbackFilterIterator(
                $dirIt,
                static fn(SplFileInfo $cur): bool => !$cur->isDir() || !in_array($cur->getFilename(), self::IGNORED_DIRS, true),
            );
            $it = new RecursiveIteratorIterator($filter);

            foreach ($it as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $code = file_get_contents($file->getPathname());
                if (false === $code) {
                    continue;
                }

                ++$checked;
                $rel = $this->relativePath($file->getPathname(), $root);
                $this->inspectSinglePhpFile($rel, $code, $availableViews, $findings);
                $prover->collectPassedViewVars($rel, $code, $passedViewVars);
            }
        }

        return $checked;
    }

    /**
     * @param list<string>     $availableViews
     * @param list<AppFinding> $findings
     */
    private function inspectSinglePhpFile(string $rel, string $code, array $availableViews, array &$findings): void
    {
        // Rule 6: Missing view template passed to `$views->render('...')` / `$this->views->render('...')` / `$view->render('...')`
        if ([] !== $availableViews && preg_match_all('/(?:\bviews?|\bviewEngine|\btemplates?|\btpl)\s*->\s*render\s*\(\s*[\'"]([a-zA-Z0-9_\/.-]+)[\'"]/i', $code, $rm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($rm as $m) {
                $tpl = ltrim($m[1][0], '/');
                if (!in_array($tpl, $availableViews, true)) {
                    $line = $this->lineNumber($code, $m[0][1]);
                    $similar = $this->suggestClosest($tpl, $availableViews);
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-MISSING-VIEW',
                        severity: 'error',
                        who: 'Kongono ViewEngine Contract Check',
                        what: sprintf('Controller renders template `%s`, but `views/%s.kgn.html` does not exist.', $tpl, $tpl),
                        why: 'At runtime this route will throw `ViewException: Template not found`.',
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: [
                            [] !== $similar
                                ? sprintf('Replace `%s` with `%s` (closest existing template).', $tpl, $similar[0])
                                : sprintf('Create `views/%s.kgn.html` or fix the template name in `->render(...)`.', $tpl),
                        ],
                        didYouMean: $similar,
                        file: $rel,
                        line: $line,
                    );
                }
            }
        }

        // Rule 9: Mutable static properties break FrankenPHP / RoadRunner persistent workers!
        $this->detectMutableStaticProperties($code, $rel, $findings);
    }

    /**
     * Uses PHP 8.5 `PhpToken::tokenize()` to catch mutable `static $prop` declarations in app code.
     *
     * @param list<AppFinding> $findings
     */
    private function detectMutableStaticProperties(string $code, string $rel, array &$findings): void
    {
        try {
            $tokens = array_values(PhpToken::tokenize($code));
        } catch (Throwable) {
            return;
        }

        $count = count($tokens);
        $braceDepth = 0;
        /** @var list<int> $classDepthStack */
        $classDepthStack = [];
        $prevSigId = null;

        for ($i = 0; $i < $count; ++$i) {
            $tok = $tokens[$i];
            if (T_WHITESPACE === $tok->id || T_COMMENT === $tok->id || T_DOC_COMMENT === $tok->id) {
                continue;
            }

            if ((T_CLASS === $tok->id || T_TRAIT === $tok->id) && T_DOUBLE_COLON !== $prevSigId) {
                $classDepthStack[] = $braceDepth + 1;
            } elseif ('{' === $tok->text) {
                ++$braceDepth;
            } elseif ('}' === $tok->text) {
                --$braceDepth;
                while ([] !== $classDepthStack && $braceDepth < $classDepthStack[count($classDepthStack) - 1]) {
                    array_pop($classDepthStack);
                }
            }

            // Look for `static` at class/trait body depth (not inside a method body, not `static function`, not `const`)
            $currentClassDepth = [] !== $classDepthStack ? $classDepthStack[count($classDepthStack) - 1] : -1;
            if ($currentClassDepth > 0 && $braceDepth === $currentClassDepth && T_STATIC === $tok->id) {
                for ($j = $i + 1; $j < min($count, $i + 24); ++$j) {
                    $next = $tokens[$j];
                    if (T_WHITESPACE === $next->id || T_COMMENT === $next->id || T_DOC_COMMENT === $next->id) {
                        continue;
                    }
                    if (T_FUNCTION === $next->id || T_FN === $next->id || T_CONST === $next->id || '(' === $next->text || ';' === $next->text || '{' === $next->text) {
                        break;
                    }
                    if (T_VARIABLE === $next->id) {
                        $findings[] = new AppFinding(
                            code: 'KGN-DIAG-PERSISTENT-STATIC',
                            severity: 'warning',
                            who: 'Kongono Persistent-Runtime Guard',
                            what: sprintf('Mutable static property `%s` detected in `%s`.', $next->text, $rel),
                            why: 'Kongono is persistent-runtime-first (FrankenPHP, RoadRunner, `kongo serve`). Mutable static properties survive across requests and can leak one user\'s state into another user\'s request.',
                            where: sprintf('%s:%d', $rel, $next->line),
                            howToFix: [
                                sprintf('Convert `static %s` into an injected service bound in `app/bootstrap.php` (using `CacheInterface` or request-scoped state) or a `const` if immutable.', $next->text),
                            ],
                            file: $rel,
                            line: $next->line,
                        );
                        break;
                    }
                }
            }

            $prevSigId = $tok->id;
        }
    }

    /**
     * @return list<string>
     */
    private function collectLogicalViews(string $viewsDir): array
    {
        if (!is_dir($viewsDir)) {
            return [];
        }

        $views = [];
        $realViews = realpath($viewsDir);
        $normBase = rtrim(str_replace('\\', '/', false !== $realViews ? $realViews : $viewsDir), '/') . '/';
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.kgn.html')) {
                continue;
            }
            $norm = str_replace('\\', '/', $file->getPathname());
            if (str_starts_with($norm, $normBase)) {
                $views[] = substr($norm, strlen($normBase), -strlen('.kgn.html'));
            }
        }
        sort($views);

        return $views;
    }

    private function reflectHandler(mixed $handler): ?ReflectionFunctionAbstract
    {
        try {
            if ($handler instanceof \Closure) {
                return new ReflectionFunction($handler);
            }
            if (is_array($handler) && 2 === count($handler) && (is_string($handler[0]) || is_object($handler[0])) && is_string($handler[1])) {
                return new ReflectionMethod($handler[0], $handler[1]);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array{all: list<string>, builtins: list<string>}
     */
    private function extractParamNames(ReflectionFunctionAbstract $ref): array
    {
        $all = [];
        $builtins = [];
        foreach ($ref->getParameters() as $param) {
            $all[] = $param->getName();
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                $builtins[] = $param->getName();
            }
        }

        return ['all' => $all, 'builtins' => $builtins];
    }

    /**
     * @return array{file: ?string, line: ?int}
     */
    private function reflectionLocation(?ReflectionFunctionAbstract $ref, string $root): array
    {
        if (null === $ref) {
            return ['file' => null, 'line' => null];
        }
        $file = $ref->getFileName();
        $line = $ref->getStartLine();
        if (false === $file || false === $line) {
            return ['file' => null, 'line' => null];
        }

        return ['file' => $this->relativePath($file, $root), 'line' => $line];
    }

    /**
     * @param ReflectionClass<object> $ref
     * @return array{file: ?string, line: ?int}
     */
    private function classLocation(ReflectionClass $ref, string $root): array
    {
        $file = $ref->getFileName();
        $line = $ref->getStartLine();
        if (false === $file || false === $line) {
            return ['file' => null, 'line' => null];
        }

        return ['file' => $this->relativePath($file, $root), 'line' => $line];
    }

    /**
     * @param list<string> $candidates
     * @return list<string>
     */
    private function suggestClosest(string $needle, array $candidates): array
    {
        $scored = [];
        $lower = strtolower($needle);
        foreach ($candidates as $cand) {
            $dist = levenshtein($lower, strtolower($cand));
            if ($dist <= max(3, (int) floor(strlen($lower) * 0.45))) {
                $scored[$cand] = $dist;
            }
        }
        asort($scored);

        return array_slice(array_keys($scored), 0, 3);
    }

    private function lineNumber(string $source, int $byteOffset): int
    {
        return substr_count(substr($source, 0, $byteOffset), "\n") + 1;
    }

    private function relativePath(string $absPath, string $root): string
    {
        $norm = str_replace('\\', '/', $absPath);
        $base = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($norm, $base) ? substr($norm, strlen($base)) : basename($norm);
    }
}
