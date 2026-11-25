<?php

declare(strict_types=1);

namespace Kongono\Cli\Policy;

use Closure;
use FilesystemIterator;
use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Cli\Diagnostics\FullStackContractProver;
use Kongono\Http\App;
use Kongono\Http\Routing\Route;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Attributes\Can;
use Kongono\Security\Attributes\VerifyWebhook;
use Kongono\Security\Middleware\CsrfMiddleware;
use PhpToken;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use SplFileInfo;
use Throwable;

/**
 * Executable Architecture & Security Invariant Prover (`kongo policy:check` & MCP `kongono_policy_check`).
 *
 * Evaluates built-in security invariants plus optional user-defined `app/invariants.php` rules
 * against Kongono's live `ApplicationGraph`, route attributes, DTO contracts, and `src/` namespace graph.
 */
final readonly class PolicyVerifier
{
    /** @var list<string> */
    private const array IGNORED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'storage', '.cache'];

    /**
     * Built-in zero-config security invariants applied when no custom suite disables them.
     *
     * @return list<Invariant>
     */
    public static function defaultInvariants(): array
    {
        return [
            Invariant::routesMatching('/admin/*', 'KGN-POLICY-ADMIN-AUTH')
                ->except('/admin/login', '/admin/logout')
                ->mustRequireAuth()
                ->describe('Every `/admin/*` route must enforce authentication (`#[Authenticated]`, `#[Can]`, or auth middleware).'),
            Invariant::routesMatching('/api/webhooks/*', 'KGN-POLICY-WEBHOOK-VERIFY')
                ->methods('POST')
                ->mustVerifyWebhookSignature()
                ->describe('Every `POST /api/webhooks/*` endpoint must verify cryptographic webhook signatures (`#[VerifyWebhook]` or explicit HMAC signature header check).'),
        ];
    }

    /**
     * Verify all invariants against an app root and live `App`.
     *
     * @param list<Invariant>|null $customRules Overrides `app/invariants.php` when provided (great for tests)
     * @return array{
     *     score: int,
     *     passed: bool,
     *     rules_evaluated: int,
     *     skipped_route_rules: int,
     *     custom_policy_loaded: bool,
     *     counts: array{error: int, warning: int},
     *     violations: list<array{
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
    public function verify(
        string $appRoot,
        ?App $app = null,
        ?array $customRules = null,
        bool $includeDefaults = true,
        bool $requireApp = false,
    ): array {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');

        $customLoaded = false;
        /** @var list<Invariant> $rules */
        $rules = $includeDefaults ? self::defaultInvariants() : [];

        if (null !== $customRules) {
            $customLoaded = [] !== $customRules;
            array_push($rules, ...$customRules);
        } else {
            $policyFile = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'invariants.php';
            if (is_file($policyFile)) {
                $loaded = require $policyFile;
                if (is_array($loaded)) {
                    $customLoaded = true;
                    foreach ($loaded as $item) {
                        if ($item instanceof Invariant) {
                            $rules[] = $item;
                        }
                    }
                }
            }
        }

        /** @var list<AppFinding> $violations */
        $violations = [];
        $prover = new FullStackContractProver();
        $rulesEvaluated = 0;
        $skippedRouteRules = 0;

        foreach ($rules as $rule) {
            if ('route' === $rule->kind) {
                if (null === $app) {
                    ++$skippedRouteRules;
                    continue;
                }
                ++$rulesEvaluated;
                $this->verifyRouteInvariant($rule, $app, $root, $prover, $violations);
            } elseif ('namespace' === $rule->kind) {
                ++$rulesEvaluated;
                $this->verifyNamespaceInvariant($rule, $root, $violations);
            }
        }

        $bootstrapExists = is_file($root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php');
        if ($skippedRouteRules > 0 && ($requireApp || $bootstrapExists)) {
            $violations[] = new AppFinding(
                code: 'KGN-POLICY-BOOTSTRAP-FAILED',
                severity: 'error',
                who: 'Kongono Architecture & Security Policy Verifier',
                what: sprintf('Application bootstrap failed or was unavailable — %d route security invariant(s) could not be evaluated.', $skippedRouteRules),
                why: 'Policy checks fail closed when app/bootstrap.php cannot be loaded so broken bootstraps never silently bypass route security gates.',
                where: 'app/bootstrap.php',
                howToFix: [
                    'Ensure app/bootstrap.php loads cleanly and returns a valid Kongono\\Http\\App instance.',
                    'Re-run `php vendor/bin/kongo policy:check` once bootstrap succeeds.',
                ],
                command: 'php vendor/bin/kongo policy:check',
            );
        }

        $errors = 0;
        $warnings = 0;
        foreach ($violations as $v) {
            if ('error' === $v->severity) {
                ++$errors;
            } else {
                ++$warnings;
            }
        }

        return [
            'score' => max(0, 100 - ($errors * 20) - ($warnings * 5)),
            'passed' => 0 === $errors,
            'rules_evaluated' => $rulesEvaluated,
            'skipped_route_rules' => $skippedRouteRules,
            'custom_policy_loaded' => $customLoaded,
            'counts' => [
                'error' => $errors,
                'warning' => $warnings,
            ],
            'violations' => array_map(static fn(AppFinding $f): array => $f->toArray(), $violations),
        ];
    }

    /**
     * @param list<AppFinding> $violations
     */
    private function verifyRouteInvariant(
        Invariant $rule,
        App $app,
        string $root,
        FullStackContractProver $prover,
        array &$violations,
    ): void {
        $globalMiddleware = $app->graph()->middleware;
        $hasGlobalCsrf = false;
        $hasGlobalAuth = false;
        foreach ($globalMiddleware as $gmw) {
            if (CsrfMiddleware::class === $gmw || str_ends_with($gmw, '\\CsrfMiddleware')) {
                $hasGlobalCsrf = true;
            }
            // Only globally enforcing auth middleware (never optional principal loaders like SessionAuthMiddleware
            // or BearerAuthMiddleware with required: false) may count as global authentication enforcement.
            if (1 === preg_match('/\\\\(?:RequireAuth(?:enticated)?|RequireRole|RequireCapability|EnforceAuth|AdminAuth)Middleware\z/i', $gmw)) {
                $hasGlobalAuth = true;
            }
        }

        foreach ($app->router()->routes() as $route) {
            if (!$this->matchesGlob($route->path, $rule->targetPattern)) {
                continue;
            }
            if ($this->matchesAnyGlob($route->path, $rule->exceptPatterns)) {
                continue;
            }
            if ([] !== $rule->methods && [] === array_intersect($rule->methods, $route->methods)) {
                continue;
            }

            $ref = $this->reflectHandler($route->handler);
            $loc = $this->reflectionLocation($ref, $root);
            $attrs = $this->collectHandlerAttributes($route);
            $routeMwObjects = $route->routeMiddleware();
            $routeMwClasses = array_map(static fn(object $mw): string => $mw::class, $routeMwObjects);
            $where = null !== $loc['file'] && null !== $loc['line']
                ? sprintf('%s:%d (%s)', $loc['file'], $loc['line'], $route->handlerLabel())
                : $route->handlerLabel();

            $routeLabel = implode('|', $route->methods) . ' ' . $route->path;
            $hasAuth = $hasGlobalAuth || $this->hasAuthProtection($attrs, $routeMwClasses, $routeMwObjects, $ref);

            if ('require_auth' === $rule->assertion && !$hasAuth) {
                $violations[] = new AppFinding(
                    code: $rule->id,
                    severity: 'error',
                    who: 'Kongono Architecture & Security Policy',
                    what: sprintf('Route `%s` violates invariant `%s`: missing authentication/authorization protection.', $routeLabel, $rule->id),
                    why: '' !== $rule->description
                        ? $rule->description
                        : sprintf('Routes matching `%s` must declare `#[Authenticated]`, `#[Can(...)]`, or auth middleware.', $rule->targetPattern),
                    where: $where,
                    howToFix: [
                        sprintf('Add `#[Authenticated]` or `#[Can(\'admin\')]` to `%s`, or attach auth middleware to the route group.', $route->handlerLabel()),
                    ],
                    codeFix: "#[Authenticated]\n#[Can('admin')]",
                    file: $loc['file'],
                    line: $loc['line'],
                );
            } elseif ('require_auth_or_csrf' === $rule->assertion) {
                $csrfCoversRoute = $hasGlobalCsrf && !str_starts_with($route->path, '/api/');
                $hasWebhook = in_array(VerifyWebhook::class, $attrs, true);
                if (!$hasAuth && !$csrfCoversRoute && !$hasWebhook) {
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture & Security Policy',
                        what: sprintf('Mutating route `%s` has neither CSRF protection nor explicit authentication/webhook verification.', $routeLabel),
                        why: '' !== $rule->description
                            ? $rule->description
                            : 'State-changing endpoints without CSRF or auth protection can be forged or abused by unauthenticated callers.',
                        where: $where,
                        howToFix: [
                            sprintf('Add `#[Authenticated]` (or `#[VerifyWebhook]` for webhooks) to `%s`, or ensure `CsrfMiddleware` protects browser routes.', $route->handlerLabel()),
                        ],
                        codeFix: '#[Authenticated]',
                        file: $loc['file'],
                        line: $loc['line'],
                    );
                }
            } elseif ('require_attribute' === $rule->assertion && null !== $rule->assertionArg) {
                if (!in_array($rule->assertionArg, $attrs, true)) {
                    $short = str_contains($rule->assertionArg, '\\')
                        ? substr($rule->assertionArg, (int) strrpos($rule->assertionArg, '\\') + 1)
                        : $rule->assertionArg;
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture & Security Policy',
                        what: sprintf('Route `%s` is missing required attribute `#[%s]`.', $routeLabel, $short),
                        why: '' !== $rule->description
                            ? $rule->description
                            : sprintf('Policy requires all routes matching `%s` to declare `#[%s]`.', $rule->targetPattern, $rule->assertionArg),
                        where: $where,
                        howToFix: [
                            sprintf('Annotate `%s` (or its controller class) with `#[%s]`.', $route->handlerLabel(), $short),
                        ],
                        codeFix: sprintf('#[%s]', $short),
                        file: $loc['file'],
                        line: $loc['line'],
                    );
                }
            } elseif ('require_dto' === $rule->assertion && null !== $ref) {
                $dto = $prover->findInputDtoFromReflection($ref, $app->container());
                if (null === $dto) {
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture & Security Policy',
                        what: sprintf('Write route `%s` does not bind a validated PHP 8.5 Input DTO.', $routeLabel),
                        why: '' !== $rule->description
                            ? $rule->description
                            : 'Reading raw `$request->input()` skips Kongono\'s automatic schema validation and OpenAPI 3.1 contract generation.',
                        where: $where,
                        howToFix: [
                            sprintf('Create a readonly Input DTO (`php vendor/bin/kongo make:dto ...`) and type-hint it in `%s`.', $route->handlerLabel()),
                        ],
                        command: 'php vendor/bin/kongo make:dto CreateResourceDto',
                        file: $loc['file'],
                        line: $loc['line'],
                    );
                }
            } elseif ('require_middleware' === $rule->assertion && null !== $rule->assertionArg) {
                $needle = $rule->assertionArg;
                $allMw = [...$globalMiddleware, ...$routeMwClasses];
                $found = false;
                foreach ($allMw as $mw) {
                    if ($mw === $needle || str_ends_with($mw, '\\' . ltrim($needle, '\\')) || str_contains($mw, $needle)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture & Security Policy',
                        what: sprintf('Route `%s` is missing required middleware `%s`.', $routeLabel, $needle),
                        why: '' !== $rule->description
                            ? $rule->description
                            : sprintf('Policy requires middleware `%s` on all routes matching `%s`.', $needle, $rule->targetPattern),
                        where: $where,
                        howToFix: [
                            sprintf('Attach `%s` via `$router->group(...)` or `$route->middleware(...)`.', $needle),
                        ],
                        file: $loc['file'],
                        line: $loc['line'],
                    );
                }
            } elseif ('require_webhook_verification' === $rule->assertion) {
                $verified = in_array(VerifyWebhook::class, $attrs, true)
                    || $this->handlerVerifiesWebhookSignature($ref);
                if (!$verified) {
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture & Security Policy',
                        what: sprintf('Webhook route `%s` has no cryptographic signature verification (`#[VerifyWebhook]` or signature header check).', $routeLabel),
                        why: '' !== $rule->description
                            ? $rule->description
                            : 'Unverified webhook endpoints allow forged third-party billing/provisioning events.',
                        where: $where,
                        howToFix: [
                            sprintf('Annotate `%s` with `#[VerifyWebhook(\'WEBHOOK_SECRET\')]` or verify the provider HMAC signature header before acting.', $route->handlerLabel()),
                        ],
                        codeFix: '#[VerifyWebhook(\'STRIPE_WEBHOOK_SECRET\')]',
                        file: $loc['file'],
                        line: $loc['line'],
                    );
                }
            }
        }
    }

    /**
     * Verifies module/layer boundary invariants across `src/` PHP files using PHP 8.5 `PhpToken::tokenize()`.
     *
     * @param list<AppFinding> $violations
     */
    private function verifyNamespaceInvariant(Invariant $rule, string $root, array &$violations): void
    {
        $forbidden = $rule->assertionArg ?? '';
        if ('' === $forbidden) {
            return;
        }
        $srcDir = $root . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($srcDir)) {
            return;
        }

        $dirIt = new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator(
            $dirIt,
            static fn(SplFileInfo $cur): bool => !$cur->isLink() && (!$cur->isDir() || !in_array(strtolower($cur->getFilename()), self::IGNORED_DIRS, true)),
        );
        $it = new RecursiveIteratorIterator($filter);

        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || $file->isLink() || !$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if (false === $code) {
                continue;
            }

            $ns = $this->extractNamespace($code);
            if (null === $ns || ($ns !== $rule->targetPattern && !str_starts_with($ns, $rule->targetPattern . '\\'))) {
                continue;
            }

            $rel = $this->relativePath($file->getPathname(), $root);
            foreach ($this->extractImportedSymbols($code) as $imp) {
                $sym = ltrim($imp['symbol'], '\\');
                if ($sym === $forbidden || str_starts_with($sym, $forbidden . '\\')) {
                    $violations[] = new AppFinding(
                        code: $rule->id,
                        severity: 'error',
                        who: 'Kongono Architecture Boundary Guard',
                        what: sprintf('Module `%s` (`%s`) illegally depends on forbidden namespace `%s` via `%s`.', $rule->targetPattern, $rel, $forbidden, $sym),
                        why: '' !== $rule->description
                            ? $rule->description
                            : sprintf('Architecture invariant forbids `%s` from importing or referencing `%s`.', $rule->targetPattern, $forbidden),
                        where: sprintf('%s:%d', $rel, $imp['line']),
                        howToFix: [
                            sprintf('Remove the dependency on `%s` in `%s:%d` and depend on a shared contract/interface instead.', $sym, $rel, $imp['line']),
                        ],
                        file: $rel,
                        line: $imp['line'],
                    );
                }
            }
        }
    }

    private function handlerVerifiesWebhookSignature(?ReflectionFunctionAbstract $ref): bool
    {
        if (null === $ref) {
            return false;
        }
        $file = $ref->getFileName();
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        if (false === $file || false === $start || false === $end || !is_file($file)) {
            return false;
        }
        $lines = file($file);
        if (false === $lines) {
            return false;
        }
        $slice = implode('', array_slice($lines, max(0, $start - 1), max(1, $end - $start + 1)));

        // Strip PHP comments before scanning so a comment like `// TODO: check X-Signature` never satisfies the invariant
        $codeOnly = $slice;
        try {
            $stripped = '';
            foreach (PhpToken::tokenize("<?php\n" . $slice) as $tok) {
                if (T_OPEN_TAG === $tok->id || T_COMMENT === $tok->id || T_DOC_COMMENT === $tok->id) {
                    continue;
                }
                $stripped .= $tok->text;
            }
            $codeOnly = $stripped;
        } catch (Throwable) {
            // Fall back to raw slice only if tokenization fails on a partial fragment
        }

        return 1 === preg_match('/(?:Stripe-Signature|X-Signature|X-Hub-Signature|Webhook-Signature|WebhookVerifier|hash_equals|verifySignature)/i', $codeOnly);
    }

    /**
     * @param list<string> $attrs
     * @param list<string> $routeMwClasses
     * @param list<object> $routeMwObjects
     */
    private function hasAuthProtection(array $attrs, array $routeMwClasses, array $routeMwObjects = [], ?ReflectionFunctionAbstract $ref = null): bool
    {
        if (in_array(Authenticated::class, $attrs, true) || in_array(Can::class, $attrs, true)) {
            return true;
        }
        foreach ($attrs as $attr) {
            if (1 === preg_match('/(?:^|\\\\)(?:Authenticated|Can|Require(?:Auth(?:enticated)?|Role|Permission|Capability)|Admin(?:Only|Auth)?|Auth(?:enticated|Guard|Required)?|Gate|Permission)\z/i', $attr)) {
                return true;
            }
        }

        // Check actual route-attached middleware instances: exclude optional principal loaders (`SessionAuthMiddleware`
        // or `BearerAuthMiddleware` with `required: false`) from satisfying auth enforcement invariants.
        foreach ($routeMwObjects as $mwObj) {
            $class = $mwObj::class;
            if (str_ends_with($class, '\\SessionAuthMiddleware')) {
                continue;
            }
            if (str_ends_with($class, '\\BearerAuthMiddleware')) {
                try {
                    $rc = new ReflectionClass($mwObj);
                    if ($rc->hasProperty('required')) {
                        $required = $rc->getProperty('required')->getValue($mwObj);
                        if (false === $required) {
                            continue;
                        }
                    }
                } catch (Throwable) {
                    continue;
                }

                return true;
            }
            if (self::isAuthMiddlewareClass($class)) {
                return true;
            }
        }

        if ([] === $routeMwObjects) {
            foreach ($routeMwClasses as $mw) {
                if (self::isAuthMiddlewareClass($mw)) {
                    return true;
                }
            }
        }

        // A non-nullable `Kongono\Security\Principal` parameter also enforces authentication at dispatch time
        if (null !== $ref) {
            foreach ($ref->getParameters() as $param) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && 'Kongono\\Security\\Principal' === $type->getName() && !$type->allowsNull()) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function isAuthMiddlewareClass(string $class): bool
    {
        if (str_ends_with($class, '\\SessionAuthMiddleware') || 'SessionAuthMiddleware' === $class) {
            return false;
        }
        $short = str_contains($class, '\\')
            ? substr($class, (int) strrpos($class, '\\') + 1)
            : $class;

        return 1 === preg_match(
            '/(?:^|(?<=[a-z]))(?:RequireAuth(?:enticated)?|Auth|Admin|Role|Gate|Guard|Permission|Bearer|TokenAuth)(?=[A-Z]|\z)/',
            $short,
        );
    }

    /** @return list<string> */
    private function collectHandlerAttributes(Route $route): array
    {
        $names = [];
        foreach ($route->routeMiddlewareAttributes() as $attrObj) {
            $names[] = $attrObj::class;
        }
        if (is_array($route->handler)) {
            try {
                $rc = new ReflectionClass($route->handler[0]);
                foreach ($rc->getAttributes() as $a) {
                    $names[] = $a->getName();
                }
                if ($rc->hasMethod($route->handler[1])) {
                    foreach ($rc->getMethod($route->handler[1])->getAttributes() as $a) {
                        $names[] = $a->getName();
                    }
                }
            } catch (Throwable) {
                // ignore
            }
        } elseif ($route->handler instanceof Closure) {
            try {
                $rf = new ReflectionFunction($route->handler);
                foreach ($rf->getAttributes() as $a) {
                    $names[] = $a->getName();
                }
            } catch (Throwable) {
                // ignore
            }
        }

        return array_values(array_unique($names));
    }

    private function extractNamespace(string $code): ?string
    {
        if (1 === preg_match('/^\s*namespace\s+([A-Za-z0-9_\\\\]+)\s*;/m', $code, $m)) {
            return trim($m[1], '\\');
        }

        return null;
    }

    /** @return list<array{symbol: string, line: int}> */
    private function extractImportedSymbols(string $code): array
    {
        $out = [];
        try {
            $tokens = array_values(PhpToken::tokenize($code));
        } catch (Throwable) {
            return [];
        }

        $count = count($tokens);
        for ($i = 0; $i < $count; ++$i) {
            $tok = $tokens[$i];
            if (T_USE === $tok->id) {
                $line = $tok->line;
                $j = $i + 1;
                while ($j < $count && $tokens[$j]->isIgnorable()) {
                    ++$j;
                }
                // Skip closure `function () use ($var)` clauses
                if ($j >= $count || '(' === $tokens[$j]->text) {
                    continue;
                }
                if (T_FUNCTION === $tokens[$j]->id || T_CONST === $tokens[$j]->id) {
                    ++$j;
                }

                $name = '';
                $skipAlias = false;
                for (; $j < $count; ++$j) {
                    $t = $tokens[$j];
                    if ($t->isIgnorable()) {
                        continue;
                    }
                    if ('{' === $t->text) {
                        $prefix = rtrim($name, '\\');
                        $sub = '';
                        $subSkipAlias = false;
                        for (++$j; $j < $count; ++$j) {
                            $gt = $tokens[$j];
                            if ($gt->isIgnorable()) {
                                continue;
                            }
                            if ('}' === $gt->text || ',' === $gt->text) {
                                if ('' !== $sub) {
                                    $out[] = ['symbol' => ('' !== $prefix ? $prefix . '\\' : '') . ltrim($sub, '\\'), 'line' => $line];
                                }
                                $sub = '';
                                $subSkipAlias = false;
                                if ('}' === $gt->text) {
                                    break;
                                }
                                continue;
                            }
                            if (T_AS === $gt->id) {
                                $subSkipAlias = true;
                                continue;
                            }
                            if ($subSkipAlias || T_FUNCTION === $gt->id || T_CONST === $gt->id) {
                                continue;
                            }
                            if (T_NAME_QUALIFIED === $gt->id || T_NAME_FULLY_QUALIFIED === $gt->id || T_STRING === $gt->id || T_NS_SEPARATOR === $gt->id) {
                                $sub .= $gt->text;
                            }
                        }
                        $name = '';
                        $skipAlias = false;
                        continue;
                    }
                    if (';' === $t->text || ',' === $t->text) {
                        if ('' !== $name) {
                            $out[] = ['symbol' => $name, 'line' => $line];
                        }
                        $name = '';
                        $skipAlias = false;
                        if (';' === $t->text) {
                            break;
                        }
                        continue;
                    }
                    if (T_AS === $t->id) {
                        $skipAlias = true;
                        continue;
                    }
                    if ($skipAlias) {
                        continue;
                    }
                    if (T_NAME_QUALIFIED === $t->id || T_NAME_FULLY_QUALIFIED === $t->id || T_STRING === $t->id || T_NS_SEPARATOR === $t->id) {
                        $name .= $t->text;
                    }
                }
                $i = $j;
            } elseif (T_NAME_FULLY_QUALIFIED === $tok->id) {
                $out[] = ['symbol' => $tok->text, 'line' => $tok->line];
            }
        }

        return $out;
    }

    /** @param list<string> $globs */
    private function matchesAnyGlob(string $path, array $globs): bool
    {
        foreach ($globs as $g) {
            if ($this->matchesGlob($path, $g)) {
                return true;
            }
        }

        return false;
    }

    private function matchesGlob(string $path, string $glob): bool
    {
        $escaped = preg_quote($glob, '#');
        $regex = '#\A' . str_replace('\*', '.*', $escaped) . '\z#i';

        return 1 === preg_match($regex, $path);
    }

    /** @param Closure|array{0: class-string, 1: string} $handler */
    private function reflectHandler(Closure|array $handler): ?ReflectionFunctionAbstract
    {
        try {
            return $handler instanceof Closure
                ? new ReflectionFunction($handler)
                : new ReflectionMethod($handler[0], $handler[1]);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{file: ?string, line: ?int} */
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

    private function relativePath(string $absPath, string $root): string
    {
        $norm = str_replace('\\', '/', $absPath);
        $base = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($norm, $base) ? substr($norm, strlen($base)) : basename($norm);
    }
}
