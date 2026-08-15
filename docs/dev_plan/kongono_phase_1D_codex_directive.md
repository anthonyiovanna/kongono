# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1D
## Typed Adapter-Controlled Delivery Permission Foundation

**Directive status:** Architect-authorized execution contract
**Phase type:** Narrow value implementation in an existing package
**Execution role:** Executor, not architect
**Date:** 2026-08-15

---

## 1. Mission

Phase 1A accepted a fail-closed two-permission architecture for trusted
developer diagnostic presentation. Phase 1C implemented only the first typed
input, `TrustedDeveloperBootstrapPermission`, in the separate
`kongono/diagnostic-activation` package.

Phase 1D must implement only the typed representation of the second input: the
outer adapter's already-determined permission for one current delivery
boundary.

Add exactly one production declaration to the existing package:

```text
Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission
```

It is an unbacked Experimental enum with exactly:

```text
case Denied
case Allowed
public function allowsTrustedPresentation(): bool
```

`Denied` returns `false`; `Allowed` returns `true`.

The enum represents a decision that a future outer adapter has already made
for the current delivery. It does not inspect a request, authenticate an
operator, prove transport trust, infer a network boundary, or authorize a
caller by itself. The Diagnostic Activation package owns only the neutral typed
representation. The future adapter retains sole ownership of the evidence,
mechanism, and authority used to select the case.

Phase 1D must not combine the new value with
`TrustedDeveloperBootstrapPermission`. It must not access an exception,
diagnostic metadata or context, projector, renderer, document, request,
response, transport, configuration source, or runtime adapter.

After Phase 1D, both policy inputs have explicit types, but the two-gate
eligibility calculation and enforcement boundary remain unimplemented.

---

## 2. Authority and mandatory stop rule

Before broad quality validation or mutation, read completely:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `README.md`;
5. `docs/doctrine.md`;
6. `docs/documentation-standard.md`;
7. `docs/engineering/api-status.md`;
8. `docs/engineering/error-handling.md`;
9. `docs/engineering/package-layout.md`;
10. `docs/architecture/overview.md`;
11. `docs/architecture/runtime-model.md`;
12. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
13. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
14. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
15. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
16. `docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md`;
17. `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`;
18. `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md`;
19. `packages/api-metadata/README.md`;
20. `packages/api-metadata/src/Api.php`;
21. `packages/api-metadata/src/ApiStatus.php`;
22. `packages/diagnostic-activation/README.md`;
23. `packages/diagnostic-activation/composer.json`;
24. `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php`;
25. `packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php`;
26. `packages/diagnostic-presentation/README.md`;
27. `packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php`;
28. `packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php`;
29. `composer.json`;
30. `composer.lock`;
31. `.php-cs-fixer.dist.php`;
32. `phpstan.neon.dist`;
33. `phpunit.xml.dist`;
34. `tests/Architecture/ApiStatusConventionTest.php`;
35. `docs/dev_plan/kongono_phase_1A_codex_directive.md`;
36. `docs/task reports/Phase_1A.md`;
37. `docs/dev_plan/kongono_phase_1C_codex_directive.md`;
38. `docs/task reports/Phase_1C.md`;
39. this Phase 1D directive; and
40. the zero-byte `docs/task reports/Phase_1D.md` caller placeholder.

Earlier directives and reports are immutable historical evidence, not active
implementation authority. Current Accepted ADRs, repository policy, and this
directive control Phase 1D.

The Phase 1D directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local copy is not self-authenticating. If either value is absent or differs,
stop before broad validation or mutation and report the observed identity.

Apply literally:

```text
STOP
ASK
DO NOT GUESS
```

Stop before continuing if any caller record, Git identity, path inventory,
working-file identity, status classification, dependency result, API surface,
test count, or security rule differs from this directive.

Do not repair, normalize, stage, commit, reset, restore, clean, stash, rebase,
amend, rewrite, or otherwise alter a mismatch.

---

## 3. Locked starting state

### 3.1 Accepted Git and implementation baseline

Require exactly before Phase 1D caller setup:

```text
branch                    main
local branches            1
HEAD                      837863d11feda282e4959ef7ec98a810c184531e
HEAD tree                 24a82a296b9075a2d7640f015a06230b12ed5fa2
HEAD parent               7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
commit count              7
HEAD subject              Establish Kongono framework baseline through Phase 1A
tracked files             319
tracked modes             319 x 100644
modified tracked paths     24
staged paths                0
untracked paths            15
total status paths         39
remotes                     0
tags                        0
package directories         7
package manifests           7
all Composer manifests      8
production PHP files       50
test PHP files             81
PHP CS Fixer files        131
API-status declarations    50
API-status attributes      48
PHPUnit tests             243
PHPUnit assertions       4191
```

The semantic index must match HEAD exactly: 319 stage-zero mode-`100644`
entries, no higher stage, no intent-to-add entry, and no staged delta. Raw index
cache/stat bytes are not a project-content identity.

All untracked and total status counts in this directive are file-level counts.
Use `git status --porcelain=v1 --untracked-files=all`; the default condensed
directory display reports fewer entries and is not accepted evidence.

The exact 24 modified tracked paths are:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostic-presentation/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

`composer.lock` must remain:

```text
bytes                    181650
SHA-256                  6EB8CF5E003C6A17D41C8127057BD4A05C8B984A909BB9B7F07845A712689A8E
```

The accepted Phase 1C protected set is exactly 143 files with aggregate
SHA-256:

```text
DC984D2056F3F67E56E58218EDDFB4A59AFC1469C3796A4AF233163E4C99329E
```

Use the same sorted relative-path plus file-byte aggregate algorithm recorded
by Phase 1C. Stop if the baseline protected inventory or digest differs.

### 3.2 Immutable historical caller records

The exact ten caller records already present are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_1B_codex_directive.md` | 30579 | `0A7FBF14E61CF6971C673A90006F3A4F913638679A879685A9715A4097BB8B31` |
| `docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md` | 20048 | `E94B5217CDC8F07A3BE7FFEEF4E80C26BD7C12561024E9611B5A8DC2E2175D5F` |
| `docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md` | 21661 | `50D795817F18753797EFF867AC271EB08AEFEF1B74B1FACD1A726EC0C6BCA493` |
| `docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md` | 19220 | `3CD09281EF71E5EE3F8DC183D83DB5CB2734C4B56D3FEDAC0095F1C0F4C75EC6` |
| `docs/task reports/Phase_1B.md` | 3001 | `256FD7B335163B9029E341E673DD457C46AA5532F95A01D442E339F263A8C6C8` |
| `docs/task reports/Phase_1B_1_correction.md` | 4078 | `62F61AA859FAEAB35ABCA865603D312DED3D1ED75DBC3035F996B711A346E017` |
| `docs/task reports/Phase_1B_2_correction.md` | 2699 | `B6C3E0C156BCFC75894E8F5E5A98392FF182A1DEEA71162448B4B9BE177B719B` |
| `docs/task reports/Phase_1B_3_correction.md` | 18805 | `56F1938083B9D58F8BE39C0567FF0A63A4A2EC38FDB00D91C8D5CE2BDC93CCE4` |
| `docs/dev_plan/kongono_phase_1C_codex_directive.md` | 35459 | `0E0027BA05FE305DCA92CADA56843EDF42D91C664A2C507C1C90FF983A7FD7C3` |
| `docs/task reports/Phase_1C.md` | 20551 | `28864B5D4B1B3D4EE9D7564E83F23FFC5E9D4D6CC4DD55C251FB13A5E393931B` |

They must remain ordinary, untracked, unstaged, absent from HEAD, limited to
the default data stream, and byte-identical. Do not edit any historical caller
record.

### 3.3 Accepted Phase 1C untracked implementation files

Before Phase 1D, require these exact identities:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md` | 7713 | `DE40D3EB8BBCC32B33A1B411BA27BD51E2BDE8C4FB82126A22D117F53FD8280B` |
| `packages/diagnostic-activation/README.md` | 4110 | `106AC57D8A6FD7D86FDBA4C58E1367962B38C56FFEE6BA57D2DFF1202EF83769` |
| `packages/diagnostic-activation/composer.json` | 428 | `C66A7BE2DF90476CD1B516BC448F3BF41737E89F798BD379C69D70EA9DC0E771` |
| `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php` | 707 | `0B83C99CA936CEB4264CCD6433367C976B5912FD952287F9F5F068226E4B00DC` |
| `packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php` | 1989 | `E5511B3773405CB366AF341B8BDBC98EFB4F7C53B4318A9BA17BBACB8B8F0423` |

Phase 1D may modify only the first two of these under Section 4. It must preserve
the manifest, bootstrap enum, and bootstrap test byte-for-byte.

### 3.4 Phase 1D caller setup

The caller adds exactly:

```text
docs/dev_plan/kongono_phase_1D_codex_directive.md
docs/task reports/Phase_1D.md
```

The report must initially be an ordinary zero-byte, untracked, unstaged file,
absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Before implementation, exact status is:

```text
modified tracked paths    24
staged paths               0
untracked paths           17
total status paths        41
```

Stop if anything else exists.

---

## 4. Exact authorized file boundary

### 4.1 Create exactly these three non-caller files

```text
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php
packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php
```

### 4.2 Modify exactly these nineteen tracked files

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostic-presentation/README.md
tests/Architecture/ApiStatusConventionTest.php
```

These are all already modified in the accepted Phase 1C worktree. Their status
classification therefore remains ` M`; Phase 1D must not enlarge the tracked
modified-path inventory beyond the accepted 24 paths.

### 4.3 Modify exactly these two accepted untracked implementation files

```text
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
packages/diagnostic-activation/README.md
```

They remain untracked and unstaged. This authorization does not extend to any
other accepted Phase 1C untracked file.

### 4.4 Report-only caller mutation

Populate only after every non-report gate passes:

```text
docs/task reports/Phase_1D.md
```

Do not edit the Phase 1D directive or any earlier directive or report.

No path outside Sections 4.1 through 4.4 is authorized.

---

## 5. Existing package boundary

Phase 1D adds no package and no dependency. Preserve exactly:

```text
package name       kongono/diagnostic-activation
namespace          Kongono\DiagnosticActivation\
source root        src/
test namespace     Kongono\DiagnosticActivation\Tests\
test root          tests/
type               library
```

The package manifest remains byte-identical and requires exactly:

```json
{
  "kongono/api-metadata": "*",
  "php": ">=8.5"
}
```

The production dependency graph remains exactly:

```text
api-metadata -> PHP
diagnostic-activation -> api-metadata
diagnostics -> api-metadata
kernel -> api-metadata + diagnostics
application-graph -> api-metadata + diagnostics
dependency-injection -> api-metadata + diagnostics
diagnostic-presentation -> api-metadata + diagnostics + kernel + application-graph + dependency-injection
framework root -> all seven packages
```

No existing package gains a dependency on Diagnostic Activation. Diagnostic
Presentation remains separate and directly callable. A future outer adapter may
consume both packages only under a later directive.

The package owns typed representations of the two independent activation-policy
inputs. It does not own raw evidence, transport trust evaluation, authentication,
configuration loading, combined eligibility, presentation access, or delivery.

---

## 6. Exact production API

Create exactly one production declaration:

```php
namespace Kongono\DiagnosticActivation;

#[Api(ApiStatus::Experimental)]
enum AdapterControlledDeliveryPermission
{
    case Denied;
    case Allowed;

    public function allowsTrustedPresentation(): bool;
}
```

The actual method body must return `true` only for `self::Allowed` and `false`
for `self::Denied` using explicit enum-case identity:

```php
return self::Allowed === $this;
```

The declaration must be:

- an unbacked enum;
- `final` by PHP enum semantics;
- marked exactly once with `#[Api(ApiStatus::Experimental)]`;
- in its exact PSR-4 file;
- limited to `Denied`, then `Allowed`, in that order;
- limited to its explicit `allowsTrustedPresentation(): bool` method plus
  PHP's automatic `UnitEnum::cases()` surface;
- free of custom properties, traits, interfaces, constants, constructors,
  factories, parsers, serializers, aliases, magic methods, and mutable state;
  and
- documented contextually without narrating the code.

Because it is unbacked, it exposes no `from()` or `tryFrom()` raw-value API.
Case names are PHP source API names, not request, header, network, transport,
configuration, or authentication tokens.

No exception type or diagnostic identifier is added. Both cases are valid
typed decisions. Failure or uncertainty while establishing delivery trust is
outside the enum and must fail closed to `Denied` at the future adapter
boundary; Phase 1D implements no such evaluation.

The existing `TrustedDeveloperBootstrapPermission` source and test must remain
byte-identical. Do not introduce a shared interface, trait, abstract type,
helper, alias, or generic permission API.

---

## 7. Locked semantics and security boundary

The implementation and documentation must preserve all of these rules:

1. Trusted presentation requires both trusted bootstrap permission and
   adapter-controlled delivery permission to be affirmative.
2. The new enum models only the second decision.
3. `Allowed` means only that a future outer adapter has affirmatively selected
   permission for one current delivery boundary.
4. `Allowed` alone never authorizes exception access, diagnostic metadata or
   context access, Safe or Protected field copying, projection, rendering,
   persistence, logging, transmission, or delivery.
5. `Denied` prohibits the trusted path regardless of the bootstrap value.
6. Unknown, unavailable, ambiguous, failed, expired, or unverified adapter
   evidence must never become `Allowed`.
7. Directly selecting `Allowed` in PHP does not authenticate an operator,
   establish a trusted listener, prove a loopback or private connection, or
   create authority.
8. The enum does not inspect a request, route, header, cookie, body, session,
   token, hostname, origin, referrer, IP address, socket, TLS state, client
   certificate, proxy assertion, frontend value, or command-line input.
9. No environment label, truthy string, AI output, heuristic, exception value,
   API status, database value, provider response, extension, agent, prior
   operation, global, registry, container, thread-local, or fiber-local state
   may select or upgrade the value.
10. The value carries no delivery identifier, subject, audience, address,
    credential, timestamp, expiry, evidence, reason, or mutable state.
11. The future adapter must evaluate permission independently for each delivery
    and must not reuse an affirmative selection from a prior delivery.
12. PHP enum cases are language-level singletons; object identity therefore
    proves only which case was selected and never proves delivery identity,
    freshness, or authorization.
13. The future adapter owns the mechanism and authority used to select the
    value. Diagnostic Activation owns only the type.
14. Bootstrap permission remains independently fixed for a future adapter
    instance; delivery permission remains per-delivery and cannot change it.
15. Diagnostic Presentation remains directly callable and cannot enforce the
    outer two-gate rule.
16. No secret, token, marker, package visibility trick, constructor
    restriction, or API attribute turns the enum into a capability or security
    boundary.
17. No I/O, filesystem, environment, network, logging, telemetry, event,
    persistence, serialization, cache, Kernel, Graph, DI, Diagnostics,
    projector, renderer, exception, request, response, or adapter access occurs.
18. Combined evaluation and enforcement remain absent after Phase 1D.

---

## 8. Tests and architecture enforcement

### 8.1 Focused package tests

Create exactly two PHPUnit test methods in
`AdapterControlledDeliveryPermissionTest.php`, totaling exactly 12 assertions.

The tests must prove:

1. `cases()` returns exactly `Denied`, then `Allowed`;
2. `Denied->allowsTrustedPresentation()` is `false`;
3. `Allowed->allowsTrustedPresentation()` is `true`;
4. the declaration is an enum and is unbacked;
5. it has exactly one `Api` attribute with `ApiStatus::Experimental`;
6. its declared public surface is exactly automatic `cases()` and explicit
   `allowsTrustedPresentation()`;
7. `allowsTrustedPresentation()` returns `bool` and accepts no parameters; and
8. `from()` and `tryFrom()` do not exist.

Mirror the established Phase 1C test structure without modifying the existing
test. Do not use snapshots, mocks, data providers, fixtures, serialization,
reflection mutation, subprocesses, filesystem writes, environment variables,
or hidden test declarations.

Focused package expectation after Phase 1D:

```text
packages/diagnostic-activation/tests/Unit
4 tests
24 assertions
```

The new test file itself contributes exactly:

```text
2 tests
12 assertions
```

### 8.2 Root API-status enforcement

Modify `ApiStatusConventionTest.php` only to:

- import `AdapterControlledDeliveryPermission` in sorted order; and
- add it to `EXPECTED_STATUS` as `ApiStatus::Experimental` in the existing
  package/type order.

Preserve every other inventory, package count, manifest assertion, diagnostic
catalog assertion, exception-marker assertion, and helper byte-for-byte except
where formatting the two additions inherently changes adjacent lines.

The new declaration adds exactly five assertions to the focused architecture
suite: one autoloadability assertion and four API-attribute/status assertions.

Focused expected result:

```text
6 tests
575 assertions
```

Do not weaken dynamic discovery or replace the exact inventory with a broad
check.

### 8.3 Full-suite expectation

The final complete suite must be exactly:

```text
245 tests
4208 assertions
```

If the implementation needs a different count, stop. Do not add filler
assertions or weaken coverage to force the target.

---

## 9. Composer, autoload, lockfile, and tooling invariants

Phase 1D changes no Composer manifest, lockfile, autoload mapping, PHPUnit XML,
PHPStan configuration, or PHP CS Fixer configuration.

The existing package PSR-4 roots already cover the two new files. Preserve
byte-for-byte:

```text
.php-cs-fixer.dist.php
composer.json
composer.lock
packages/diagnostic-activation/composer.json
phpstan.neon.dist
phpunit.xml.dist
```

Do not run `composer update`. Do not edit or regenerate `composer.lock`.

Run:

```text
composer dump-autoload --optimize --strict-psr
```

It must succeed without a PSR-4 warning. Prove from root autoloading that both
the new production enum and test class are autoloadable.

Final expected counts:

```text
package directories         7
package manifests           7
all Composer manifests      8
production PHP files       51
test PHP files             82
protected files           145
PHP CS Fixer files        133
API-status declarations    51
API-status attributes      49
```

Compute and report the final 145-path protected aggregate SHA-256 using the
same established algorithm. The final digest is an executor output, not an
architect-supplied assumption.

Validate the unchanged root and package manifests directly. Any lockfile,
manifest, package graph, dependency, script, repository, stability, plugin,
platform, autoload, or tooling delta is unauthorized.

---

## 10. ADR 0040 and documentation reconciliation

### 10.1 ADR 0040

Create:

```text
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
```

It must have:

```text
Status: Accepted
Date: 2026-08-15
```

Use the repository ADR structure with Context, Decision, Rationale, Security
implications, Lifecycle and concurrency, Performance implications,
Consequences, Rejected alternatives, and What remains open.

Record exactly:

- the exact enum name, cases, method, and Experimental status;
- ownership of the neutral type by Diagnostic Activation;
- continued ownership of decision evidence, evaluation, and authority by the
  future outer adapter;
- why this does not add a package or dependency;
- why the enum is unbacked and has no raw parser surface;
- `Denied` and `Allowed` semantics;
- fail-closed treatment of missing, failed, uncertain, ambiguous, expired, or
  unverified evidence;
- direct PHP case selection not proving authorization;
- per-delivery isolation and prohibition on reusing an affirmative decision;
- enum identity not proving delivery identity or freshness;
- both typed permissions now existing while combined evaluation remains absent;
- Diagnostic Presentation remaining separate and directly callable;
- no exception, diagnostic identifier, configuration, transport, adapter,
  presentation access, I/O, or ambient state; and
- every rejected and deferred capability in this directive.

ADR 0040 must explicitly state that it narrowly supersedes ADR 0039's broad
rejection of any delivery-permission value in Diagnostic Activation only to
permit this neutral typed representation. It does not supersede adapter
ownership of the decision mechanism, the separation of the two permissions,
or the rejection of combined enforcement in this phase.

What remains open must include:

- exact outer adapter package and composition contract;
- trusted-delivery evidence and evaluation mechanism;
- authentication, listener, connection, proxy, and transport trust policy;
- combined two-gate evaluation and enforcement boundary;
- non-trusted and activation-failure behavior;
- raw bootstrap configuration source, parser, validation failure, and reload;
- HTTP, CLI, and other transport behavior;
- production diagnostic model and allowlists;
- simultaneous primary, activation, projection, rendering, transport, and
  cleanup failure representation;
- logging, telemetry, incidents, serialization, localization, and persistence;
  and
- Kernel lifecycle, DI wiring, Application Graph, extensions, frontend, and
  agents.

Do not leave the exact typed delivery-decision API or neutral type ownership
open after this phase.

### 10.2 ADR 0039 reconciliation

Modify ADR 0039 narrowly so it no longer claims that every delivery-permission
value in Diagnostic Activation is rejected or unimplemented.

It must distinguish:

- Phase 1C's original bootstrap-only implementation boundary;
- Phase 1D's later neutral delivery-decision type;
- adapter ownership of the evidence and mechanism;
- the still-absent combined gate and enforcement; and
- ADR 0040's narrow supersession.

Preserve ADR 0039's original bootstrap API decision. Do not rewrite history to
pretend the delivery type existed in Phase 1C.

### 10.3 Existing documentation

Modify only the authorized documentation paths so they consistently state:

- the runtime still contains seven packages;
- Diagnostic Activation now owns exactly two Experimental unbacked permission
  enums;
- the bootstrap value is adapter-instance-lifetime input while the delivery
  value represents one current delivery decision;
- both enums are immutable, stateless, deterministic, unbacked, and I/O-free;
- both typed inputs exist, but the combined gate and enforcement do not;
- the future outer adapter still owns delivery evidence and evaluation;
- `Allowed` on either enum is insufficient by itself;
- direct case selection and direct presentation calls do not prove authority;
- missing, failed, uncertain, or unverified delivery evidence fails closed;
- an affirmative delivery decision cannot be reused across deliveries;
- Diagnostic Presentation remains separate and has no dependency on Diagnostic
  Activation;
- no request inspection, adapter, transport, configuration loader, served page,
  or production enforcement exists; and
- package graph and API-status counts are current.

Update old Phase 1C implementation notes additively. Preserve the historical
fact that Phase 1C implemented only the bootstrap enum. Add a separate Phase 1D
note rather than rewriting Phase 1C as though both values existed then.

Do not rewrite historical directives or reports. Do not imply that Phase 1D
implements the Phase 1A truth table or any adapter.

---

## 11. Validation gates

### Gate A - caller and baseline

Before broad quality or mutation:

- verify the independently supplied Phase 1D directive identity;
- verify the Phase 1D report is the exact zero-byte placeholder;
- verify all ten historical caller identities;
- verify exact 41-path pre-change status;
- verify the exact 24 modified and 17 untracked inventories;
- verify all five accepted Phase 1C implementation-file identities;
- verify HEAD/tree/parent/history/refs and semantic index equality;
- verify counts, lockfile, protected aggregate, modes, streams, and file types;
  and
- verify no merge/rebase/cherry-pick/revert/bisect/lock state exists.

### Gate B - pre-change quality

Run exactly:

```text
composer quality
```

Expected:

```text
security advisories     0
platform requirements   pass
PHP CS Fixer findings   0 of 131
PHPStan errors          0
PHPUnit tests           243
PHPUnit assertions      4191
```

If the unchanged command fails only because the managed environment cannot
access the Composer advisory service or cache, rerun the identical command with
approved access. Do not weaken or skip the fail-closed audit.

### Gate C - implementation

Create and modify only Section 4 paths. Preserve every locked manifest,
lockfile, tooling file, bootstrap source/test, and caller record exactly.

### Gate D - focused validation

Run at minimum:

```text
composer validate --no-check-publish
composer --working-dir=packages/diagnostic-activation validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostic-activation/tests/Unit
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer analyse
composer cs
```

Expected focused tests:

```text
Diagnostic Activation   4 tests, 24 assertions
API architecture        6 tests, 575 assertions
```

### Gate E - direct architecture and security proof

Prove directly:

- exact new enum reflection shape, cases, method, attribute, and lack of
  backing;
- absence of `from()` and `tryFrom()`;
- existing bootstrap source and test remain byte-identical;
- exact package dependency graph and absence of reverse dependencies;
- all manifests, lockfile, and tooling files remain byte-identical;
- no production import outside API Metadata;
- no config, environment, request, superglobal, filesystem, or network access;
- no exception, diagnostic, projector, renderer, Kernel, Graph, DI, adapter,
  transport, logging, telemetry, persistence, or serialization code;
- no combined evaluator, guard, service, or enforcement call;
- exact created and modified inventories;
- strict UTF-8/LF, one final LF, no BOM/NUL/trailing whitespace/conflict marker;
- ordinary-file/default-stream/no-reparse/no-executable modes;
- all local Markdown links resolve;
- no secret-category or prohibited artifact finding;
- `git diff --check` passes except immutable untracked historical records are
  outside the tracked diff; and
- no staged path or Git history/ref mutation exists.

### Gate F - final canonical quality

Run exactly:

```text
composer quality
```

Expected:

```text
security advisories     0
platform requirements   pass
PHP CS Fixer findings   0 of 133
PHPStan errors          0
PHPUnit tests           245
PHPUnit assertions      4208
```

The same unchanged environment-only retry rule applies.

### Gate G - report population

Only after Gates A through F pass, populate the existing Phase 1D report. Then
verify report structure, file safety, final status, exact scope, and external
report identity. Do not run quality again after report population.

---

## 12. Final Git and file state

No staging or commit is authorized.

Before report population, exact Phase 1D scope relative to the accepted Phase
1C worktree is:

```text
new non-caller paths                    3
tracked paths additionally edited       19
accepted untracked paths edited           2
```

Because the nineteen tracked paths were already modified by accepted Phase 1C,
the final tracked status count remains 24.

After report population, final status is exactly:

```text
modified tracked paths    24
staged paths               0
untracked paths           20
total status paths        44
```

The twenty untracked paths are:

- the ten immutable Phase 1B/1C caller records;
- the Phase 1D directive;
- the populated Phase 1D report;
- the five accepted Phase 1C untracked implementation files; and
- the three Section 4.1 Phase 1D files.

HEAD, tree, parent, commit count, branch, refs, remotes, tags, and history must
remain unchanged. Do not create a checkpoint in Phase 1D.

---

## 13. Prohibited scope

Do not implement or introduce:

- a backed enum, raw string/integer value, parser, `from()`, or `tryFrom()`;
- a shared permission interface, trait, abstract type, helper, alias, or generic
  permission collection;
- a combined gate, eligibility evaluator, policy service, guard, capability,
  token, grant, registry, service locator, or ambient current state;
- delivery evidence evaluation, authentication, operator identity, network
  trust, listener policy, proxy policy, IP/CIDR logic, TLS or certificate logic;
- request, response, middleware, router, handler, server, route, header, status,
  content type, CSP, cache, or served page;
- configuration keys, environment variables, files, loaders, schemas,
  validation exceptions, reload, or watchers;
- access to an exception, diagnostic metadata/context, projector, renderer,
  rendered document, source code, stack trace, or production presentation;
- logging, telemetry, traces, metrics, incidents, persistence, serialization,
  localization, events, retry, recursion, cleanup, or multi-failure behavior;
- Kernel lifecycle, operation scope, DI binding, Application Graph node/edge,
  extension, plugin, CLI, frontend, agent, ORM, or database integration;
- a new package, package dependency, exception marker, base exception,
  diagnostic catalog, diagnostic ID, Composer plugin, or third-party dependency;
- any manifest, lockfile, autoload mapping, PHPUnit, PHPStan, or formatter change;
- any suppression, baseline, exclusion, policy exception, or weakened quality
  rule;
- staging, commit, branch, tag, remote, push, stash, reset, restore, clean,
  rebase, merge, cherry-pick, amend, or history rewrite; or
- any Phase 1E or later capability.

---

## 14. Strict acceptance criteria

Every criterion must pass:

1. All required authorities were read completely before mutation.
2. The independently delivered Phase 1D directive identity matched.
3. The Phase 1D report began as the exact zero-byte caller placeholder.
4. All ten historical caller records retained exact identities and Git states.
5. Git identity, history, semantic index, refs, exact status inventories,
   lockfile, protected aggregate, modes, streams, and pre-change counts matched.
6. All five accepted Phase 1C implementation files matched their locked
   identities before mutation.
7. Pre-change quality passed at 243 tests and 4,191 assertions.
8. Exactly three non-caller paths were created, nineteen tracked paths were
   edited, and two accepted untracked implementation paths were edited.
9. No tracked path outside the accepted 24-path modified inventory changed.
10. Exactly one production declaration was added.
11. The exact unbacked enum has only `Denied`, `Allowed`, automatic `cases()`,
    and `allowsTrustedPresentation(): bool`.
12. The enum is Experimental and has no backing value, raw parser, property,
    trait, custom interface, factory, serializer, evidence, or mutable state.
13. `Denied` returns false and `Allowed` returns true.
14. The existing bootstrap enum and test remain byte-identical.
15. The new enum is documented as a typed per-delivery decision, not
    authentication, authorization proof, capability, or security boundary.
16. Delivery evidence evaluation and combined enforcement remain absent.
17. No raw config, environment, request, ambient state, I/O, or presentation
    access exists in Diagnostic Activation.
18. No package, dependency, manifest, lockfile, autoload mapping, or tooling
    change occurred.
19. Optimized strict PSR-4 autoload generation passed without warning.
20. Focused Diagnostic Activation tests passed at exactly 4 tests and 24
    assertions.
21. API architecture tests passed at exactly 6 tests and 575 assertions.
22. Exact production inventories and API statuses are enforced.
23. ADR 0040 is Accepted, dated 2026-08-15, and records only the authorized
    decision plus its narrow supersession of ADR 0039.
24. Documentation preserves Phase 1C history while accurately describing both
    implemented typed inputs and the absent combined gate.
25. Diagnostic Presentation remains separate, directly callable, and unchanged
    except for its authorized README reconciliation.
26. No new exception, diagnostic ID, package marker, or failure family exists.
27. Final counts are exactly 7 package directories, 7 package manifests, 8
    total manifests, 51 production PHP files, 82 test PHP files, 145 protected
    files, 133 fixer files, 51 API-status declarations, and 49 attributes.
28. Final quality passed at exactly 245 tests and 4,208 assertions with zero
    advisories, fixer findings, and PHPStan errors.
29. Links, text integrity, file safety, streams, modes, secret-category scans,
    and `git diff --check` passed.
30. Final status is exactly 24 modified, 20 untracked, 44 total, and zero
    staged.
31. HEAD, tree, parent, history, branch, refs, remotes, and tags remained
    unchanged.
32. The report is structurally exact, evidence-backed, externally identified,
    and populated only after all non-report gates passed.
33. No prohibited scope, policy weakening, Git mutation, or later-phase work
    occurred.

Any failed or unverified criterion makes status `Blocked` or `Partial`, never
`Complete`.

---

## 15. Completion report

Populate only after complete success using exactly:

```text
# PHASE 1D TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND API CONTRACT
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. COMPOSER AND LOCKFILE EVIDENCE
## 12. ENVIRONMENT
## 13. COMMANDS RUN
## 14. VALIDATION RESULTS
## 15. DOCUMENTATION REVIEW
## 16. SECURITY AND FILE-SAFETY REVIEW
## 17. WARNINGS / KNOWN ISSUES
## 18. QUESTIONS / BLOCKERS
## 19. FINAL GIT STATUS
## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must:

- enumerate all three created, nineteen tracked-edited, and two accepted
  untracked-edited paths;
- report all twelve caller records and the final report state;
- report pre/post Git identities, status arithmetic, and lack of Git mutation;
- report both enums' exact reflection shapes and behavior;
- report the existing bootstrap source/test identity preservation;
- report package graph, reverse-dependency, and manifest/lock invariance;
- report optimized strict PSR-4 evidence;
- report focused and complete test/assertion counts;
- report PHPStan, fixer, audit, platform, and manifest-validation results;
- report final counts and protected aggregate digest;
- report ADR 0040 identity, ADR 0039 reconciliation, and documentation review;
- report link, text, file-safety, stream, secret-category, and scope checks;
- state no evidence evaluator, combined gate, enforcement, config reader,
  adapter, transport, diagnostic access, or later capability exists;
- enumerate PASS/FAIL for all 33 criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not include the report's own final byte count or SHA-256 inside its body.
Return both separately after final read-back.

---

## 16. Required final outcome

Phase 1D ends with exactly one additional Experimental unbacked enum in the
existing Diagnostic Activation package. It carries a future outer adapter's
already-determined permission for one delivery boundary without inspecting,
parsing, authenticating, evaluating, or enforcing anything.

Both typed inputs to the Accepted two-gate policy now exist:

```text
TrustedDeveloperBootstrapPermission
AdapterControlledDeliveryPermission
```

Each is independently fail-closed. Neither `Allowed` case is sufficient by
itself. No combined evaluator or runtime enforcement exists.

No configuration source, evidence mechanism, authentication rule, adapter,
request, response, transport, diagnostic access, projection call, renderer
call, served page, production model, logger, telemetry path, incident system,
persistence, or runtime integration exists after this phase.

Do not begin the next phase.
