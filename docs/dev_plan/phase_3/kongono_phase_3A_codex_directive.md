# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3A
## Production HTTP Integration Milestone, Roadmap, Documentation Reconciliation, and Quality Stabilization

**Directive type:** architecture, documentation, and contributor-tooling stabilization only

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 3A

**Depends on:** accepted Phase 2H final checkpoint and populated final-closure report

---

## 1. Mission

Establish the bounded Phase 3 milestone and its dependency-ordered A-H roadmap
without implementing production HTTP integration.

Phase 2 is code-complete at its accepted checkpoint. Kongono has thirteen
acyclic production packages and a deterministic test-owned runnable fixture.
It does not yet provide a supported production SAPI profile, inbound production
request conversion, response emission, operator bootstrap configuration,
deployment entrypoint, or production integration acceptance proof.

Phase 3 targets the next honest milestone:

> Kongono's first supported synchronous production HTTP integration profile.

That milestone is smaller than a full framework, production-hardening claim,
or 1.0 release. It must connect a host-provided PHP web runtime to the existing
Web Runtime/Web Application path with explicit bounded input conversion, exact
response emission, fail-closed operator-owned trust inputs, deterministic
cleanup, deployment documentation, and external acceptance proof.

Phase 3A is architecture/documentation first. It must not select or implement a
specific SAPI, server, listener, package name, namespace, dependency edge, API,
request parser, emitter, configuration object, front controller, deployment
file, or production permission source. Those decisions begin only under later
authenticated subphase authority.

Phase 3A also performs two bounded housekeeping duties:

1. reconcile current documentation that still describes Phase 2H as future or
   says no runnable fixture exists; and
2. make the root PHPStan Composer script use an explicit contributor-only
   512 MB PHP process so ordinary `composer quality` no longer depends on a
   temporary INI overlay.

No production PHP, package test, dependency, lockfile, package manifest, or
runtime behavior changes in Phase 3A.

---

## 2. Locked doctrine and interpretation

Preserve every accepted Kongono doctrine and ADR unless this directive
expressly adds a new Phase 3 planning decision.

In particular:

- Kernel remains runtime/transport independent.
- Web Application remains the sole owner of one Kernel operation scope per
  accepted application call.
- Web Runtime remains the outer generic/trusted failure and send/close
  orchestration boundary.
- Request, response, route, permission, principal, configuration, exception,
  and operation state remain out of ambient global/static/singleton/current
  storage.
- Client-controlled request data cannot enable trusted diagnostics.
- Trusted presentation still requires both independent typed permissions.
- Denial occurs before diagnostic source access and never falls back to trusted
  output.
- Production and trusted failure output remain separate.
- Application Graph, Dependency Injection, diagnostics, API-status, package
  dependency direction, and lifecycle contracts remain unchanged.
- PSR interfaces are interoperability boundaries only where already accepted.
- No Symfony component, Laravel component, alternate framework kernel, or
  third-party application framework is introduced.
- Historical directives, reports, checkpoint commits, and point-in-time ADR
  text remain immutable evidence.

The new Phase 3 milestone is planned direction, not implemented functionality.
Every modified current document must distinguish:

```text
implemented now  Phase 2 fixture-runnable thirteen-package framework path
planned next     Phase 3 supported production HTTP integration profile
still future     broader framework capabilities, hardening, stable release
```

Do not describe Phase 3 as implemented, selected, deployable, production-ready,
stable, supported, or complete during Phase 3A.

---

## 3. Independent directive authentication

Before broad validation, editing, or any repository mutation, compare this
directive with the byte count and SHA-256 supplied independently by the
architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_3A_codex_directive.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

The caller must also provide:

```text
docs/task reports/Phase_3A.md
```

At entry it must be an ordinary, untracked, unstaged file absent from `HEAD`,
zero bytes, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every permitted non-report gate passes. Never stage or
commit it in Phase 3A.

---

## 4. Required authorities

Read completely before editing:

```text
AGENTS.md
README.md
ARCHITECTURE.md
SECURITY.md
composer.json
composer.lock
phpstan.neon.dist
.php-cs-fixer.dist.php
phpunit.xml.dist
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/engineering/toolchain.md
docs/engineering/package-layout.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/http-message-model.md
docs/architecture/http-server-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/architecture/routing-model.md
docs/architecture/phase-2-roadmap.md
docs/architecture/phase-2-runnable-fixture.md
docs/adr/README.md
docs/adr/0001-kongono-identity.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md
docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
docs/dev_plan/kongono_phase_3A_codex_directive.md
```

The filenames are exact. Do not substitute another ADR by number or inferred
topic.

Historical ADR statements such as “Phase 2H remains” may be accurate
point-in-time evidence inside their accepted decision context. Do not rewrite
those ADRs. Reconcile only the exact current documentation authorized in
Section 8 and record the current milestone in new ADR 0051.

---

## 5. Accepted baseline and caller records

### 5.1 Repository baseline before Phase 3A caller setup

Require:

```text
branch                 main
HEAD                   647053d4bddc4c76090bca9bf3d693faa5880c52
HEAD tree              3ad60da1dc535fc0b7a166ac8522f954d4c67fac
HEAD parent            d7846f137a1d53f5306f48d19196b916fde44ac2
commit count           10
tracked files          536
tracked modifications    0
staged paths              0
untracked paths           1
total status paths        1
package directories      13
package manifests        13
production PHP files    100
test PHP files          130
style-covered PHP       230
```

The sole untracked path must be:

```text
docs/task reports/Phase_2H_final_closure.md
```

Verify it is ordinary, untracked, unstaged, absent from `HEAD`, and exactly:

```text
bytes       16240
SHA-256     F187072B7B69327BB2129152AFF1114B36F6D2611A4E986CB06F95955341C245
```

Verify the checkpoint subject is exactly:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require one local branch, zero remotes, zero tags, and no unexpected
repository-owned refs. Enumerate environment-managed
`refs/codex/turn-diffs/**` separately and do not mutate them.

Because all 536 tracked paths must equal `HEAD`, the authenticated commit/tree
identity is the tracked-content baseline. Do not reconstruct a synthetic
pre-Phase-2 manifest or hash generated caches.

### 5.2 Exact locked file identities

Require:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `composer.json` | 4262 | `17A365F0D4C006EF61BC6EB87F947F8C67BBC761A7F494FD3B892EC68CB2E5A5` |
| `composer.lock` | 196324 | `28B03AFDC8FF9C6F2F754E65B97C867E499BAD2D4427E0F1881FBBA88E1E6862` |
| `docs/engineering/toolchain.md` | 2340 | `499C2267B431D918521A7A7261151711D0AF4CF9B2611230262F32AFC9ACC664` |
| `docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md` | 24888 | `3E81A2F99CF02CDD9A8F3B0A3D774636E507E9FE0218C0A9A28736CF955EE889` |
| `docs/task reports/Phase_2H_final_closure.md` | 16240 | `F187072B7B69327BB2129152AFF1114B36F6D2611A4E986CB06F95955341C245` |

### 5.3 Entry after Phase 3A caller setup

After the caller adds only the authenticated Phase 3A directive and zero-byte
report, require:

```text
tracked modifications  0
staged paths            0
untracked paths         3
total status paths      3
tracked files         536
```

The exact untracked paths are:

```text
docs/dev_plan/kongono_phase_3A_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
docs/task reports/Phase_3A.md
```

Stop on a real content, path, identity, mode, stream, history, index, or caller
record mismatch. A read-only diagnostic command with a parser, quoting, or
checker defect may be simplified and rerun if the repository is first proven
unchanged; record that procedural correction rather than treating it as a
framework failure.

---

## 6. Phase 3 milestone decision

Create an Accepted architecture decision defining Phase 3 as the bounded path
from fixture-runnable Kongono to its first supported synchronous production HTTP
integration profile.

The Phase 3 finish line must require all of these, without implementing them in
Phase 3A:

1. One explicitly selected host-provided synchronous PHP web execution profile.
2. One outermost production boundary that captures only the selected raw host
   inputs and converts them into the existing first-party HTTP message model.
3. Explicit syntax, normalization, duplication, count, byte, body, stream,
   upload, cookie, query, header, and invalid-input policies before parsing code.
4. One exact response emission contract covering status, headers, body,
   duplicate header values, committed-output uncertainty, and cleanup.
5. One immutable operator-owned bootstrap profile parsed and validated before
   serving, without introducing a framework-wide configuration system.
6. Independent trusted-bootstrap and current-delivery permission sources that
   cannot be upgraded by client input.
7. Existing Web Runtime/Web Application/Kernel ownership preserved exactly.
8. Fixed generic failure output when trusted presentation is ineligible or any
   production trust input is absent, invalid, ambiguous, or failed.
9. A minimal supported production entry composition with no ambient framework
   current-request/service-locator state.
10. External acceptance proof through the selected production integration
    boundary, including success, unmatched, generic failure, trusted/denied
    diagnostics, malformed input, partial/failed emission, cleanup, and repeated
    operation isolation where the selected host permits it.
11. Deployment and security documentation that states exact prerequisites and
    limitations without claiming broader production hardening.
12. A Phase 3 checkpoint only after all planned subphases pass.

The milestone must explicitly remain smaller than:

- a first-party socket listener or process manager;
- support for every PHP SAPI/server/runtime;
- proxy/TLS termination implementation;
- production authentication/authorization;
- sessions, CSRF, templates, frontend, database, ORM, migrations, cache, queue,
  events, mail, scheduler, realtime, telemetry, agents, extensions, or CLI;
- a general configuration framework or secrets manager;
- automatic incident recovery or uncertain-runtime reuse;
- a supported application generator/template ecosystem;
- Stable API, semantic-versioning, 1.0, full-framework, feature-parity, or
  production-hardening certification.

---

## 7. Fixed Phase 3 A-H roadmap

Document this dependency-ordered roadmap:

| Phase | Boundary |
|---|---|
| `3A` | Accept the production HTTP integration milestone, security/persistent-runtime gates, A-H roadmap, current-status reconciliation, and stable contributor PHPStan memory command. No production runtime implementation. |
| `3B` | Architecture-only selection of the exact synchronous PHP host profile, raw-input boundary, package ownership, dependency direction, API status, limits, invalid-input behavior, and acceptance surface. |
| `3C` | Implement only the accepted bounded inbound host-snapshot-to-first-party-server-request conversion and production tests. |
| `3D` | Implement only the accepted bounded response-emission boundary, committed-output uncertainty rules, and production tests. |
| `3E` | Implement only the accepted immutable operator bootstrap profile and fail-closed trusted bootstrap/current-delivery input composition; no general configuration system. |
| `3F` | Implement only the supported production composition/entry boundary using the existing Web Runtime and Web Application ownership. |
| `3G` | Add bounded integration hardening, failure/cleanup behavior, deployment/security documentation, and exact selected-profile operational constraints. |
| `3H` | Run external production-profile acceptance, reconcile documentation, and create the Phase 3 checkpoint. |

Lock this order. Later work cannot outrun the decisions it depends on.

Phase 3A does not choose exact package names, namespaces, dependencies, classes,
methods, constructors, diagnostics, configuration keys, environment variables,
host globals, server software, deployment files, or status/header/body mapping.
Phase 3B must make those choices explicitly or defer them further.

Main phases do not automatically continue to `Z`; Phase 3 ends at `3H` unless
an architect-approved ADR changes the roadmap.

---

## 8. Exact authorized file boundary

### 8.1 Create exactly two documentation paths

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/architecture/phase-3-roadmap.md
```

### 8.2 Modify exactly sixteen existing paths

```text
composer.json
README.md
ARCHITECTURE.md
SECURITY.md
docs/doctrine.md
docs/glossary.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/http-message-model.md
docs/architecture/routing-model.md
docs/architecture/phase-2-roadmap.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
packages/kernel/README.md
packages/http-server/README.md
packages/diagnostic-handler/README.md
```

### 8.3 Closed outside scope

Do not create, modify, delete, rename, normalize, restore, or regenerate any
other repository path.

In particular, freeze:

- all `packages/**/src/**/*.php` production files;
- all PHP test/fixture files;
- every package `composer.json`;
- `composer.lock`;
- `phpstan.neon.dist`, `.php-cs-fixer.dist.php`, and `phpunit.xml.dist`;
- all historical directives and reports;
- ADRs 0001 through 0050; and
- Git index, commits, refs, branches, tags, remotes, hooks, and configuration.

No staging or commit is authorized in Phase 3A.

---

## 9. ADR 0051 requirements

Create:

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
```

Use:

```text
Status: Accepted
Date: 2026-08-20
```

Include at least:

```text
Context
Decision
Phase 3 finish line
Fixed A-H sequence
Security invariants
Persistent-runtime and cleanup invariants
Consequences
Rejected alternatives
What remains open
Related material
```

Record:

- Phase 2 checkpoint identity and fixture-runnable meaning;
- why the next milestone is a supported production integration profile rather
  than ORM/auth/frontend/agent breadth;
- why host integration remains an outer leaf and cannot enter Kernel;
- the twelve finish-line requirements in Section 6;
- the exact A-H roadmap in Section 7;
- no concrete host/SAPI/package/API decision in 3A;
- the contributor-only PHPStan memory stabilization is not runtime architecture;
- the residual current-document progress labels are corrected in 3A while
  historical records remain unchanged;
- every prohibited capability and non-guarantee; and
- all decisions still open for 3B and later.

Rejected alternatives must cover at least:

- immediately adding a first-party network listener/process manager;
- importing Symfony HttpKernel, Laravel runtime components, or another
  framework integration layer;
- implementing raw `$_SERVER`/superglobal parsing before limits and ownership;
- implementing an emitter before committed-output failure semantics;
- using request flags to enable trusted diagnostics;
- introducing ambient current-request/container state;
- implementing a general configuration framework in place of one bounded
  operator bootstrap profile;
- claiming production readiness after a single adapter; and
- jumping directly to ORM, authentication, frontend, agents, or ecosystem
  features before a supported production edge exists.

---

## 10. Phase 3 roadmap requirements

Create:

```text
docs/architecture/phase-3-roadmap.md
```

Include:

- purpose and exact milestone boundary;
- current starting point after Phase 2;
- fixed A-H table;
- dependency graph;
- end-of-Phase-3 external acceptance checklist;
- security gates for every subphase;
- persistent-runtime and cleanup gates;
- current versus planned behavior table;
- deliberate exclusions;
- correction/subphase policy;
- relationship to the test-owned Phase 2 fixture;
- relationship to Web Runtime/Web Application/Kernel ownership;
- relationship to future full-framework/product milestones; and
- links to ADR 0051, ADRs 0043/0049/0050, the Phase 2 roadmap, runtime models,
  security policy, doctrine, and package layout.

The external acceptance checklist must include at least:

1. selected host-profile invocation reaches the public production entry;
2. bounded raw input becomes one first-party server request;
3. malformed/oversized/ambiguous input fails deterministically before the
   application path where required;
4. one accepted input becomes one Web Application call and one Kernel scope;
5. routing/middleware/handler behavior remains deterministic;
6. one response is emitted with exact status/header/body behavior;
7. partial or committed-output failure follows explicit fail-closed semantics;
8. generic failures expose no sensitive data;
9. trusted diagnostics require both non-client-controlled permissions;
10. bootstrap/delivery denial occurs before protected source access;
11. cleanup occurs exactly once on success and failure;
12. repeated operations do not leak request/permission/response/failure state;
13. no client input changes operator configuration or trust;
14. deployment prerequisites and unsupported environments are explicit; and
15. tests exercise the real package path from outside internal packages.

Do not invent the exact expected status codes, header policies, raw input keys,
configuration names, or server process commands. Those remain 3B decisions.

---

## 11. Current-document reconciliation

Update only the authorized current documentation so it consistently states:

- Phase 2A-H is complete at commit
  `647053d4bddc4c76090bca9bf3d693faa5880c52`;
- Kongono is fixture-runnable through test-owned black-box infrastructure;
- exactly thirteen production packages exist;
- no production SAPI/server/listener/emitter or supported deployment profile is
  implemented;
- Phase 3A plans the next production-integration milestone without implementing
  it;
- Phase 3 is not a full-framework or production-ready claim; and
- historical ADR/directive/report wording remains point-in-time evidence.

Correct stale present-tense/current-status claims including:

```text
not yet a runnable web framework
Phase 2H remains ...
Phase 2H is next
no runnable fixture exists
no runnable application exists
runnable web behavior remains unimplemented
```

Do not mechanically delete every historical use of those phrases. Correct them
only in the sixteen current paths authorized by Section 8. Historical ADRs and
caller records remain unchanged.

Specific reconciliation duties:

- `README.md`: current status, fixture-runnable command/link, Phase 3 planned
  boundary, and non-production caveat;
- `ARCHITECTURE.md`: current thirteen-package plus fixture topology and planned
  production edge;
- `SECURITY.md`: Phase 2 security proof versus unimplemented production trust,
  parsing, proxy/TLS, authentication, operations, and hardening;
- `docs/doctrine.md`: Phase 2 complete, fixture ownership, Phase 3 locked
  direction without selection;
- `docs/glossary.md`: current entries no longer describe Phase 2H as future;
- `docs/adr/README.md`: add ADR 0051 and replace “Current Phase 2 decision” with
  current milestone context including Phase 3A;
- architecture models: package-local statements remain precise while global
  current-state claims recognize the root-owned fixture;
- package READMEs: distinguish what the package itself does not own from the
  completed root-owned fixture and planned production edge;
- package layout: retain exactly thirteen packages, record no Phase 3 package
  choice, and link the new roadmap; and
- Phase 2 roadmap: retain historical A-H evidence, state Phase 2 is checkpointed,
  and link forward to the separately authorized Phase 3 roadmap.

Do not claim that the Phase 2 fixture is a production application or supported
template.

---

## 12. Contributor PHPStan memory stabilization

### 12.1 Exact Composer script change

In root `composer.json`, replace exactly:

```json
"analyse": "phpstan analyse --configuration=phpstan.neon.dist --no-progress",
```

with:

```json
"analyse": "@php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress",
```

Require the old string exactly once before and zero times after. Require the new
string zero times before and exactly once after.

Do not change any other manifest key, dependency, repository, platform,
security policy, plugin policy, autoload mapping, package validation command,
test command, style command, audit command, or quality order.

Do not run `composer update`, `composer install`, dependency resolution, or
lockfile regeneration. `composer.lock` must remain byte-identical to the
identity in Section 5.

The 512 MB value is:

- an explicit upper limit for the contributor PHPStan child process only;
- used by both `composer analyse` and `composer quality`;
- cross-platform through Composer's `@php` executable resolution;
- not an application/runtime memory setting;
- not a PHP INI scan-directory overlay;
- not inherited framework configuration;
- not permission to increase another tool/process limit; and
- not permission for unlimited memory.

### 12.2 Toolchain documentation

Update `docs/engineering/toolchain.md` to:

- list all thirteen package validation commands accurately by package role;
- explain the fixed contributor-only 512 MB PHPStan process limit;
- distinguish contributor analysis memory from Kongono runtime policy;
- state that ordinary `composer analyse` and `composer quality` need no
  temporary INI overlay;
- retain the exact fail-closed audit/platform/style/analysis/test order;
- retain prohibition on baselines, suppressions, exclusions, skips, platform
  ignores, or policy weakening; and
- explain the one identical network/cache retry rule without allowing a skipped
  advisory gate.

### 12.3 Prevalidated command contract

The architect has already verified on the accepted checkpoint that:

```text
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
```

reports zero errors. This is preflight evidence only. The executor must still
prove the actual Composer script through `composer analyse` and
`composer quality` after editing.

---

## 13. Documentation and file-quality rules

All created/modified text must be:

- strict UTF-8 without BOM;
- LF-only;
- exactly one final LF;
- free of trailing whitespace and NUL;
- ordinary, non-reparse files with only the default data stream; and
- intended as Git mode `100644`.

Use repository-relative Markdown links. Validate every local link in the
modified/new documentation. Do not treat historical caller-record links or
Windows absolute evidence links as current-document local-link failures when
the documentation standard explicitly exempts them.

Write plainly and distinguish implemented, planned, rejected, and open work.
Do not use vague future language where the exact A-H owner is known, and do not
invent details reserved for 3B.

---

## 14. Validation sequence

### Gate A - authenticated preflight

Before mutation:

- authenticate this directive externally;
- read all authorities completely;
- verify the exact Git/checkpoint baseline;
- verify the final-closure report and locked file identities;
- verify the zero-byte Phase 3A report;
- verify exact entry status paths;
- verify file safety/modes; and
- verify no Phase 3 implementation/package path already exists.

Do not rerun the old temporary-overlay procedure. The accepted Phase 2 final
closure report is the prior canonical baseline.

### Gate B - exact tooling stabilization

Make only the exact Composer script replacement and toolchain documentation
update. Then run:

```text
composer validate:composer
composer validate:packages
composer analyse
```

Require:

- root/package validation succeeds with only already-accepted warning
  categories;
- `composer analyse` invokes the selected PHP process with 512M and reports
  zero errors;
- normal fresh `php -r "echo ini_get('memory_limit');"` remains `128M` after the
  command;
- no `PHP_INI_SCAN_DIR` manipulation or temporary INI path exists; and
- `composer.lock` remains byte-identical.

If this exact script contract fails for a semantic or portability reason, stop
before architecture documentation. A command typo or read-only checker defect
may be corrected and rerun only if repository bytes are unchanged.

### Gate C - architecture and current-status documentation

Create/modify only Section 8 paths. Validate:

- ADR 0051 status/date/sections/decisions;
- exact A-H roadmap and dependency order;
- all finish-line/security/persistent-runtime requirements;
- no 3B decision was silently made;
- no current document says Phase 2H is still future or no root-owned runnable
  fixture exists;
- no current document calls the fixture production-ready;
- exact thirteen-package count and package graph claims;
- exact links and documentation doctrine;
- strict file/encoding/stream/mode rules; and
- `git diff --check` passes.

PHP CS Fixer may not modify Markdown/JSON. Do not run `composer cs:fix`.

### Gate D - canonical quality

Run literal:

```text
composer quality
```

Require:

```text
Composer validation        pass
locked security audit      zero advisories
platform requirements      pass
PHP CS Fixer               0 of 230 files fixable
PHPStan                    zero errors
PHPUnit                    480 tests, 8575 assertions
```

If the audit fails solely because managed network/cache access is denied, fully
finish the failed command, prove repository state unchanged, and run the
identical literal `composer quality` once with approved access. No other retry,
skip, platform ignore, audit bypass, baseline, suppression, exclusion, memory
increase, or test-count relaxation is authorized.

After quality, prove normal PHP remains 128M and no temporary INI/scan variable
was used.

### Gate E - final preservation and report

Require exact final path scope and status counts in Section 15. Prove:

- no PHP production/test file changed;
- all package manifests and `composer.lock` are byte-identical;
- only the root analyse script changed in `composer.json`;
- no dependency/plugin/policy/autoload/version change;
- exact docs created/modified;
- no stage, commit, ref, branch, remote, tag, hook, or Git configuration change;
- no Phase 3 runtime/package/API path exists; and
- report remains zero-byte until every other gate passes.

Only then populate the report.

---

## 15. Exact expected final state

Before report population require:

```text
tracked modifications  16
untracked paths          5
total status paths      21
staged paths              0
tracked files           536
package directories      13
package manifests        13
production PHP files    100
test PHP files          130
style-covered PHP       230
```

The sixteen modified paths are exactly Section 8.2.

The five untracked paths are exactly:

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/architecture/phase-3-roadmap.md
docs/dev_plan/kongono_phase_3A_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
docs/task reports/Phase_3A.md
```

After report population, the path counts remain unchanged. No stage or commit
is created.

---

## 16. Prohibited scope

Do not implement or select:

- a production package, namespace, class, interface, enum, exception,
  diagnostic, API status, or Composer dependency;
- a SAPI, FPM, CGI, Apache module, built-in server, RoadRunner, FrankenPHP,
  OpenSwoole, ReactPHP, Amp, Workerman, socket, listener, or process manager;
- raw superglobal access, request parsing, upload handling, cookie/query/body
  normalization, response emission, `header()`, output buffering, or stream
  copying;
- a front controller, deployment file, supported application template, or
  framework CLI;
- environment/configuration keys, `.env`, secrets loading, config repository,
  reload/watch behavior, or a general configuration framework;
- proxy/TLS trust, host validation, authentication, authorization, session,
  CSRF, rate limiting, incident handling, logging, telemetry, tracing, metrics,
  retry, or recovery;
- ORM, database, migration, model, template, frontend, cache, queue, event, mail,
  scheduler, realtime, agent, extension, MCP, or A2A runtime capability;
- stable API, release packaging, semantic versioning, marketplace/ecosystem, 1.0,
  feature parity, full framework, or production-readiness claim;
- Symfony/Laravel framework components or another framework kernel;
- dependency update, lock regeneration, plugin, platform/policy weakening;
- PHPStan baseline, suppression, ignore, exclusion, reduced level, or unlimited
  memory;
- staging, commit, amend, reset, checkout, restore, stash, clean, rebase, merge,
  branch/tag/remote mutation, push, hook bypass, signing override, or history
  rewrite; or
- Phase 3B implementation.

---

## 17. Stop and bounded-correction rules

Stop and report exact expected/observed evidence if:

- directive/caller identity or real repository baseline differs;
- a protected or out-of-scope path changed;
- the Composer script cannot work as specified without another manifest/tool
  change;
- `composer.lock` changes;
- a concrete 3B+ architecture decision would be needed;
- canonical quality has a genuine non-network failure;
- documentation remains contradictory after authorized reconciliation;
- final scope/counts differ; or
- a prohibited action would be required.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Within the exact Section 8 boundary, the executor may correct ordinary Markdown
formatting, link targets, wording consistency, or JSON indentation discovered
by the required gates and rerun those focused gates. This does not require a
new directive when it makes no architectural decision, changes no path scope,
and preserves all locked meaning.

A non-mutating PowerShell/parser/quoting/checker error may be simplified and
rerun after proving repository bytes unchanged. Report it as procedural
evidence, not a Kongono defect. Do not consume the network retry for such an
error.

No other semantic expansion is authorized.

---

## 18. Phase 3A task completion report

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_3A.md
```

Use exactly these headings:

```text
# PHASE 3A TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PHASE 3 MILESTONE
## 8. FIXED A-H ROADMAP
## 9. PHASE 2 DOCUMENTATION RECONCILIATION
## 10. TOOLCHAIN STABILIZATION
## 11. ARCHITECTURAL DECISIONS
## 12. DEVIATIONS FROM DIRECTIVE
## 13. DEPENDENCIES ADDED/REMOVED
## 14. ENVIRONMENT
## 15. COMMANDS RUN
## 16. VALIDATION RESULTS
## 17. DOCUMENTATION REVIEW
## 18. SECURITY AND FILE-SAFETY REVIEW
## 19. WARNINGS / KNOWN ISSUES
## 20. QUESTIONS / BLOCKERS
## 21. FINAL GIT STATUS
## 22. ACCEPTANCE CRITERIA
## 23. NEXT STEP
```

Report:

- independent Phase 3A directive identity;
- Phase 2 final-closure report identity;
- exact checkpoint identity and clean tracked baseline;
- exact created/modified path inventories;
- ADR 0051 and A-H roadmap decisions;
- explicit list of still-open 3B choices;
- every corrected stale current-status statement category;
- exact Composer analyse script before/after;
- proof that normal PHP remains 128M and no overlay was used;
- exact validation, audit, style, PHPStan, test, and assertion results;
- lockfile and protected-source preservation;
- whether bounded formatting/checker correction authority was used;
- exact final `16/5/21/0` status and no Git mutation;
- confirmation that Phase 3A implemented no production runtime behavior; and
- final report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3B.
```

The report must be strict UTF-8 without BOM, LF-only, exactly one final LF, no
trailing whitespace/NUL, ordinary, untracked, unstaged, absent from `HEAD`, and
remain outside every historical commit.

---

## 19. Acceptance criteria

Phase 3A is Complete only when all are true:

1. directive identity matched independently supplied values;
2. Phase 2 checkpoint, final report, and exact entry status matched;
3. all required authorities were read completely;
4. exactly two documentation paths were created;
5. exactly sixteen existing paths were modified;
6. no other path changed;
7. ADR 0051 is Accepted, dated correctly, and records the exact milestone;
8. the A-H roadmap matches Section 7 exactly;
9. no concrete 3B+ selection or implementation was introduced;
10. every authorized current document describes Phase 2 as complete and the
    fixture as test-owned/non-production;
11. no historical ADR/directive/report was rewritten;
12. root `analyse` script matches the exact `@php -d memory_limit=512M` command;
13. no other Composer manifest field changed;
14. `composer.lock` remained byte-identical;
15. ordinary `composer analyse` passes at zero errors without an overlay;
16. literal `composer quality` passes with zero advisories, platform success,
    0/230 style findings, zero PHPStan errors, and 480 tests/8575 assertions;
17. normal PHP remains 128M after validation;
18. all links, encoding, line-ending, whitespace, NUL, stream, ordinary-file,
    mode, and `git diff --check` gates pass;
19. package/PHP/dependency/diagnostic inventories remain unchanged;
20. no production/test/package-manifest/tool-config path outside the exact
    root script change was modified;
21. no stage, commit, ref, history, branch, tag, remote, hook, signing, or Git
    configuration mutation occurred;
22. final state is exactly `16/5/21/0` with 536 tracked files;
23. report remained zero-byte until all other criteria passed;
24. the populated report has the exact heading/final-sentence/file-quality
    contract; and
25. the report identifies Phase 3B as architecture-only selection, not
    implementation.

Any failed criterion means status cannot be Complete.

---

## 20. Required final outcome

Kongono has an Accepted, inspectable Phase 3 production HTTP integration
milestone and fixed A-H dependency order. Current documentation accurately
states that Phase 2 is fixture-runnable and checkpointed while production host
integration remains unimplemented. Historical evidence is preserved.

The contributor quality command runs PHPStan through an explicit bounded 512 MB
child process without changing normal PHP runtime memory or requiring temporary
INI overlays. No production code, package, dependency, lockfile, API, SAPI,
adapter, emitter, configuration system, deployment entry, or Phase 3 runtime
behavior is added.

Then await architect review before Phase 3B.
