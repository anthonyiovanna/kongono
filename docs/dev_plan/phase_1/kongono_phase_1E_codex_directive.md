# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1E
## Pure Two-Permission Trusted-Presentation Eligibility

**Directive status:** Architect-authorized execution contract
**Phase type:** Narrow immutable value and truth-table implementation
**Execution role:** Executor, not architect
**Date:** 2026-08-15

---

## 1. Mission

Phase 1A accepted the fail-closed two-permission architecture for trusted
developer diagnostic presentation. Phase 1C implemented the typed trusted-
bootstrap decision. Phase 1D implemented the independent typed adapter-
controlled delivery decision. Both values are Experimental, immutable,
unbacked, and owned as neutral representations by
`kongono/diagnostic-activation`.

Phase 1E must implement only the pure combined truth-table calculation. Add
exactly one immutable production declaration to the existing package:

```text
Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility
```

It is a final readonly Experimental class constructed with exactly:

```text
TrustedDeveloperBootstrapPermission
AdapterControlledDeliveryPermission
```

in that order. Its sole non-constructor public method is:

```text
public function allowsTrustedPresentation(): bool
```

The result is `true` only when both supplied permissions are `Allowed`. Every
other combination returns `false`.

The class calculates eligibility only. It does not enforce the result, access
an exception or diagnostic, call a projector or renderer, choose a response,
deliver output, authenticate an operator, inspect a transport, evaluate raw
evidence, or parse configuration.

Construction with `Allowed + Allowed` does not prove authority. A future outer
adapter remains responsible for supplying an already-validated bootstrap value
and an independently established delivery value for the current delivery. The
eligibility object must be scoped to that one delivery and must not be retained
or reused as ambient authorization.

Phase 1E resolves only the pure calculation half of the previously open
“combined evaluation and enforcement boundary.” Enforcement remains wholly
unimplemented.

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
19. `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md`;
20. `packages/api-metadata/README.md`;
21. `packages/api-metadata/src/Api.php`;
22. `packages/api-metadata/src/ApiStatus.php`;
23. `packages/diagnostic-activation/README.md`;
24. `packages/diagnostic-activation/composer.json`;
25. `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php`;
26. `packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php`;
27. `packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php`;
28. `packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php`;
29. `packages/diagnostic-presentation/README.md`;
30. `packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php`;
31. `packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php`;
32. `composer.json`;
33. `composer.lock`;
34. `.php-cs-fixer.dist.php`;
35. `phpstan.neon.dist`;
36. `phpunit.xml.dist`;
37. `tests/Architecture/ApiStatusConventionTest.php`;
38. `docs/dev_plan/kongono_phase_1A_codex_directive.md`;
39. `docs/task reports/Phase_1A.md`;
40. `docs/dev_plan/kongono_phase_1D_codex_directive.md`;
41. `docs/task reports/Phase_1D.md`;
42. this Phase 1E directive; and
43. the zero-byte `docs/task reports/Phase_1E.md` caller placeholder.

Earlier directives and reports are immutable historical evidence, not active
implementation authority. Current Accepted ADRs, repository policy, and this
directive control Phase 1E.

The Phase 1E directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local copy is not self-authenticating. If either value is absent or differs,
stop before broad validation or mutation and report the observed identity.

Apply literally:

```text
STOP
ASK
DO NOT GUESS
```

Stop if any caller record, Git identity, path inventory, working-file identity,
status classification, API shape, dependency result, test count, or security
rule differs from this directive.

Do not repair, normalize, stage, commit, reset, restore, clean, stash, rebase,
amend, rewrite, or otherwise alter a mismatch.

---

## 3. Locked starting state

### 3.1 Accepted Git and implementation baseline

Require exactly before Phase 1E caller setup:

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
untracked paths            20
total status paths         44
remotes                     0
tags                        0
package directories         7
package manifests           7
all Composer manifests      8
production PHP files       51
test PHP files             82
PHP CS Fixer files        133
API-status declarations    51
API-status attributes      49
PHPUnit tests             245
PHPUnit assertions       4208
```

The semantic index must match HEAD exactly: 319 stage-zero mode-`100644`
entries, no higher stage, no intent-to-add entry, and no staged delta. Raw index
cache/stat bytes are not a project-content identity.

All untracked and total status counts in this directive are file-level counts.
Use `git status --porcelain=v1 --untracked-files=all`; condensed directory
display is not accepted evidence.

The exact 24 modified tracked paths remain:

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

The accepted Phase 1D protected set is exactly 145 files with aggregate
SHA-256:

```text
2D60C2EAC7109C488BF684CBF93FB8E699B26996A2DE77C9DE4C5FDB8D7E59B8
```

Use the accepted ordinal algorithm: sort repository-relative `/` paths
ordinally, encode each as path + NUL + decimal byte length + NUL + uppercase
file SHA-256 + LF, concatenate as UTF-8 without BOM, and SHA-256 the result.

The protected set is every PHP file below `packages/` and `tests/`, root
`composer.json`, `composer.lock`, all seven package manifests, and the three
root PHP quality configuration files.

### 3.2 Immutable historical caller records

The exact twelve caller records already present are:

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
| `docs/dev_plan/kongono_phase_1D_codex_directive.md` | 39847 | `9A98F82B00B46256647C1F5A29B0D9147E5EB087194BEE6CE72ABA302E635115` |
| `docs/task reports/Phase_1D.md` | 22623 | `45EB98654C68F87E07A6A874FE3C3AEB566D7DC12154E69415734173A62F4DFB` |

They must remain ordinary, untracked, unstaged, absent from HEAD, limited to
the default data stream, and byte-identical. Do not edit a historical caller
record.

### 3.3 Accepted Phase 1D untracked implementation files

Before Phase 1E, require these exact identities:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md` | 8489 | `60E7187D4542DD57970731A62B69C48A62CAD68BEE53652B49A89514005ED89D` |
| `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md` | 10281 | `C674B3F0B0463CDBC17158562087090CFCB3716CBB663C36B21330298477E9E2` |
| `packages/diagnostic-activation/README.md` | 5158 | `438C9B4E839E4117500600C967386FBD5F5B3F554395BDD73445C0899CA145D8` |
| `packages/diagnostic-activation/composer.json` | 428 | `C66A7BE2DF90476CD1B516BC448F3BF41737E89F798BD379C69D70EA9DC0E771` |
| `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php` | 707 | `0B83C99CA936CEB4264CCD6433367C976B5912FD952287F9F5F068226E4B00DC` |
| `packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php` | 690 | `938D1BCEE66F647045C5EF9D45426482E662992D3FF2E994687CEE7A0BE83732` |
| `packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php` | 1989 | `E5511B3773405CB366AF341B8BDBC98EFB4F7C53B4318A9BA17BBACB8B8F0423` |
| `packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php` | 1989 | `114D97F3B0EAA2116C051B84259D8154899992786677DF57AC62F9ED7F7ABDDE` |

Phase 1E may modify only the first three under Section 4. Preserve the manifest,
both existing enums, and both existing tests byte-for-byte.

### 3.4 Phase 1E caller setup

The caller adds exactly:

```text
docs/dev_plan/kongono_phase_1E_codex_directive.md
docs/task reports/Phase_1E.md
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
untracked paths           22
total status paths        46
```

Stop if anything else exists.

---

## 4. Exact authorized file boundary

### 4.1 Create exactly these three non-caller files

```text
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php
packages/diagnostic-activation/tests/Unit/TrustedDeveloperPresentationEligibilityTest.php
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

These paths are already modified in the accepted Phase 1D worktree and remain
classified ` M`. Do not enlarge the 24-path tracked inventory.

### 4.3 Modify exactly these three accepted untracked implementation files

```text
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
packages/diagnostic-activation/README.md
```

They remain untracked and unstaged. No other accepted Phase 1D untracked file
may change.

### 4.4 Report-only caller mutation

Populate only after every non-report gate passes:

```text
docs/task reports/Phase_1E.md
```

Do not edit the Phase 1E directive or any earlier directive or report.

No path outside Sections 4.1 through 4.4 is authorized.

---

## 5. Existing package and dependency boundary

Phase 1E adds no package and no dependency. Preserve exactly:

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
Presentation remains separate and directly callable.

Diagnostic Activation owns only the three neutral Experimental values after
this phase: the bootstrap permission, delivery permission, and pure combined
eligibility. It owns no external evidence or enforcement boundary.

---

## 6. Exact production API

Create exactly one production declaration:

```php
declare(strict_types=1);

namespace Kongono\DiagnosticActivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperPresentationEligibility
{
    public function __construct(
        private TrustedDeveloperBootstrapPermission $bootstrapPermission,
        private AdapterControlledDeliveryPermission $deliveryPermission,
    );

    public function allowsTrustedPresentation(): bool;
}
```

The constructor body must be empty after property promotion. It accepts no
defaults, nullable types, unions, intersections, references, variadics,
attributes, or additional parameters.

The method body must be exactly the semantic conjunction of the two existing
permission methods, using short-circuit boolean AND:

```php
return $this->bootstrapPermission->allowsTrustedPresentation()
    && $this->deliveryPermission->allowsTrustedPresentation();
```

The declaration must be:

- `final` and `readonly`;
- declared under `strict_types=1` with exactly the two API Metadata imports;
- marked exactly once with `#[Api(ApiStatus::Experimental)]`;
- in its exact PSR-4 file;
- limited to exactly two private promoted instance properties named
  `bootstrapPermission`, then `deliveryPermission`;
- limited to the public constructor and
  `allowsTrustedPresentation(): bool`;
- free of accessors, mutators, factories, static methods, traits, interfaces,
  constants, aliases, magic methods, serialization hooks, and mutable/static
  state; and
- documented contextually without narrating the code.

The class stores only the two enum cases. It stores no boolean result, delivery
identifier, evidence, subject, audience, request, address, credential,
timestamp, expiry, failure, diagnostic, or rendered content.

It throws no deliberate exception. Every constructible pair is valid. PHP's
normal type errors remain engine behavior and must not be caught, translated,
or documented as a Kongono diagnostic.

Preserve both existing enum sources and tests byte-for-byte. Do not add a
shared interface, trait, base class, helper, alias, collection, or generic
permission API.

---

## 7. Exact truth table and calling contract

The implementation must produce exactly:

| Bootstrap permission | Delivery permission | `allowsTrustedPresentation()` |
| --- | --- | --- |
| `Denied` | `Denied` | `false` |
| `Denied` | `Allowed` | `false` |
| `Allowed` | `Denied` | `false` |
| `Allowed` | `Allowed` | `true` |

No other state or outcome exists.

The caller must already possess:

1. an already-validated bootstrap permission chosen by a trusted future
   composition boundary; and
2. an independently established delivery permission chosen by a future outer
   adapter for the current delivery.

This class validates neither precondition. Direct construction is possible and
does not prove them. API status, PHP type safety, package ownership, readonly
state, object identity, and a `true` result are not authorization evidence.

The object may be created only for one current delivery flow. A future adapter
must not retain it in a singleton, container-wide service, global, static,
registry, worker cache, previous-operation state, thread-local, or fiber-local
slot. It cannot be reused as authority for another request, job, message,
command, tenant, user, operation, concurrent call, or delivery.

---

## 8. Locked security and non-enforcement semantics

Preserve all of these rules:

1. Both permissions remain independent and mandatory.
2. The result is true only for `Allowed + Allowed`.
3. Any denial is final for the trusted path; one permission never upgrades the
   other.
4. Missing, failed, uncertain, ambiguous, expired, or unverified external
   evidence must be resolved fail-closed before construction.
5. The class does not evaluate bootstrap configuration or delivery evidence.
6. The class does not authenticate a user or operator, establish a trusted
   listener, inspect a network boundary, or prove transport trust.
7. The class does not enforce its result or prevent direct calls to Diagnostic
   Presentation.
8. The class must be evaluated before any future presentation access, but Phase
   1E performs no such access.
9. A `true` result must not be stored, logged, serialized, transmitted, cached,
   persisted, or reused as a capability.
10. A false result causes no fallback, exception, diagnostic, log, metric,
    response, or side effect in this pure value.
11. No client-controlled request, route, header, cookie, body, session, token,
    hostname, origin, referrer, IP, socket, TLS state, proxy assertion,
    frontend value, or CLI input is accessed.
12. No environment label, truthy string, AI output, heuristic, exception,
    database value, provider response, extension, agent, global, registry,
    container, thread-local, or fiber-local state is accessed.
13. No secret, token, marker, hidden constructor, package visibility, or API
    attribute turns the object into a capability or security boundary.
14. No I/O, filesystem, network, logging, telemetry, event, persistence,
    serialization, Kernel, Graph, DI, Diagnostics, projector, renderer,
    request, response, transport, or adapter access occurs.
15. Enforcement remains a future outer-adapter responsibility.

---

## 9. Tests and architecture enforcement

### 9.1 Focused package tests

Create exactly two PHPUnit test methods in
`TrustedDeveloperPresentationEligibilityTest.php`, totaling exactly 20
assertions.

The first method has exactly four assertions and proves every truth-table row.

The second method has exactly sixteen assertions and proves:

1. the class is final;
2. the class is readonly;
3. exactly one `Api` attribute exists;
4. its status is `ApiStatus::Experimental`;
5. declared property names are exactly `bootstrapPermission`, then
   `deliveryPermission`;
6. property types are exactly `TrustedDeveloperBootstrapPermission`, then
   `AdapterControlledDeliveryPermission`;
7. each of the two properties is private, readonly, and non-static, producing
   exactly six property-flag assertions;
8. declared public methods are exactly `__construct` and
   `allowsTrustedPresentation`;
9. constructor parameter types are exactly the two permission types in the
   required order;
10. `allowsTrustedPresentation()` returns `bool`; and
11. `allowsTrustedPresentation()` accepts no parameters.

Use direct construction for truth-table behavior and reflection only for
shape. Do not use snapshots, mocks, data providers, fixtures, serialization,
reflection mutation, subprocesses, filesystem writes, environment variables,
or hidden declarations.

Focused package expectation after Phase 1E:

```text
packages/diagnostic-activation/tests/Unit
6 tests
44 assertions
```

The new test file contributes exactly:

```text
2 tests
20 assertions
```

### 9.2 Root API-status enforcement

Modify `ApiStatusConventionTest.php` only to:

- import `TrustedDeveloperPresentationEligibility` in sorted order; and
- add it to `EXPECTED_STATUS` as `ApiStatus::Experimental` in existing
  package/type order.

Preserve every other inventory, count, manifest assertion, diagnostic catalog,
exception-marker assertion, and helper unchanged except where the two additions
inherently alter adjacent lines.

The new declaration adds exactly five assertions: one autoloadability assertion
and four API-attribute/status assertions.

Focused expected result:

```text
6 tests
580 assertions
```

### 9.3 Full-suite expectation

The final complete suite must be exactly:

```text
247 tests
4233 assertions
```

If a different count is required, stop. Do not add filler assertions or weaken
coverage.

---

## 10. Composer, autoload, lockfile, and tooling invariants

Phase 1E changes no Composer manifest, lockfile, autoload mapping, PHPUnit XML,
PHPStan configuration, or PHP CS Fixer configuration.

Preserve byte-for-byte:

```text
.php-cs-fixer.dist.php
composer.json
composer.lock
packages/diagnostic-activation/composer.json
phpstan.neon.dist
phpunit.xml.dist
```

Do not run `composer update`. Do not edit or regenerate `composer.lock`.

The existing PSR-4 roots cover the two new files. Run:

```text
composer dump-autoload --optimize --strict-psr
```

It must pass without PSR-4 warning. Prove root autoloading for the new
production and test classes.

Final expected counts:

```text
package directories         7
package manifests           7
all Composer manifests      8
production PHP files       52
test PHP files             83
protected files           147
PHP CS Fixer files        135
API-status declarations    52
API-status attributes      50
```

Compute and report the final 147-path protected aggregate using the exact
algorithm in Section 3.1. The final digest is executor output.

Validate the unchanged root and package manifests directly. Any dependency,
manifest, lockfile, package graph, script, repository, stability, plugin,
platform, autoload, PHPUnit, PHPStan, or formatter delta is unauthorized.

---

## 11. ADR 0041 and documentation reconciliation

### 11.1 ADR 0041

Create:

```text
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
```

It must have:

```text
Status: Accepted
Date: 2026-08-15
```

Use Context, Decision, Rationale, Security implications, Lifecycle and
concurrency, Performance implications, Consequences, Rejected alternatives,
and What remains open.

Record exactly:

- exact class name, constructor, properties, method, and Experimental status;
- the four-row truth table;
- final readonly immutable ownership in Diagnostic Activation;
- pure conjunction of the two existing typed decisions;
- one-delivery scope and prohibition on reuse as ambient authority;
- direct construction and `true` not proving authorization;
- no validation of either input's external provenance;
- no stored result, evidence, delivery identity, or external context;
- no exception or diagnostic identifier;
- no package or dependency change;
- Diagnostic Presentation remaining separate and directly callable;
- pure calculation being resolved while enforcement remains absent;
- no I/O, ambient state, adapter, configuration, evidence evaluation,
  presentation access, or transport; and
- all rejected and deferred scope in this directive.

ADR 0041 must explicitly narrow the open “combined two-gate evaluation and
enforcement boundary” recorded by ADRs 0039 and 0040. The pure calculation is
resolved; the owning outer-adapter enforcement boundary, failure behavior, and
all integration remain open. Do not claim the security boundary is complete.

What remains open must include:

- outer adapter package, construction, and composition contract;
- where and how the eligibility object is created and consumed;
- enforcement before exception/diagnostic/presentation access;
- trusted-delivery evidence and evaluation mechanism;
- bootstrap configuration source, parser, validation failure, and reload;
- non-trusted and activation-failure behavior;
- authentication, listener, connection, proxy, and transport trust policy;
- HTTP, CLI, and other transport behavior;
- production diagnostic model and allowlists;
- simultaneous primary, activation, projection, rendering, transport, and
  cleanup failure representation;
- logging, telemetry, incidents, serialization, localization, persistence;
  and
- Kernel lifecycle, DI wiring, Application Graph, extensions, frontend, and
  agents.

Do not leave the exact pure eligibility API or truth table open after Phase 1E.

### 11.2 ADR 0039 and ADR 0040 reconciliation

Modify both additively. Preserve their historical phase boundaries and APIs.

Each must distinguish:

- Phase 1C's bootstrap value;
- Phase 1D's delivery value;
- Phase 1E's pure combined eligibility object;
- the still-absent external evidence evaluation and enforcement boundary; and
- ADR 0041's resolution of calculation only.

In `What remains open`, replace the combined calculation-plus-enforcement item
with the still-open enforcement/composition item. Do not rewrite history as if
the Phase 1E class existed earlier.

### 11.3 Existing documentation

Modify only authorized documentation so it consistently states:

- the runtime still contains seven packages;
- Diagnostic Activation owns exactly two permission enums and one final
  readonly eligibility class;
- the class stores the two decisions and computes the exact AND truth table;
- eligibility is scoped to one current delivery and cannot be ambient/reused;
- direct construction and true do not prove authority;
- bootstrap parsing and delivery evidence evaluation remain outside it;
- pure calculation exists but enforcement does not;
- Diagnostic Presentation remains separate and directly callable;
- no diagnostic access, projector/renderer call, request inspection, adapter,
  transport, served page, production enforcement, or side effect exists; and
- package graph, API-status counts, and current tests are accurate.

Keep Phase 1C and Phase 1D implementation notes historical and additive. Add
separate Phase 1E notes. Do not rewrite earlier directives or reports.

---

## 12. Validation gates

### Gate A - caller and baseline

Before broad quality or mutation:

- verify independently supplied Phase 1E directive identity;
- verify the Phase 1E report is the exact zero-byte placeholder;
- verify all twelve historical caller identities;
- verify exact 46-path pre-change status and file-level inventories;
- verify all eight accepted Phase 1D implementation-file identities;
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
PHP CS Fixer findings   0 of 133
PHPStan errors          0
PHPUnit tests           245
PHPUnit assertions      4208
```

If only managed Composer cache/network access blocks the command, rerun the
identical command with approved access. Never weaken or skip the audit.

### Gate C - implementation

Create and modify only Section 4 paths. Preserve all locked manifests,
lockfile, tooling files, existing source/tests, and caller records exactly.

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

Expected focused results:

```text
Diagnostic Activation   6 tests, 44 assertions
API architecture        6 tests, 580 assertions
```

### Gate E - direct architecture and security proof

Prove directly:

- exact class reflection shape, constructor/property order and types, method,
  attribute, final, and readonly status;
- all four truth-table rows;
- no stored boolean result or extra property/method;
- both existing enums/tests remain byte-identical;
- exact package graph and absence of reverse dependencies;
- manifests, lockfile, and tooling remain byte-identical;
- no production import outside API Metadata;
- no config, environment, request, superglobal, filesystem, or network access;
- no exception, diagnostic, projector, renderer, Kernel, Graph, DI, adapter,
  transport, logging, telemetry, persistence, or serialization code;
- no enforcement call, source access, response behavior, or delivery;
- exact created and modified inventories;
- strict UTF-8/LF, one final LF, no BOM/NUL/trailing whitespace/conflict marker;
- ordinary-file/default-stream/no-reparse/no-executable modes;
- all local Markdown links resolve;
- no secret-category or prohibited artifact finding;
- `git diff --check` passes; and
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
PHP CS Fixer findings   0 of 135
PHPStan errors          0
PHPUnit tests           247
PHPUnit assertions      4233
```

The same unchanged environment-only retry rule applies.

### Gate G - report population

Only after Gates A through F pass, populate the existing Phase 1E report. Then
verify report structure, file safety, final status, exact scope, and external
report identity. Do not run quality again afterward.

---

## 13. Final Git and file state

No staging or commit is authorized.

Before report population, exact Phase 1E scope relative to accepted Phase 1D:

```text
new non-caller paths                     3
tracked paths additionally edited        19
accepted untracked paths edited            3
```

The nineteen tracked paths were already modified, so the final tracked status
count remains 24.

After report population, final status is exactly:

```text
modified tracked paths    24
staged paths               0
untracked paths           25
total status paths        49
```

The 25 untracked paths are:

- the twelve immutable Phase 1B through 1D caller records;
- the Phase 1E directive;
- the populated Phase 1E report;
- the eight accepted Phase 1D untracked implementation files; and
- the three Section 4.1 Phase 1E files.

HEAD, tree, parent, commit count, branch, refs, remotes, tags, and history must
remain unchanged. Do not create a checkpoint.

---

## 14. Prohibited scope

Do not implement or introduce:

- enforcement, a guard, middleware, handler, policy service, access-control
  service, capability, token, grant, registry, service locator, or ambient
  authorization;
- access to exceptions, diagnostic metadata/context, projectors, renderers,
  rendered documents, source, traces, or production presentation;
- evidence evaluation, authentication, operator identity, listener/network
  policy, proxy assertions, IP/CIDR, TLS, or certificate logic;
- requests, responses, routing, servers, headers, status, content type, CSP,
  cache, transport, HTTP, CLI, or served pages;
- configuration keys, environment variables, files, loaders, schemas,
  validation exceptions, reload, or watchers;
- a backed enum, scalar parser, shared interface, trait, base class, helper,
  alias, factory, collection, serializer, accessor, mutator, or static API;
- a stored boolean result, evidence, identity, timestamp, expiry, reason,
  failure, or external context;
- logging, telemetry, traces, metrics, incidents, persistence, localization,
  events, retry, fallback, recursion, cleanup, or multi-failure behavior;
- Kernel lifecycle, operation scope, DI binding, Application Graph, extension,
  plugin, frontend, agent, ORM, database, or third-party integration;
- a new package, dependency, exception marker, base exception, diagnostic
  catalog, diagnostic ID, Composer plugin, or stability flag;
- manifest, lockfile, autoload mapping, PHPUnit, PHPStan, or formatter changes;
- suppression, baseline, exclusion, policy exception, or weakened quality rule;
- staging, commit, branch, tag, remote, push, stash, reset, restore, clean,
  rebase, merge, cherry-pick, amend, or history rewrite; or
- any Phase 1F or later capability.

---

## 15. Strict acceptance criteria

Every criterion must pass:

1. All authorities were read completely before mutation.
2. Independently delivered Phase 1E directive identity matched.
3. Phase 1E report began as the exact zero-byte caller placeholder.
4. All twelve historical caller records retained exact identities and states.
5. Git identity, semantic index, refs, exact inventories, lockfile, protected
   aggregate, modes, streams, and starting counts matched.
6. All eight accepted Phase 1D implementation files matched their locked
   starting identities.
7. Pre-change quality passed at 245 tests and 4,208 assertions.
8. Exactly three non-caller paths were created, nineteen tracked paths edited,
   and three accepted untracked implementation paths edited.
9. No tracked path outside the accepted 24-path inventory changed.
10. Exactly one production declaration was added.
11. The class is exactly final, readonly, Experimental, and in the required
    namespace/file.
12. It has exactly two private readonly non-static promoted properties with the
    required names, types, and order.
13. Public methods are exactly the constructor and
    `allowsTrustedPresentation(): bool`.
14. Constructor parameters have exact non-null types/order and no defaults.
15. All four truth-table rows match the Accepted two-permission policy.
16. The method is the pure conjunction of the two enum methods.
17. No result, evidence, identity, external context, or mutable/static state is
    stored.
18. Existing enum sources and tests remain byte-identical.
19. The class is documented as eligibility calculation, not authorization
    proof or enforcement.
20. No evidence evaluation, enforcement, config, adapter, transport,
    diagnostic access, or side effect exists.
21. No package, dependency, manifest, lockfile, autoload mapping, or tooling
    change occurred.
22. Optimized strict PSR-4 autoload passed without warning.
23. Focused Diagnostic Activation tests passed at 6 tests, 44 assertions.
24. API architecture tests passed at 6 tests, 580 assertions.
25. Exact production inventories and API statuses are enforced.
26. ADR 0041 is Accepted, dated 2026-08-15, and resolves only pure calculation.
27. ADRs 0039 and 0040 preserve history and narrow open work to enforcement.
28. Documentation consistently distinguishes implemented calculation from
    absent external validation and enforcement.
29. Diagnostic Presentation remains separate, directly callable, and unchanged
    except for authorized README reconciliation.
30. No exception, diagnostic ID, marker, or failure family was added.
31. Final counts are exactly 7 package directories, 7 package manifests, 8
    total manifests, 52 production PHP files, 83 test PHP files, 147 protected
    files, 135 fixer files, 52 API-status declarations, and 50 attributes.
32. Final quality passed at 247 tests and 4,233 assertions with zero advisories,
    fixer findings, and PHPStan errors.
33. Links, text integrity, file safety, streams, modes, secret scans, and
    `git diff --check` passed.
34. Final status is exactly 24 modified, 25 untracked, 49 total, zero staged;
    HEAD and history remained unchanged; report structure and identity passed;
    and no prohibited or later work occurred.

Any failed or unverified criterion makes status `Blocked` or `Partial`, never
`Complete`.

---

## 16. Completion report

Populate only after complete success using exactly:

```text
# PHASE 1E TASK COMPLETION REPORT

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

- enumerate all three created, nineteen tracked-edited, and three accepted
  untracked-edited paths;
- report all fourteen caller records and final report state;
- report pre/post Git identity, exact file-level status, and no Git mutation;
- report the class reflection shape and exact truth table;
- report preservation of both existing enum source/test pairs;
- report package graph, reverse dependencies, manifest/lock/tooling invariance;
- report strict optimized PSR-4 evidence;
- report focused and full test/assertion counts;
- report PHPStan, fixer, audit, platform, and manifest results;
- report final inventories and protected aggregate digest;
- report ADR 0041 identity and ADR 0039/0040 reconciliation;
- report link, text, file-safety, stream, secret, and scope checks;
- state no external validation, enforcement, diagnostic access, adapter,
  transport, configuration, or later capability exists;
- enumerate PASS/FAIL for all 34 criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not include the report's own final byte count or SHA-256 inside its body.
Return both separately after final read-back.

---

## 17. Required final outcome

Phase 1E ends with exactly one new final readonly Experimental eligibility
class in the existing Diagnostic Activation package. It receives the two
already-typed permission decisions and implements only the exact four-row AND
truth table.

The package then contains:

```text
TrustedDeveloperBootstrapPermission
AdapterControlledDeliveryPermission
TrustedDeveloperPresentationEligibility
```

The first two represent independent decisions. The third calculates their pure
combined eligibility for one delivery. None proves the external provenance of
its inputs, and none enforces access.

No evidence mechanism, authentication rule, configuration source, enforcement
boundary, adapter, request, response, transport, diagnostic access, projection,
rendering, delivery, production model, logging, telemetry, persistence, or
runtime integration exists after this phase.

Do not begin the next phase.
