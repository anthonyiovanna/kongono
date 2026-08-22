# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1H

## Transport-Neutral Trusted Diagnostic Handler Implementation

**Directive type:** bounded package implementation

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Implement the exact transport-neutral trusted diagnostic handler accepted by ADR 0042 and corrected by Phase 1G.1.

Create the eighth Kongono package:

```text
kongono/diagnostic-handler
Kongono\DiagnosticHandler\
```

Create exactly one production declaration:

```php
Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter
```

Its sole public method must:

1. evaluate the supplied `TrustedDeveloperPresentationEligibility` first;
2. return `null` immediately when eligibility is false, without calling any source accessor or presentation API;
3. when eligibility is true, project the supported package exception with the existing `TrustedDeveloperDiagnosticProjector`;
4. render that detached model with the existing `TrustedDeveloperHtmlRenderer`; and
5. return the renderer's exact HTML string unchanged.

The handler catches nothing. All `Throwable` values propagate unchanged. It performs no delivery and establishes no permission provenance.

### 1.1 Required outcome

After Phase 1H:

- Kongono has eight implemented package directories;
- Diagnostic Handler is the sole implemented leaf above Activation and Presentation;
- the package contains exactly one production PHP declaration;
- the class is Experimental, final, readonly, stateless, and constructor-free;
- denial is proven to avoid source access;
- allowance is proven against real Kernel, Graph, and DI contextual exceptions;
- existing projector and renderer output remains exact;
- no handler exception, diagnostic ID, transport, response, adapter, configuration, evidence evaluator, or non-trusted model is added;
- canonical quality passes with exactly 254 tests and more than 4,233 assertions; and
- no Git staging or commit occurs.

---

## 2. Authority and mandatory stop rule

### 2.1 Read completely before acting

Read these authorities in order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `README.md`
4. `SECURITY.md`
5. `docs/doctrine.md`
6. `docs/documentation-standard.md`
7. every Accepted ADR in `docs/adr/`
8. `docs/architecture/overview.md`
9. `docs/architecture/runtime-model.md`
10. `docs/engineering/api-status.md`
11. `docs/engineering/error-handling.md`
12. `docs/engineering/package-layout.md`
13. `docs/glossary.md`
14. `packages/diagnostic-activation/README.md`
15. `packages/diagnostic-presentation/README.md`
16. Phase 1F, Phase 1G, and Phase 1G.1 directives and reports
17. this Phase 1H directive
18. the zero-byte `docs/task reports/Phase_1H.md` placeholder

Inspect all production declarations and tests directly involved in activation, projection, rendering, package exception markers, API-status discovery, Composer validation, autoloading, static analysis, formatting, and PHPUnit discovery.

### 2.2 Authority order

For Phase 1H:

1. this directive controls the exact implementation boundary;
2. ADR 0042 controls the selected package and public contract;
3. ADRs 0038-0041 control permission, eligibility, evidence ownership, and fail-closed behavior;
4. ADRs 0034-0037 control projection and rendering;
5. existing source controls unchanged component behavior; and
6. earlier reports are historical evidence, not authority for new work.

No open question may be resolved by assumption.

### 2.3 Independent directive identity

The architect will supply the byte count and uppercase SHA-256 of this directive outside the file. Compare those values with:

```text
docs/dev_plan/kongono_phase_1H_codex_directive.md
```

Do not treat the local file as self-authenticating.

The Phase 1H report placeholder must initially be ordinary, untracked, unstaged, absent from `HEAD`, and zero bytes:

```text
docs/task reports/Phase_1H.md
```

Required initial SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.4 Stop rather than assume

At the first baseline mismatch, caller-record mismatch, unexpected Composer delta, unauthorized path, test infeasibility, architecture contradiction, or failed gate:

1. stop immediately;
2. preserve the current worktree and index;
3. make no unrelated correction;
4. do not stage, commit, reset, restore, clean, stash, amend, or retry unless this directive explicitly permits it;
5. leave the Phase 1H report zero bytes unless every non-report gate passes; and
6. report expected and observed values, exact options, and a recommendation.

Do not guess. Do not broaden the package. Do not invent a new result, exception, service, or adapter.

### 2.5 Environment-only retries

One unchanged approved-access retry is allowed when:

- `composer quality` is blocked solely by managed advisory-network or Composer-cache restrictions; or
- the exact authorized Composer lock-update command is blocked solely by managed network/cache restrictions.

The retry must preserve command semantics and file scope. A functional Composer, lock, analysis, formatting, or test failure is not an environment-only failure.

---

## 3. Locked starting state

### 3.1 Git and repository baseline before caller setup

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `8fe793b124384744ac43887037360154c149241a` |
| `HEAD^{tree}` | `9de9388e85e463639f8d7fbbc90ae085f947a1b0` |
| `HEAD^` | `837863d11feda282e4959ef7ec98a810c184531e` |
| Commit count | `8` |
| Index entries | `344`, all stage zero and `100644` |
| Modified tracked paths | `13` |
| Untracked paths | `7` |
| Total status paths | `20` |
| Staged paths | `0` |
| Remotes | `0` |
| Tags | `0` |
| Package directories | `7` |
| Package manifests | `7` |
| Composer manifests including root | `8` |
| Production PHP files | `52` |
| Test PHP files | `83` |
| PHP CS Fixer-scoped files | `135` |
| Production declarations | `52` |
| API attributes | `50` |

The exact modified tracked inventory is:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
```

The exact untracked inventory is:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
docs/task reports/Phase_1G_1_correction.md
```

`packages/diagnostic-handler/` must not exist.

### 3.2 Locked historical caller records

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1F_codex_directive.md` | 32834 | `433EE2ECCD65B24C9CDCE8C3616302695F46359FA59958DA857F716177C4A0ED` |
| `docs/task reports/Phase_1F.md` | 15619 | `A5D0AE6355276FD7E53077ED2048E2BB6A84CAF56D21B6A82B77DC6493C82F01` |
| `docs/dev_plan/kongono_phase_1G_codex_directive.md` | 35789 | `58E9408FDA8734507AC49564D8AEAAFC65EDDA9F99A211468523C4BA2CA2838A` |
| `docs/task reports/Phase_1G.md` | 16449 | `813433CE270B1963E95112D44CB098CE55A039F8F22828157451F1FC930AD21A` |
| `docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md` | 16441 | `6ADCDBD2DF4CC207ACE34214A89E4D19CACA224D9E03B6083326F55E3AA3E7A9` |
| `docs/task reports/Phase_1G_1_correction.md` | 15444 | `4C3320F22D603EF84AAAFDD1C7FCF23F161314894590FAF735CADF68F29F54CF` |

All six remain immutable, untracked, unstaged, and absent from `HEAD`.

### 3.3 Locked implementation baseline

`composer.lock` is:

```text
bytes: 181650
SHA-256: 6EB8CF5E003C6A17D41C8127057BD4A05C8B984A909BB9B7F07845A712689A8E
```

The 147-file pre-Phase-1H protected set has aggregate:

```text
4D6E7C4F11F86C56065AFBC8999E55BB2132741B80AAF515C482C9219FB19D9E
```

Canonical baseline quality is:

```text
security advisories: 0
platform: pass
PHP CS Fixer: 0 of 135 files fixable
PHPStan: 0 errors
PHPUnit: 247 tests, 4233 assertions
```

### 3.4 Corrected architecture target identities

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md` | 13821 | `91FE4B01CCC136AB85282AC3D84AC74A06C83A70D92EF6B555B94BF40F26AD80` |
| `docs/engineering/error-handling.md` | 70939 | `049F7FCB978D845ED743DB42CFC5E62B91D62B03A5B920A71B45BE376F764000` |

Both corrected namespace imports must be present and all malformed variants absent before implementation.

### 3.5 Expected state after caller setup

After the caller adds the Phase 1H directive and zero-byte report:

```text
modified tracked paths: 13
untracked paths: 9
total status paths: 22
staged paths: 0
```

The two additional untracked paths are exactly:

```text
docs/dev_plan/kongono_phase_1H_codex_directive.md
docs/task reports/Phase_1H.md
```

---

## 4. Exact authorized file boundary

### 4.1 Create exactly these five non-caller files

```text
packages/diagnostic-handler/README.md
packages/diagnostic-handler/composer.json
packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php
packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php
packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php
```

### 4.2 Modify exactly these twenty-two tracked files

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-presentation/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

### 4.3 Modify exactly one accepted untracked architecture file

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
```

### 4.4 Report-only caller mutation

After every non-report gate passes, populate:

```text
docs/task reports/Phase_1H.md
```

No other path may be created or modified.

---

## 5. Exact package manifest and root Composer changes

### 5.1 New package manifest

Create `packages/diagnostic-handler/composer.json` with this semantic content and conventional two-space JSON formatting:

```json
{
  "name": "kongono/diagnostic-handler",
  "description": "Eligibility-gated trusted diagnostic presentation orchestration for Kongono Framework.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/application-graph": "*",
    "kongono/dependency-injection": "*",
    "kongono/diagnostic-activation": "*",
    "kongono/diagnostic-presentation": "*",
    "kongono/kernel": "*",
    "php": ">=8.5"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\DiagnosticHandler\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\DiagnosticHandler\\Tests\\": "tests/"
    }
  }
}
```

Do not add `kongono/diagnostics`, a third-party package, `provide`, `replace`, `conflict`, scripts, plugins, extra metadata, or a version.

### 5.2 Root manifest

Modify root `composer.json` only as follows:

1. add `"kongono/diagnostic-handler": "@dev"` in alphabetic position under `require`;
2. add `"Kongono\\DiagnosticHandler\\Tests\\": "packages/diagnostic-handler/tests/"` in alphabetic position under `autoload-dev`; and
3. add `composer --working-dir=packages/diagnostic-handler validate --no-check-publish` in alphabetic package position under `validate:packages`.

All other root manifest semantics remain unchanged.

### 5.3 Exact lock update command

After both manifests are correct, run exactly:

```powershell
composer update kongono/diagnostic-handler --with-dependencies --no-interaction
```

No other Composer update/install command is authorized to create the lock delta.

The resulting lock may change only:

- the root content hash;
- the addition of one `kongono/diagnostic-handler` path-package entry;
- that entry's mechanically generated path reference; and
- existing local path-package references only if Composer mechanically regenerates them without changing their version, requirements, package type, or source package manifest.

The new lock entry must have the exact dependency semantics from Section 5.1 and no third-party addition. No existing package may change version, requirement set, source type, dist type, abandonment state, or installed role. Report every changed lock field and every path reference before accepting the delta.

If any unrelated lock delta appears, stop immediately. Do not hand-edit the lockfile or rerun a broader update.

---

## 6. Exact production contract

Create:

```text
packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php
```

Its production semantics must be exactly equivalent to:

```php
<?php

declare(strict_types=1);

namespace Kongono\DiagnosticHandler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Throwable;

/**
 * Coordinates eligibility-gated trusted diagnostic HTML presentation.
 *
 * The caller remains responsible for both permission decisions and all transport
 * authority. A non-null result is presentation output, not proof of authorization.
 */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnosticHtmlPresenter
{
    /**
     * @throws Throwable An eligibility, source-accessor, projection, or rendering failure propagates unchanged.
     */
    public function renderIfEligible(
        TrustedDeveloperPresentationEligibility $eligibility,
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): ?string {
        if (!$eligibility->allowsTrustedPresentation()) {
            return null;
        }

        $diagnostic = (new TrustedDeveloperDiagnosticProjector())->project($source);

        return (new TrustedDeveloperHtmlRenderer())->render($diagnostic);
    }
}
```

Formatting may be normalized only by the repository's existing style. The declaration shape and method body semantics are locked.

### 6.1 Exact shape

The class must have:

- exactly one `Api` attribute with `ApiStatus::Experimental`;
- `final readonly class` shape;
- zero declared properties;
- zero explicit constructors;
- zero interfaces, traits, parents, aliases, factories, helpers, or nested declarations;
- exactly one declared public method;
- no public constant;
- no static method or property; and
- no dynamic property or retained state.

### 6.2 Exact method contract

The exact signature is:

```php
public function renderIfEligible(
    TrustedDeveloperPresentationEligibility $eligibility,
    KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
): ?string;
```

Do not accept `Throwable`, `object`, `mixed`, a universal marker, callback, boolean, raw permission enum, array, DTO, request, configuration value, evidence, projector, renderer, container, or service locator.

### 6.3 Exact execution order

The call to `allowsTrustedPresentation()` must precede:

- any `$source` method call;
- projector construction or invocation;
- trusted-model construction or copying; and
- renderer construction or invocation.

False returns `null` immediately. The denied branch performs no other production operation.

True performs exactly:

```text
source -> existing projector -> existing trusted model -> existing renderer -> exact string
```

Do not modify, wrap, prepend, append, minify, cache, classify, inspect, log, store, serialize, or deliver the HTML.

### 6.4 Failure behavior

No `try`, `catch`, `finally`, retry, fallback, recursion, wrapper exception, new exception, diagnostic ID, cleanup model, log, metric, trace, incident, event, or best-effort value is allowed.

Every `Throwable` propagates with unchanged object identity.

### 6.5 Security and lifecycle

The handler does not prove either permission's origin. Direct construction of `Allowed` plus `Allowed` remains possible. The future outer adapter owns configuration validation, evidence, authentication, authorization, eligibility construction, transport, non-trusted behavior, and delivery.

The class retains nothing across calls. It reads no environment, request, superglobal, registry, container, Kernel state, Graph, database, filesystem, network, cache, thread-local, fiber-local, or prior-operation state.

---

## 7. Exact test fixture and focused tests

### 7.1 Fixture

Create exactly one fixture declaration:

```text
packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php
Kongono\DiagnosticHandler\Tests\Fixture\ExplodingKernelDiagnosticException
```

It must:

- be `final`;
- extend native `RuntimeException`;
- implement only `Kongono\Kernel\Exception\ExceptionInterface`;
- receive and retain one caller-supplied `Throwable` sentinel;
- implement `diagnosticId()`, `diagnosticTitle()`, `diagnosticRemediation()`, and `diagnosticContext()` with their exact interface return types;
- throw the identical retained sentinel from every diagnostic accessor;
- define no API attribute;
- perform no I/O or global mutation; and
- exist only to prove denied calls perform no source access and allowed accessor failures retain identity.

One declaration per PSR-4 file is mandatory.

### 7.2 Unit test file

Create:

```text
packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php
```

It must contain exactly seven public test methods and no data provider:

1. `testDeclarationShapeAndExperimentalStatusAreExact`
2. `testEveryDeniedPermissionCombinationReturnsNullWithoutSourceAccess`
3. `testAllowedKernelFailureReturnsTheExactExistingPipelineHtml`
4. `testAllowedGraphFailureReturnsTheExactExistingPipelineHtml`
5. `testAllowedDependencyInjectionFailureReturnsTheExactExistingPipelineHtml`
6. `testAllowedSourceAccessorThrowablePropagatesWithIdentity`
7. `testRepeatedAndInterleavedCallsRetainNoState`

### 7.3 Required assertions

The tests must prove:

- exact class status, final/readonly shape, zero properties, no constructor, sole method inventory, non-static method, parameter order, exact union members, nullable-string return, and absence of an implemented interface or parent other than `object`;
- all three denied combinations (`Denied/Denied`, `Denied/Allowed`, `Allowed/Denied`) return `null` with the exploding fixture and therefore invoke no diagnostic accessor;
- an allowed real Kernel `LifecycleException`, obtained by exercising the existing public/Experimental Kernel lifecycle API, produces byte-identical output to direct projector-then-renderer composition;
- an allowed real Graph `InvalidGraphException`, obtained by exercising an existing public/Experimental Graph validation path, produces byte-identical output to direct composition;
- an allowed real DI `InvalidDefinitionException` or `UnknownServiceException`, obtained by exercising an existing public/Experimental DI validation or resolution path, produces byte-identical output to direct composition;
- successful output is non-empty complete HTML and contains the selected real diagnostic ID only as already rendered by the existing renderer;
- with allowed eligibility, an accessor sentinel thrown by the fixture emerges as the identical object;
- allowed, denied, and allowed calls may be interleaved on one presenter instance without state leakage; and
- repeated allowed calls return deterministic equal strings.

Use real existing contextual package exceptions for the three success paths. Capture them through existing public or Experimental package behavior; do not import or construct an Internal diagnostic catalog in Handler tests. Do not duplicate projector or renderer logic in the expected result; obtain expected HTML by directly invoking the existing projector and renderer.

Do not use mocks, prophecy, runtime `eval`, generated declarations, serialization, reflection mutation, anonymous marker implementations, subprocesses, sleeps, filesystem mutation, network access, or environment state.

The focused handler suite must contain exactly seven tests. The complete suite must contain exactly 254 tests. Report the exact final assertion total; it must be greater than 4,233.

---

## 8. Tooling and API-status integration

### 8.1 PHP CS Fixer

Add these directories in alphabetic package position:

```php
->in(__DIR__.'/packages/diagnostic-handler/src')
->in(__DIR__.'/packages/diagnostic-handler/tests')
```

Final fixer scope: exactly 138 PHP files.

### 8.2 PHPStan

Add:

```yaml
- packages/diagnostic-handler/src
- packages/diagnostic-handler/tests
```

in alphabetic package position. Retain level max and strict rules.

### 8.3 PHPUnit

Add the handler test directory and source directory in alphabetic package position. Do not change failure strictness, bootstrap, cache, colors, or another suite.

### 8.4 API convention

Modify `tests/Architecture/ApiStatusConventionTest.php` to:

- import `TrustedDeveloperDiagnosticHtmlPresenter`;
- add it to `EXPECTED_STATUS` as Experimental;
- expect exactly eight production source directories;
- validate the exact new handler manifest requirements from Section 5.1;
- add `kongono/diagnostic-handler => @dev` to the exact root requirement assertion;
- retain exact Activation and Presentation manifest assertions;
- prove Activation and Presentation do not require Handler;
- prove Handler does not directly require Diagnostics;
- preserve all existing marker, catalog, diagnostic, and API checks; and
- discover exactly 53 production declarations with 51 explicit `Api` attributes.

Do not add a universal handler exception marker or alter existing status assignments.

---

## 9. Autoload and Composer validation

After the lock update and source creation, run:

```powershell
composer dump-autoload --optimize --strict-psr --strict-ambiguous
```

It must succeed with no PSR-4 or ambiguity finding. In dev autoload:

```text
TrustedDeveloperDiagnosticHtmlPresenter: autoloadable
ExplodingKernelDiagnosticException: autoloadable
TrustedDeveloperDiagnosticHtmlPresenterTest: autoloadable
```

Then run:

```powershell
composer dump-autoload --optimize --no-dev --strict-psr --strict-ambiguous
```

Prove:

```text
TrustedDeveloperDiagnosticHtmlPresenter: autoloadable
ExplodingKernelDiagnosticException: not autoloadable
TrustedDeveloperDiagnosticHtmlPresenterTest: not autoloadable
```

Restore the normal development autoloader with:

```powershell
composer dump-autoload --optimize --strict-psr --strict-ambiguous
```

Also validate the new package directly and through root `validate:packages`.

No plugin, script alias collision, package version, release metadata, or production autoload of tests is allowed.

---

## 10. README and documentation requirements

### 10.1 New package README

`packages/diagnostic-handler/README.md` must document:

- package purpose and exact dependency boundary;
- exact Experimental class and method;
- denial-before-access behavior;
- true-path projector and renderer sequence;
- unchanged success and `Throwable` propagation;
- `null` meaning and non-meanings;
- evidence and authority remaining adapter-owned;
- persistent-runtime isolation;
- direct callability not proving authorization;
- current limitations and prohibited capabilities; and
- links to ADRs 0034-0042, Security, error handling, package layout, Activation, and Presentation.

### 10.2 Reconcile ADRs 0038-0042

Record a Phase 1H implementation note in each applicable ADR:

- the neutral sequencing boundary now exists;
- denial-before-access is executable and tested;
- the package does not evaluate evidence or deliver output;
- external adapter composition and non-trusted behavior remain open; and
- no resolved Phase 1H implementation item remains falsely listed as open.

ADR 0042 remains Accepted and must change its planned/future language to current implementation language where appropriate. Preserve future adapter work.

### 10.3 Project documentation

Reconcile every Section 4.2 documentation file so all agree:

- eight packages are implemented;
- the root consumes all eight;
- Diagnostic Handler is the only current package depending on Activation and Presentation;
- Activation and Presentation remain independent lower layers;
- the presenter is Experimental and directly callable;
- false returns `null` before source access;
- true returns exact renderer HTML;
- failures propagate unchanged;
- no adapter, configuration, evidence evaluator, transport, delivery, response, or production model exists; and
- current behavior is not confused with future HTTP or other integration.

Update `docs/engineering/api-status.md` to exact 53-declaration/51-attribute counts and handler status.

Update both existing package READMEs because their former “no existing package depends on this package” statements become false. Describe only the new one-way Handler edge; do not imply a reverse edge.

### 10.4 Open decisions

Retain as open:

- exact outer adapter packages and composition;
- bootstrap configuration schema, parser, failure, restart, and reload;
- delivery evidence, authentication, listener, connection, proxy, operator, and audience policy;
- non-trusted failure response and production diagnostic model;
- HTTP, CLI, queue, worker, and all other transport behavior;
- simultaneous primary/handler/projection/rendering/transport/cleanup failure representation;
- logging, telemetry, incidents, persistence, serialization, localization, and documentation lookup;
- Kernel lifecycle, DI wiring, Application Graph, extensions, frontend, and agents; and
- stabilization of Experimental APIs.

Do not leave the package, class, method, execution order, return behavior, or handler implementation listed as future after successful implementation.

---

## 11. Prohibited scope

Phase 1H must not:

- create any additional production or test declaration;
- add a handler interface, exception, diagnostic catalog, diagnostic ID, result type, response type, factory, service, alias, DTO, guard, capability, token, or registry;
- inject projector or renderer through a constructor;
- accept raw permissions, boolean, string, configuration, evidence, request, `Throwable`, `object`, `mixed`, or callback;
- alter the two permission enums or eligibility class;
- alter projector, model, field, renderer, package exception, diagnostic context, or catalog behavior;
- catch, wrap, suppress, log, retry, recurse, recover, or translate a failure;
- render before eligibility or access source diagnostics on denial;
- add an HTTP request, response, status, header, content type, CSP, cache rule, route, middleware, controller, server, or served page;
- add CLI, queue, worker, RPC, WebSocket, event, frontend, extension, agent, or other delivery;
- select bootstrap configuration, evidence, authentication, authorization, listener, proxy, operator, or audience policy;
- implement the non-trusted response, production model, allowlist, redaction, incident, logging, telemetry, persistence, serialization, or localization;
- add Kernel lifecycle integration, DI registration, Application Graph participation, container lookup, service locator, or ambient state;
- add a third-party dependency or plugin;
- weaken Composer security policy, static analysis, formatter, or tests;
- stage, commit, push, branch, tag, reset, restore, clean, stash, rebase, or amend;
- edit historical directives or reports; or
- begin Phase 1I.

---

## 12. Validation gates

### Gate A - caller and baseline

Before broad validation or mutation, prove:

- all Section 2.1 authorities were read completely;
- the externally supplied Phase 1H identity matches;
- the report is the required zero-byte placeholder;
- all six historical caller records match;
- Git identity, history, index, branch, remote, tag, modes, and exact status inventories match Section 3 after caller setup;
- corrected ADR 0042 and error-handling identities match;
- all malformed namespace variants are absent;
- `composer.lock` and the protected aggregate match;
- exactly seven package directories exist and Handler is absent;
- baseline production/test/declaration/attribute/fixer counts match; and
- all paths are ordinary, with no reparse point or non-default stream.

Stop before quality or edits on failure.

### Gate B - pre-change quality

Run:

```powershell
composer quality
```

It must pass with 247 tests, 4,233 assertions, zero PHPStan errors, zero fixer findings across 135 files, platform success, and zero advisories. Only accepted warning categories may appear.

Re-prove Gate A afterward.

### Gate C - manifests and lock

Create the package manifest and make only the root manifest changes in Section 5. Validate both manifests before the update. Run the exact lock-update command once, subject only to the environment retry rule.

Inspect the complete lock delta before proceeding. Stop on any unauthorized field or package delta.

### Gate D - production and tests

Create only the Section 4.1 source, fixture, test, and README. Apply the exact tooling and API-status changes. Run syntax checks on every new/modified PHP file.

### Gate E - focused behavior

Run at minimum:

```powershell
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostic-handler/tests
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/diagnostic-handler/src packages/diagnostic-handler/tests tests/Architecture/ApiStatusConventionTest.php
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
```

Required:

- handler suite: exactly seven passing tests;
- architecture suite: all existing tests pass;
- focused PHPStan: zero errors;
- fixer: zero files fixable;
- reflection contract exact;
- all denial and allowance behavior exact;
- sentinel `Throwable` identity exact;
- no state leakage; and
- no prohibited source token or dependency.

### Gate F - autoload and architecture

Run Section 9's dev/no-dev/dev autoload sequence and direct package validation.

Prove:

- exactly eight packages and eight package manifests;
- exactly nine Composer manifests including root;
- exactly 53 production PHP files;
- exactly 85 test PHP files;
- exactly 138 fixer-scoped PHP files;
- exactly 53 production declarations and 51 API attributes;
- exactly one Handler production declaration;
- exact package dependency edges and no cycles;
- no direct Handler-to-Diagnostics edge;
- no Activation-to-Handler or Presentation-to-Handler reverse edge;
- no Handler exception, diagnostic ID, binding, service, Graph node, or Kernel integration;
- source body ordering is exact;
- no `try`, `catch`, `finally`, I/O, environment, superglobal, request, response, transport, log, telemetry, cache, serialization, reflection, container, or service-locator token occurs in Handler production source; and
- documentation accurately distinguishes current implementation from future integrations.

### Gate G - final canonical quality

Run:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform: pass
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, more than 4233 assertions
```

Record the exact assertion total. Re-run every focused structural, dependency, documentation, link, encoding, stream, Git, caller-record, lock, and prohibited-scope proof afterward.

### Gate H - report

Only after Gates A-G pass, populate `docs/task reports/Phase_1H.md` using Section 15. Validate the report and final state. Return the report's final byte count and uppercase SHA-256 outside the report body.

---

## 13. Security and file-safety acceptance

Prove:

- no credential, secret, private key, signing material, private email, or machine-specific absolute path was introduced into accepted implementation/documentation;
- all new/modified text files are UTF-8 without BOM, LF-only, exactly one final LF, with no trailing whitespace or NUL;
- every status path is ordinary and has no reparse point or non-default alternate stream;
- new files use normal `100644` intent and no executable/symlink/submodule mode;
- local Markdown links resolve;
- `git diff --check` passes;
- no staged path exists;
- Git history and refs are unchanged; and
- historical caller records retain exact bytes.

---

## 14. Exact final repository state

After successful report population:

```text
branch: main
HEAD: 8fe793b124384744ac43887037360154c149241a
HEAD tree: 9de9388e85e463639f8d7fbbc90ae085f947a1b0
commit count: 8
modified tracked paths: 22
untracked paths: 14
total status paths: 36
staged paths: 0
package directories: 8
package manifests: 8
Composer manifests including root: 9
production PHP files: 53
test PHP files: 85
PHP CS Fixer-scoped files: 138
production declarations: 53
API attributes: 51
remotes: 0
tags: 0
```

The exact modified tracked paths must be Section 4.2.

The exact untracked paths must be:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1H_codex_directive.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
docs/task reports/Phase_1G_1_correction.md
docs/task reports/Phase_1H.md
packages/diagnostic-handler/README.md
packages/diagnostic-handler/composer.json
packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php
packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php
packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php
```

---

## 15. Strict acceptance criteria

Phase 1H passes only if:

1. all authorities were read completely;
2. external directive identity and zero-byte report matched;
3. all historical caller records and corrected architecture identities matched;
4. exact starting Git and inventory state matched;
5. baseline quality passed with 247 tests and 4,233 assertions;
6. exactly five non-caller files were created;
7. exactly 22 tracked files and ADR 0042 were modified;
8. no other path changed except the final report;
9. the new package manifest and root manifest match Section 5;
10. the exact Composer command produced only the authorized lock delta;
11. no third-party dependency, plugin, or security-policy change occurred;
12. the production class exactly matches Section 6;
13. eligibility is evaluated before every source or presentation access;
14. denial returns `null` without source access;
15. real Kernel, Graph, and DI allowance paths return exact existing-pipeline HTML;
16. all failures propagate unchanged and sentinel identity is tested;
17. the class retains no state and interleaved calls are isolated;
18. exactly one PSR-compliant fixture and one seven-test unit file were created;
19. tooling and API-status integration are exact;
20. dev/no-dev autoload boundaries pass with no PSR or ambiguity warning;
21. eight-package dependency topology is exact and acyclic;
22. no Handler-to-Diagnostics or reverse Handler edge exists;
23. all documentation and ADRs describe current implementation consistently;
24. all deferred adapter, configuration, evidence, non-trusted, transport, production, and operational work remains open;
25. no prohibited later capability or ambient state was introduced;
26. security, encoding, file-safety, link, and Git checks pass;
27. final canonical quality passes with exactly 254 tests and more than 4,233 assertions;
28. final counts and status match Section 14;
29. no staging, commit, ref, history, remote, or tag mutation occurred;
30. the report was populated only after all non-report gates and remains outside `HEAD`; and
31. no unauthorized assumption or deviation occurred.

No partial pass is acceptable.

---

## 16. Standard executor completion report

Use exactly these headings in order:

```markdown
# PHASE 1H TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. PACKAGE AND DEPENDENCY CONTRACT

## 8. PRODUCTION API AND BEHAVIOR

## 9. TESTS AND FIXTURES

## 10. ARCHITECTURAL DECISIONS

## 11. DEVIATIONS FROM DIRECTIVE

## 12. DEPENDENCIES ADDED OR REMOVED

## 13. COMPOSER AND LOCKFILE EVIDENCE

## 14. ENVIRONMENT

## 15. COMMANDS RUN

## 16. VALIDATION RESULTS

## 17. DOCUMENTATION REVIEW

## 18. SECURITY AND FILE-SAFETY REVIEW

## 19. WARNINGS, BLOCKERS, AND FINAL GIT STATUS

## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- exact directive and caller identities;
- starting and final Git inventories;
- created and modified path lists;
- complete package and lock delta;
- exact production shape and execution order;
- focused test names and results;
- exact final test/assertion total;
- dev/no-dev autoload evidence;
- API declaration/attribute and file counts;
- dependency-topology and prohibited-scope proof;
- documentation, link, encoding, stream, and security results;
- exact warnings and any permitted unchanged retries;
- criterion-by-criterion results; and
- remaining open work.

Do not include a private email address. Do not claim a pass without observed output. Do not call blocked work complete.

The final nonblank line must be exactly:

```text
Await architect review before Phase 1I.
```

Return the report's final byte count and uppercase SHA-256 separately after completion.

---

## 17. Required final outcome

Phase 1H implements one small, inspectable enforcement coordinator:

```text
explicit eligibility + supported contextual source
    false -> null before source access
    true -> existing projector -> existing renderer -> unchanged HTML string
```

The handler does not decide whether either permission is legitimate. It does not catch failures or deliver output. Kongono gains no HTTP, CLI, request, response, route, middleware, adapter, configuration source, evidence evaluator, authentication policy, production diagnostic surface, logging, telemetry, persistence, serialization, Kernel lifecycle integration, DI binding, Application Graph participation, extension, frontend, or agent behavior.

Do not begin Phase 1I.
