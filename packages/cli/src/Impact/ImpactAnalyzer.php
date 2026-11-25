<?php

declare(strict_types=1);

namespace Kongono\Cli\Impact;

use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Attributes\Can;
use Kongono\Security\Attributes\MaxAiCost;
use Kongono\Security\Attributes\MaxQueries;
use Kongono\Security\Attributes\NoOutboundHttp;
use Kongono\Security\Attributes\ReadOnlyDb;
use Kongono\Security\Attributes\VerifyWebhook;

/**
 * Semantic Blast-Radius Diff Engine (`kongo impact` & MCP `kongono_impact`).
 *
 * Compares a baseline `AppSnapshot` against the current live Kongono app to report
 * security regressions, breaking API/DTO changes, removed routes/views, and additive changes.
 */
final readonly class ImpactAnalyzer
{
    /** @var list<string> */
    private const array SECURITY_ATTRIBUTES = [
        Authenticated::class,
        Can::class,
        VerifyWebhook::class,
        ReadOnlyDb::class,
        NoOutboundHttp::class,
        MaxQueries::class,
        MaxAiCost::class,
    ];

    /**
     * @return array{
     *     risk_level: 'none'|'low'|'high'|'critical',
     *     breaking_changes: int,
     *     security_regressions: int,
     *     routes_added: list<string>,
     *     routes_removed: list<string>,
     *     views_added: list<string>,
     *     views_removed: list<string>,
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
    public function diff(AppSnapshot $baseline, AppSnapshot $current): array
    {
        /** @var list<AppFinding> $findings */
        $findings = [];
        $securityRegressions = 0;
        $breakingChanges = 0;

        // 1. Global security middleware removals
        foreach ($baseline->middleware as $mw) {
            if (
                !in_array($mw, $current->middleware, true)
                && 1 === preg_match('/(?:Csrf|Session|SecurityHeaders|RateLimit|Auth|Bearer)Middleware\z/i', $mw)
            ) {
                ++$securityRegressions;
                ++$breakingChanges;
                $findings[] = new AppFinding(
                    code: 'KGN-IMPACT-SECURITY-REGRESSION',
                    severity: 'error',
                    who: 'Kongono Semantic Blast-Radius Analyzer',
                    what: sprintf('Global security middleware `%s` was removed compared to the baseline snapshot.', $mw),
                    why: 'Removing a global security middleware weakens protection across every route in the application.',
                    where: 'app/bootstrap.php',
                    howToFix: [
                        sprintf('Restore `%s` in `$app->middleware(...)` or intentionally update the baseline snapshot via `kongo snapshot`.', $mw),
                    ],
                );
            }
        }

        // 2. Route removals, security/capability attribute regressions, and breaking DTO contract changes
        $routesRemoved = [];
        foreach ($baseline->routes as $sig => $oldRoute) {
            if (!isset($current->routes[$sig])) {
                $routesRemoved[] = $sig;
                ++$breakingChanges;
                $findings[] = new AppFinding(
                    code: 'KGN-IMPACT-ROUTE-REMOVED',
                    severity: 'error',
                    who: 'Kongono Semantic Blast-Radius Analyzer',
                    what: sprintf('Breaking change: Route `%s` (previously `%s`) was removed.', $sig, $oldRoute['handler']),
                    why: 'Existing browser links, forms, webhooks, or TypeScript/mobile SDK clients calling this endpoint will receive HTTP 404/405.',
                    where: $sig,
                    howToFix: [
                        sprintf('Restore `%s`, add a redirect/deprecation route, or regenerate client SDKs (`kongo sdk:generate`) and update baseline (`kongo snapshot`).', $sig),
                    ],
                );
                continue;
            }

            $newRoute = $current->routes[$sig];

            // 2a. Security / capability attribute regression on existing route
            foreach (self::SECURITY_ATTRIBUTES as $secAttr) {
                if (in_array($secAttr, $oldRoute['attributes'], true) && !in_array($secAttr, $newRoute['attributes'], true)) {
                    ++$securityRegressions;
                    ++$breakingChanges;
                    $short = substr($secAttr, (int) strrpos($secAttr, '\\') + 1);
                    $findings[] = new AppFinding(
                        code: 'KGN-IMPACT-SECURITY-REGRESSION',
                        severity: 'error',
                        who: 'Kongono Semantic Blast-Radius Analyzer',
                        what: sprintf('SECURITY REGRESSION: Route `%s` lost `#[%s]` since the baseline snapshot.', $sig, $short),
                        why: sprintf('Removing `#[%s]` relaxes authentication, authorization, or capability sandboxing on a live endpoint.', $short),
                        where: sprintf('%s (%s)', $sig, $newRoute['handler']),
                        howToFix: [
                            sprintf('Re-add `#[%s]` to `%s` before merging.', $short, $newRoute['handler']),
                        ],
                        codeFix: sprintf('#[%s]', $short),
                    );
                }
            }

            // 2b. Breaking DTO contract changes (field removed, type changed, or optional -> newly required)
            $oldDto = $oldRoute['dto'];
            $newDto = $newRoute['dto'];
            if (null !== $oldDto && null !== $newDto) {
                foreach ($oldDto['fields'] as $fieldName => $oldSpec) {
                    if (!isset($newDto['fields'][$fieldName])) {
                        ++$breakingChanges;
                        $findings[] = new AppFinding(
                            code: 'KGN-IMPACT-DTO-BREAKING',
                            severity: 'error',
                            who: 'Kongono Semantic Blast-Radius Analyzer (API / Form Contract)',
                            what: sprintf('Breaking DTO change on `%s`: field `%s` was removed from `%s`.', $sig, $fieldName, $newDto['class']),
                            why: 'Existing forms and SDK clients sending this field will silently lose data or fail validation.',
                            where: sprintf('%s (%s)', $sig, $newDto['class']),
                            howToFix: [
                                sprintf('Keep `%s` as an optional deprecated parameter on `%s`, or coordinate a versioned SDK update.', $fieldName, $newDto['class']),
                            ],
                        );
                        continue;
                    }
                    $newSpec = $newDto['fields'][$fieldName];
                    if ($oldSpec['type'] !== $newSpec['type']) {
                        ++$breakingChanges;
                        $findings[] = new AppFinding(
                            code: 'KGN-IMPACT-DTO-BREAKING',
                            severity: 'error',
                            who: 'Kongono Semantic Blast-Radius Analyzer (API / Form Contract)',
                            what: sprintf('Breaking DTO type change on `%s`: `%s` changed from `%s` to `%s`.', $sig, $fieldName, $oldSpec['type'], $newSpec['type']),
                            why: 'Clients sending the previous type will fail validation with HTTP 422.',
                            where: sprintf('%s (%s)', $sig, $newDto['class']),
                            howToFix: [
                                sprintf('Preserve `%s` compatibility or release a new API version.', $oldSpec['type']),
                            ],
                        );
                    } elseif (!$oldSpec['required'] && $newSpec['required']) {
                        ++$breakingChanges;
                        $findings[] = new AppFinding(
                            code: 'KGN-IMPACT-DTO-BREAKING',
                            severity: 'error',
                            who: 'Kongono Semantic Blast-Radius Analyzer (API / Form Contract)',
                            what: sprintf('Breaking DTO change on `%s`: previously optional field `%s` is now required.', $sig, $fieldName),
                            why: sprintf('Existing clients and forms omitting `%s` will now fail with HTTP 422.', $fieldName),
                            where: sprintf('%s (%s)', $sig, $newDto['class']),
                            howToFix: [
                                sprintf('Give `$%s` a safe default value on `%s` to remain backwards-compatible.', $fieldName, $newDto['class']),
                            ],
                        );
                    }
                }
                foreach ($newDto['fields'] as $fieldName => $newSpec) {
                    if (!isset($oldDto['fields'][$fieldName]) && $newSpec['required']) {
                        ++$breakingChanges;
                        $findings[] = new AppFinding(
                            code: 'KGN-IMPACT-DTO-BREAKING',
                            severity: 'error',
                            who: 'Kongono Semantic Blast-Radius Analyzer (API / Form Contract)',
                            what: sprintf('Breaking DTO change on `%s`: new required field `%s` added to `%s`.', $sig, $fieldName, $newDto['class']),
                            why: sprintf('Existing clients and forms that do not yet send `%s` will fail with HTTP 422.', $fieldName),
                            where: sprintf('%s (%s)', $sig, $newDto['class']),
                            howToFix: [
                                sprintf('Make `$%s` optional (with a default or nullable type) until all forms and SDK consumers are updated.', $fieldName),
                            ],
                        );
                    }
                }
            }
        }

        $routesAdded = [];
        foreach (array_keys($current->routes) as $sig) {
            if (!isset($baseline->routes[$sig])) {
                $routesAdded[] = $sig;
            }
        }

        $viewsRemoved = array_values(array_diff($baseline->views, $current->views));
        $viewsAdded = array_values(array_diff($current->views, $baseline->views));
        foreach ($viewsRemoved as $vr) {
            $findings[] = new AppFinding(
                code: 'KGN-IMPACT-VIEW-REMOVED',
                severity: 'warning',
                who: 'Kongono Semantic Blast-Radius Analyzer',
                what: sprintf('View template `views/%s.kgn.html` was removed since the baseline snapshot.', $vr),
                why: 'Any controller, `@extends`, `@include`, or `<x-...>` tag still referencing this template will fail at runtime.',
                where: sprintf('views/%s.kgn.html', $vr),
                howToFix: [
                    'Run `kongo diagnose` to prove zero controllers or templates still reference this view.',
                ],
            );
        }

        $riskLevel = 'none';
        if ($securityRegressions > 0) {
            $riskLevel = 'critical';
        } elseif ($breakingChanges > 0) {
            $riskLevel = 'high';
        } elseif ([] !== $routesAdded || [] !== $viewsAdded || [] !== $viewsRemoved) {
            $riskLevel = 'low';
        }

        return [
            'risk_level' => $riskLevel,
            'breaking_changes' => $breakingChanges,
            'security_regressions' => $securityRegressions,
            'routes_added' => $routesAdded,
            'routes_removed' => $routesRemoved,
            'views_added' => $viewsAdded,
            'views_removed' => $viewsRemoved,
            'findings' => array_map(static fn(AppFinding $f): array => $f->toArray(), $findings),
        ];
    }
}
