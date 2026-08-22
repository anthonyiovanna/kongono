# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1C
## Typed Trusted-Developer Bootstrap Permission Foundation

**Directive status:** Architect-authorized execution contract
**Phase type:** Narrow package and value implementation
**Execution role:** Executor, not architect
**Date:** 2026-08-15

---

## 1. Mission

Phase 1A accepted a fail-closed two-permission architecture for trusted
developer diagnostic presentation. Phase 1B preserved all accepted work through
Phase 1A in commit `837863d11feda282e4959ef7ec98a810c184531e`.

Phase 1C must implement only the first typed input to that future policy: one
explicit, immutable, server-bootstrap-owned decision describing whether trusted
developer presentation was denied or allowed at bootstrap.

Create a new acyclic package:

```text
kongono/diagnostic-activation
Kongono\DiagnosticActivation
```

Its sole production declaration is the unbacked Experimental enum:

```text
Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission
```

with exactly:

```text
case Denied
case Allowed
public function allowsTrustedPresentation(): bool
```

`Denied` returns `false`; `Allowed` returns `true`.

The type is a validated decision value passed from a trusted future composition
boundary. It is not a configuration parser, a capability token, proof of
transport authorization, a delivery permission, a combined gate, or an
enforcement mechanism. Selecting `Allowed` directly in PHP does not make an
audience trusted. A future adapter must still establish its independent
adapter-controlled delivery permission before source access, projection,
rendering, or delivery.

Phase 1C must not implement raw configuration, environment reading, delivery
permission, eligibility evaluation, an adapter, request/response behavior,
exception access, projection, rendering, or delivery.

---

## 2. Authority and mandatory stop rule

Before quality validation or mutation, read completely:

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
18. `packages/api-metadata/README.md`;
19. `packages/api-metadata/src/Api.php`;
20. `packages/api-metadata/src/ApiStatus.php`;
21. `packages/diagnostic-presentation/README.md`;
22. `packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php`;
23. `packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php`;
24. `composer.json`;
25. `.php-cs-fixer.dist.php`;
26. `phpstan.neon.dist`;
27. `phpunit.xml.dist`;
28. `tests/Architecture/ApiStatusConventionTest.php`;
29. `docs/dev_plan/kongono_phase_1A_codex_directive.md`;
30. `docs/task reports/Phase_1A.md`;
31. `docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md`;
32. `docs/task reports/Phase_1B_3_correction.md`;
33. this Phase 1C directive; and
34. the zero-byte `docs/task reports/Phase_1C.md` caller placeholder.

Earlier directives and reports are immutable historical evidence, not active
implementation authority. Current Accepted ADRs, repository policy, and this
directive control Phase 1C.

The Phase 1C directive is caller-controlled authority. Its exact expected byte
count and SHA-256 must be supplied independently by the architect outside this
file. The local file is not self-authenticating. If either value is absent or
does not match, stop before broad quality validation or mutation and report the
observed identity.

Apply literally:

```text
STOP
ASK
DO NOT GUESS
```

Stop before continuing if any caller record, Git identity, path inventory,
file identity, status classification, package boundary, dependency result,
autoload result, test count, API surface, or security rule differs from this
directive.

Do not repair, normalize, stage, commit, reset, restore, clean, stash, rebase,
amend, rewrite, or otherwise alter a mismatch.

---

## 3. Locked starting state

### 3.1 Git and implementation baseline before caller setup

Require exactly:

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
modified tracked paths      0
staged paths                0
untracked paths             8
total status paths          8
remotes                     0
tags                        0
package directories         6
package manifests           6
all Composer manifests      7
production PHP files       49
test PHP files             80
PHP CS Fixer files        129
API-status declarations    49
API-status attributes      47
PHPUnit tests             241
PHPUnit assertions       4170
```

The semantic index must match HEAD exactly: 319 stage-zero mode-`100644`
entries, no higher stage, no intent-to-add entry, no staged delta, and no
unstaged tracked delta. Raw index cache/stat bytes are not a content identity;
do not treat a metadata-only refresh as a project change when semantic entries
and HEAD equality remain exact.

`composer.lock` must remain:

```text
bytes                    180654
SHA-256                  FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387
```

The protected set must remain 140 files with aggregate SHA-256:

```text
82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E
```

### 3.2 Existing Phase 1B caller records

The exact eight untracked paths are:

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

They must remain ordinary, untracked, unstaged, absent from HEAD, and limited to
the default data stream. Preserve their bytes exactly. Historical copied report
formatting is not active authority and creates no formatting exception for new
Phase 1C content.

### 3.3 Phase 1C caller setup

The caller adds exactly:

```text
docs/dev_plan/kongono_phase_1C_codex_directive.md
docs/task reports/Phase_1C.md
```

The report must initially be an ordinary zero-byte, untracked, unstaged file,
absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Before implementation, exact status is:

```text
modified tracked paths     0
staged paths               0
untracked paths           10
total status paths        10
```

The ten paths are the eight Phase 1B records plus the two Phase 1C caller
records. Stop if anything else exists.

---

## 4. Exact authorized file boundary

### 4.1 Create exactly these five non-caller files

```text
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
packages/diagnostic-activation/README.md
packages/diagnostic-activation/composer.json
packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php
packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php
```

### 4.2 Modify exactly these twenty-four tracked files

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

### 4.3 Report-only caller mutation

Populate only after every non-report gate passes:

```text
docs/task reports/Phase_1C.md
```

Do not edit the Phase 1C directive or any Phase 1B caller record.

No path outside Sections 4.1 through 4.3 is authorized.

---

## 5. New package contract

### 5.1 Package identity and graph

Create:

```text
package name       kongono/diagnostic-activation
namespace          Kongono\DiagnosticActivation\
source root        src/
test namespace     Kongono\DiagnosticActivation\Tests\
test root          tests/
type               library
```

The package manifest requires exactly, in sorted order:

```json
{
  "kongono/api-metadata": "*",
  "php": ">=8.5"
}
```

It has no production or development dependency beyond those exact requirements
and its own PSR-4 mappings. It declares no script, plugin, binary, extra
metadata, conflict, provide, replace, suggest, repository, minimum stability,
or version.

The resulting production package graph is exactly:

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

No existing package depends on Diagnostic Activation in Phase 1C. Diagnostic
Presentation must not depend on it. The future adapter may consume both only
under a later directive.

### 5.2 Package purpose

The package owns only the typed trusted-bootstrap permission value. It does not
own raw configuration, config validation, transport delivery permission,
combined eligibility, exception access, presentation, or delivery.

Its README must cover:

- purpose and package boundary;
- exact two-case value semantics;
- absence and invalid raw input remaining outside the package;
- `Denied` as the future composition boundary's result for missing permission;
- invalid or ambiguous raw configuration being rejected before an adapter
  serves work, by a future configuration layer;
- `Allowed` still requiring independent adapter-controlled delivery permission;
- direct enum-case selection not proving trust or authorization;
- immutable, stateless, I/O-free, persistent-runtime-safe behavior;
- no ambient/global/request/config access;
- Experimental stability;
- exact non-goals; and
- links to ADRs 0038 and 0039, security guidance, error handling, package
  layout, and Diagnostic Presentation.

Do not describe the package as an authorization engine, gatekeeper, access
control system, capability system, or security boundary.

---

## 6. Exact production API

Create exactly one production declaration:

```php
namespace Kongono\DiagnosticActivation;

#[Api(ApiStatus::Experimental)]
enum TrustedDeveloperBootstrapPermission
{
    case Denied;
    case Allowed;

    public function allowsTrustedPresentation(): bool;
}
```

The actual method body must return `true` only for `self::Allowed` and `false`
for `self::Denied` using an explicit identity comparison. It must not branch on
case names, strings, integers, environment values, or other external data.

The declaration must be:

- an unbacked enum;
- `final` by PHP enum semantics;
- marked exactly once with `#[Api(ApiStatus::Experimental)]`;
- in its exact PSR-4 file;
- limited to the cases `Denied`, then `Allowed`, in that order;
- limited to its explicit `allowsTrustedPresentation(): bool` method plus PHP's
  automatic `UnitEnum::cases()` surface;
- free of custom properties, traits, interfaces, constants, constructors,
  factories, parsers, serializers, aliases, magic methods, and static mutable
  state; and
- documented contextually without narrating the code.

Because it is unbacked, it must expose no `from()` or `tryFrom()` raw-value API.
Do not add a string or integer representation. Case names are source-level API
names, not accepted configuration tokens.

No exception type or diagnostic identifier is added. Both enum cases are valid;
invalid raw input belongs to the future configuration boundary and must never be
passed to or parsed by this package.

---

## 7. Locked security and lifecycle semantics

The implementation and documentation must preserve all of these rules:

1. Trusted presentation still requires both trusted bootstrap permission and
   adapter-controlled delivery permission.
2. This enum models only the first decision.
3. `Allowed` alone never authorizes source access, projection, field copying,
   rendering, or delivery.
4. `Denied` prohibits the trusted path regardless of any future delivery value.
5. Absence never becomes `Allowed`; a future trusted composition boundary must
   supply `Denied` when no enabling permission was configured.
6. Raw configuration parsing and validation are not implemented.
7. Invalid or ambiguous raw configuration remains a future bootstrap failure,
   not a third enum case, implicit denial parser, or fallback to `Allowed`.
8. No client-controlled request, route, header, cookie, body, session, token,
   hostname, origin, referrer, or frontend value may select the decision.
9. No environment label, truthy string, AI output, heuristic, exception value,
   API status, database value, provider response, extension, agent, prior
   operation, mutable global, registry, thread-local, or fiber-local state may
   authorize trusted output.
10. The enum is immutable and retains no per-operation state.
11. A future adapter must retain one already-validated bootstrap decision for
    its own lifetime; Phase 1C creates no adapter or reload behavior.
12. Delivery permission remains per-delivery and adapter-owned; Phase 1C creates
    no delivery value or persistence.
13. Diagnostic Presentation remains directly callable and cannot enforce this
    outer rule.
14. No secret, token, marker, package visibility trick, constructor restriction,
    or API attribute turns the enum into a security boundary.
15. No I/O, filesystem, environment, network, logging, telemetry, event,
    persistence, serialization, cache, container, Kernel, Graph, DI, projector,
    renderer, or exception access occurs.

---

## 8. Tests and architecture enforcement

### 8.1 Focused package tests

Create exactly two PHPUnit test methods in
`TrustedDeveloperBootstrapPermissionTest.php`, totaling exactly 12 assertions.

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

Do not use snapshots, mocks, data providers, fixtures, serialization,
reflection mutation, subprocesses, filesystem writes, environment variables,
or hidden test declarations.

Focused expected result:

```text
2 tests
12 assertions
```

### 8.2 Root API-status and package-graph enforcement

Modify `ApiStatusConventionTest.php` only as required to:

- import `TrustedDeveloperBootstrapPermission`;
- add it to the exact status inventory as Experimental;
- change production package-source discovery from six to seven directories;
- add an exact manifest assertion for `kongono/diagnostic-activation`;
- add `kongono/diagnostic-activation: @dev` to the exact root requirement
  assertion; and
- preserve every existing diagnostic catalog, exception-marker, dependency-
  separation, and package-manifest assertion unchanged otherwise.

The new declaration and new package-manifest block must add exactly nine
assertions to the existing focused architecture suite.

Focused expected result:

```text
6 tests
570 assertions
```

Do not weaken dynamic discovery, replace exact inventories with broad checks,
or remove an existing assertion.

### 8.3 Full-suite expectation

The final complete suite must be exactly:

```text
243 tests
4191 assertions
```

If the implementation needs a different count, stop. Do not add filler
assertions or weaken coverage to force the target.

---

## 9. Composer, autoload, lockfile, and tooling expansion

### 9.1 Root Composer wiring

Modify root `composer.json` in existing sorted style to add exactly:

- `kongono/diagnostic-activation: @dev` to `require`;
- `Kongono\\DiagnosticActivation\\Tests\\` mapped to
  `packages/diagnostic-activation/tests/` in root `autoload-dev`; and
- package validation for `packages/diagnostic-activation` in
  `validate:packages`.

Do not add a root production PSR-4 mapping; the path package owns production
autoloading. Do not add a plugin, repository, script category, dependency,
stability flag, alias, or policy exception.

### 9.2 Composer-generated lock update

After both manifests are correct, run exactly:

```text
composer update kongono/diagnostic-activation --no-interaction
```

If the command fails only because the managed environment cannot access the
Composer cache or network, rerun the identical command with approved access.

Never edit `composer.lock` manually.

The allowed lock delta is limited to:

- the root content hash required by the root manifest change;
- one new `kongono/diagnostic-activation` path-package record;
- its exact requirements on `kongono/api-metadata: *` and `php: >=8.5`;
- its generated path-package reference and path transport metadata; and
- mechanical lock metadata required solely for that addition.

No existing package or third-party package version, requirement, source,
dist/reference, abandonment state, advisory policy, plugin policy, or platform
requirement may change. If Composer changes any existing package reference or
any other package record, stop and report the exact generated delta. Do not
accept or hand-edit it.

### 9.3 Tooling configuration

Add the new package's `src` and `tests` directories in the existing package
order to:

- `.php-cs-fixer.dist.php`;
- `phpstan.neon.dist`;
- `phpunit.xml.dist` testsuite; and
- `phpunit.xml.dist` source include.

Do not change any rule, level, PHP version, cache policy, failure policy,
exclusion, baseline, suppression, or existing directory.

Final expected counts:

```text
package directories         7
package manifests           7
all Composer manifests      8
production PHP files       50
test PHP files             81
protected files           143
PHP CS Fixer files        131
API-status declarations    50
API-status attributes      48
```

Compute and report the final 143-path protected aggregate SHA-256 using the
existing protected-set algorithm. The final digest is an output, not an
architect-supplied assumption.

### 9.4 Autoload validation

Run:

```text
composer dump-autoload --optimize --strict-psr
```

It must succeed with no PSR-4 warning. Prove from root autoloading that the
production enum and test class are autoloadable. Also validate the new package
manifest directly.

Do not generate a package-local vendor directory, commit generated autoload
files, or add classmap/file autoloading.

---

## 10. ADR 0039 and documentation reconciliation

### 10.1 ADR 0039

Create:

```text
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
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

- ownership by the new Diagnostic Activation package;
- the exact acyclic package edge to API Metadata only;
- the exact unbacked enum name, cases, method, and Experimental status;
- why unbacked cases prevent an implicit raw-string parser surface;
- `Denied` and `Allowed` semantics;
- absence not constructing `Allowed`;
- invalid raw configuration remaining a future pre-serving validation failure;
- `Allowed` not establishing delivery permission;
- direct PHP case selection not proving authorization;
- no combined eligibility or enforcement in Phase 1C;
- no dependency from Diagnostic Presentation to Diagnostic Activation;
- immutability, no ambient state, no I/O, and persistent-runtime safety;
- no new exception or diagnostic identifier; and
- every rejected and deferred capability in this directive.

What remains open must include:

- raw server configuration key/source/schema/parser and validation failure;
- construction/composition API that chooses the enum;
- adapter package and lifecycle ownership;
- adapter-controlled delivery permission type and mechanism;
- the combined two-gate evaluation/enforcement boundary;
- non-trusted failure behavior;
- HTTP/CLI or other transport behavior;
- production diagnostic model and allowlists;
- logging, telemetry, incidents, serialization, localization, reload, Kernel,
  DI, Graph, extensions, frontend, and agents.

Do not leave “exact typed bootstrap decision API and ownership” open after this
phase; Phase 1C resolves exactly that item.

### 10.2 Existing documentation

Modify only the authorized documentation paths to ensure they agree that:

- the runtime now contains seven packages;
- Diagnostic Activation owns the implemented typed bootstrap permission;
- Diagnostic Presentation remains separate and does not depend on it;
- the enum is implemented, Experimental, immutable, unbacked, and I/O-free;
- only the bootstrap decision value exists;
- delivery permission and the combined gate remain unimplemented;
- no raw configuration is read or parsed;
- invalid raw configuration rejection remains a future composition concern;
- no adapter, request/response, served page, or runtime enforcement exists;
- direct enum selection or direct presentation calls do not prove
  authorization;
- both permissions still must precede presentation access; and
- the package graph and API-status counts are current.

Update ADR open-work lists narrowly: remove only the now-resolved exact typed
bootstrap API/ownership item and retain configuration, delivery, adapter,
transport, and production work.

Do not rewrite historical directives or reports. Do not imply that Phase 1C
implements the Phase 1A truth-table enforcement.

---

## 11. Validation gates

### Gate A - caller and baseline

Before broad quality or mutation:

- verify the independently supplied Phase 1C directive identity;
- verify the Phase 1C report is the exact zero-byte placeholder;
- verify all eight Phase 1B caller identities;
- verify exact ten-path pre-change status;
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
PHP CS Fixer findings   0 of 129
PHPStan errors          0
PHPUnit tests           241
PHPUnit assertions      4170
```

The unchanged environment-only approved-access retry is permitted. Do not
weaken or skip the fail-closed audit.

### Gate C - implementation and lock generation

Create/modify only Section 4 paths. Generate the lockfile only through the exact
Composer command. Stop immediately on an unauthorized lock delta.

### Gate D - focused validation

Run at minimum:

```text
composer --working-dir=packages/diagnostic-activation validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostic-activation/tests/Unit
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer analyse
composer cs
```

Expected focused tests:

```text
Diagnostic Activation   2 tests, 12 assertions
API architecture        6 tests, 570 assertions
```

### Gate E - direct architecture and security proof

Prove directly:

- exact enum reflection shape, cases, method, attribute, and lack of backing;
- absence of `from()` and `tryFrom()`;
- package dependency graph and absence of reverse dependencies;
- no production import outside API Metadata;
- no config/environment/request/superglobal/filesystem/network access;
- no exception, projector, renderer, Kernel, Graph, DI, Diagnostics, adapter,
  transport, logging, telemetry, or serialization code;
- exact created and modified inventories;
- strict UTF-8/LF, one final LF, no BOM/NUL/trailing whitespace/conflict marker;
- ordinary-file/default-stream/no-reparse/no-executable modes;
- local Markdown links resolve;
- no secret-category or prohibited artifact finding;
- `git diff --check` passes except the immutable untracked historical Phase 1B
  records are outside the tracked Phase 1C diff; and
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
PHP CS Fixer findings   0 of 131
PHPStan errors          0
PHPUnit tests           243
PHPUnit assertions      4191
```

The same unchanged environment-only retry rule applies.

### Gate G - report population

Only after Gates A through F pass, populate the existing Phase 1C report. Then
verify report structure, file safety, final status, exact scope, and external
report identity. Do not run quality again after report population.

---

## 12. Final Git and file state

No staging or commit is authorized.

Before report population, exact non-caller implementation scope is:

```text
modified tracked paths    24
new non-caller paths        5
```

After report population, final status is exactly:

```text
modified tracked paths    24
staged paths               0
untracked paths           15
total status paths        39
```

The 15 untracked paths are:

- the eight locked Phase 1B caller records;
- the Phase 1C directive;
- the populated Phase 1C report; and
- the five Section 4.1 implementation/documentation files.

HEAD, tree, parent, commit count, branch, refs, remotes, tags, and history must
remain unchanged. Do not create a checkpoint in Phase 1C.

---

## 13. Prohibited scope

Do not implement or introduce:

- a backed enum, raw string/integer value, parser, `from()`, or `tryFrom()`;
- configuration keys, environment variables, files, loaders, schemas,
  validation exceptions, reload, or watchers;
- delivery permission types or adapter authorization mechanisms;
- a combined gate, eligibility evaluator, policy service, guard, token,
  capability, grant, registry, service locator, or ambient current state;
- any adapter, request, response, middleware, router, handler, server, route,
  header, status, content type, CSP, cache, or served page;
- any access to an exception, diagnostic metadata/context, projector, renderer,
  or rendered document;
- production presentation, redaction, allowlists, or fallback output;
- logging, telemetry, traces, metrics, incidents, persistence, serialization,
  localization, events, retry, recursion, or cleanup behavior;
- Kernel lifecycle, operation scope, DI binding, Application Graph node/edge,
  extension, plugin, CLI, frontend, agent, ORM, or database integration;
- a new exception marker, base exception, diagnostic catalog, diagnostic ID, or
  dependency on Diagnostics;
- any third-party dependency, Composer plugin, suppression, baseline,
  exclusion, policy exception, or weakened quality rule;
- staging, commit, branch, tag, remote, push, stash, reset, restore, clean,
  rebase, merge, cherry-pick, amend, or history rewrite; or
- any Phase 1D or later capability.

---

## 14. Strict acceptance criteria

Every criterion must pass:

1. All required authorities were read completely before mutation.
2. The independently delivered Phase 1C directive identity matched.
3. The Phase 1C report began as the exact zero-byte caller placeholder.
4. All eight Phase 1B caller records retained exact identities and Git states.
5. Git identity, history, semantic index, refs, inventories, lockfile, protected
   aggregate, modes, streams, and pre-change counts matched.
6. Pre-change quality passed at 241 tests and 4,170 assertions.
7. Exactly five non-caller paths were created and twenty-four tracked paths were
   modified.
8. `kongono/diagnostic-activation` is the seventh package and depends only on
   API Metadata and PHP.
9. No existing package depends on Diagnostic Activation.
10. Exactly one production declaration was added.
11. The exact unbacked enum has only `Denied`, `Allowed`, automatic `cases()`,
    and `allowsTrustedPresentation(): bool`.
12. The enum is Experimental and has no backing value, raw parser, property,
    trait, custom interface, factory, serializer, or mutable state.
13. `Denied` returns false and `Allowed` returns true.
14. The enum is documented as a typed bootstrap decision, not authorization or
    a security boundary.
15. Delivery permission and combined enforcement remain absent.
16. No raw config, environment, request, ambient state, I/O, or presentation
    access exists in the new package.
17. Root Composer, autoload-dev, package validation, PHPUnit, PHPStan, and PHP CS
    Fixer wiring include the new package without weakening existing settings.
18. Composer alone generated the lockfile.
19. Lock delta is limited to the root content hash and exact new local package
    record; no existing or third-party package record changed.
20. Optimized strict PSR-4 autoload generation passed without warning.
21. Focused package tests passed at exactly 2 tests and 12 assertions.
22. API architecture tests passed at exactly 6 tests and 570 assertions.
23. Exact production/package inventories and status attributes are enforced.
24. ADR 0039 is Accepted, dated 2026-08-15, and records only the authorized
    decision.
25. Documentation consistently distinguishes the implemented value from absent
    config, delivery permission, enforcement, adapter, and transport behavior.
26. Existing projection/renderer APIs and Diagnostic Presentation package
    remain unchanged except for its authorized README reconciliation.
27. No new exception, diagnostic ID, or package marker was introduced.
28. Final counts are exactly 7 package directories, 7 package manifests, 8
    total manifests, 50 production PHP files, 81 test PHP files, 143 protected
    files, 131 fixer files, 50 API-status declarations, and 48 attributes.
29. Final quality passed at exactly 243 tests and 4,191 assertions with zero
    advisories, fixer findings, and PHPStan errors.
30. Links, text integrity, file safety, streams, modes, secret-category scans,
    and `git diff --check` passed.
31. Final status is exactly 24 modified, 15 untracked, 39 total, and zero staged.
32. HEAD, tree, parent, history, branch, refs, remotes, and tags remained
    unchanged.
33. The report is structurally exact, evidence-backed, externally identified,
    and populated only after all non-report gates passed.
34. No prohibited scope, policy weakening, Git mutation, or later-phase work
    occurred.

Any failed or unverified criterion makes status `Blocked` or `Partial`, never
`Complete`.

---

## 15. Completion report

Populate only after complete success using exactly:

```text
# PHASE 1C TASK COMPLETION REPORT

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

- enumerate all five created and twenty-four modified non-report paths;
- report all ten pre-change caller records and the final report state;
- report pre/post Git identities, status arithmetic, and lack of Git mutation;
- report the enum's exact reflection shape and behavior;
- report package dependency and reverse-dependency proofs;
- report Composer command and exact semantic lock delta;
- report optimized strict PSR-4 evidence;
- report focused and complete test/assertion counts;
- report PHPStan, fixer, audit, and platform results;
- report final counts and protected aggregate digest;
- report ADR 0039 identity and documentation reconciliation;
- report link, text, file-safety, stream, secret-category, and scope checks;
- state no delivery permission, enforcement, config reader, adapter, transport,
  or later capability exists;
- enumerate PASS/FAIL for all 34 criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not include the report's own final byte count or SHA-256 inside its body.
Return both separately after final read-back.

---

## 16. Required final outcome

Phase 1C ends with one new acyclic package and one Experimental unbacked enum
that carries an already-validated trusted-bootstrap decision without parsing or
enforcing anything.

`Denied` and `Allowed` are explicit immutable states. Only `Allowed` reports
that bootstrap permission permits the trusted path, and it remains insufficient
without a future adapter's separate delivery permission.

No configuration source, validation failure, delivery type, combined gate,
adapter, request, response, diagnostic access, projection call, renderer call,
served page, production model, logger, telemetry path, incident system, or
runtime integration exists after this phase.

Do not begin the next phase.
