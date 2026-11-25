---
name: kongono-self-verifying-proofs
description: >-
  Closed-loop verification workflow for Kongono Framework v1.20+ (PHP 8.5+):
  unified 5-pillar readiness proofs (`kongo prove`), 17-rule auto-fixing
  (`kongo diagnose --fix`), executable architecture invariants (`app/invariants.php`),
  least-privilege route capabilities (`#[ReadOnlyDb]`, `#[NoOutboundHttp]`,
  `#[MaxQueries]`, `#[MaxAiCost]`), persistent-worker leak & N+1 proofs (`kongo perf`),
  multi-tenant isolation (`kongo prove:isolation`), Production Incident Time-Machine
  (`kongo incident:replay|test`), semantic impact/shadow diffs, and 26-tool MCP loop.
  Use before merging PRs, after refactoring, when fixing production incidents, or
  when validating any AI-generated Kongono code.
---

# Kongono Self-Verifying Proofs & Closed-Loop Verification

Kongono proves correctness mathematically instead of relying on manual review. Always close the loop with these tools after editing a Kongono application.

## Step 1: Prefer Live MCP Tools When Connected

When `.vscode/mcp.json` or `.mcp.json` (`php vendor/bin/kongo mcp`) is available, call:
- `kongono_prove_all` — run all 5 verification pillars in one Readiness Certificate (`A+`..`F`).
- `kongono_diagnose` — run the 17-rule static & full-stack contract inspector.
- `kongono_policy_check` — evaluate `app/invariants.php` architecture & security rules.
- `kongono_perf_probe` — prove zero persistent-worker leaks and zero SQL N+1 regressions.
- `kongono_prove_isolation` — prove multi-tenant & auth BOLA/IDOR boundaries.
- `kongono_incident_replay` — replay a recorded `KGN-*` production incident in-process.
- `kongono_impact` / `kongono_shadow_diff` / `kongono_upgrade_plan` — check blast radius, behavioral equivalence, and PHP 8.5 codemod hygiene.

## Step 2: Declare Least-Privilege Route Capabilities (`Kongono\Security\Attributes`)

Mount `Kongono\Security\Middleware\CapabilityMiddleware` in `app/bootstrap.php` and decorate routes with the minimum runtime capabilities they need:
- `#[ReadOnlyDb]` — blocks `INSERT`/`UPDATE`/`DELETE`/`DDL` statements pre-execution at `PdoConnection`.
- `#[NoOutboundHttp]` — blocks outbound `SafeHttpClient` requests from that route.
- `#[MaxQueries(N)]` — enforces a hard per-request SQL query ceiling to prevent N+1 regressions.
- `#[MaxAiCost(maxUsd: 0.05)]` — caps AI provider spend per request.

## Step 3: Encode Team Rules in `app/invariants.php`

Express non-negotiable architectural and security policies as executable PHP rules:
```php
<?php

declare(strict_types=1);

use Kongono\Cli\Policy\Invariant;
use Kongono\Security\Attributes\Authenticated;

return [
    Invariant::routesMatching('/api/admin/*', 'KGN-POLICY-ADMIN')
        ->mustHaveAttribute(Authenticated::class)
        ->describe('Admin API endpoints require authentication.'),
    Invariant::routesMatching('/webhooks/*', 'KGN-POLICY-WEBHOOK')
        ->mustVerifyWebhookSignature()
        ->describe('Inbound webhooks must verify cryptographic signatures.'),
];
```
Verify with `php vendor/bin/kongo policy:check`.

## Step 4: Turn Production 5xx Errors Into Regression Tests

1. Enable `$app->recordIncidents()` in `app/bootstrap.php` (captures secret-scrubbed 5xx payloads to `storage/incidents/*.json`, never leaking secrets to end users when `APP_DEBUG=false`).
2. Reproduce and lock down fixes offline in-process:
   ```bash
   php vendor/bin/kongo incident:list
   php vendor/bin/kongo incident:show <KGN-REF>
   php vendor/bin/kongo incident:replay <KGN-REF>
   php vendor/bin/kongo incident:test <KGN-REF>
   ```

## Step 5: Prove Refactors Safely (Blast Radius & Behavioral Equivalence)

Before a large refactor, record a baseline snapshot and behavioral shadow corpus, then verify zero regressions afterward:
```bash
php vendor/bin/kongo snapshot --out=storage/baseline.json
php vendor/bin/kongo shadow:record --out=storage/golden.json
# ... perform refactor ...
php vendor/bin/kongo impact --against=storage/baseline.json
php vendor/bin/kongo shadow:diff --against=storage/golden.json
php vendor/bin/kongo upgrade --plan
```

## Final Verification Gate (Required Before Finishing Any Change)

Run and confirm **Grade A+ (100/100 PASS)** and zero errors:
```bash
php vendor/bin/kongo diagnose --fix
php vendor/bin/kongo prove
php vendor/bin/kongo watch --once
php vendor/bin/kongo test
```
