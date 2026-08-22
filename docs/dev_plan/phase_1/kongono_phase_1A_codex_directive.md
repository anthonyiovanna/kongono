# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1A
## Fail-Closed Trusted-Developer Diagnostic Activation Architecture

**Directive status:** Architect-authorized execution contract
**Phase type:** Architecture and documentation only
**Execution role:** Executor, not architect
**Date:** 2026-08-14

---

## 1. Mission

Phase 0Z implemented Kongono's first real developer-facing diagnostic artifact:
an exact, accessible, escaped, deterministic HTML document produced from an
already-projected trusted developer diagnostic. That capability is deliberately
isolated. Nothing currently authorizes projection, invokes the renderer from a
runtime boundary, or serves its output.

Phase 1A must establish the security architecture that must exist before any
adapter may expose that document. It resolves only the fail-closed activation
and delivery-authorization policy. It does **not** implement an activation type,
configuration reader, HTTP request or response, router, error handler, runtime
adapter, served page, production model, or other executable capability.

The central rule is:

```text
trusted bootstrap permission
    AND
adapter-controlled delivery permission
        BEFORE source access, projection, rendering, or delivery
```

Both permissions are required. Neither permission may be inferred by the
Diagnostic Presentation package. Absence, ambiguity, invalid configuration,
evaluation failure, or an untrusted input must never become enabled behavior.

This phase must explain this boundary precisely enough that a later
implementation directive can choose typed APIs and a transport adapter without
inventing security semantics.

Phase 1A is successful only when current documentation consistently states:

- the projector and renderer remain low-level pure Experimental APIs;
- direct calls to them do not establish authorization;
- trusted presentation is disabled unless an outer composition boundary has
  made both required decisions explicitly;
- no request or other client-controlled value can enable either decision;
- configuration changes cannot mutate a live shared decision implicitly;
- denial occurs before the projector reads exception accessors or copies
  Protected fields;
- rendering remains distinct from delivery;
- invalid activation configuration is rejected before serving work;
- uncertain delivery authorization takes the non-trusted path;
- no implementation of these rules exists yet; and
- HTTP, production presentation, logging, telemetry, incident handling, and
  every later capability remain future work.

Do not begin Phase 1B or any transport implementation.

---

## 2. Authority and mandatory stop rule

Before editing, read completely, in this order:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `docs/doctrine.md`;
5. `docs/documentation-standard.md`;
6. `docs/engineering/api-status.md`;
7. `docs/engineering/error-handling.md`;
8. `docs/architecture/overview.md`;
9. `docs/architecture/runtime-model.md`;
10. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
11. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
12. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`;
13. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
14. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
15. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
16. `docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md`;
17. `packages/diagnostic-presentation/README.md`;
18. `packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php`;
19. `packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php`;
20. `docs/dev_plan/kongono_phase_0Z_codex_directive.md`;
21. `docs/task reports/Phase_0Z.md`;
22. this directive; and
23. the zero-byte `docs/task reports/Phase_1A.md` caller placeholder.

Earlier directives and reports are historical evidence. They do not override
accepted current ADRs or this directive. Preserve their bytes exactly.

The Phase 1A directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local file is not self-authenticating. If either value is absent or does not
match, stop before quality validation or mutation and report the observed local
identity.

Apply the repository policy literally:

```text
STOP
ASK
DO NOT GUESS
```

Stop before mutation when any authority, caller record, Git identity, inventory,
file identity, file type, stream, encoding, or scope condition differs from this
directive. Do not repair, normalize, stage, commit, reset, restore, clean, stash,
rebase, amend, or otherwise alter the mismatch.

During execution, stop before continuing if the requested prose would require
selecting any of the following:

- a PHP class, interface, enum, attribute, function, or method contract;
- a configuration key, filename, schema, source, loader, or parser;
- an environment variable or application-environment convention;
- an HTTP request, response, middleware, handler, router, status, header, or
  content-negotiation contract;
- a user, role, session, tenant, identity, authentication, authorization, or
  network-listener mechanism;
- a generic Throwable catch boundary or shared exception marker;
- a fallback or recursive diagnostic page;
- a logging, telemetry, incident, retry, cancellation, or cleanup mechanism;
- a production disclosure model or allowlist;
- a package, dependency, Composer, autoload, or tooling change; or
- any implementation beyond the documentation boundary in Section 4.

Those decisions remain future work unless this directive states their exact
architecture at the policy level.

---

## 3. Locked starting state

### 3.1 Repository identity before caller setup

The accepted repository state is:

```text
branch                  main
local branches          1
HEAD                    7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree               b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent             75749a37a184996203e569e039bddbc3ec10cdf7
commit count            6
remotes                 0
tags                    0
tracked modifications   32
untracked paths         39
total status paths      71
staged paths            0
tracked diff            32 files, 907 insertions, 212 deletions
```

The user will add exactly these caller records after receiving this directive:

```text
docs/dev_plan/kongono_phase_1A_codex_directive.md
docs/task reports/Phase_1A.md
```

The report must initially be an ordinary zero-byte, untracked, unstaged file,
absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Therefore the expected executor pre-mutation status after caller setup is:

```text
tracked modifications   32
untracked paths         41
total status paths      73
staged paths            0
```

The exact modified-path inventory is:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/glossary.md
packages/diagnostics/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

The exact untracked-path inventory after caller setup is:

```text
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0W_codex_directive.md
docs/dev_plan/kongono_phase_0X_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0X_codex_directive.md
docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Y_codex_directive.md
docs/dev_plan/kongono_phase_0Z_codex_directive.md
docs/dev_plan/kongono_phase_1A_codex_directive.md
docs/task reports/Phase_0V.md
docs/task reports/Phase_0V_1_correction.md
docs/task reports/Phase_0W.md
docs/task reports/Phase_0X.md
docs/task reports/Phase_0X_1_correction.md
docs/task reports/Phase_0Y.md
docs/task reports/Phase_0Y_1_correction.md
docs/task reports/Phase_0Z.md
docs/task reports/Phase_1A.md
packages/diagnostic-presentation/README.md
packages/diagnostic-presentation/composer.json
packages/diagnostic-presentation/src/Developer/DeveloperDiagnosticOwner.php
packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnostic.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticField.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php
packages/diagnostic-presentation/src/Diagnostic/DiagnosticPresentationDiagnostic.php
packages/diagnostic-presentation/src/Exception/DiagnosticPresentationException.php
packages/diagnostic-presentation/src/Exception/ExceptionInterface.php
packages/diagnostic-presentation/src/Exception/InvalidDiagnosticProjectionException.php
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-empty.html
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-full.html
packages/diagnostic-presentation/tests/Unit/DeveloperDiagnosticOwnerTest.php
packages/diagnostic-presentation/tests/Unit/DiagnosticPresentationDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/ExceptionBoundaryTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticFieldTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticProjectorTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperHtmlRendererTest.php
```

No accepted pre-existing status path may disappear, change classification, or
be rewritten outside the Phase 1A boundary.

### 3.2 Locked Phase 0Z records

These accepted caller records are exact:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0Z_codex_directive.md` | 49502 | `C815F40FF45871835D8937DB1FAD4BAF25DE6482CAAC6E03F223F56C6CC7BD0F` |
| `docs/task reports/Phase_0Z.md` | 18163 | `B3E3CE79A74F73A9A179AE3BD6CD9C2DF7B4DF058F79B3BA2F465C705183E582` |

Preserve every earlier directive and report byte-for-byte. Phase 1A may populate
only its own report after complete success.

### 3.3 Locked implementation identities

Phase 1A must not change executable behavior. These artifacts are exact:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php` | 3171 | `DA3859A41B027952BB28391FBA87E468B7227A0DB4FCFC22655DF80485F14D36` |
| `packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php` | 12318 | `2AD9FEF1CDA0CE8D51960ADF2B1285D4D7A9813734E3492C70B476242A179A8D` |
| `packages/diagnostic-presentation/composer.json` | 566 | `22CA3AC255E292C624C93AC4B56377F4B514A1CC64031B946EC7958EE96A232C` |
| `composer.json` | 2886 | `0BF4227C1FA046D5F55F4742BA572D56E5AAE13DE79F43192CB8A7B65CAA7BBD` |
| `composer.lock` | 180654 | `FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387` |

The complete protected set contains exactly 140 files and has aggregate
SHA-256:

```text
82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E
```

Use the accepted ordinal aggregate algorithm: repository-relative `/` paths,
each encoded as path + NUL + decimal byte length + NUL + uppercase file
SHA-256 + LF, then SHA-256 the UTF-8-without-BOM concatenation.

The protected set is every PHP file below `packages/` and `tests/`, root
`composer.json`, `composer.lock`, all six package manifests, and the three root
PHP quality configuration files. It must remain byte-identical through Phase
1A.

The exact implementation counts remain:

```text
package directories       6
package manifests         6
all Composer manifests    7
production PHP files      49
test PHP files            80
protected files           140
PHP CS Fixer files        129
API-status declarations   49
PHPUnit tests             241
PHPUnit assertions        4170
```

### 3.4 Starting identities for the documentation boundary

Before editing, verify every authorized existing path:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `README.md` | 8512 | `AC55C5513AA15A879EAB6EC950E2B8F9F9FBE4E1A7DC9EB4D26A632A8902CFF4` |
| `ARCHITECTURE.md` | 10818 | `7BCB66A5A08E61809B128962C28A9C8A6A37BC1F350500D8ECCEAB1E28412619` |
| `SECURITY.md` | 9140 | `681108038BE785AFAB4F1B992C40DF6CB691952CAF250A5B955108A3275633D0` |
| `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md` | 23845 | `61148DC83ECC3B6DC54F4EB005ABDBA7807749FE2D8B1CD8F5A1F8F6B2CB68A0` |
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 12914 | `E13C13BC479ECF31C31DCD7962A2D2F8373E2F79A2E614E58006756E5734BAED` |
| `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md` | 7680 | `9EA7B479ED5ECB4B0F85B1115C0928DEA869F65899C98559FA9D40A51C62E9BF` |
| `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md` | 14871 | `75E487DF102D88E19F462E03F6D7610BFB46D47FA500D934AA7D736986504D97` |
| `docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md` | 8051 | `6AE46B2D59BF8AC42910B5309F38E689785BABC032936D014E49AAA90BB2C106` |
| `docs/adr/README.md` | 4378 | `C10AE79D11963FA3682CA13955877D2FF2BA6F4A8377E250F199F705107D15B8` |
| `docs/architecture/overview.md` | 9201 | `CEDCBEB42DCCF0392CD7BA79C821D0D7974DCDA40E3F38461656C9182FB76F4A` |
| `docs/architecture/runtime-model.md` | 6662 | `6EF5E45FF131CD32638A70E2CC07DC9DCC29E2BF1FE1FDB53955EA4AF376A880` |
| `docs/doctrine.md` | 15778 | `D15905E26486581763E209A844539BAC380AD4557F236B0909CB41F141DD46DF` |
| `docs/engineering/error-handling.md` | 62105 | `FD05BA2175104A1F7E60437CB1485BD849A277964E8F8C5DEF06946F8792D5AB` |
| `docs/glossary.md` | 20835 | `4F21E02691B26D6564EC53FCDED09D903660B11330BCC4ADAF9F90FEA183156C` |
| `packages/diagnostic-presentation/README.md` | 6008 | `6F7C96F51678740E86036C86D237F9DC96C46679983F359F48B3B2FB7F20EF5A` |

`docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md` must not
exist before Phase 1A.

---

## 4. Exact file boundary

### 4.1 Create exactly one architecture record

```text
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
```

### 4.2 Modify exactly fifteen existing documentation files

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/error-handling.md
docs/glossary.md
packages/diagnostic-presentation/README.md
```

### 4.3 Populate exactly one caller report after complete success

```text
docs/task reports/Phase_1A.md
```

The report is not an implementation file. Do not populate it until every gate
passes.

### 4.4 Everything else is immutable

Do not create, modify, rename, move, delete, stage, or commit any other path.
In particular, all PHP, tests, fixtures, Composer manifests, `composer.lock`,
tooling configuration, historical directives, and historical reports are
outside scope.

No formatting or link checker may rewrite files. All validation must be
read-only.

---

## 5. Locked activation architecture

ADR 0038 and every reconciliation must express the following exact policy.

### 5.1 Two independent permissions

Trusted diagnostic presentation requires two affirmative permissions:

1. **Trusted bootstrap permission** — a server-controlled composition boundary
   has deliberately enabled the trusted developer capability for the adapter
   instance before it begins serving work.
2. **Adapter-controlled delivery permission** — the outer adapter has
   independently determined that this specific delivery boundary is eligible
   to receive trusted developer output.

One permission never implies the other. Bootstrap permission does not prove
that an audience or connection is trusted. Delivery permission does not enable
the capability when bootstrap permission is absent.

The exact future evaluation table is:

| Bootstrap permission | Delivery permission | Trusted projection/rendering |
| --- | --- | --- |
| denied or absent | any value | prohibited |
| allowed | denied or uncertain | prohibited |
| allowed | allowed | eligible, subject to the later adapter contract |
| invalid or evaluation failure | any value | prohibited; never default to allowed |

`Eligible` does not mean that Phase 1A implements a call, response, route, or
handler. It records the only state in which a future adapter may proceed.

### 5.2 Evaluation order

Both permissions must be affirmative before the future outer adapter:

- calls a package exception's diagnostic metadata or context accessors for
  presentation;
- invokes `TrustedDeveloperDiagnosticProjector`;
- copies Safe or Protected fields into the trusted model;
- invokes `TrustedDeveloperHtmlRenderer`; or
- delivers, stores, logs, transmits, or otherwise exposes the returned HTML.

The source exception may already own its eagerly constructed bounded diagnostic
context as part of the accepted package-local failure contract. Denial does not
erase or mutate that exception. It only prohibits presentation access,
projection, rendering, and delivery.

A future adapter must not optimistically project or render and discard the
result after denial. The gate precedes access because avoiding unnecessary
Protected-data copies is part of the boundary.

### 5.3 Bootstrap permission source

The bootstrap decision must be supplied to a future adapter as an already
validated, explicit, typed, immutable server-owned decision. Diagnostic
Presentation must not read or parse its source.

This phase does not select the type, constructor, factory, configuration key,
file, command-line option, dependency-injection binding, framework service, or
adapter API. It locks only these constraints:

- absence means disabled;
- invalid or ambiguous raw configuration must be rejected at bootstrap before
  serving work, never coerced or silently treated as enabled;
- a raw string such as `development`, `local`, `debug`, `true`, `1`, `yes`, or
  an application-environment label is not itself permission;
- a mutable global, superglobal, static, process registry, service locator,
  thread-local, fiber-local, previous-operation value, or cached ambient lookup
  cannot supply permission;
- the Diagnostic Presentation package cannot discover or infer permission;
- AI output, a heuristic, exception type, diagnostic identifier, context value,
  hostname, filesystem presence, or network reachability cannot supply
  permission; and
- changing raw configuration cannot mutate a live adapter's trusted decision
  behind its back.

An explicit restart or construction of a new future adapter instance is required
before a changed bootstrap decision can take effect. The exact configuration
reload lifecycle remains future.

### 5.4 Delivery permission source

The delivery decision belongs to the future outer adapter and must be based on
server-controlled transport ownership. Phase 1A intentionally does not select a
network binding, listener, authenticated operator identity, local socket,
middleware, access-control rule, or equivalent mechanism.

These prohibitions are locked:

- no query parameter, route value, path, header, cookie, form value, body,
  session value, user-supplied token, or other client-controlled value may
  enable delivery;
- no request can upgrade denied or absent bootstrap permission;
- no database, provider payload, arbitrary application callback, extension,
  plugin, or agent output may self-authorize delivery;
- no prior request, operation, tenant, user, fiber, or worker state may leak an
  allowed decision into a later delivery; and
- uncertainty is denial, not a prompt, fallback, partial page, or best effort.

Legitimate transport-specific authentication and listener policies remain open
for the future adapter architecture. This phase does not forbid those future
server-owned controls; it forbids treating raw client assertions as authority.

### 5.5 Low-level API truth

`TrustedDeveloperDiagnosticProjector` and
`TrustedDeveloperHtmlRenderer` remain low-level pure Experimental APIs. Their
signatures and behavior do not change. They cannot determine whether their
caller is trusted and must not pretend to enforce the two permissions.

Direct consumer calls remain possible at the PHP language level. Such calls are
outside an implemented activation boundary and place the security obligation on
the caller. Documentation must not describe direct callability as proof of
authorization.

Do not propose a forge-proof token, hidden constructor, secret string, marker
interface, attribute, API-status value, DI service, or capability object inside
Diagnostic Presentation as a substitute for outer enforcement. PHP package
visibility is not a security boundary.

### 5.6 Failure behavior

The architecture distinguishes two moments:

- **Bootstrap validation failure:** the future adapter must reject its startup
  or construction before accepting work. It must not continue with trusted
  diagnostics enabled. The exact failure type and startup mechanism remain
  open.
- **Delivery authorization denial or uncertainty:** trusted presentation is not
  invoked. A future adapter must use its separately designed non-trusted failure
  path. The exact generic response, status, body, incident reference, logging,
  and transport behavior remain open.

The activation system must not catch a projector or renderer failure and then
recursively invoke trusted rendering. It must not replace the primary
application failure with an activation failure or conceal simultaneous failures.
Exact multi-failure representation remains open.

### 5.7 Persistent-runtime lifecycle

The future bootstrap decision is immutable for the lifetime of one adapter
instance. It is not operation state and cannot be mutated by a request, job,
message, command, tenant, user, or previous failure.

The future delivery decision is evaluated by the adapter for the current
delivery boundary without storing it in a static or ambient current context. A
decision for one operation cannot authorize another operation.

This phase selects no Kernel integration, operation-scope field, reset hook,
container lifetime, singleton, worker recycle policy, concurrency primitive, or
adapter instance type. It locks only isolation and explicit lifetime ownership.

### 5.8 Disclosure and production separation

Trusted developer output can contain Protected values. Final HTML escaping
prevents markup injection but does not authorize disclosure, lower a
classification, or make the model safe for production.

The two-gate architecture applies only to the existing trusted developer model.
It does not define production eligibility. Production continues to require a
separate deny-by-default model with explicit field allowlists. Production must
not consume the trusted model and redact it afterward.

Safe remains eligibility metadata, not automatic public disclosure. Protected
remains trusted-only. Forbidden material remains prohibited from capture rather
than becoming another display class.

### 5.9 Performance and side effects

Permission evaluation should be constant bounded work before projection, but no
performance guarantee or API is selected in this phase. The architecture adds
no I/O, allocation, cache, network call, filesystem read, environment lookup,
log, metric, trace, event, incident, or retained runtime state.

Documentation changes are the only Phase 1A side effect.

---

## 6. ADR 0038 exact requirements

Create:

```text
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
```

The title must be exactly:

```text
# ADR 0038: Fail-Closed Trusted-Developer Diagnostic Activation
```

It must contain these second-level sections in this order:

```text
## Status
## Date
## Context
## Decision
## Rationale
## Security implications
## Lifecycle and concurrency
## Performance implications
## Consequences
## Rejected alternatives
## What remains open
```

The status and date section bodies must be exactly:

```text
## Status

Accepted

## Date

2026-08-14
```

The ADR must:

1. link ADRs 0034, 0036, and 0037;
2. state that Phase 1A is architecture/documentation only;
3. state that no activation or adapter implementation exists;
4. define the two independent permissions and exact truth table from Section
   5.1;
5. require both permissions before presentation access, projection, rendering,
   or delivery;
6. distinguish the exception's existing eager context from later presentation
   access and copying;
7. define absence, invalidity, ambiguity, evaluation failure, and uncertainty as
   fail-closed outcomes;
8. require invalid raw bootstrap configuration to be rejected before serving;
9. state that Diagnostic Presentation reads no raw configuration;
10. reject raw environment labels and truthy strings as authority;
11. reject every client-controlled delivery trigger listed in Section 5.4;
12. state that exact future transport-owned authorization mechanisms remain
    unselected;
13. preserve projector/renderer purity and direct-call truth;
14. explain why a package-local token is not a security boundary;
15. define immutable adapter-instance bootstrap lifetime and per-delivery
    isolation;
16. preserve Safe, Protected, and forbidden-material doctrine;
17. keep production presentation separate and deny-by-default;
18. state no logging, telemetry, incident, HTTP, CLI, configuration, or runtime
    side effect is implemented;
19. reject optimistic project/render-then-discard behavior;
20. reject recursive rendering and fallback activation;
21. record security, lifecycle, concurrency, and performance consequences; and
22. preserve the exact future-work list below.

`What remains open` must include:

- exact typed bootstrap decision API and ownership;
- exact server configuration source, schema, loader, validation failure, and
  reload lifecycle;
- exact outer adapter package and composition contract;
- exact delivery-authorization mechanism, including any listener or operator
  identity policy;
- HTTP request/response ownership, status, content type, cache, and CSP;
- a separate production model and explicit allowlists;
- safe copy/export formats and other renderers or transports;
- cause, source, stack, and version-matched documentation providers;
- localization;
- logging, telemetry, incident storage, and serialization;
- primary, activation, projection, rendering, transport, and cleanup
  multi-failure representation; and
- runtime adapter, Kernel lifecycle, DI wiring, Application Graph, extension,
  CLI, frontend, and agent integration.

Do not list the two-gate policy itself as open after Phase 1A. Its mechanism is
open; its fail-closed semantics are Accepted.

---

## 7. Documentation reconciliation

Modify only the files in Section 4.2. Preserve historical phase facts while
making current versus accepted-future behavior explicit.

### 7.1 `README.md`

Add a concise current-state note that Phase 1A accepts the two-gate activation
architecture but implements no activation, adapter, or served page. Keep the
renderer described as real and directly callable only as a low-level API.

### 7.2 `ARCHITECTURE.md`

Place the two permissions outside Diagnostic Presentation in the conceptual
composition flow. Preserve the six-package graph. State that no new package
edge, service, runtime integration, or configuration mechanism exists.

### 7.3 `SECURITY.md`

Replace broad future wording with the precise two-gate rule. Document fail-
closed absence/invalidity/uncertainty, bootstrap rejection before serving,
client-control prohibitions, direct-call limitations, and production
separation. Do not claim implemented enforcement.

### 7.4 ADRs 0026 and 0034 through 0037

Add Phase 1A architecture notes linking ADR 0038. Preserve each ADR's original
decision and implementation history. Move only the fail-closed two-gate policy
from unresolved to Accepted. Keep exact PHP APIs, config mechanics, adapter,
transport, production, providers, operational integration, and multi-failure
behavior open.

Do not imply Phase 1A changes the projector, renderer, diagnostic contexts,
exception families, HTML grammar, golden fixtures, or tests.

### 7.5 `docs/adr/README.md`

Add ADR 0038 as Accepted in numeric order with an accurate one-sentence
summary.

### 7.6 `docs/architecture/overview.md`

Extend the current/future boundary: the activation policy is Accepted; its
enforcement and every adapter remain absent. Include the two permissions in the
conceptual sequence without presenting them as code.

### 7.7 `docs/architecture/runtime-model.md`

Document immutable adapter-instance bootstrap lifetime, per-delivery isolation,
no ambient state, explicit recreation for changed decisions, and the required
evaluation order before source access. Preserve the absence of runtime adapters
and Kernel integration.

### 7.8 `docs/doctrine.md`

Add the fail-closed two-gate rule as locked diagnostic doctrine. State that
permission mechanisms remain future and that API status or direct callability
is not authorization.

### 7.9 `docs/engineering/error-handling.md`

Add an implementer-facing activation section with the exact truth table,
evaluation order, denial behavior, direct-call warning, persistent-runtime
lifetime, and future boundaries. It must be understandable without reading the
ADR but must link ADR 0038.

### 7.10 `docs/glossary.md`

Add or reconcile concise definitions for:

- trusted bootstrap permission;
- adapter-controlled delivery permission; and
- trusted diagnostic activation.

Definitions must say architecture Accepted, mechanism unimplemented.

### 7.11 `packages/diagnostic-presentation/README.md`

State that the package does not own either permission, cannot inspect raw
configuration or requests, and does not enforce trust. Explain the exact calling
precondition for its low-level projector and renderer. Preserve all current API,
output, bounds, failure, and package facts.

---

## 8. Prohibited scope

Phase 1A must not create or modify:

- PHP source, tests, fixtures, autoloading, manifests, lockfiles, or tooling;
- an activation, policy, decision, capability, token, grant, configuration, or
  authorization type;
- an environment variable, configuration key, file, parser, loader, or default;
- an HTTP package, PSR-7/15 integration, request, response, middleware, handler,
  router, server, runtime, status, header, CSP, cache, or content negotiation;
- a generic Throwable catcher or universal diagnostic interface;
- a production diagnostic model, redaction pass, disclosure allowlist, generic
  response, or incident reference;
- a logger, metric, trace, event, telemetry hook, incident store, retry,
  cancellation, cleanup runner, or multi-failure type;
- a CLI command, executable, frontend, browser integration, JavaScript, copy
  control, JSON/problem document, localization, or alternate renderer;
- Kernel lifecycle behavior, operation-scope data, DI service definitions,
  Application Graph nodes, extension hooks, or agent capabilities;
- a new package or dependency;
- a Composer plugin, policy exception, PHPStan baseline, suppression, exclusion,
  or weakened quality gate;
- a release, version, license, tag, branch, remote, commit, or staging change; or
- any opportunistic cleanup, reformatting, renaming, or adjacent correction.

Do not modify the existing projector or renderer to add an authorization check.
That would misplace the boundary and violate the accepted pure API contracts.

Do not write example PHP classes or configuration snippets that accidentally
select a future API. Conceptual pseudocode may use only the generic permission
names from this directive and must be clearly labeled non-executable.

---

## 9. Execution gates

### Gate A - authority and caller identity

1. Read every authority in Section 2 completely.
2. Receive the Phase 1A directive byte count and SHA-256 independently from the
   architect.
3. Verify the local directive matches both values.
4. Verify `Phase_1A.md` is the exact zero-byte caller placeholder.
5. Verify all locked Phase 0Z identities.

Stop immediately on any mismatch.

### Gate B - repository and file baseline

Verify:

- branch, refs, HEAD, tree, parent, commit count, and index;
- exact 32/41/73 status after caller setup;
- exact modified and untracked path inventories;
- cumulative tracked diff 32 files, 907 insertions, 212 deletions;
- every Section 3.4 file identity;
- protected count and digest;
- package, PHP, test, fixer, and API-status counts;
- all protected and caller paths are ordinary files with exact case;
- no unexpected alternate stream, reparse point, or mode change exists;
- every non-empty text caller record and applicable protected text file has no
  BOM, NUL, CRLF, trailing whitespace, or missing/extra final LF; the required
  zero-byte report placeholder is intentionally exempt from final-LF checks;
  and
- ADR 0038 is absent.

Stop before mutation on any mismatch.

### Gate C - pre-change quality

Run exactly:

```text
composer quality
```

Expected result:

```text
security advisories     0
PHP CS Fixer findings   0 of 129
PHPStan errors          0
PHPUnit tests           241
PHPUnit assertions      4170
```

Accepted Composer warning categories are only the current missing-license,
authorized local unbounded development constraints, and the environment's
missing native curl support when Composer reports it.

If the unchanged security audit fails solely because the managed sandbox cannot
access Composer cache or the advisory network, rerun the identical
`composer quality` command once with approved access. Do not skip the audit,
change cache/policy settings, or run a weaker substitute.

Stop on a real quality failure or unexpected warning category.

### Gate D - documentation work

1. Create ADR 0038.
2. Modify exactly the fifteen authorized existing documents.
3. Do not populate the report yet.
4. Re-read every changed document completely.

### Gate E - focused validation

Use read-only validation to prove:

- exactly one non-caller architecture artifact was created: ADR 0038;
- exactly fifteen existing documentation paths changed during Phase 1A;
- ADR 0038 has the exact title, status, date, and section order;
- the truth table contains all four required outcome rows;
- both permission names and their independence are consistent everywhere;
- every changed current-state document says enforcement is unimplemented;
- no document calls the raw environment, request, client, API status, projector,
  or renderer an authorization mechanism;
- no document leaves the two-gate policy itself open;
- no document claims an HTTP adapter, configuration type, or served page exists;
- no new PHP FQCN, configuration key, environment variable, status code, header,
  route, CLI command, or dependency was selected;
- every local Markdown link resolves;
- every changed document is UTF-8 without BOM, LF-only, contains no NUL or
  trailing whitespace, and ends with exactly one LF;
- `git diff --check` passes;
- all protected identities and the aggregate digest remain exact;
- all Composer manifests, lockfile, source, tests, fixtures, and tooling remain
  byte-identical; and
- Git refs, history, index, modes, streams, case, and file types remain exact.

### Gate F - post-change quality

Run the identical:

```text
composer quality
```

It must still pass with exactly 241 tests and 4170 assertions, zero advisories,
zero PHPStan errors, and zero fixer findings among 129 files. Use the same single
approved-access retry rule only for an environment-blocked advisory check.

### Gate G - report and final proof

Only after Gates A through F pass:

1. populate `docs/task reports/Phase_1A.md` using Section 11;
2. validate its exact headings and final line;
3. verify it remains untracked, unstaged, and absent from HEAD;
4. verify final status is exactly 32 modified, 42 untracked, 74 total, zero
   staged;
5. verify no accepted prior status path disappeared or changed classification;
6. verify the only new non-caller path is ADR 0038;
7. report the actual cumulative diff statistic without pre-authorizing a target;
8. re-read the final report from disk; and
9. return the report's final byte count and SHA-256 outside its body.

Do not run quality again after report population. The report is documentation
evidence, not production or tooling input.

---

## 10. Strict acceptance criteria

Every item must pass:

1. All authorities were read completely before mutation.
2. The independently delivered Phase 1A directive identity matched.
3. The zero-byte report and every locked caller record matched.
4. Repository identity, histories, refs, index, status, inventories, hashes,
   modes, streams, case, and file types matched the locked baseline.
5. Pre-change quality passed with exactly 241 tests and 4170 assertions.
6. Exactly ADR 0038 was created as the sole new non-caller architecture
   artifact.
7. Exactly the fifteen authorized existing documentation paths were modified.
8. No executable, fixture, manifest, lock, dependency, tooling, or quality
   configuration changed.
9. ADR 0038 is Accepted, dated 2026-08-14, and has the exact required sections.
10. The two permissions are separate and both are mandatory.
11. The exact four-row fail-closed truth table is present and consistent.
12. Permission precedes presentation access, projection, copying, rendering, and
    delivery.
13. Existing eager exception context is distinguished from later presentation
    access.
14. Absence is disabled; invalid raw bootstrap configuration is rejected before
    serving; uncertainty never enables delivery.
15. Client-controlled values, raw environment labels, AI, heuristics, ambient
    state, and previous-operation state cannot authorize trusted output.
16. Bootstrap lifetime is immutable per future adapter instance and delivery
    decisions cannot leak across operations.
17. The projector and renderer remain unchanged low-level pure APIs that do not
    claim authorization.
18. Safe, Protected, forbidden-material, escaping, and production-separation
    doctrine remain exact.
19. Exact PHP/config/transport mechanisms remain open and are not invented.
20. HTTP, CLI, runtime, logging, telemetry, incident, production, extension,
    frontend, and agent capabilities remain unimplemented.
21. Documentation current/future statements and ADR open lists reconcile.
22. Links, UTF-8/LF text, whitespace, file safety, and `git diff --check` pass.
23. Protected count remains 140 with aggregate
    `82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E`.
24. Post-change quality passes unchanged at 241 tests and 4170 assertions.
25. Final Git state is exactly 32 modified, 42 untracked, 74 total, zero staged,
    with unchanged HEAD, refs, history, and index.
26. The completion report is structurally exact, evidence-backed, externally
    identified, and contains no assumption presented as verification.
27. No prohibited scope, Git mutation, policy weakening, or next-phase work
    occurred.

Any failed or unverified item makes status `Blocked` or `Partial`, never
`Complete`.

---

## 11. Completion report

Populate only after complete success, with exactly these headings:

```text
# PHASE 1A TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. ENVIRONMENT
## 11. COMMANDS RUN
## 12. VALIDATION RESULTS
## 13. DOCUMENTATION REVIEW
## 14. SECURITY AND FILE-SAFETY REVIEW
## 15. WARNINGS / KNOWN ISSUES
## 16. QUESTIONS / BLOCKERS
## 17. GIT DIFF SUMMARY
## 18. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must:

- enumerate the one created ADR and all fifteen modified documents;
- state that the executor authored no decision beyond this directive;
- report every environment version actually observed;
- report pre/post quality results and exact counts;
- report ADR 0038 byte count and SHA-256;
- report final protected count and aggregate;
- report source, test, manifest, lock, fixture, and tooling immutability;
- report link, text, file-safety, status, and cumulative diff evidence;
- explain the two permissions, evaluation order, and fail-closed outcomes;
- distinguish Accepted architecture from absent implementation;
- enumerate PASS/FAIL for all 27 acceptance criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not put the report's final byte count or SHA-256 inside its body. Return them
separately after final read-back.

---

## 12. Required final outcome

Phase 1A ends with one Accepted architecture decision and no new executable
capability.

Kongono will have a precise rule for the future trusted developer page: an
explicit immutable server-bootstrap permission and an independent adapter-owned
delivery permission must both be affirmative before presentation access,
projection, rendering, or delivery. Missing, invalid, ambiguous, failed, or
uncertain decisions remain fail closed.

The rule must not be presented as implemented enforcement. The projector and
renderer remain pure low-level Experimental APIs. No activation type, config
reader, HTTP adapter, served page, production surface, logger, telemetry path,
incident system, or runtime integration exists after this phase.

This documentation boundary prevents later work from creating a convenient but
unsafe request switch, environment heuristic, ambient debug mode, or cosmetic
capability token. It leaves exact implementation decisions for a separately
authorized phase.

Do not begin that phase.
