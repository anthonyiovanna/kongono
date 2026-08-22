# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0L

## Actionable Diagnostics and Error-Boundary Doctrine

## Mission

Establish Kongono's first explicit, developer-friendly diagnostic and error-handling doctrine.

Phase 0L is a documentation-and-ADR phase. It must make the framework's future error behavior precise before any shared diagnostic PHP API, HTTP error renderer, logger, telemetry integration, retry engine, runtime adapter, or generic result abstraction is designed.

The required outcome is an inspectable contract in which every deliberate Kongono failure must eventually answer:

```text
What failed?
Why did it fail?
Where did it fail?
What can the developer do next?
```

This phase documents and reserves diagnostic identifiers for existing deliberate failure categories. It does not add those identifiers to runtime exceptions yet and must not claim that the development error page exists.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0L executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `README.md`, `ARCHITECTURE.md`, `SECURITY.md`, `CONTRIBUTING.md`;
3. `docs/doctrine.md`, `docs/documentation-standard.md`, and `docs/glossary.md`;
4. all files under `docs/architecture/`, `docs/engineering/`, and `docs/adr/`;
5. all production exception types, deliberate throw sites, tests, and package READMEs;
6. all accepted directives, reports, and correction reports through Phase 0K;
7. this directive.

The architectural decisions in this directive are explicitly approved. Do not choose another diagnostic syntax, taxonomy, catch policy, error-page boundary, or implementation mechanism.

## Mandatory stop rule

Stop without editing if:

- the repository baseline differs from Section 3;
- the Phase 0K report or Phase 0L caller records differ from Section 4;
- an unclassified file, staged change, concurrent change, or unexpected Git object exists;
- an allowed document cannot express the locked contract without contradicting an earlier locked decision;
- implementing the documentation accurately would require production code, a new package, a dependency, or a public API;
- a required validation fails for a repository reason;
- the exact current deliberate failure categories cannot be reconciled with Section 7;
- any architectural decision remains ambiguous.

When blocked:

```text
STOP
DO NOT GUESS
DO NOT FIX ADJACENT WORK
DO NOT STAGE OR COMMIT
REPORT THE EXACT DECISION OR MISMATCH
```

---

# 2. EXACT PHASE BOUNDARY

Phase 0L authorizes only:

1. one Accepted ADR governing actionable diagnostics and error boundaries;
2. one engineering guide defining the diagnostic contract and reserved catalog;
3. narrow updates to existing authoritative documentation and package READMEs;
4. documentation validation and the unchanged canonical quality gate;
5. the standard executor completion report.

Phase 0L does not authorize:

```text
PHP production or test changes
new exception classes, interfaces, enums, attributes, values, factories, or methods
changes to existing exception inheritance, constructors, messages, or throw sites
a universal Kongono exception marker
a shared diagnostic interface or diagnostics package
a generic Result, Either, Outcome, Problem, Violation, or error-envelope type
HTTP, routing, middleware, responses, status mapping, templates, or rendering
a development or production error page implementation
CLI error output or exit-code behavior
logging, telemetry, tracing, exporters, incident storage, or correlation machinery
retry, circuit-breaker, timeout, cancellation, compensation, or recovery machinery
runtime adapters or operation cleanup orchestration
source snippets, stack-trace rendering, debug-mode configuration, or environment capture
Application Graph schema, metadata, IDs, serialization, or diagnostic nodes
dependency injection integration or diagnostic services
dependencies, Composer changes, autoload changes, tooling-policy changes, or lockfile changes
Git staging, commit, tag, branch, remote, push, or history changes
```

Documentation must distinguish every planned mechanism from current repository behavior.

---

# 3. ACCEPTED PRE-PHASE-0L BASELINE

Require this exact committed baseline:

```text
Branch: main
Only local branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD parent count: 1
Commit count: 4
HEAD subject: Establish Kongono framework baseline through Phase 0J
Tracked files: 192
Tracked modes: 192 x 100644
Tracked worktree diff: empty
Index diff: empty
Remotes: 0
Tags: 0
```

The committed implementation baseline is:

```text
Production packages: 3
Production PHP files: 27
Production class-like declarations: 27
First-party PHP/configuration files syntax-checked: 90
PHP CS Fixer inventory: 89 files
PHPUnit: 127 tests, 567 assertions
Focused ApplicationGraphTest: 36 tests, 132 assertions
Focused ApiStatusConventionTest: 5 tests, 154 assertions
PHPStan: 0 errors
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Existing accepted Composer warnings remain limited to the missing license and unreleased local `@dev` or `*` constraints.

Before editing, independently verify the exact exception families and all deliberate production throw sites. The current package boundaries are:

```text
Kongono\Kernel\Exception\ExceptionInterface
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
```

They are separate Public package catch boundaries. Their abstract bases are Internal and their concrete failures are Experimental. Native engine failures are not broadly wrapped merely to enter a Kongono family.

---

# 4. CALLER RECORDS AND WORKTREE CONTRACT

Before Phase 0L caller setup, the sole untracked path is:

```text
docs/task reports/Phase_0K.md
    18,034 bytes
    SHA-256 F6EA293C490872B598C9E9F1E0D7AA6B18D1F69A4C68E1434813FE55E8871F6F
```

It is the accepted Phase 0K completion report. Preserve it byte-for-byte, untracked and unstaged.

The caller will add:

```text
docs/dev_plan/kongono_phase_0L_codex_directive.md
docs/task reports/Phase_0L.md
```

The directive must be a non-empty, byte-identical copy of this artifact. Record its size and SHA-256 and never edit it.

The Phase 0L report must initially be an ordinary zero-byte placeholder. Do not read it as a directive, stage it, commit it, rename it, or delete it. Populate it only after implementation and validation are complete, following Section 16.

After caller setup and before executor edits, require exactly:

```text
Tracked modifications: 0
Staged paths: 0
Untracked paths: 3
  docs/dev_plan/kongono_phase_0L_codex_directive.md
  docs/task reports/Phase_0K.md
  docs/task reports/Phase_0L.md
```

No other worktree path is authorized.

---

# 5. EXACT FILE-CHANGE BOUNDARY

## Create exactly

```text
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/engineering/error-handling.md
```

## Modify exactly

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

## Preserve exactly

All other files, including:

```text
composer.json
composer.lock
all package composer.json files
all PHP source and tests
all tooling configuration
all ADRs 0001 through 0025
all earlier directives and reports
docs/dev_plan/kongono_phase_0L_codex_directive.md
docs/task reports/Phase_0K.md
```

Do not stage or commit anything. Do not create a backup, temporary file, generated artifact, or alternate report inside the repository.

---

# 6. LOCKED FAILURE TAXONOMY

All updated documentation must use these distinctions consistently.

## 6.1 Deliberate framework failures

A deliberate framework failure is raised by Kongono because a caller violated an explicit framework invariant, supplied invalid framework configuration or structure, requested an unavailable framework capability, or attempted an invalid lifecycle action.

Rules:

- the owning package's exception marker remains the recovery/catch boundary;
- package exception families remain separate;
- deliberate failures must eventually have one stable diagnostic identifier and actionable diagnostic content;
- configuration, structural, and lifecycle misuse normally fail fast and are not presumed retryable;
- failed invariant checks must preserve valid state when the existing contract promises pre-mutation validation;
- current runtime identifiers are not implemented in Phase 0L.

## 6.2 Expected application outcomes

Invalid user input, authorization denial, business-rule rejection, optimistic conflict, and other expected application decisions are normal application outcomes, not automatically exceptions.

Future domain or capability APIs should prefer focused typed outcomes where the unsuccessful result is an expected part of ordinary control flow. Phase 0L does not select or implement a framework-wide Result/Either/Outcome abstraction, error union, validation library, or transport representation.

## 6.3 Infrastructure and external failures

Filesystem, network, database, process, provider, protocol, and other external failures belong to the capability or adapter that can add semantic meaning.

- preserve the original `Throwable` unless translation adds a real owning-layer meaning;
- semantic wrapping must retain the original as `previous`;
- do not wrap merely to make an error look like a Kongono failure;
- do not erase provider details from protected diagnostics;
- do not expose provider details directly to an untrusted surface;
- retry remains an explicit boundary policy, never an inference from a broad exception superclass.

No current package implements an infrastructure boundary in Phase 0L.

## 6.4 Engine and programmer failures

PHP `Error`, `TypeError`, assertion/invariant defects, and unknown programmer failures are not business outcomes and must not be converted into validation results or reassuring public errors.

Core code may catch `Throwable` only at a true outer runtime boundary or temporarily to guarantee cleanup before rethrow. Phase 0L implements neither boundary nor cleanup mechanism.

## 6.5 Cancellation and control signals

Cancellation, worker stop, timeout, and other control-flow signals require explicit future contracts. Do not classify or catch them as ordinary errors by assumption. Their type, propagation, cleanup, and reporting semantics remain open.

---

# 7. RESERVED DIAGNOSTIC IDENTIFIER CONTRACT

Diagnostic identifiers exist to make one failure category searchable, testable, documentable, and recognizable across exception output, future developer pages, CLI output, logs, telemetry, IDE tooling, and support material.

## 7.1 Syntax

Reserve this exact ASCII syntax:

```text
KGN-<DOMAIN>-<NNNN>
```

Rules:

- `KGN` is the fixed Kongono diagnostic prefix;
- `DOMAIN` is an approved uppercase ASCII owner token;
- `NNNN` is exactly four decimal digits from `1000` through `9999`;
- identifiers compare byte-for-byte and are case-sensitive;
- no whitespace, underscore, localization, version, environment, HTTP status, severity, timestamp, or instance identifier appears in the identifier;
- an assigned identifier is never reused for a different semantic reason;
- removal or replacement leaves the old identifier reserved;
- wording and remediation may improve without changing semantic identity;
- a semantic split receives new identifiers rather than overloading one old identifier;
- identical deliberate reasons at multiple throw sites may share one identifier;
- identifiers are not PHP exception codes and must not be placed in or inferred from `Exception::getCode()`;
- consumers and tooling must never parse exception message prose to discover an identifier;
- diagnostic identifiers are not Application Graph IDs, documentation IDs, operation IDs, trace IDs, request IDs, incident references, database keys, URLs, or transport error codes.

Reserved current domains:

```text
KERNEL  Kongono Kernel lifecycle ownership
GRAPH   Kongono Application Graph structural validation
DI      Kongono Dependency Injection definition and resolution
```

A future domain requires an explicit owning package or capability and documented allocation. Do not add speculative domains.

## 7.2 Reserved Kernel catalog

Document these exact future assignments without changing current PHP:

| Identifier | Failure reason | Required remediation direction |
| --- | --- | --- |
| `KGN-KERNEL-1001` | Boot requested when the Kernel is not `Unbooted` | Boot each Kernel instance exactly once from `Unbooted` |
| `KGN-KERNEL-1002` | Shutdown requested when the Kernel is not `Booted` | Shut down only a booted, non-shutdown Kernel |
| `KGN-KERNEL-1003` | Shutdown requested while operations remain active | End every operation owned by that Kernel before shutdown |
| `KGN-KERNEL-1004` | Operation begin requested when the Kernel is not `Booted` | Boot first and do not begin work after shutdown |
| `KGN-KERNEL-1005` | Operation end requested when the Kernel is not `Booted` | End active scopes while their owning Kernel is booted |
| `KGN-KERNEL-1006` | Operation scope is inactive, already ended, or owned by another Kernel | Pass the exact active scope to the Kernel instance that created it |

## 7.3 Reserved Application Graph catalog

| Identifier | Failure reason | Required remediation direction |
| --- | --- | --- |
| `KGN-GRAPH-1001` | Empty node-key value | Supply a non-empty opaque graph-local key |
| `KGN-GRAPH-1002` | Empty node-kind value | Supply a non-empty open node-kind label |
| `KGN-GRAPH-1003` | Empty relation-kind value | Supply a non-empty open relation-kind label |
| `KGN-GRAPH-1004` | Nodes input is not a zero-based consecutive list | Reindex the node array as a PHP list |
| `KGN-GRAPH-1005` | Relations input is not a zero-based consecutive list | Reindex the relation array as a PHP list |
| `KGN-GRAPH-1006` | A nodes-list entry is not a `Node` | Replace the reported entry with a valid `Node` |
| `KGN-GRAPH-1007` | A relations-list entry is not a `Relation` | Replace the reported entry with a valid `Relation` |
| `KGN-GRAPH-1008` | Duplicate node-key value | Remove or rename the duplicate within that snapshot |
| `KGN-GRAPH-1009` | Relation source does not reference an existing node | Add the source node or correct the source key |
| `KGN-GRAPH-1010` | Relation target does not reference an existing node | Add the target node or correct the target key |
| `KGN-GRAPH-1011` | Duplicate source/kind/target relation triple | Remove the duplicate or use a different relation kind when semantically distinct |

## 7.4 Reserved Dependency Injection catalog

| Identifier | Failure reason | Required remediation direction |
| --- | --- | --- |
| `KGN-DI-1001` | Service or dependency contract does not name an existing interface | Supply the exact declared interface name and ensure it can autoload |
| `KGN-DI-1002` | Service or dependency contract is not canonical | Use the declaration's exact case and spelling without a leading slash or alias |
| `KGN-DI-1003` | Resolver contract or subinterface is used as a service/dependency contract | Remove resolver injection and keep resolution at the composition root |
| `KGN-DI-1004` | Implementation does not name an existing class | Supply the exact autoloadable implementation class |
| `KGN-DI-1005` | Implementation class name is not canonical | Use the declaration's exact class name without alias or case variation |
| `KGN-DI-1006` | Implementation is not publicly instantiable | Use a named, concrete, publicly instantiable class |
| `KGN-DI-1007` | Implementation does not implement the declared service contract | Select an implementation satisfying that exact interface |
| `KGN-DI-1008` | Implementation exposes the Kongono resolver contract | Remove resolver implementation/injection from the service graph |
| `KGN-DI-1009` | Dependencies input is not a zero-based consecutive list | Reindex dependencies as a PHP list |
| `KGN-DI-1010` | Dependency-list entry is not a canonical-name string | Supply an interface-name string at the reported position |
| `KGN-DI-1011` | Implementation has no constructor but declares dependencies | Remove the dependency entries or define a matching public constructor |
| `KGN-DI-1012` | Implementation constructor is not public | Make the constructor public or choose another implementation |
| `KGN-DI-1013` | Constructor parameter count differs from dependency count | Make the ordered definition and constructor arity identical |
| `KGN-DI-1014` | Constructor parameter is optional, defaulted, variadic, or by-reference | Use a required, non-defaulted, non-variadic, by-value parameter |
| `KGN-DI-1015` | Constructor parameter lacks one non-nullable interface type | Use one non-nullable named interface type |
| `KGN-DI-1016` | Constructor parameter type differs from the dependency at that position | Align the exact interface type and ordered dependency |
| `KGN-DI-1017` | Definitions input is not a zero-based consecutive list | Reindex definitions as a PHP list |
| `KGN-DI-1018` | Definition-list entry is not a `ServiceDefinition` | Replace the reported entry with a validated definition |
| `KGN-DI-1019` | More than one definition uses the same contract | Retain exactly one definition for that contract |
| `KGN-DI-1020` | A declared dependency has no definition | Add the missing contract definition or remove the dependency |
| `KGN-DI-1021` | Definition graph contains a dependency cycle | Break the reported cycle through explicit design changes |
| `KGN-DI-1022` | Requested service name does not resolve to an existing interface | Request the exact autoloadable interface name |
| `KGN-DI-1023` | Requested interface is noncanonical or is the resolver contract | Use the exact supported service interface and never resolve the resolver itself |
| `KGN-DI-1024` | No definition exists for the requested service contract | Register an explicit definition at the composition root |
| `KGN-DI-1025` | Constructed object violates a previously validated service contract | Treat this as a framework invariant failure and inspect the definition/implementation boundary |

These tables reserve semantic assignments only. They are not evidence that current exception objects expose identifiers or remediation metadata.

---

# 8. ACTIONABLE DIAGNOSTIC CONTENT

The ADR and engineering guide must require every future deliberate Kongono diagnostic to provide, directly or through a documented version-matched registry:

1. stable diagnostic identifier;
2. precise plain-language title;
3. explanation of what operation failed;
4. the exact invariant, expectation, or state that was violated;
5. safe actual-versus-expected information when available;
6. location, input position, symbol, or owning component when known and safe;
7. one short remediation direction that a developer can act on;
8. the original exception chain when semantic wrapping occurred;
9. an owning package/capability;
10. a documentation location suitable for version-matched lookup.

Prohibit vague deliberate messages that only say:

```text
Invalid value
Invalid configuration
Operation failed
Unexpected error
Something went wrong
Unable to continue
```

Those phrases may appear only when followed by the exact cause and remediation. Tests in a future runtime diagnostic phase must verify assigned identifiers and required reason-specific information. Phase 0L changes no message and adds no test.

## Precision without unsafe disclosure

Precision does not authorize dumping raw sensitive or unbounded input.

- prefer a field name, type, position, count, state, or safely represented identifier;
- quote and escape displayed values;
- truncate unbounded values with an explicit truncation marker;
- never include credentials, tokens, cookies, authorization headers, session values, encryption material, private keys, full environment dumps, or arbitrary request bodies;
- do not promise that a Graph key, service name, route, SQL value, file path, or provider message is safe for an untrusted audience;
- retain richer protected diagnostics internally when required, but render only by explicit disclosure policy.

Exact context and redaction APIs remain open. Do not design an arbitrary diagnostic context bag in this phase.

---

# 9. CATCHING, WRAPPING, LOGGING, RETRY, AND CLEANUP

Lock these behavioral rules in documentation without implementing them.

## Catching

- Catch a package marker only when the caller can add recovery, translation, or boundary behavior.
- Do not catch merely to rethrow the same exception or hide its origin.
- Catch `Throwable` broadly only at a true top-level runtime boundary or temporarily to guarantee cleanup before rethrow.
- A future diagnostic capability interface, if any, is not selected here and must not silently become a universal recovery boundary.

## Wrapping

- Wrap only when crossing into an owning semantic layer that adds meaningful context.
- Preserve the original as `previous`.
- Never discard the exception chain.
- Never wrap native/autoloader/consumer failures merely to force them into a Kongono package family.

## Logging and telemetry

- Exceptions do not log themselves.
- One outer boundary owns final recording to prevent duplicate log entries.
- OpenTelemetry remains the intended future instrumentation model, but Phase 0L adds no telemetry.
- Diagnostic identifiers may later correlate protected records, but they are not incident or trace identifiers.

## Retry

- Retry is never implied by `RuntimeException`, a package marker, an HTTP status, or a diagnostic-number range.
- A future retry decision must be explicit, bounded, operation-aware, and safe for idempotency.
- Framework misuse, invalid structure/configuration, and invariant defects are not presumed retryable.

## Persistent-runtime cleanup

- Every future operation boundary must guarantee scope/resource cleanup even when application work throws.
- Successful cleanup must not replace or erase the primary failure.
- A cleanup failure must never be silently discarded.
- If cleanup leaves runtime state uncertain, that worker/runtime instance must not quietly accept more work.
- The future mechanism for preserving simultaneous primary and cleanup failures remains open.
- Phase 0L does not change `Kernel`, `OperationScope`, or lifecycle behavior and does not claim cleanup orchestration exists.

---

# 10. FUTURE DEVELOPMENT AND PRODUCTION ERROR EXPERIENCE

Document the following as an Accepted product/architecture requirement and an unimplemented future capability.

## Development experience

A future development renderer should intentionally optimize diagnosis. Its default information architecture is:

```text
Diagnostic ID and precise title
What happened
Why it happened
How to fix it
Relevant application location and safely escaped source excerpt
Exception/cause chain
Operation and request context when available
Application frames emphasized
Framework and vendor frames collapsible
Sanitized copyable Markdown or JSON diagnostic report
Version-matched local documentation reference
```

Requirements:

- clean, readable, accessible presentation;
- useful without mandatory client-side JavaScript;
- deterministic server-side content;
- HTML/source/context escaping;
- bounded source and value display;
- no secret disclosure merely because development mode is enabled;
- no remote call, AI request, or internet lookup required to explain a known Kongono diagnostic;
- a developer may search the stable identifier when additional help is needed;
- framework/version information may be shown, but environment dumps are prohibited;
- developer mode must come from trusted server configuration, never a request header, query parameter, cookie, or client-controlled value.

## Production experience

Future production surfaces must expose only a safe, intentionally generic response appropriate to that transport, plus an opaque incident/reference identifier when protected recording exists.

Production output must not expose:

```text
diagnostic detail not explicitly classified for public disclosure
exception class or message
stack trace or source excerpt
absolute path
environment/configuration values
credentials, cookies, headers, request bodies, SQL, provider payloads, or internal topology
```

The opaque incident reference is separate from the stable diagnostic identifier. HTTP status mapping, RFC problem formats, response negotiation, localization, CLI exit codes, templates, and rendering APIs remain open and unimplemented.

---

# 11. REQUIRED ADR 0026

Create:

```text
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
```

Use the repository ADR structure exactly:

```text
Title
Status: Accepted
Date: 2026-08-13
Context
Decision
Rationale
Consequences
Rejected alternatives
What remains open
```

The ADR must record:

1. the four-category failure taxonomy in Section 6;
2. expected application outcomes versus exceptional failures;
3. continued separation of package exception markers;
4. no universal exception/recovery marker in Phase 0L;
5. the exact identifier syntax, allocation, stability, and non-identity rules;
6. the three current owner domains and reserved catalog in Section 7;
7. the actionable-content requirements in Section 8;
8. secure disclosure and redaction principles;
9. catch, wrap, chain, log-once, retry, and cleanup rules;
10. future development and production experience separation;
11. strict current-versus-planned wording;
12. no runtime implementation in Phase 0L.

Rejected alternatives must include:

```text
vague prose without stable identifiers
parsing exception messages as a machine contract
using Exception::getCode() as the diagnostic registry
one universal Kongono exception family now
wrapping every Throwable
exceptions that log or render themselves
automatic retry inferred from hierarchy or number range
exposing development diagnostics in production
request-controlled debug mode
a framework-wide Result abstraction in this phase
implementing the error page before a transport boundary exists
```

What remains open must include the future runtime API for identifiers/metadata, exact redaction/context types, renderer/package ownership, HTTP/problem mapping, CLI output, incident storage, logging/telemetry integration, documentation URL/version mechanism, localization, source excerpt provider, retry/cancellation types, and primary-plus-cleanup failure representation.

Add ADR 0026 to `docs/adr/README.md` as item 26 with the exact title.

---

# 12. REQUIRED ENGINEERING GUIDE

Create:

```text
docs/engineering/error-handling.md
```

It must be the operational source of truth for contributors and future implementers. Include:

1. purpose and current implementation status;
2. failure taxonomy with use/do-not-use guidance;
3. current three package exception boundaries;
4. diagnostic identifier syntax and stability rules;
5. the complete reserved Kernel, Graph, and DI catalog from Section 7;
6. actionable diagnostic content requirements;
7. before/after examples of vague versus precise errors;
8. safe-value, escaping, truncation, and redaction guidance;
9. catch and wrapping examples in pseudocode clearly labeled unimplemented where appropriate;
10. logging-once and exception-chain rules;
11. retry/idempotency rules;
12. persistent-runtime cleanup rules;
13. development versus production presentation requirements;
14. a concise future development-page wireframe in text;
15. testing expectations for the later implementation phase;
16. explicit non-goals and open decisions;
17. links to ADR 0026, package READMEs, runtime model, security policy, documentation standard, and API-status guide.

Use examples only. Do not add PHP files or claim an executable interface. The example diagnostic `KGN-GRAPH-1004` may illustrate the intended display but must be labeled reserved/not yet emitted by runtime code.

Add the guide to `docs/engineering/README.md`.

---

# 13. REQUIRED RECONCILIATION OF EXISTING DOCUMENTATION

Modify only the exact files in Section 5 and make these changes narrowly.

## `README.md`

- identify Phase 0L as the current documentation/ADR phase while stating that production behavior remains the accepted Phase 0J implementation;
- add actionable, secure diagnostics to the core principles;
- link the error-handling engineering guide;
- state that runtime diagnostic identifiers and error pages remain unimplemented.

## `ARCHITECTURE.md`

- describe Phase 0L as Accepted diagnostic/error-boundary doctrine only;
- link the engineering guide and ADR index;
- keep all current Phase 0J implementation claims accurate;
- state that no renderer, shared diagnostic API, or transport mapping exists.

## `SECURITY.md`

- add disclosure rules separating protected diagnostics from untrusted output;
- prohibit client-controlled debug mode;
- prohibit automatic environment/request/secret dumping;
- state that the policy is architectural and the renderer is not implemented.

## `docs/architecture/overview.md`

- add diagnostic/error boundaries to intended architecture;
- retain exact current package behavior;
- distinguish reserved identifiers from emitted runtime metadata.

## `docs/architecture/runtime-model.md`

- replace the claim that operation-failure cleanup is wholly unresolved with the newly locked principles from Section 9;
- keep exact cleanup mechanism, runtime adapter, cancellation, and recovery APIs open;
- state that no cleanup orchestration is implemented.

## `docs/doctrine.md`

- add a concise locked actionable-diagnostics doctrine;
- include the four questions from the Mission;
- distinguish expected application outcomes from exceptional failures;
- require package ownership, chain preservation, secure disclosure, and persistent-runtime cleanup;
- state implementation remains future work.

## `docs/documentation-standard.md`

- strengthen the Failure concern so documented deliberate failures include identifier, cause, safe context, remediation, and boundary behavior when implemented;
- prohibit vague failure documentation;
- distinguish reserved/planned diagnostics from runtime reality.

## `docs/glossary.md`

Add concise entries for:

```text
Actionable Diagnostic
Diagnostic Identifier
Incident Reference
Expected Application Outcome
Error Boundary
```

Keep Diagnostic Identifier distinct from Documentation ID and Application Graph identity.

## Package READMEs

For Kernel, Application Graph, and Dependency Injection:

- retain the existing package-specific exception marker as the owning catch boundary;
- link to ADR 0026 and the engineering guide;
- identify the package's reserved diagnostic domain;
- say explicitly that current exceptions do not yet expose the reserved identifiers or structured remediation metadata;
- warn that current diagnostic messages may include caller-supplied identifiers and must not be exposed directly to an untrusted production surface;
- retain all current failure propagation, lifecycle, and non-goal behavior;
- do not rewrite current runtime behavior as if Phase 0L changed it.

## ADR and engineering indexes

- add only the exact new entries;
- retain numbering, titles, ordering, and local links.

---

# 14. VALIDATION CONTRACT

Before editing, run and record:

```text
environment/tool versions
exact Git branch, HEAD, tree, parent, commit count, subject
branch/remote/tag counts
tracked/index cleanliness
exact three-path caller inventory
sizes and SHA-256 for all caller records
composer.json and composer.lock hashes
production/test/tool inventory counts
composer quality
```

After editing and before writing the report:

1. confirm exactly the 13 tracked documents in Section 5 are modified;
2. confirm exactly the two authorized new content documents exist;
3. confirm no PHP, JSON, lockfile, Composer, tooling, source, or test file changed;
4. confirm no staged path and no Git object/history change;
5. confirm the Phase 0K report and Phase 0L directive remain byte-identical;
6. confirm the Phase 0L report is still zero bytes;
7. confirm ADR 0026 is Accepted, dated 2026-08-13, and indexed once;
8. confirm the engineering guide is indexed once;
9. confirm each canonical catalog contains the exact 42 unique semantic assignments from Section 7, every repeated documentation reference maps an identifier to the same reason, and no identifier is assigned two meanings;
10. confirm no identifier outside the three authorized domains is assigned;
11. confirm all local Markdown links resolve;
12. confirm strict UTF-8 without BOM, LF endings, final newlines, no trailing whitespace, NUL, replacement character, mojibake, or conflict marker across every changed/new document;
13. confirm planned/error-page statements are not phrased as implemented behavior;
14. confirm current exception families, throw sites, source counts, tests, API statuses, and package boundaries remain unchanged;
15. run all 90 first-party PHP syntax checks even though PHP is unchanged;
16. run focused `ApplicationGraphTest.php` and `ApiStatusConventionTest.php`;
17. run canonical `composer quality` unchanged;
18. confirm exact expected quality baseline remains 127 tests and 567 assertions, PHPStan 0, style 0/89, zero advisories, and platform pass;
19. repeat Git status and content hashes after all tooling;
20. inspect the diff for scope, clarity, contradiction, accidental stabilization, and false implementation claims.

The reserved identifier count is exact:

```text
Kernel: 6
Application Graph: 11
Dependency Injection: 25
Total: 42
```

Composer warnings must remain limited to the accepted categories. If only the unchanged advisory network/cache access is sandbox-blocked, rerun the same command with authorized access. Do not weaken or skip it.

After report population, validate the report itself and run final Git status. Expected final categories are:

```text
13 modified tracked documentation files
2 new untracked content documents
3 untracked caller records
  Phase 0K report, preserved
  Phase 0L directive, preserved
  Phase 0L report, populated
0 staged paths
0 PHP/source/test/configuration changes
```

---

# 15. PROHIBITED SCOPE AND ACCEPTANCE CRITERIA

## Prohibited scope

Do not:

- implement or modify runtime exception behavior;
- add diagnostic IDs to PHP or exception messages;
- add a shared diagnostics abstraction;
- add a renderer, template, route, middleware, command, logger, telemetry hook, retry policy, or cleanup runner;
- introduce HTTP status codes, RFC problem details, CLI exit codes, localization, or incident persistence;
- add arbitrary structured context or redaction code;
- add source-reading/debug utilities;
- infer cancellation behavior;
- modify any dependency, Composer manifest, lockfile, autoload rule, quality policy, or API status;
- modify existing ADR decisions beyond adding the ADR index entry;
- create Phase 0M work;
- stage, commit, tag, branch, add a remote, push, pull, fetch, reset, restore, or rewrite history;
- clean up unrelated wording or files.

## Acceptance criteria

Phase 0L is accepted only if:

- the baseline and caller records match exactly;
- only the 15 authorized content paths change;
- ADR 0026 and the engineering guide contain the complete locked contract;
- the identifier syntax and exact 42-entry catalog are correct and unique;
- every updated document distinguishes current behavior from future implementation;
- package exception separation remains intact;
- expected application outcomes are not conflated with framework exceptions;
- native/external wrapping rules preserve original causes;
- vague errors are explicitly rejected;
- secure diagnostic disclosure and client-independent debug configuration are locked;
- future development and production experiences are clearly separated;
- persistent-runtime cleanup principles are locked without inventing an API;
- no PHP API, source, test, dependency, tooling, lockfile, or Git history changes;
- all Markdown integrity/link checks pass;
- syntax, focused tests, static analysis, style, platform, audit, and full PHPUnit pass unchanged;
- the Phase 0K report and Phase 0L directive remain byte-identical;
- the final Phase 0L report is complete, populated only after validation, untracked, and unstaged;
- every prohibited item remains absent.

---

# 16. STANDARD EXECUTOR COMPLETION REPORT

Write the final report only to:

```text
docs/task reports/Phase_0L.md
```

Keep it untracked and unstaged. Put the entire report inside one outer `text` fence with no text outside it. Use exactly the repository-standard 16 sections in this order:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

Mandatory evidence:

- exact baseline Git hashes/counts and final proof they did not change;
- exact initial/final status inventories;
- sizes and SHA-256 for Phase 0K report and Phase 0L directive inside the report;
- the final Phase 0L report byte count and SHA-256 in the accompanying executor response after the report's last write, with the report itself noting that embedding its own final digest would change that digest;
- exact created and modified path lists;
- confirmation that every other file remained byte-identical;
- exact 42-ID uniqueness/catalog validation, treating consistent cross-document repetition as references to one semantic assignment rather than additional allocations;
- current-versus-planned wording audit;
- Markdown encoding, final-newline, whitespace, conflict-marker, and link results;
- complete syntax, focused-test, and canonical quality results with exact counts;
- warnings and any environmental retry stated truthfully;
- `None` for decisions/deviations/dependencies/questions when none exist;
- a line-by-line PASS/FAIL acceptance checklist;
- Section 16 exactly: `Await architect review before Phase 0M.`

Before returning, validate the report's one-fence structure, exact 16-section order, strict UTF-8, LF, final newline, and lack of trailing whitespace. Re-run Git status after the final report write. Do not stage or commit the report.

---

# 17. FINAL EXECUTOR INSTRUCTION

Implement only the documentation and ADR boundary defined here. Make Kongono's future diagnostics precise, searchable, actionable, secure, package-owned, persistent-runtime-aware, and honest about what is not yet implemented.

Do not add runtime diagnostic machinery or the error page. Do not begin Phase 0M. Stop rather than infer any missing API, context schema, renderer, transport mapping, logging, retry, cleanup, or telemetry decision.
