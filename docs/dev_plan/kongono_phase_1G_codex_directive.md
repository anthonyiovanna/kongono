# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1G

## Transport-Neutral Trusted Diagnostic Handler Architecture

**Directive type:** architecture and documentation only

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Establish the exact architecture for Kongono's first transport-neutral trusted diagnostic handler without implementing that handler or any adapter, transport, configuration mechanism, or non-trusted failure response.

Phase 1E implemented the pure two-permission eligibility value. It deliberately left open where that value is consumed and how the existing trusted projector and HTML renderer are reached only after eligibility succeeds. Phase 1G resolves that architectural boundary and nothing more.

The new Accepted decision must define a planned eighth package:

```text
kongono/diagnostic-handler
Kongono\DiagnosticHandler\
```

The planned package will be a leaf above Diagnostic Activation and Diagnostic Presentation. Its one planned Experimental production declaration is:

```php
Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter
```

Its one planned non-constructor public method is:

```php
public function renderIfEligible(
    TrustedDeveloperPresentationEligibility $eligibility,
    KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
): ?string;
```

The contract is exact:

- evaluate `allowsTrustedPresentation()` before any source accessor, projection, field copying, or rendering;
- return `null` immediately when eligibility is false;
- when eligibility is true, project the supported contextual package exception and render the trusted HTML document;
- propagate any eligibility, source-accessor, projection, or rendering `Throwable` unchanged;
- catch nothing and provide no fallback, recursive rendering, retry, logging, or delivery;
- retain no source, eligibility, result, HTML, or cross-delivery state; and
- perform no transport operation.

Phase 1G documents this future contract only. It creates no package directory, Composer manifest, PHP declaration, test, autoload mapping, dependency edge, lockfile change, runtime binding, service, or executable enforcement.

### 1.1 Why this boundary is next

The current boolean eligibility calculation is intentionally insufficient as a complete security boundary. Outer adapters still own the evidence and authority used to choose the two permission values. The planned handler centralizes only the deterministic ordering rule: deny before diagnostic presentation access, otherwise invoke the already-existing pure projection and renderer.

This preserves three separate concerns:

1. a future outer adapter validates bootstrap configuration and independently evaluates current-delivery evidence;
2. Diagnostic Activation represents and combines the two decisions; and
3. the future Diagnostic Handler sequences allowed presentation without owning transport authority or delivery.

The handler must not be described as authenticating an audience, proving permission provenance, or making direct low-level presentation calls impossible. PHP callers remain responsible for correct composition.

---

## 2. Authority and mandatory stop rule

### 2.1 Read completely before acting

Read these authorities in order before any edit:

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
16. the Phase 1A through Phase 1F directives and completion reports present in the repository
17. this Phase 1G directive
18. the zero-byte `docs/task reports/Phase_1G.md` placeholder

The existing code must be inspected only to verify current behavior and the absence of the planned package. Do not modify it.

### 2.2 Authority order

For Phase 1G:

1. this directive controls the exact Phase 1G file boundary and required decision;
2. Accepted ADRs and doctrine control all previously locked architecture;
3. accepted completion reports provide historical evidence but do not authorize new work;
4. current production code is authoritative for statements about implemented behavior; and
5. future-looking documentation must not be presented as current runtime behavior.

This directive narrowly supersedes the prior statement that the exact neutral presentation-consumption boundary is wholly open. It does not supersede the two-permission policy, the pure eligibility truth table, outer-adapter evidence ownership, the absence of current enforcement, or any security prohibition.

### 2.3 Independent directive identity

The architect will provide this directive's expected byte count and uppercase SHA-256 outside the file. Compare those independently delivered values with:

```text
docs/dev_plan/kongono_phase_1G_codex_directive.md
```

Do not treat the local directive as self-authenticating.

The report placeholder must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_1G.md
```

Its required initial SHA-256 is:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.4 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, changed protected byte, failed validation gate, or conflict between authorities:

1. stop immediately;
2. make no further edit;
3. do not stage, commit, reset, restore, clean, stash, or repair anything;
4. keep the Phase 1G report zero bytes unless every non-report gate has passed; and
5. report the expected value, observed value, exact blocker, viable options, and a recommendation.

Do not guess. Do not broaden the architecture. Do not select an open mechanism merely to make prose feel complete.

### 2.5 Environment-only retry

One unchanged retry with approved host/network access is allowed only when the required `composer quality` command is blocked solely by managed Composer cache or advisory-network restrictions. The retry must not change configuration, dependencies, command semantics, or files.

No other retry that mutates repository state is authorized.

---

## 3. Locked starting state

### 3.1 Accepted Git checkpoint

Before the caller adds the two Phase 1G records, the repository must be exactly:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `8fe793b124384744ac43887037360154c149241a` |
| `HEAD^{tree}` | `9de9388e85e463639f8d7fbbc90ae085f947a1b0` |
| `HEAD^` | `837863d11feda282e4959ef7ec98a810c184531e` |
| Commit count | `8` |
| Tracked files | `344` |
| Index entries | `344` stage-zero entries |
| Tracked modes | all `100644` |
| Modified tracked paths | `0` |
| Staged paths | `0` |
| Untracked paths | `2` |
| Total status paths | `2` |
| Local branches | `1` |
| Remotes | `0` |
| Tags | `0` |

The two untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/task reports/Phase_1F.md
```

### 3.2 Locked Phase 1F caller records

These two historical caller records remain untracked, unstaged, and absent from `HEAD` throughout Phase 1G:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1F_codex_directive.md` | 32834 | `433EE2ECCD65B24C9CDCE8C3616302695F46359FA59958DA857F716177C4A0ED` |
| `docs/task reports/Phase_1F.md` | 15619 | `A5D0AE6355276FD7E53077ED2048E2BB6A84CAF56D21B6A82B77DC6493C82F01` |

Do not edit, rename, stage, commit, normalize, or regenerate either record.

### 3.3 Locked implementation and tooling baseline

The accepted repository has:

```text
packages: 7
package manifests: 7
Composer manifests including root: 8
production PHP files: 52
test PHP files: 83
PHP CS Fixer-scoped files: 135
API declarations: 52
API attributes: 50
PHPUnit: 247 tests, 4233 assertions
```

The accepted `composer.lock` identity is:

```text
bytes: 181650
SHA-256: 6EB8CF5E003C6A17D41C8127057BD4A05C8B984A909BB9B7F07845A712689A8E
```

The protected set consists of all 52 production PHP files, all 83 test PHP files, all eight Composer manifests, `composer.lock`, `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and `phpunit.xml.dist`: exactly 147 files.

Using the accepted ordinal path-and-primary-byte aggregation algorithm, its required aggregate is:

```text
files: 147
aggregate SHA-256: 4D6E7C4F11F86C56065AFBC8999E55BB2132741B80AAF515C482C9219FB19D9E
```

Every protected byte and mode must remain unchanged.

### 3.4 Expected state after caller setup

After the caller adds the Phase 1G directive and zero-byte report placeholder, but before the executor edits documentation:

```text
modified tracked paths: 0
staged paths: 0
untracked paths: 4
total status paths: 4
```

The exact four untracked paths must be:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
```

No other starting difference is authorized.

---

## 4. Exact authorized file boundary

### 4.1 Create exactly one non-caller file

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
```

### 4.2 Modify exactly these thirteen tracked files

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

Each listed file must receive a necessary Phase 1G reconciliation. Do not leave an authorized file unchanged. Do not modify any other tracked file.

### 4.3 Report-only caller mutation

After every non-report gate passes, populate exactly:

```text
docs/task reports/Phase_1G.md
```

### 4.4 Specifically protected paths

Do not modify:

- any PHP source or test;
- any Composer manifest or `composer.lock`;
- any tooling configuration;
- either package README;
- any ADR other than 0038, 0039, 0040, 0041, 0042, and the ADR index;
- any prior directive or completion report;
- `.gitignore`, `.gitattributes`, or `.editorconfig`; or
- any Git metadata.

---

## 5. Exact Phase 1G architectural decision

ADR 0042 and every reconciled document must agree on the following exact decision.

### 5.1 Planned package ownership

The future eighth package is:

```text
Composer package: kongono/diagnostic-handler
Namespace: Kongono\DiagnosticHandler\
Status in Phase 1G: planned only; not present in the repository
```

The future package is a leaf. It may depend directly only on:

```text
php >=8.5
kongono/api-metadata
kongono/diagnostic-activation
kongono/diagnostic-presentation
kongono/kernel
kongono/application-graph
kongono/dependency-injection
```

The direct Kernel, Graph, and DI edges are required because the planned public method names their three separate Public contextual exception markers. Do not hide those source types behind `object`, `Throwable`, a universal Kongono marker, an adapter DTO, or an erased callback.

The handler will not directly require `kongono/diagnostics` because its planned production API does not name a Diagnostics type. Diagnostic Presentation retains its own direct Diagnostics dependency.

No existing package will depend on Diagnostic Handler. The framework root will consume it only when a later implementation directive creates the package. The graph remains acyclic:

```text
api-metadata ───────────────┐
diagnostic-activation ──────┤
diagnostic-presentation ────┤
kernel ─────────────────────┤
application-graph ──────────┤
dependency-injection ───────┴──> diagnostic-handler
```

These are the future handler's direct edges only. The diagram does not replace or alter the already accepted dependencies inside Diagnostic Presentation, Kernel, Graph, or DI. In particular, Diagnostic Presentation does not depend on Diagnostic Activation; both are direct dependencies of the future handler.

### 5.2 Planned production declaration

The future package owns exactly one planned production declaration in its first implementation boundary:

```php
Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter
```

It is planned as:

- `final readonly class`;
- `#[Api(ApiStatus::Experimental)]`;
- stateless, with no declared instance or static property;
- no explicit constructor;
- no interface, trait, parent class, factory, alias, or service registration; and
- exactly one declared public method.

The exact planned method is:

```php
public function renderIfEligible(
    TrustedDeveloperPresentationEligibility $eligibility,
    KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
): ?string;
```

Use these exact aliases in architecture examples:

```php
use KongonoApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use KongonoDependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
```

The class name, namespace, method name, parameter order, parameter types, and return type are selected architecture. Phase 1G does not create them.

### 5.3 Exact planned execution order

The future method contract is:

1. call `$eligibility->allowsTrustedPresentation()`;
2. if the result is `false`, return `null` immediately;
3. only after a true result, create or invoke `TrustedDeveloperDiagnosticProjector` with `$source`;
4. pass the detached trusted model to `TrustedDeveloperHtmlRenderer`; and
5. return the renderer's complete HTML string unchanged.

No source method may be called before the eligibility check returns true. No projection model may be built before that check. No HTML may be rendered speculatively and discarded on denial.

Passing the already-caught source exception object as a method argument is not itself diagnostic presentation access. The future outer adapter remains responsible for avoiding premature diagnostic access while establishing the two decisions.

### 5.4 Denied result

`null` means exactly:

```text
trusted developer HTML was not produced because combined eligibility was false
```

It is not:

- an HTTP status or body;
- a production diagnostic model;
- an incident identifier;
- an instruction to suppress logging;
- evidence that the source was handled;
- a retry signal;
- an empty HTML document;
- a fallback page; or
- a successful delivery.

A future outer adapter must choose a separately designed non-trusted failure path when it receives `null`. Phase 1G does not select that path.

The existing renderer's successful output is always returned as its exact non-null string. The future handler must not edit, wrap, prepend, append, minify, cache, classify, or transport the HTML.

### 5.5 Failure behavior

The future handler catches no `Throwable`.

Any failure from:

- evaluating eligibility;
- a package exception accessor;
- projection validation;
- trusted-model construction; or
- HTML rendering

propagates unchanged to the caller. The handler adds no exception family, diagnostic identifier, wrapper, fallback, retry, recursion, cleanup failure model, log, metric, trace, incident record, or best-effort output.

Although the current eligibility method is total and the current renderer has no documented deliberate exception, the orchestration contract must not assume future implementations cannot throw.

### 5.6 Security meaning

The future handler enforces only the sequencing rule for values it receives. It does not establish that either input was legitimately selected.

In particular:

- direct construction of an `Allowed` plus `Allowed` eligibility object remains possible;
- a true eligibility result remains neither a capability token nor proof of transport trust;
- the future outer adapter remains the sole owner of bootstrap provenance, per-delivery evidence, evaluation, authentication, listener policy, connection policy, and audience authorization;
- client-controlled data may not select or upgrade either permission;
- absence, failure, ambiguity, uncertainty, staleness, or unverifiable evidence must become denial before the handler is called;
- no package visibility trick can make direct low-level projector or renderer calls impossible; and
- production presentation must use a separate deny-by-default model and explicit allowlists.

The handler must not be described as a security boundary that authenticates its caller. It is a deterministic enforcement coordinator inside a correctly composed outer boundary.

### 5.7 Lifecycle and concurrency

The planned handler is stateless and safe for persistent runtimes:

- one call corresponds to one current delivery;
- it retains neither permission, source, model, HTML, nor result;
- it has no global, static, singleton, registry, container-current, worker-current, thread-local, fiber-local, tenant-current, request-current, or previous-operation state;
- it performs no cache lookup or write;
- one call cannot authorize another; and
- interleaved and repeated calls depend only on their explicit arguments.

The future outer adapter may retain the immutable bootstrap permission for its instance lifetime, but it must create the combined eligibility from a newly evaluated delivery permission for each current delivery. Exact adapter lifecycle and restart behavior remain future work.

### 5.8 Performance

The planned handler performs bounded orchestration:

- denial is one eligibility evaluation followed by `null`, with no source access, projection, field copy, or rendering;
- allowance performs the existing bounded projection and HTML rendering work;
- no extra retained cache, I/O, serialization, network access, filesystem access, logging, telemetry, event dispatch, reflection, container lookup, or Graph query is introduced; and
- no throughput, latency, allocation, or response-size guarantee is selected beyond the existing component contracts.

### 5.9 Current versus planned state

Every document must distinguish these states exactly.

Current after Phase 1G:

- seven packages exist;
- Diagnostic Activation owns two permission enums and the pure eligibility class;
- Diagnostic Presentation owns the projector, detached trusted model, and HTML renderer;
- those low-level APIs remain directly callable;
- no Diagnostic Handler package or class exists;
- no enforcement coordinator exists; and
- no configuration, evidence evaluation, adapter, transport, delivery, or production presentation exists.

Planned for a later implementation phase:

- the eighth `kongono/diagnostic-handler` package;
- the exact `TrustedDeveloperDiagnosticHtmlPresenter` contract;
- enforcement of denial before diagnostic presentation access inside that method; and
- neutral production tests for the planned contract.

Do not say Phase 1G implements, provides, exposes, or ships the handler.

---

## 6. ADR 0042 requirements

Create:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
```

Use exactly:

```text
# ADR 0042: Transport-Neutral Trusted Diagnostic Handler

## Status

Accepted

## Date

2026-08-15
```

The ADR must contain these sections in order:

1. `Context`
2. `Decision`
3. `Rationale`
4. `Security implications`
5. `Lifecycle and concurrency`
6. `Performance implications`
7. `Consequences`
8. `Rejected alternatives`
9. `What remains open`

The ADR must record every decision in Section 5, including:

- exact planned package and namespace;
- exact dependency topology and why the three source-package edges are direct;
- exact planned class and API status;
- exact method signature and parameter order;
- false-before-access ordering;
- `null` meaning and its explicit non-meanings;
- unchanged successful renderer output;
- unchanged `Throwable` propagation;
- no authorization-provenance claim;
- persistent-runtime isolation;
- current-versus-planned status; and
- that implementation is explicitly deferred.

The rejected alternatives must include:

- placing enforcement inside Diagnostic Activation;
- adding an Activation dependency to Diagnostic Presentation;
- putting orchestration in the framework root;
- erasing the source union to `Throwable`, `object`, a universal marker, or callback;
- accepting raw permissions, booleans, strings, arrays, request values, configuration, or evidence in the handler;
- projecting or rendering before eligibility;
- using a capability token, grant, secret, hidden constructor, interface, registry, service locator, or ambient state as proof;
- returning an empty string, trusted model, union of unrelated results, response object, status code, or production model on denial;
- catching, wrapping, logging, retrying, or recursively rendering a failure;
- combining delivery evidence evaluation with presentation orchestration;
- transport-specific behavior; and
- implementing any class or package during Phase 1G.

`What remains open` must retain:

- the implementation phase for Diagnostic Handler;
- exact outer adapter packages and transport contracts;
- bootstrap configuration source, schema, parser, validation failure, and reload;
- delivery evidence, authentication, listener, connection, proxy, and audience policy;
- non-trusted failure response and production diagnostic model;
- HTTP, CLI, queue, worker, and other delivery behavior;
- simultaneous primary/orchestration/projection/rendering/transport/cleanup failure policy;
- logging, telemetry, incidents, serialization, localization, persistence, and documentation lookup;
- Kernel lifecycle, DI wiring, Application Graph, extension, frontend, and agent integration; and
- future API stabilization.

Do not list the handler package name, class name, method signature, ordering rule, return meaning, or dependency topology as open after accepting them.

---

## 7. Required documentation reconciliation

### 7.1 `ARCHITECTURE.md`

Add the Accepted Phase 1G planned boundary while preserving the current seven-package statement. Show the handler as planned, not implemented. Reconcile the conceptual composition to:

```text
future outer adapter establishes both typed permissions
    -> TrustedDeveloperPresentationEligibility
        -> planned Diagnostic Handler checks eligibility
            false -> null to the future adapter's still-unselected non-trusted path
            true  -> projector -> trusted HTML renderer -> string to the future adapter
```

State that the handler neither evaluates evidence nor delivers output.

### 7.2 `README.md`

Update the project-status and diagnostics overview with one concise planned-boundary paragraph. Do not advertise a usable error handler or served error page.

### 7.3 `SECURITY.md`

Document that the planned handler checks eligibility before any source accessor or presentation operation, but cannot prove permission provenance. Preserve all Safe/Protected/Forbidden doctrine and the separate production-model requirement.

### 7.4 ADRs 0038-0041

Append or integrate a Phase 1G architecture note in each ADR:

- ADR 0038: the handler architecture resolves neutral sequencing, not external authority or delivery;
- ADR 0039: the bootstrap enum remains an input, and the handler neither parses nor upgrades it;
- ADR 0040: the delivery enum remains selected by the outer adapter from its own evidence;
- ADR 0041: the planned handler is the selected consumer of the eligibility object and checks it before source access.

Reconcile `What remains open` so no resolved Phase 1G decision remains falsely open. Retain implementation and all external mechanisms as future.

### 7.5 ADR index

Add ADR 0042 as entry 42 with status and a precise summary. Update any count or sequence language without altering earlier entry meaning.

### 7.6 Architecture overview and runtime model

Describe the planned leaf edge and one-call-per-delivery sequencing. Preserve the current package count and state. Explicitly reject ambient state and cross-delivery reuse.

### 7.7 Error-handling guide

Add the exact planned call flow, source union, denial behavior, failure propagation, and responsibility split. Make clear that `null` is not a response and that no runtime handler exists yet.

### 7.8 Package-layout guide

Record the planned eighth package separately from the seven implemented packages. Show its planned direct dependencies and prohibit reverse dependencies from Activation or Presentation.

### 7.9 Glossary

Add or reconcile concise terms for:

- `Diagnostic Handler`;
- `trusted diagnostic handler`;
- `renderIfEligible`; and
- `denied trusted presentation result`.

Every definition must label the handler as planned only after Phase 1G.

---

## 8. Prohibited scope

Phase 1G must not:

- create `packages/diagnostic-handler/`;
- create any PHP file or test;
- edit any Composer manifest, lockfile, autoload mapping, PHPUnit suite, PHPStan path, or fixer configuration;
- add or remove a dependency;
- implement `TrustedDeveloperDiagnosticHtmlPresenter` or `renderIfEligible`;
- implement a guard, capability, token, service, interface, factory, DTO, result object, exception, diagnostic identifier, or alias;
- implement configuration reading, parsing, validation, reload, environment access, or command-line options;
- implement delivery evidence evaluation, authentication, authorization, listener, connection, proxy, origin, IP, TLS, certificate, operator, or audience policy;
- implement an HTTP request, response, status, content type, header, CSP, cache rule, middleware, route, controller, handler integration, server, or served page;
- implement CLI, queue, worker, WebSocket, RPC, event, extension, agent, or frontend delivery;
- define the non-trusted failure response, production diagnostic model, incident model, allowlist, or redaction system;
- alter existing projector, renderer, eligibility, permission, exception, context, or API-status behavior;
- add Kernel, DI, Graph, container, service-locator, lifecycle, operation-scope, or Application Graph integration;
- add logging, telemetry, metrics, traces, events, persistence, serialization, localization, retry, fallback, recursion, or cleanup orchestration;
- add a third-party dependency;
- stage, commit, push, tag, branch, amend, rebase, reset, restore, clean, or stash;
- edit prior directives or reports; or
- begin Phase 1H or any later implementation.

---

## 9. Validation gates

### Gate A - identity and baseline

Before broad validation or edits, prove:

- all Section 2.1 authorities were read completely;
- the external Phase 1G directive identity matches;
- the Phase 1G report is the required zero-byte placeholder;
- both Phase 1F caller records match Section 3.2;
- Git history, tree, index, branch, remote, tag, status, and mode state match Section 3;
- the four post-caller untracked paths are exact;
- the 147-file protected set matches its locked digest;
- `composer.lock` matches its locked identity;
- all repository paths in scope are ordinary and have no non-default stream or reparse point; and
- `packages/diagnostic-handler/` does not exist.

Stop before quality or edits on failure.

### Gate B - pre-change quality

Run:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 135 files fixable
PHPStan: 0 errors
PHPUnit: 247 tests, 4233 assertions
```

Only the already accepted Composer warning categories are allowed: missing package licenses, unbounded local development constraints, and optional curl performance support if actually emitted. Report exact warnings.

Re-prove Gate A after quality.

### Gate C - documentation implementation

Create and modify only the Section 4 paths. Preserve UTF-8 without BOM, LF-only endings, exactly one final LF, and no trailing whitespace or NUL.

### Gate D - focused architecture proof

Prove directly that:

- ADR 0042 has the exact title, Accepted status, date, and section order;
- all exact names and the method signature appear consistently;
- all 13 authorized tracked documents changed and no other tracked path changed;
- ADR 0042 is the only new non-caller path;
- current-versus-planned wording is explicit;
- the current package count remains seven;
- the planned package count is eight only after future implementation;
- every open list removes resolved decisions and retains deferred work;
- all local links resolve;
- no stale statement claims the consumption boundary remains wholly unselected;
- no document claims the handler currently exists;
- no document calls the planned handler an authentication or transport authority;
- denial is consistently `null` before source access;
- success is consistently unchanged renderer HTML;
- `Throwable` propagation and no-catch policy are consistent;
- no source/test/package/manifest/lock/tooling file changed;
- the protected set, lockfile, caller records, HEAD, index, and Git topology remain exact; and
- no `packages/diagnostic-handler/` path exists.

### Gate E - final quality

Run the unchanged canonical gate again:

```powershell
composer quality
```

It must again pass with 247 tests and 4,233 assertions, zero PHPStan errors, zero fixer findings across 135 files, platform success, and zero advisories.

Then re-run every Gate D proof.

### Gate F - report population

Only after Gates A-E pass, populate `docs/task reports/Phase_1G.md` using Section 12. Validate the report and final Git state afterward.

---

## 10. Exact final repository state

After successful report population:

```text
branch: main
HEAD: 8fe793b124384744ac43887037360154c149241a
HEAD tree: 9de9388e85e463639f8d7fbbc90ae085f947a1b0
commit count: 8
modified tracked paths: 13
untracked paths: 5
total status paths: 18
staged paths: 0
remotes: 0
tags: 0
```

The exact modified tracked paths must be the 13 paths in Section 4.2.

The exact untracked paths must be:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
```

The repository must still contain exactly seven package directories and no `packages/diagnostic-handler/` directory.

---

## 11. Strict acceptance criteria

Phase 1G is accepted only if all criteria pass:

1. all governing authorities were read completely;
2. the externally supplied directive identity matched;
3. both Phase 1F caller records matched;
4. the starting and post-caller Git states matched;
5. pre-change canonical quality passed with 247 tests and 4,233 assertions;
6. exactly one ADR was created at the authorized path;
7. exactly 13 tracked documents were modified;
8. no other file was created or modified except the final report;
9. ADR 0042 is Accepted and dated 2026-08-15;
10. the planned package is exactly `kongono/diagnostic-handler` with namespace `Kongono\DiagnosticHandler\`;
11. the planned class and exact method contract match Section 5.2;
12. the future dependency topology matches Section 5.1;
13. the false-before-access execution order is explicit and consistent;
14. `null` has only the authorized meaning and is not described as a response;
15. successful HTML is returned unchanged;
16. all `Throwable` values propagate unchanged and no catch/fallback is planned;
17. outer adapters retain all evidence and authority ownership;
18. the handler is not described as authenticating its caller or proving provenance;
19. lifecycle, concurrency, and persistent-runtime isolation are explicit;
20. current seven-package state is distinguished from the planned eighth package;
21. all resolved and open decisions are reconciled consistently;
22. no implementation, package directory, PHP declaration, test, dependency, manifest, lockfile, tooling, runtime, transport, or later capability was introduced;
23. all local links, encoding, line-ending, whitespace, NUL, ordinary-file, stream, reparse, and security checks passed;
24. the 147 protected files and `composer.lock` remained exact;
25. final canonical quality passed with 247 tests and 4,233 assertions;
26. Git history, index, branch, remote, tag, and modes remained unchanged;
27. final status is exactly 13 modified, five untracked, 18 total, and zero staged;
28. the report was populated only after every non-report gate passed;
29. the report remained untracked, unstaged, and outside `HEAD`; and
30. no prohibited scope or unauthorized assumption occurred.

No partial pass is acceptable.

---

## 12. Standard executor completion report

Use exactly these headings in this order:

```markdown
# PHASE 1G TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. ARCHITECTURAL DECISIONS

## 8. PACKAGE AND PLANNED API CONTRACT

## 9. DEVIATIONS FROM DIRECTIVE

## 10. DEPENDENCIES ADDED OR REMOVED

## 11. ENVIRONMENT

## 12. COMMANDS RUN

## 13. VALIDATION RESULTS

## 14. DOCUMENTATION REVIEW

## 15. SECURITY AND FILE-SAFETY REVIEW

## 16. WARNINGS AND KNOWN ISSUES

## 17. QUESTIONS OR BLOCKERS

## 18. FINAL GIT STATUS

## 19. ACCEPTANCE CRITERIA

## 20. NEXT STEP
```

The report must include:

- `Complete` only if every criterion passed;
- independent directive and caller-record identities;
- exact starting and final Git state;
- pre-change and final quality results;
- exact created and modified path inventories;
- a concise statement of the package, topology, class, method, ordering, return, failure, security, and lifecycle decisions;
- direct proof that no implementation or protected byte changed;
- documentation/link/encoding/security evidence;
- exact warnings and any environment-only retry;
- a criterion-by-criterion pass/fail list; and
- the final status inventory.

Do not include a private email address. Do not claim a command passed unless its output was observed. Do not call blocked work complete.

The final nonblank line must be exactly:

```text
Await architect review before Phase 1H.
```

After the report is complete, return its final byte count and uppercase SHA-256 separately outside the report body.

---

## 13. Required final outcome

Phase 1G ends with one new Accepted architectural boundary and no runtime change.

Kongono still has seven implemented packages. The future eighth Diagnostic Handler package now has an exact name, dependency direction, Experimental class, method signature, denial behavior, success behavior, failure behavior, and persistent-runtime contract. It will centralize eligibility-before-access sequencing but will not authenticate an audience, evaluate evidence, own transport authority, deliver HTML, or make direct low-level calls impossible.

No Diagnostic Handler code exists after this phase. No configuration, adapter, request, response, middleware, route, CLI, worker, production diagnostic surface, logging, telemetry, persistence, serialization, Kernel integration, DI wiring, Application Graph integration, extension, frontend, or agent behavior is implemented.

Do not begin Phase 1H.
