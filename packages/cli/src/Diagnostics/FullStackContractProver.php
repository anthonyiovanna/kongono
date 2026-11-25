<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use BackedEnum;
use Closure;
use Kongono\Core\Container;
use Kongono\Http\App;
use Kongono\Http\HttpException;
use Kongono\Validation\ValidatableDtoInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Full-Stack Contract Prover — mathematically proves zero drift across HTML templates,
 * forms, the live Kongono Router, PHP 8.5 Input DTOs, and controller-to-view data bags.
 *
 * Powers Rules 15, 16, and 17 inside `kongo diagnose`, `kongo lsp`, `kongo check`, and MCP.
 */
final readonly class FullStackContractProver
{
    /** @var list<string> */
    private const array STATIC_EXTENSIONS = [
        '.css', '.js', '.mjs', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp',
        '.ico', '.woff', '.woff2', '.ttf', '.eot', '.json', '.xml', '.txt', '.pdf', '.map', '.zip', '.csv',
    ];

    /** @var list<string> */
    private const array RESERVED_TEMPLATE_VARS = [
        'this', 'slot', 'csrf', 'errors', 'old', 'request', 'app', 'message', 'flash', 'session',
        '__env', '__vars', '_GET', '_POST', '_SERVER', '_COOKIE', '_SESSION', '_FILES', '_ENV', 'GLOBALS',
    ];

    /**
     * Proves internal `<a href>` links, `<form action>` routes, and `<form>` inputs ↔ PHP 8.5 Input DTOs
     * for a single `.kgn.html` template against a live booted Kongono `App`.
     *
     * @param list<AppFinding> $findings
     */
    public function proveTemplateAgainstApp(
        string $rel,
        string $raw,
        string $appRoot,
        App $app,
        array &$findings,
    ): void {
        // Preserve line numbers while stripping template comments and `<pre>` / `<code>` doc examples
        $clean = preg_replace_callback(
            '/\{\{--.*?--\}\}|<(pre|code)\b[^>]*>.*?<\/\1>/is',
            static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
            $raw,
        ) ?? $raw;

        $router = $app->router();
        $allPaths = array_values(array_unique(array_map(static fn($r): string => $r->path, $router->routes())));
        $virtualPrefixes = $this->detectVirtualPrefixes($app);

        // Rule 15a: Static internal `<a href="/...">` ↔ Live Router Proof
        if (preg_match_all('/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $clean, $linkMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            /** @var array<string, true> $reportedLinks */
            $reportedLinks = [];
            foreach ($linkMatches as $m) {
                $href = trim($m[1][0]);
                $path = $this->extractTestableInternalPath($href, $appRoot, $virtualPrefixes);
                if (null === $path || isset($reportedLinks[$path])) {
                    continue;
                }

                try {
                    $router->match('GET', $path);
                } catch (HttpException $e) {
                    $reportedLinks[$path] = true;
                    $line = $this->lineNumber($clean, $m[0][1]);
                    $similar = $this->suggestClosest($path, $allPaths);
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-BROKEN-LINK',
                        severity: 'warning',
                        who: 'Kongono Full-Stack Contract Prover (Link ↔ Router)',
                        what: 405 === $e->statusCode
                            ? sprintf('Link `<a href="%s">` points to a route that does not accept `GET` (%s).', $href, $e->getMessage())
                            : sprintf('Link `<a href="%s">` does not match any registered `GET` route in this application.', $href),
                        why: 'Clicking this link in the browser will return HTTP ' . $e->statusCode . ' instead of a live page.',
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: [
                            [] !== $similar
                                ? sprintf('Update `href="%s"` to `%s` (closest registered route), or register `GET %s`.', $href, $similar[0], $path)
                                : sprintf('Register a `GET %s` route or fix the `href` attribute in `%s`.', $path, $rel),
                        ],
                        didYouMean: $similar,
                        file: $rel,
                        line: $line,
                    );
                }
            }
        }

        // Rule 15b & Rule 16: `<form method="..." action="/...">` ↔ Live Router & Input DTO Proof
        if (preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $clean, $formMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            foreach ($formMatches as $fm) {
                $attrs = $fm[1][0];
                $body = $fm[2][0];
                $line = $this->lineNumber($clean, $fm[0][1]);

                $action = '';
                if (1 === preg_match('/\baction\s*=\s*["\']([^"\']*)["\']/i', $attrs, $am)) {
                    $action = trim($am[1]);
                }
                $path = $this->extractTestableInternalPath($action, $appRoot, $virtualPrefixes);
                if (null === $path) {
                    continue;
                }

                $method = 'GET';
                if (1 === preg_match('/\bmethod\s*=\s*["\']?([a-zA-Z]+)["\']?/i', $attrs, $mm)) {
                    $method = strtoupper($mm[1]);
                }
                if ('POST' === $method && 1 === preg_match('/<input\b[^>]*\bname\s*=\s*["\']_method["\'][^>]*\bvalue\s*=\s*["\']([a-zA-Z]+)["\']/i', $body, $ovm)) {
                    $method = strtoupper($ovm[1]);
                }

                try {
                    $match = $router->match($method, $path);
                } catch (HttpException $e) {
                    $similar = $this->suggestClosest($path, $allPaths);
                    $allow = $e->headers['Allow'] ?? '';
                    $findings[] = new AppFinding(
                        code: 'KGN-DIAG-FORM-ROUTE',
                        severity: 'error',
                        who: 'Kongono Full-Stack Contract Prover (Form ↔ Router)',
                        what: 405 === $e->statusCode
                            ? sprintf('Form submits `%s %s`, but `%s` only allows `%s`.', $method, $path, $path, '' !== $allow ? $allow : 'other methods')
                            : sprintf('Form submits `%s %s`, which does not match any registered route.', $method, $path),
                        why: sprintf('Submitting this form in the browser will fail with HTTP %d.', $e->statusCode),
                        where: sprintf('%s:%d', $rel, $line),
                        howToFix: [
                            405 === $e->statusCode
                                ? sprintf('Change the form `method` to match `%s`, or register a `%s %s` route.', '' !== $allow ? $allow : 'the route method', $method, $path)
                                : ([] !== $similar
                                    ? sprintf('Update `action="%s"` to `%s`, or register `%s %s`.', $action, $similar[0], $method, $path)
                                    : sprintf('Register `%s %s` or fix the form `action`.', $method, $path)),
                        ],
                        didYouMean: $similar,
                        file: $rel,
                        line: $line,
                    );
                    continue;
                }

                // Rule 16: Form Input Fields ↔ Target Route PHP 8.5 Input DTO Proof
                if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                    continue;
                }
                // Skip JS-assembled payloads or dynamically included partial forms
                if (
                    1 === preg_match('/\b(?:k-on:submit|onsubmit|data-ajax)\b/i', $attrs)
                    || str_contains($body, '@include(')
                    || 1 === preg_match('/\bname\s*=\s*["\'][^"\']*\{\{/i', $body)
                ) {
                    continue;
                }

                $dtoRef = $this->findInputDtoForHandler($match->route->handler, $app->container());
                if (null === $dtoRef) {
                    continue;
                }

                $this->proveFormMatchesDto(
                    rel: $rel,
                    formLine: $line,
                    method: $method,
                    path: $path,
                    formBody: $body,
                    routeParams: $match->params,
                    dtoRef: $dtoRef,
                    findings: $findings,
                );
            }
        }
    }

    /**
     * Rule 17: Proves that variables referenced in a `.kgn.html` view don't have typos relative to
     * literal array keys passed into `$views->render('tpl', [...])` in controllers.
     *
     * @param array<string, list<array{keys: list<string>, file: string, line: int}>> $passedViewVars
     * @param array<string, array{rel: string, raw: string}>                          $viewSources
     * @param list<AppFinding>                                                        $findings
     */
    public function proveViewVariables(array $passedViewVars, array $viewSources, array &$findings): void
    {
        foreach ($passedViewVars as $logicalView => $callSites) {
            if (!isset($viewSources[$logicalView]) || [] === $callSites) {
                continue;
            }

            /** @var list<string> $passedKeys */
            $passedKeys = [];
            foreach ($callSites as $site) {
                foreach ($site['keys'] as $k) {
                    if (!in_array($k, $passedKeys, true)) {
                        $passedKeys[] = $k;
                    }
                }
            }
            if ([] === $passedKeys) {
                continue;
            }

            $viewRel = $viewSources[$logicalView]['rel'];
            $raw = $viewSources[$logicalView]['raw'];
            $source = preg_replace_callback(
                '/\{\{--.*?--\}\}/s',
                static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
                $raw,
            ) ?? $raw;

            // Collect locally declared template variables (loop vars + `@php` assignments)
            $localDeclared = self::RESERVED_TEMPLATE_VARS;
            if (preg_match_all('/@(?:foreach|forelse)\s*\([^)]*\bas\s+\$([a-zA-Z_][a-zA-Z0-9_]*)(?:\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*))?/i', $source, $loopM, PREG_SET_ORDER) > 0) {
                foreach ($loopM as $lm) {
                    $localDeclared[] = $lm[1];
                    if (isset($lm[2])) {
                        $localDeclared[] = $lm[2];
                    }
                }
            }
            if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=(?!=)/', $source, $assignM) > 0) {
                foreach ($assignM[1] as $assignedVar) {
                    $localDeclared[] = $assignedVar;
                }
            }
            $known = array_values(array_unique([...$passedKeys, ...$localDeclared]));

            // Find variables referenced in `{{ ... }}`, `{!! ... !!}`, `@if(...)`, `@foreach(...)`, `@forelse(...)`, and `:prop="$..."`
            $exprPatterns = [
                '/\{\{\s*(.+?)\s*\}\}/s',
                '/\{!!\s*(.+?)\s*!!\}/s',
                '/@(?:if|elseif|unless|isset)\s*\((.+)\)/',
                '/@(?:foreach|forelse)\s*\((.+?)\s+as\s+/i',
                '/:[a-zA-Z0-9_-]+\s*=\s*"([^"]+)"/',
            ];

            /** @var array<string, true> $reportedVars */
            $reportedVars = [];
            foreach ($exprPatterns as $pattern) {
                if (preg_match_all($pattern, $source, $em, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) <= 0) {
                    continue;
                }
                foreach ($em as $match) {
                    $expr = $match[1][0];
                    $baseOffset = $match[1][1];
                    if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\b/', $expr, $vm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) <= 0) {
                        continue;
                    }
                    foreach ($vm as $vMatch) {
                        $varName = $vMatch[1][0];
                        if (strlen($varName) < 3 || isset($reportedVars[$varName]) || in_array($varName, $known, true)) {
                            continue;
                        }

                        // Flag ONLY clear close typos (distance 1 for <=5 chars, distance <=2 for >=6 chars) to prevent false positives on globals
                        $maxDist = strlen($varName) <= 5 ? 1 : 2;
                        $closest = null;
                        $bestDist = 99;
                        foreach ($passedKeys as $passed) {
                            if (strlen($passed) < 3) {
                                continue;
                            }
                            $d = levenshtein(strtolower($varName), strtolower($passed));
                            if ($d > 0 && $d <= $maxDist && $d < $bestDist) {
                                $bestDist = $d;
                                $closest = $passed;
                            }
                        }

                        if (null !== $closest) {
                            $reportedVars[$varName] = true;
                            $line = $this->lineNumber($source, $baseOffset + $vMatch[0][1]);
                            $caller = $callSites[0];
                            $findings[] = new AppFinding(
                                code: 'KGN-DIAG-VIEW-VAR-TYPO',
                                severity: 'warning',
                                who: 'Kongono Full-Stack Contract Prover (Controller ↔ View)',
                                what: sprintf('Template `%s` references `$%s`, which is not passed by `%s:%d` (passed: `$%s`).', $viewRel, $varName, $caller['file'], $caller['line'], implode('`, `$', $passedKeys)),
                                why: sprintf('`$%s` looks like a typo for `$%s` and will trigger an undefined variable warning or render blank.', $varName, $closest),
                                where: sprintf('%s:%d', $viewRel, $line),
                                howToFix: [
                                    sprintf('Rename `$%s` to `$%s` in `%s:%d`, or pass `\'%s\'` from `%s:%d`.', $varName, $closest, $viewRel, $line, $varName, $caller['file'], $caller['line']),
                                ],
                                didYouMean: ['$' . $closest],
                                file: $viewRel,
                                line: $line,
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Extracts literal array keys from `$views->render('tpl', ['a' => ..., 'b' => ...])` calls in a PHP file.
     *
     * @param array<string, list<array{keys: list<string>, file: string, line: int}>> $passedViewVars
     */
    public function collectPassedViewVars(string $rel, string $code, array &$passedViewVars): void
    {
        if (preg_match_all('/(?:\bviews?|\bviewEngine|\btemplates?|\btpl)\s*->\s*render\s*\(\s*[\'"]([a-zA-Z0-9_\/.-]+)[\'"]\s*,\s*\[([^\]]*)\]/is', $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) <= 0) {
            return;
        }

        foreach ($matches as $m) {
            $tpl = ltrim($m[1][0], '/');
            $arrBody = $m[2][0];
            // Skip spread arrays (`...$data`) since additional dynamic keys may exist
            if (str_contains($arrBody, '...')) {
                continue;
            }
            if (preg_match_all('/[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]\s*=>/', $arrBody, $km) > 0) {
                $keys = array_values(array_unique($km[1]));
                if ([] !== $keys) {
                    $passedViewVars[$tpl][] = [
                        'keys' => $keys,
                        'file' => $rel,
                        'line' => $this->lineNumber($code, $m[0][1]),
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, string>   $routeParams
     * @param ReflectionClass<object> $dtoRef
     * @param list<AppFinding>        $findings
     */
    private function proveFormMatchesDto(
        string $rel,
        int $formLine,
        string $method,
        string $path,
        string $formBody,
        array $routeParams,
        ReflectionClass $dtoRef,
        array &$findings,
    ): void {
        $ctor = $dtoRef->getConstructor();
        if (null === $ctor) {
            return;
        }

        /** @var list<string> $dtoProps */
        $dtoProps = [];
        /** @var list<string> $requiredDtoProps */
        $requiredDtoProps = [];
        foreach ($ctor->getParameters() as $param) {
            $pName = $param->getName();
            $dtoProps[] = $pName;
            if (!$param->isDefaultValueAvailable() && !$param->allowsNull() && !array_key_exists($pName, $routeParams)) {
                $requiredDtoProps[] = $pName;
            }
        }

        // Collect all submitted field names from `<input>`, `<select>`, `<textarea>`
        /** @var list<string> $formFields */
        $formFields = [];
        if (preg_match_all('/<(input|select|textarea)\b([^>]*)>/i', $formBody, $fieldMatches, PREG_SET_ORDER) > 0) {
            foreach ($fieldMatches as $fm) {
                $tag = strtolower($fm[1]);
                $fAttrs = $fm[2];
                if ('input' === $tag && 1 === preg_match('/\btype\s*=\s*["\']?(?:submit|button|reset|image)["\']?/i', $fAttrs)) {
                    continue;
                }
                if (1 !== preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/i', $fAttrs, $nm)) {
                    continue;
                }
                $rawName = trim($nm[1]);
                $baseName = (string) preg_replace('/\[.*$/', '', $rawName);
                if ('' === $baseName || in_array($baseName, ['_csrf', '_token', '_method'], true)) {
                    continue;
                }
                if (!in_array($baseName, $formFields, true)) {
                    $formFields[] = $baseName;
                }
            }
        }

        $dtoShort = $dtoRef->getShortName();

        // 1. Unknown / misspelled form fields not declared on the DTO
        foreach ($formFields as $field) {
            if (!in_array($field, $dtoProps, true)) {
                $similar = $this->suggestClosest($field, $dtoProps);
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-FORM-DTO-MISMATCH',
                    severity: 'warning',
                    who: sprintf('Kongono Full-Stack Contract Prover (Form ↔ %s)', $dtoShort),
                    what: sprintf('Form `%s %s` submits field `name="%s"`, which does not exist on `%s`.', $method, $path, $field, $dtoShort),
                    why: sprintf('`%s` only validates properties: `%s`. Extra or misspelled fields are silently ignored and often leave the real field empty (HTTP 422).', $dtoShort, implode('`, `', $dtoProps)),
                    where: sprintf('%s:%d', $rel, $formLine),
                    howToFix: [
                        [] !== $similar
                            ? sprintf('Rename `name="%s"` to `name="%s"` in `%s:%d`.', $field, $similar[0], $rel, $formLine)
                            : sprintf('Remove `name="%s"` or add `$%s` to `%s::__construct()`.', $field, $field, $dtoShort),
                    ],
                    didYouMean: $similar,
                    file: $rel,
                    line: $formLine,
                );
            }
        }

        // 2. Missing required DTO fields not present in the form
        foreach ($requiredDtoProps as $reqField) {
            if (!in_array($reqField, $formFields, true)) {
                $findings[] = new AppFinding(
                    code: 'KGN-DIAG-FORM-DTO-MISSING',
                    severity: 'error',
                    who: sprintf('Kongono Full-Stack Contract Prover (Form ↔ %s)', $dtoShort),
                    what: sprintf('Form `%s %s` is missing required `%s` field `name="%s"`.', $method, $path, $dtoShort, $reqField),
                    why: sprintf('Submitting this form will fail validation (HTTP 422) because `%s::$%s` is required and has no default value.', $dtoShort, $reqField),
                    where: sprintf('%s:%d', $rel, $formLine),
                    howToFix: [
                        sprintf('Add an input with `name="%s"` inside the `<form>` at `%s:%d` (or make `$%s` optional/nullable on `%s`).', $reqField, $rel, $formLine, $reqField, $dtoShort),
                    ],
                    codeFix: sprintf('<input name="%s" required>', $reqField),
                    file: $rel,
                    line: $formLine,
                );
            }
        }
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     * @return ReflectionClass<object>|null
     */
    private function findInputDtoForHandler(Closure|array $handler, Container $container): ?ReflectionClass
    {
        try {
            $ref = $handler instanceof Closure
                ? new ReflectionFunction($handler)
                : new ReflectionMethod($handler[0], $handler[1]);
        } catch (Throwable) {
            return null;
        }

        return $this->findInputDtoFromReflection($ref, $container);
    }

    /** @return ReflectionClass<object>|null */
    public function findInputDtoFromReflection(ReflectionFunctionAbstract $ref, Container $container): ?ReflectionClass
    {
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            $className = $type->getName();
            if (!class_exists($className) || $container->hasBinding($className)) {
                continue;
            }
            if (str_starts_with($className, 'Kongono\\') && !str_contains($className, '\\Tests\\')) {
                continue;
            }
            if (is_subclass_of($className, BackedEnum::class)) {
                continue;
            }

            $rc = new ReflectionClass($className);
            if (!$rc->isInstantiable() || $rc->isInternal()) {
                continue;
            }
            if ($rc->implementsInterface(ValidatableDtoInterface::class)) {
                return $rc;
            }

            $ctor = $rc->getConstructor();
            if (null === $ctor || 0 === $ctor->getNumberOfParameters()) {
                continue;
            }
            $allDtoFields = true;
            foreach ($ctor->getParameters() as $cp) {
                $ct = $cp->getType();
                if (!$ct instanceof ReflectionNamedType) {
                    $allDtoFields = false;
                    break;
                }
                if (!$ct->isBuiltin() && !is_subclass_of($ct->getName(), BackedEnum::class)) {
                    $allDtoFields = false;
                    break;
                }
            }
            if ($allDtoFields) {
                return $rc;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function detectVirtualPrefixes(App $app): array
    {
        $prefixes = ['/_kongono'];
        foreach ($app->graph()->middleware as $mw) {
            if (str_ends_with($mw, '\\DevDashboardMiddleware')) {
                $prefixes[] = '/_dev';
            }
            if (str_ends_with($mw, '\\OpsDashboardMiddleware')) {
                $prefixes[] = '/_ops';
            }
        }

        return $prefixes;
    }

    /**
     * @param list<string> $virtualPrefixes
     */
    private function extractTestableInternalPath(string $rawUrl, string $appRoot, array $virtualPrefixes): ?string
    {
        if ('' === $rawUrl || !str_starts_with($rawUrl, '/') || str_starts_with($rawUrl, '//')) {
            return null;
        }
        // Skip dynamic template expressions (`{{ ... }}`, `{!! ... !!}`, `<?`)
        if (str_contains($rawUrl, '{{') || str_contains($rawUrl, '{!') || str_contains($rawUrl, '<?')) {
            return null;
        }

        $parsed = parse_url($rawUrl);
        $path = is_array($parsed) && is_string($parsed['path'] ?? null) ? $parsed['path'] : $rawUrl;
        if ('' === $path || !str_starts_with($path, '/')) {
            return null;
        }

        foreach ($virtualPrefixes as $vp) {
            if ($path === $vp || str_starts_with($path, rtrim($vp, '/') . '/')) {
                return null;
            }
        }

        $lower = strtolower($path);
        foreach (self::STATIC_EXTENSIONS as $ext) {
            if (str_ends_with($lower, $ext)) {
                return null;
            }
        }

        // Skip paths backed by a real static file in `public/`
        $publicFile = $appRoot . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($publicFile)) {
            return null;
        }

        return $path;
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
            if ($dist <= max(3, (int) floor(strlen($lower) * 0.5))) {
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
}
