<?php

declare(strict_types=1);

namespace Kongono\Cli\Audit;

use Kongono\Http\App;
use Kongono\Http\Middleware\IdempotencyMiddleware;
use Kongono\Http\Routing\Idempotent;
use Kongono\Http\Routing\Middleware;
use Kongono\Security\Middleware\RateLimitMiddleware;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Graph-driven automated security & architecture auditor for Kongono applications.
 *
 * Inspects live routes, middleware attributes, compiled view sinks, Composer supply chain,
 * and environment hygiene to surface actionable security findings in CLI, CI, and MCP.
 */
final readonly class SecurityAuditor
{
    /**
     * @return array{
     *     passed: bool,
     *     score: int,
     *     symfony_packages: int,
     *     routes_scanned: int,
     *     views_scanned: int,
     *     counts: array{critical: int, warning: int, info: int},
     *     findings: list<array{severity: 'critical'|'warning'|'info', code: string, message: string, location: string}>
     * }
     */
    public function audit(string $appRoot, ?App $app = null): array
    {
        /** @var list<array{severity: 'critical'|'warning'|'info', code: string, message: string, location: string}> $findings */
        $findings = [];
        $symfonyPackages = $this->countSymfonyPackages($appRoot);
        if ($symfonyPackages > 0) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'KGN-AUDIT-SYMFONY',
                'message' => sprintf('Detected %d symfony/* package(s) in vendor/composer/installed.json.', $symfonyPackages),
                'location' => 'composer.lock',
            ];
        }

        $this->auditEnvFile($appRoot, $findings);
        $viewsScanned = $this->auditViews($appRoot, $findings);
        $routesScanned = $this->auditRoutes($app, $findings);

        $counts = ['critical' => 0, 'warning' => 0, 'info' => 0];
        foreach ($findings as $f) {
            ++$counts[$f['severity']];
        }

        $score = max(0, 100 - ($counts['critical'] * 25) - ($counts['warning'] * 10) - ($counts['info'] * 2));

        return [
            'passed' => 0 === $counts['critical'],
            'score' => $score,
            'symfony_packages' => $symfonyPackages,
            'routes_scanned' => $routesScanned,
            'views_scanned' => $viewsScanned,
            'counts' => $counts,
            'findings' => $findings,
        ];
    }

    /**
     * @param list<array{severity: 'critical'|'warning'|'info', code: string, message: string, location: string}> $findings
     */
    private function auditEnvFile(string $appRoot, array &$findings): void
    {
        $envPath = $appRoot . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            return;
        }

        $env = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $trimmed, 2);
            $env[trim($k)] = trim($v, " \t\n\r\"'");
        }

        $isProd = 'production' === strtolower($env['APP_ENV'] ?? '');
        $debug = filter_var($env['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOL);
        if ($isProd && $debug) {
            $findings[] = [
                'severity' => 'critical',
                'code' => 'KGN-AUDIT-PROD-DEBUG',
                'message' => 'APP_DEBUG=true is enabled while APP_ENV=production. Disable debug mode in production.',
                'location' => '.env',
            ];
        }

        $appKey = $env['APP_KEY'] ?? '';
        if ('' !== $appKey && strlen($appKey) < 16) {
            $findings[] = [
                'severity' => 'critical',
                'code' => 'KGN-AUDIT-WEAK-KEY',
                'message' => 'APP_KEY is shorter than 16 bytes. Run `kongo key:generate` to create a 256-bit key.',
                'location' => '.env',
            ];
        }
    }

    /**
     * @param list<array{severity: 'critical'|'warning'|'info', code: string, message: string, location: string}> $findings
     */
    private function auditViews(string $appRoot, array &$findings): int
    {
        $viewsDir = $appRoot . DIRECTORY_SEPARATOR . 'views';
        if (!is_dir($viewsDir)) {
            return 0;
        }

        $scanned = 0;
        $realRoot = realpath($appRoot);
        $rootLen = false !== $realRoot ? strlen($realRoot) + 1 : 0;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.kgn.html')) {
                continue;
            }
            ++$scanned;
            $contents = file_get_contents($file->getPathname());
            if (false === $contents) {
                continue;
            }
            if (1 === preg_match_all('/\{!!\s*(.+?)\s*!!\}/', $contents, $matches)) {
                foreach ($matches[1] as $expr) {
                    if ('$slot' === trim($expr)) {
                        continue;
                    }
                    $rel = $rootLen > 0 ? str_replace('\\', '/', substr($file->getPathname(), $rootLen)) : $file->getFilename();
                    $findings[] = [
                        'severity' => 'info',
                        'code' => 'KGN-AUDIT-RAW-HTML',
                        'message' => sprintf('Unescaped raw output `{!! %s !!}` detected; verify value is trusted/sanitized.', trim($expr)),
                        'location' => $rel,
                    ];
                }
            }
        }

        return $scanned;
    }

    /**
     * @param list<array{severity: 'critical'|'warning'|'info', code: string, message: string, location: string}> $findings
     */
    private function auditRoutes(?App $app, array &$findings): int
    {
        if (null === $app) {
            return 0;
        }

        $routes = $app->router()->routes();
        foreach ($routes as $route) {
            $path = strtolower($route->path);
            $methods = $route->methods;
            $isMutating = in_array('POST', $methods, true) || in_array('PUT', $methods, true) || in_array('PATCH', $methods, true) || in_array('DELETE', $methods, true);
            if (!$isMutating) {
                continue;
            }

            $hasRateLimit = false;
            $hasIdempotent = false;

            foreach ($route->routeMiddleware() as $mw) {
                if ($mw instanceof RateLimitMiddleware) {
                    $hasRateLimit = true;
                }
                if ($mw instanceof IdempotencyMiddleware) {
                    $hasIdempotent = true;
                }
            }
            foreach ($route->routeMiddlewareAttributes() as $attr) {
                if ($attr instanceof Idempotent) {
                    $hasIdempotent = true;
                }
                if ($attr instanceof Middleware && RateLimitMiddleware::class === $attr->middlewareClass) {
                    $hasRateLimit = true;
                }
            }

            if (!$hasRateLimit && 1 === preg_match('#/(login|register|forgot-password|reset-password)\b#', $path)) {
                $findings[] = [
                    'severity' => 'warning',
                    'code' => 'KGN-AUDIT-AUTH-RATELIMIT',
                    'message' => sprintf('Auth route %s %s has no RateLimitMiddleware attribute attached.', implode('|', $methods), $route->path),
                    'location' => $route->handlerLabel(),
                ];
            }

            if (!$hasIdempotent && 1 === preg_match('#/(charge|pay)\b#', $path)) {
                $findings[] = [
                    'severity' => 'warning',
                    'code' => 'KGN-AUDIT-PAYMENT-IDEMPOTENCY',
                    'message' => sprintf('Payment route %s %s is missing #[Idempotent] protection against duplicate retries.', implode('|', $methods), $route->path),
                    'location' => $route->handlerLabel(),
                ];
            }
        }

        return count($routes);
    }

    private function countSymfonyPackages(string $appRoot): int
    {
        $installedPath = $appRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
        if (!is_file($installedPath)) {
            return 0;
        }
        $raw = file_get_contents($installedPath);
        $decoded = false !== $raw ? json_decode($raw, true) : null;
        if (!is_array($decoded) || !is_array($decoded['packages'] ?? null)) {
            return 0;
        }

        $count = 0;
        foreach ($decoded['packages'] as $pkg) {
            if (is_array($pkg) && is_string($pkg['name'] ?? null) && str_starts_with(strtolower($pkg['name']), 'symfony/')) {
                ++$count;
            }
        }

        return $count;
    }
}
