<?php

declare(strict_types=1);

namespace Kongono\Cli\Security;

use Closure;
use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Routing\Route;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Attributes\Can;
use Kongono\Security\Principal;
use ReflectionClass;
use ReflectionFunction;
use Throwable;

/**
 * Automated Multi-Tenant Isolation & BOLA/IDOR Prover (`kongo prove:isolation` & MCP `kongono_prove_isolation`).
 *
 * Empirically proves two security guarantees in-process:
 *  1. Every route marked `#[Authenticated]` / `#[Can]` refuses unauthenticated requests (`401`/`403`/`302`/`404`).
 *  2. For every cross-tenant `IsolationProbe` (from `app/isolation.php` or passed directly), Tenant B
 *     and unauthenticated callers are blocked AND receive zero bytes of Tenant A's canary markers.
 */
final readonly class TenantIsolationProver
{
    /**
     * @param list<IsolationProbe>|null $probes Optional explicit cross-tenant probes (defaults to `app/isolation.php` if present)
     * @return array{
     *     score: int,
     *     passed: bool,
     *     protected_routes_probed: int,
     *     tenant_probes_executed: int,
     *     skipped_mutating_probes: int,
     *     leaks_detected: int,
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
    public function prove(string $appRoot, App $app, ?array $probes = null, bool $readOnly = false): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');

        $scenarios = $probes ?? $this->loadProbesFromApp($root, $app);

        /** @var list<AppFinding> $findings */
        $findings = [];
        $protectedCount = 0;
        $leaks = 0;

        // 1. Auto-discovered unauthenticated boundary proof on all `#[Authenticated]` / `#[Can]` routes
        foreach ($app->router()->routes() as $route) {
            if (!$this->isExplicitlyProtected($route)) {
                continue;
            }
            if (!in_array('GET', $route->methods, true)) {
                continue;
            }
            ++$protectedCount;
            $concretePath = (string) preg_replace('/\{[^}]+\}/', '999999', $route->path);
            $resp = $app->handle(Request::create('GET', $concretePath, ['Accept' => 'application/json']));
            $status = $resp->getStatusCode();
            if ($status >= 200 && $status < 300) {
                ++$leaks;
                $findings[] = new AppFinding(
                    code: 'KGN-ISOLATION-UNAUTH-EXPOSURE',
                    severity: 'error',
                    who: 'Kongono Multi-Tenant Isolation Prover',
                    what: sprintf('Protected route `GET %s` returned HTTP %d to an unauthenticated request.', $route->path, $status),
                    why: 'Routes guarded by `#[Authenticated]` or `#[Can]` must reject unauthenticated callers with HTTP 401, 403, 302, or 404.',
                    where: sprintf('GET %s (%s)', $route->path, $route->handlerLabel()),
                    howToFix: [
                        'Verify your authentication middleware runs before route dispatch and rejects missing `Principal` attributes.',
                    ],
                );
            }
        }

        // 2. Adversarial Cross-Tenant BOLA / IDOR proofs (Tenant A owner vs Tenant B attacker vs Unauthenticated)
        $probesExecuted = 0;
        $skippedMutating = 0;
        foreach ($scenarios as $probe) {
            if ($readOnly && !in_array(strtoupper(trim($probe->method)), ['GET', 'HEAD', 'OPTIONS'], true)) {
                ++$skippedMutating;
                continue;
            }
            ++$probesExecuted;

            // 2a. Unauthenticated attack
            $unauthResp = $app->handle($this->buildRequest($probe, null));
            $unauthStatus = $unauthResp->getStatusCode();
            $unauthBody = (string) $unauthResp->getBody();
            $unauthLeaked = $this->findLeakedCanaries($unauthBody, $unauthResp->getHeaders(), $probe->canaries);
            if (($unauthStatus >= 200 && $unauthStatus < 300) || [] !== $unauthLeaked) {
                ++$leaks;
                $findings[] = new AppFinding(
                    code: 'KGN-ISOLATION-UNAUTH-EXPOSURE',
                    severity: 'error',
                    who: sprintf('Kongono Multi-Tenant Isolation Prover (%s)', $probe->name),
                    what: sprintf(
                        'Unauthenticated `%s %s` returned HTTP %d%s.',
                        $probe->method,
                        $probe->path,
                        $unauthStatus,
                        [] !== $unauthLeaked ? sprintf(' and leaked %d tenant canary token(s)', count($unauthLeaked)) : '',
                    ),
                    why: 'Tenant resources must never be readable or mutable without a verified authenticated Principal.',
                    where: sprintf('%s %s', $probe->method, $probe->path),
                    howToFix: [
                        'Add `#[Authenticated]` and verify tenant ownership (`($principal->attributes[\'tenant_id\'] ?? null) === $record->tenantId`) before returning data.',
                    ],
                );
            }

            // 2b. Cross-tenant BOLA / IDOR attack (Tenant B accessing Tenant A's resource!)
            $attackerResp = $app->handle($this->buildRequest($probe, $probe->attackerPrincipal));
            $attackerStatus = $attackerResp->getStatusCode();
            $attackerBody = (string) $attackerResp->getBody();
            $leakedCanaries = $this->findLeakedCanaries($attackerBody, $attackerResp->getHeaders(), $probe->canaries);

            if (($attackerStatus >= 200 && $attackerStatus < 300) || [] !== $leakedCanaries) {
                ++$leaks;
                $findings[] = new AppFinding(
                    code: 'KGN-ISOLATION-TENANT-LEAK',
                    severity: 'error',
                    who: sprintf('Kongono Multi-Tenant Isolation Prover (%s)', $probe->name),
                    what: sprintf(
                        'CRITICAL CROSS-TENANT LEAK (BOLA/IDOR): Attacker `%s` (tenant `%s`) accessed `%s %s` belonging to `%s` (tenant `%s`) — HTTP %d%s.',
                        $probe->attackerPrincipal->id,
                        $this->principalTenant($probe->attackerPrincipal),
                        $probe->method,
                        $probe->path,
                        $probe->ownerPrincipal->id,
                        $this->principalTenant($probe->ownerPrincipal),
                        $attackerStatus,
                        [] !== $leakedCanaries ? sprintf(' (leaked %d canary marker(s))', count($leakedCanaries)) : '',
                    ),
                    why: 'OWASP API #1 (Broken Object-Level Authorization): Tenant B can read or mutate Tenant A\'s data by passing Tenant A\'s resource ID.',
                    where: sprintf('%s %s', $probe->method, $probe->path),
                    howToFix: [
                        'Scope EVERY database query by the authenticated tenant (`->where(\'tenant_id\', \'=\', $principal->attributes[\'tenant_id\'] ?? null)`) or wrap queries in `RowLevelSecurity::withTenant(...)`.',
                        'Return HTTP 404 or 403 whenever a requested record belongs to another tenant.',
                    ],
                    codeFix: '$row = $db->table(\'projects\')->where(\'id\', \'=\', $id)->where(\'tenant_id\', \'=\', $principal->attributes[\'tenant_id\'] ?? null)->first();',
                );
            }
        }

        return [
            'score' => max(0, 100 - ($leaks * 25)),
            'passed' => 0 === $leaks,
            'protected_routes_probed' => $protectedCount,
            'tenant_probes_executed' => $probesExecuted,
            'skipped_mutating_probes' => $skippedMutating,
            'leaks_detected' => $leaks,
            'findings' => array_map(static fn(AppFinding $f): array => $f->toArray(), $findings),
        ];
    }

    private function principalTenant(Principal $principal): string
    {
        $val = $principal->attributes['tenant_id'] ?? $principal->attributes['org_id'] ?? null;

        return is_scalar($val) ? (string) $val : 'none';
    }

    private function buildRequest(IsolationProbe $probe, ?Principal $principal): Request
    {
        $headers = $probe->headers;
        $headers['Accept'] ??= 'application/json';
        $req = [] !== $probe->body
            ? Request::json($probe->method, $probe->path, $probe->body, $headers)
            : Request::create($probe->method, $probe->path, $headers);

        if (null !== $principal) {
            $req = $req->withAttribute(Principal::class, $principal);
        }

        return $req;
    }

    /**
     * @param array<string, array<string>> $headers
     * @param list<string>                 $canaries
     * @return list<string>
     */
    private function findLeakedCanaries(string $body, array $headers, array $canaries): array
    {
        if ([] === $canaries) {
            return [];
        }
        $haystack = $body;
        foreach ($headers as $vals) {
            $haystack .= "\n" . implode(' ', $vals);
        }
        $found = [];
        foreach ($canaries as $c) {
            if ('' !== $c && str_contains($haystack, $c)) {
                $found[] = $c;
            }
        }

        return $found;
    }

    /** @return list<IsolationProbe> */
    private function loadProbesFromApp(string $root, App $app): array
    {
        $file = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'isolation.php';
        if (!is_file($file)) {
            return [];
        }

        try {
            $loaded = require $file;
            if ($loaded instanceof Closure) {
                $loaded = $loaded($app);
            }
            if (!is_array($loaded)) {
                return [];
            }
            $out = [];
            foreach ($loaded as $item) {
                if ($item instanceof IsolationProbe) {
                    $out[] = $item;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    private function isExplicitlyProtected(Route $route): bool
    {
        foreach ($route->routeMiddlewareAttributes() as $attr) {
            if ($attr instanceof Authenticated || $attr instanceof Can) {
                return true;
            }
        }
        if (is_array($route->handler)) {
            try {
                $rc = new ReflectionClass($route->handler[0]);
                foreach ($rc->getAttributes() as $a) {
                    if (Authenticated::class === $a->getName() || Can::class === $a->getName()) {
                        return true;
                    }
                }
                if ($rc->hasMethod($route->handler[1])) {
                    foreach ($rc->getMethod($route->handler[1])->getAttributes() as $a) {
                        if (Authenticated::class === $a->getName() || Can::class === $a->getName()) {
                            return true;
                        }
                    }
                }
            } catch (Throwable) {
                return false;
            }
        } elseif ($route->handler instanceof Closure) {
            try {
                $rf = new ReflectionFunction($route->handler);
                foreach ($rf->getAttributes() as $a) {
                    if (Authenticated::class === $a->getName() || Can::class === $a->getName()) {
                        return true;
                    }
                }
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }
}
