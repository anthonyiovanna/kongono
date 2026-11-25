<?php

declare(strict_types=1);

namespace Kongono\Cli\Proof;

use Kongono\Cli\Diagnostics\IncidentReplayer;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Impact\AppSnapshot;
use Kongono\Cli\Perf\PerfProver;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Security\TenantIsolationProver;
use Kongono\Cli\Upgrade\UpgradeEngine;
use Kongono\Http\App;

/**
 * Unified Master Proof Engine (`kongo prove`, MCP `kongono_prove_all`, & `/_kongono` Proof Command Center).
 *
 * Runs all 5 deterministic Kongono proof engines in one pass (17-rule Full-Stack Contract Prover,
 * Executable Architecture & Security Policy, Multi-Tenant Isolation, Persistent-Worker Leak & N+1,
 * and PHP 8.5 Upgrade Hygiene) and emits a single Readiness Certificate + Route Capability Matrix.
 */
final readonly class MasterProver
{
    /**
     * @return array{
     *     app_name: string,
     *     app_root: string,
     *     proven_at: string,
     *     elapsed_ms: float,
     *     overall_score: int,
     *     grade: 'A+'|'A'|'B'|'C'|'F',
     *     passed: bool,
     *     total_errors: int,
     *     total_warnings: int,
     *     fixable_count: int,
     *     pillars: array{
     *         diagnose: array{label: string, score: int, passed: bool, summary: string, findings: list<array<string, mixed>>},
     *         policy: array{label: string, score: int, passed: bool, summary: string, findings: list<array<string, mixed>>},
     *         isolation: array{label: string, score: int, passed: bool, summary: string, findings: list<array<string, mixed>>},
     *         perf: array{label: string, score: int, passed: bool, summary: string, findings: list<array<string, mixed>>},
     *         upgrade: array{label: string, score: int, passed: bool, summary: string, pending_transforms: int}
     *     },
     *     route_capabilities: list<array{
     *         signature: string,
     *         handler: string,
     *         attributes: list<string>,
     *         dto: ?string
     *     }>,
     *     incidents: list<array<string, mixed>>
     * }
     */
    public function proveAll(string $appRoot, ?App $app = null, int $perfIterations = 2, float $maxMs = 250.0): array
    {
        $t0 = hrtime(true);
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');

        $diag = (new KongonoAppInspector())->inspect($root, $app);
        $policy = (new PolicyVerifier())->verify($root, $app);
        $upgrade = (new UpgradeEngine())->run($root, apply: false);

        if (null !== $app) {
            $isolation = (new TenantIsolationProver())->prove($root, $app);
            $perf = (new PerfProver())->prove($app, iterations: max(1, $perfIterations), maxMs: $maxMs);
            $snapshot = AppSnapshot::capture($root, $app);
            $appName = $snapshot->appName;
        } else {
            $noAppFinding = [
                'code' => 'KGN-PROVE-NO-APP',
                'severity' => 'error',
                'who' => 'Kongono Master Prover',
                'what' => 'Application could not be booted, so runtime isolation and performance proofs were NOT executed.',
                'why' => 'Fail-closed policy prevents unverified applications from receiving a passing readiness score.',
                'where' => $root,
                'file' => null,
                'line' => null,
                'fixable' => false,
                'how_to_fix' => ['Ensure app/bootstrap.php exists and returns a runnable Kongono\\Http\\App instance.'],
                'code_fix' => null,
                'command' => 'kongo doctor',
                'did_you_mean' => [],
            ];
            $isolation = [
                'score' => 0,
                'passed' => false,
                'protected_routes_probed' => 0,
                'tenant_probes_executed' => 0,
                'leaks_detected' => 0,
                'findings' => [$noAppFinding],
            ];
            $perf = [
                'score' => 0,
                'passed' => false,
                'routes_probed' => 0,
                'total_queries' => 0,
                'n_plus_one_detected' => 0,
                'leaks_detected' => 0,
                'findings' => [$noAppFinding],
            ];
            $snapshot = null;
            $appName = basename($root);
        }

        $upgradeScore = 0 === $upgrade['transforms_applied'] ? 100 : max(60, 100 - ($upgrade['transforms_applied'] * 10));

        $pillars = [
            'diagnose' => [
                'label' => 'Self-Diagnosis & Full-Stack Contracts (17 Rules)',
                'score' => $diag['score'],
                'passed' => $diag['passed'],
                'summary' => sprintf(
                    '%d route(s), %d view(s), %d PHP file(s) — %d error(s), %d warning(s)',
                    $diag['routes_checked'],
                    $diag['views_checked'],
                    $diag['php_files_checked'],
                    $diag['counts']['error'],
                    $diag['counts']['warning'],
                ),
                'findings' => $diag['findings'],
            ],
            'policy' => [
                'label' => 'Architecture & Security Invariants',
                'score' => $policy['score'],
                'passed' => $policy['passed'],
                'summary' => sprintf(
                    '%d rule(s) evaluated (%s) — %d violation(s)',
                    $policy['rules_evaluated'],
                    $policy['custom_policy_loaded'] ? 'defaults + custom' : 'built-in defaults',
                    count($policy['violations']),
                ),
                'findings' => $policy['violations'],
            ],
            'isolation' => [
                'label' => 'Multi-Tenant & Auth Isolation',
                'score' => $isolation['score'],
                'passed' => $isolation['passed'],
                'summary' => sprintf(
                    '%d protected route(s), %d tenant probe(s) — %d leak(s)',
                    $isolation['protected_routes_probed'],
                    $isolation['tenant_probes_executed'],
                    $isolation['leaks_detected'],
                ),
                'findings' => $isolation['findings'],
            ],
            'perf' => [
                'label' => 'Persistent-Worker Leak & N+1 Proof',
                'score' => $perf['score'],
                'passed' => $perf['passed'],
                'summary' => sprintf(
                    '%d route(s) probed — %d SQL N+1, %d leak(s)',
                    $perf['routes_probed'],
                    $perf['n_plus_one_detected'],
                    $perf['leaks_detected'],
                ),
                'findings' => $perf['findings'],
            ],
            'upgrade' => [
                'label' => 'PHP 8.5 & Codemod Hygiene',
                'score' => $upgradeScore,
                'passed' => 0 === $upgrade['transforms_applied'],
                'summary' => sprintf(
                    '%d file(s) scanned — %d codemod transform(s) pending',
                    $upgrade['files_scanned'],
                    $upgrade['transforms_applied'],
                ),
                'pending_transforms' => $upgrade['transforms_applied'],
            ],
        ];

        $overallScore = (int) round(
            ($pillars['diagnose']['score'] * 0.30)
            + ($pillars['policy']['score'] * 0.25)
            + ($pillars['isolation']['score'] * 0.20)
            + ($pillars['perf']['score'] * 0.15)
            + ($pillars['upgrade']['score'] * 0.10),
        );

        $totalErrors = $diag['counts']['error'] + $policy['counts']['error'] + $isolation['leaks_detected'];
        $totalWarnings = $diag['counts']['warning'] + $policy['counts']['warning'];
        foreach ($perf['findings'] as $pf) {
            if ('error' === $pf['severity']) {
                ++$totalErrors;
            } else {
                ++$totalWarnings;
            }
        }

        $passed = $diag['passed'] && $policy['passed'] && $isolation['passed'] && $perf['passed'];
        $grade = match (true) {
            $passed && 100 === $overallScore && 0 === $totalWarnings => 'A+',
            $passed && $overallScore >= 90 => 'A',
            $passed && $overallScore >= 80 => 'B',
            $overallScore >= 65 => 'C',
            default => 'F',
        };

        $routeCapabilities = [];
        if (null !== $snapshot) {
            foreach ($snapshot->routes as $sig => $info) {
                $shortAttrs = array_map(
                    static fn(string $a): string => str_contains($a, '\\') ? substr($a, (int) strrpos($a, '\\') + 1) : $a,
                    $info['attributes'],
                );
                $dtoClass = null !== $info['dto']
                    ? (str_contains($info['dto']['class'], '\\') ? substr($info['dto']['class'], (int) strrpos($info['dto']['class'], '\\') + 1) : $info['dto']['class'])
                    : null;
                $routeCapabilities[] = [
                    'signature' => $sig,
                    'handler' => $info['handler'],
                    'attributes' => $shortAttrs,
                    'dto' => $dtoClass,
                ];
            }
        }

        $store = (new IncidentReplayer())->resolveStore($root, $app);
        $incidents = array_map(static fn($r): array => $r->toArray(), $store->recent(15));

        return [
            'app_name' => $appName,
            'app_root' => str_replace('\\', '/', $root),
            'proven_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'elapsed_ms' => round((hrtime(true) - $t0) / 1_000_000, 2),
            'overall_score' => $overallScore,
            'grade' => $grade,
            'passed' => $passed,
            'total_errors' => $totalErrors,
            'total_warnings' => $totalWarnings,
            'fixable_count' => $diag['fixable_count'],
            'pillars' => $pillars,
            'route_capabilities' => $routeCapabilities,
            'incidents' => $incidents,
        ];
    }
}
