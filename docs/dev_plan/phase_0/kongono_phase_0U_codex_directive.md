# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0U

## Exact Dependency-Injection Diagnostic-Context Integration

## Mission

Implement the third and final bounded diagnostic-context slice for Kongono's
original deliberate-failure families: all 25 existing Dependency Injection
diagnostics across the existing 26 deliberate throw statements. Add the exact
one-way Dependency-Injection-to-Diagnostics package edge, give
`DependencyInjectionDiagnostic` the already-Accepted closed schemas, pass only
authorized bounded values at every existing throw site, and add
`DiagnosticContext` to the Public Dependency Injection exception marker and
Internal base.

Preserve every existing identifier, title, remediation, native message, zero
code, optional cause, diagnostic selection, validation priority, input-order
determinism, native failure propagation, explicit-definition contract,
transient-per-edge construction behavior, API status, and package-local
exception boundary.

Do not implement a renderer, developer error page, transport, sanitizer,
trusted-mode policy, logger, telemetry, retry, cleanup orchestration, container
scope, autowiring, PSR-11, Application Graph integration, Kernel lifecycle
integration, or another later capability.

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the executor, not the architect. Read completely before mutation:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `SECURITY.md`
4. `docs/doctrine.md`
5. `docs/documentation-standard.md`
6. `docs/adr/0023-explicit-typed-dependency-resolution.md`
7. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
8. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`
9. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
10. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`
11. `docs/adr/0030-application-graph-diagnostic-context-integration.md`
12. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
13. `docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md`
14. `docs/engineering/dependency-injection.md`
15. `docs/engineering/error-handling.md`
16. `docs/architecture/runtime-model.md`
17. `docs/dev_plan/kongono_phase_0T_codex_directive.md`
18. `docs/task reports/Phase_0T.md`
19. this Phase 0U directive
20. the zero-byte Phase 0U report

Current authority documents, Accepted ADRs, this directive, and locked caller
records are authoritative. Historical phase-scoped statements remain evidence,
not authority to restore superseded behavior.

## Mandatory stop rule

Stop without guessing, populating the report, staging, committing, or changing
an unlisted path if:

- the baseline, caller setup, or locked identity differs;
- the directive is absent or the Phase 0U report is not a zero-byte ordinary
  untracked file at execution start;
- the 25 catalog cases, 26 deliberate throw statements, messages, diagnostic
  assignments, validation priority, or deterministic definition ordering differ;
- a schema field, label, order, requirement, disclosure, operation, invariant,
  value token, precedence rule, signature, or constructor shape is unresolved;
- a context value cannot be derived from data already used by the current
  failure without new reflection, autoloading, traversal, state, or side effects;
- Composer proposes a third-party change or a local-package delta other than
  the exact Dependency Injection manifest/reference change authorized here;
- a test would require fabricated invalid readonly state, serialization,
  generated declarations, suppression, skipping, weakened assertions, or a
  behavior change;
- native autoloader or consumer-constructor failure would need catching or
  wrapping;
- any later architectural decision is required.

When blocked, keep the report zero bytes and return the exact mismatch, options,
tradeoffs, and recommendation outside it. Do not repair adjacent work.

# 2. LOCKED ACCEPTED PRE-PHASE-0U BASELINE

Before caller setup:

```text
branch                         main
local branches                 1
HEAD                           75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                      5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent                    5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                        5
tracked files                  214
remotes                        0
tags                           0
tracked modified paths         75
untracked paths                58
total status paths             133
staged paths                   0
tracked diff                   75 files, 2,161 insertions, 274 deletions
binary tracked-diff Git hash    23EC757356758126FB45E98784CC42665F009866
production declarations        40
test PHP files                 72
source plus test PHP files     112
syntax entries                 113 including .php-cs-fixer.dist.php
PHP CS Fixer Finder files       112
package directories            5
package manifests              5, plus root = 6
```

Closed inventory hashes are Git object hashes of the raw NUL-delimited command
output:

```text
git status --porcelain=v1 -z -uall | git hash-object --stdin
  E72D5CACD1C0789E57A3CED1E920025C4DDD4C0E

git diff --name-only -z | git hash-object --stdin
  0A2B40F807101FD9EA255A6B16A79208E614A2A5

git ls-files -o --exclude-standard -z | git hash-object --stdin
  43F614AE6247132BEF8D2CA58DB0E57662945EF7
```

Do not substitute sorted or newline-delimited input. The managed global-ignore
warning is accepted only when these closed inventories and explicit counts pass.

Accepted quality evidence, which must be rerun unchanged:

```text
PHP                             8.5.9
Composer                        2.10.2
Git                             2.45.1.windows.1
PHPUnit                         13.3.0
PHPStan                         2.2.8
PHP CS Fixer                    3.95.18
advisories                      0
platform                        pass
style                           0 of 112 fixable
analysis                        0 errors
tests                           194 tests, 2,521 assertions
```

A managed network/cache denial is not an audit pass. Retry only the identical
blocked Composer command with approved access.

# 3. LOCKED RECORDS AND CRITICAL INPUT IDENTITIES

Read-only caller evidence:

```text
docs/dev_plan/kongono_phase_0T_codex_directive.md
  40,907 bytes
  SHA-256 6B7AD245C5F33A10C93DE703767BB3C7F8E53D61AAE5D43A0D430938493E0430

docs/task reports/Phase_0T.md
  15,028 bytes
  SHA-256 023D21155F019CE347D57F93FFD7D02AEB9EAA6D6F2C113276454F8490DFE104
```

Critical pre-change files:

```text
composer.lock
  179,424 bytes
  8557D96C2A052145F858C2248DD3FC0C4F335CE1C13CD0653F1E6D58DF6052C5
packages/dependency-injection/composer.json
  409 bytes
  2B13601E02D5DA076E1E24E2224EE369FC2F11A5482F03B74624791BAFC7D893
packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
  7,451 bytes
  9B6413963986BD1FB962D8A1D7430B4B56886FAC34C1A9F56D2D9FD793E5D624
packages/dependency-injection/src/Exception/ExceptionInterface.php
  679 bytes
  66AECB16A5099ABF1FD2464512F7E49FA7C0BACCA5BBED75EC4C8DDF9D51FA82
packages/dependency-injection/src/Exception/DependencyInjectionException.php
  1,203 bytes
  740355BF7F0ED2A464F1670C9DB9353ECD2197AB4F252DEEA23A2029A60B6BB4
packages/dependency-injection/src/Definition/ServiceDefinition.php
  10,040 bytes
  9C6919A2F5F4FB4B0E36B3A5E311FC65E4472468623882275488CEA05A6101E4
packages/dependency-injection/src/DependencyResolver.php
  7,447 bytes
  DAF65DDBD67677DBE86D5FD470184E926D1B5E50DA1485693A5473FEEA507677
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
  25,598 bytes
  77682923E9F50DBA7675B2AC4926ACFBBA19A75B077ACF61EC455E8678FF6BE8
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
  18,154 bytes
  467CFB7C05049A3DB7C4ED59466029DF5E27C80C8198638F24C8325887DEB47E
```

The accepted DI lock reference is
`a4b4716a738bf1165235d567bd3c5a089b429c54`; root lock content hash is
`c1bc54e0fddbf2a65dc77fbbc0b3757b`.

# 4. CALLER SETUP CONTRACT

The caller copies this directive to:

```text
docs/dev_plan/kongono_phase_0U_codex_directive.md
```

and creates exactly one ordinary zero-byte report:

```text
docs/task reports/Phase_0U.md
```

Before executor mutation require exactly 75 tracked modified paths, 60
untracked paths, 135 total status paths, and zero staged paths. Relative to the
closed Section 2 inventory, the only additions must be those two caller records.
The directive must be nonzero; the report must be zero bytes. Both must be
untracked, unstaged, absent from HEAD, ordinary, and stream-safe.

# 5. AUTHORIZED FILE BOUNDARY

Create exactly:

```text
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php
```

Modify exactly these production, dependency, and test files:

```text
packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/composer.json
composer.lock
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
tests/Architecture/ApiStatusConventionTest.php
```

Modify exactly these 32 existing documentation files:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/dependency-injection.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/diagnostics/README.md
packages/kernel/README.md
```

Do not modify a concrete exception, fixture, tool configuration, root manifest,
Graph/Kernel/Diagnostics/API-Metadata production file, historical directive or
report, or Git metadata. Populate only caller-created `Phase_0U.md` after every
non-report acceptance gate passes.

# 6. PACKAGE DEPENDENCY AND LOCK CONTRACT

Add exactly `"kongono/diagnostics": "*"` to the DI package manifest, preserving
`kongono/api-metadata: *`, PHP `>=8.5`, and all other manifest semantics.

Run exactly:

```text
composer update kongono/dependency-injection --with-dependencies --no-interaction
```

Composer alone generates the lock. Authorized delta:

- DI gains only `kongono/diagnostics: *`;
- only the DI path reference may change from `a4b4716...`;
- root content hash remains `c1bc54e0fddbf2a65dc77fbbc0b3757b`;
- every other local path reference remains exact;
- all 66 third-party objects, versions, sources, distributions, checksums,
  requirement-related fields, and policies remain exact;
- zero third-party install, update, or removal occurs.

The graph becomes three independent semantic consumers:

```text
dependency-injection -> diagnostics -> api-metadata
dependency-injection ----------------> api-metadata
application-graph ----> diagnostics -> api-metadata
application-graph -------------------> api-metadata
kernel ---------------> diagnostics -> api-metadata
kernel --------------------------------> api-metadata
```

No reverse, Kernel, Graph, resolver, or universal-exception edge is added.

# 7. EXACT 25 CONTEXT SCHEMAS

The catalog retains exactly 25 cases, IDs, titles, and remediations and adds:

```php
public function contextSchema(): DiagnosticContextSchema
public function context(array $values): DiagnosticContext
```

Together with `title()` and `remediation()`, exactly four user-defined methods
exist. `context()` accepts `array<string, string|int|bool>` and constructs a
fresh context from a fresh schema. No cache or static state.

All fields are required unless marked optional; written order is exact.

| ID | Operation | Violated invariant | Ordered fields |
| --- | --- | --- | --- |
| `KGN-DI-1001` | Validate service contract | Contract must name an existing interface | `subject_role` / Subject role / Text / Safe; `contract_name` / Contract name / Text / Protected |
| `KGN-DI-1002` | Validate service contract | Contract must use its canonical declared name | `subject_role` / Subject role / Text / Safe; `contract_name` / Contract name / Text / Protected |
| `KGN-DI-1003` | Validate service contract | Resolver contract cannot enter the service graph | `subject_role` / Subject role / Text / Safe; `contract_name` / Contract name / Text / Protected |
| `KGN-DI-1004` | Validate implementation | Implementation must name an existing class | `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1005` | Validate implementation | Implementation must use its canonical declared name | `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1006` | Validate implementation | Implementation must be publicly instantiable | `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1007` | Validate implementation | Implementation must satisfy the declared contract | `contract_name` / Contract name / Text / Protected; `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1008` | Validate implementation | Implementation must not expose the resolver contract | `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1009` | Validate dependency list | Dependencies must be a PHP list | `entry_count` / Entry count / Integer / Safe; `first_unexpected_key` / First unexpected key / Text / Protected / optional |
| `KGN-DI-1010` | Validate dependency entry | Dependency entry must be a contract-name string | `dependency_position` / Dependency position / Integer / Safe; `actual_value_kind` / Actual value kind / Text / Safe |
| `KGN-DI-1011` | Validate constructor dependencies | Dependency declarations require a constructor | `implementation_name` / Implementation name / Text / Protected; `dependency_count` / Dependency count / Integer / Safe |
| `KGN-DI-1012` | Validate constructor | Constructor must be public | `implementation_name` / Implementation name / Text / Protected |
| `KGN-DI-1013` | Validate constructor arity | Constructor and definition arity must match exactly | `implementation_name` / Implementation name / Text / Protected; `expected_parameter_count` / Expected parameter count / Integer / Safe; `actual_parameter_count` / Actual parameter count / Integer / Safe |
| `KGN-DI-1014` | Validate constructor parameter mode | Parameter must be required, by-value, non-variadic, and non-defaulted | `implementation_name` / Implementation name / Text / Protected; `parameter_position` / Parameter position / Integer / Safe; `unsupported_mode` / Unsupported mode / Text / Safe |
| `KGN-DI-1015` | Validate constructor parameter type | Parameter must have one non-nullable interface type | `implementation_name` / Implementation name / Text / Protected; `parameter_position` / Parameter position / Integer / Safe; `actual_type_shape` / Actual type shape / Text / Safe |
| `KGN-DI-1016` | Validate constructor dependency type | Parameter type must equal the ordered dependency contract | `implementation_name` / Implementation name / Text / Protected; `parameter_position` / Parameter position / Integer / Safe; `expected_contract` / Expected contract / Text / Protected; `actual_contract` / Actual contract / Text / Protected |
| `KGN-DI-1017` | Validate definition list | Definitions must be a PHP list | `entry_count` / Entry count / Integer / Safe; `first_unexpected_key` / First unexpected key / Text / Protected / optional |
| `KGN-DI-1018` | Validate definition entry | Every definitions-list entry must be a ServiceDefinition | `definition_position` / Definition position / Integer / Safe; `actual_value_kind` / Actual value kind / Text / Safe |
| `KGN-DI-1019` | Validate definition uniqueness | Each service contract may have one definition | `contract_name` / Contract name / Text / Protected |
| `KGN-DI-1020` | Validate definition graph completeness | Every dependency contract must have a definition | `owner_contract` / Owner contract / Text / Protected; `missing_dependency` / Missing dependency / Text / Protected |
| `KGN-DI-1021` | Validate definition graph acyclicity | Definition graph must be acyclic | `cycle_contract` / Cycle contract / Text / Protected |
| `KGN-DI-1022` | Resolve service | Requested service must name an existing interface | `requested_contract` / Requested contract / Text / Protected |
| `KGN-DI-1023` | Resolve service | Requested contract must be canonical and not the resolver contract | `requested_contract` / Requested contract / Text / Protected; `rejection_reason` / Rejection reason / Text / Safe |
| `KGN-DI-1024` | Resolve service | Requested service must have a definition | `requested_contract` / Requested contract / Text / Protected |
| `KGN-DI-1025` | Construct service | Constructed object must satisfy the validated contract | `contract_name` / Contract name / Text / Protected; `implementation_name` / Implementation name / Text / Protected |

The two 1025 throw statements share one case and schema. Do not create a 26th.

# 8. EXACT VALUE ALGORITHMS

## 8.1 Subject role

Pass the role explicitly to contract validation:

```text
top-level definition contract    service_contract
each declared dependency         dependency_contract
```

Use it for 1001-1003; never derive it from label prose.

## 8.2 Non-list inputs

For 1009 and 1017 capture `entry_count = count($values)`. Iterate keys in
insertion order from expected integer zero and stop at the first strict mismatch.
Capture an integer mismatch as canonical base-10 text in optional
`first_unexpected_key`; omit the field for a string mismatch. Inspect no later
key and never capture the received key sequence.

Use private `firstUnexpectedListKey(array): ?string` helpers in
`ServiceDefinition` and `DependencyResolver`. Duplication is deliberate; create
no shared utility/type.

## 8.3 Native value-kind tokens

For 1010 and 1018 map `gettype()` exactly:

```text
NULL=null; boolean=boolean; integer=integer; double=float; string=string;
array=array; object=object; resource=resource;
resource (closed)=closed_resource; default=unknown
```

Do not use `get_debug_type()` for context or capture class/resource type names.

## 8.4 Unsupported parameter mode

Evaluate each existing predicate once and include every true token in fixed
order `optional`, `defaulted`, `variadic`, `by_reference`. Join with `+` and no
spaces. Examples: `optional+defaulted`, `optional+variadic`, `by_reference`.
Do not change the existing rejection condition or priority.

## 8.5 Actual type shape

For an existing 1015 failure use exact precedence:

```text
no type=missing
ReflectionUnionType=union
ReflectionIntersectionType=intersection
ReflectionNamedType allowing null=nullable
ReflectionNamedType builtin=builtin
defensive remainder=non_interface
```

Do not broaden the current 1015 condition. Existing `self` and `parent` fixtures
remain 1016 mismatches; do not add `interface_exists()` calls or reclassify them.

## 8.6 Requested-contract rejection

For 1023 preserve the current combined check. Use `noncanonical` when the
reflection declared name differs from the request; otherwise use
`resolver_contract`. Noncanonical has priority. Add no reflection/autoload step.

# 9. EXACT THROW-SITE MAPS

Every deliberate throw receives exactly one context-values argument before the
optional cause. Preserve native messages byte-for-byte.

| ID | Exact values |
| --- | --- |
| 1001-1003 | `subject_role`; exact input `contract_name` |
| 1004-1006, 1008 | exact input `implementation_name` |
| 1007 | validated `contract_name`; exact input `implementation_name` |
| 1009 | dependency `entry_count`; optional `first_unexpected_key` |
| 1010 | `dependency_position`; closed `actual_value_kind` |
| 1011 | `implementation_name`; exact `dependency_count` |
| 1012 | `implementation_name` |
| 1013 | `implementation_name`; `expected_parameter_count = count($dependencies)`; `actual_parameter_count = count($parameters)` |
| 1014 | `implementation_name`; `parameter_position`; `unsupported_mode` |
| 1015 | `implementation_name`; `parameter_position`; `actual_type_shape` |
| 1016 | `implementation_name`; `parameter_position`; `expected_contract = $dependencies[$position]`; `actual_contract = $type->getName()` |
| 1017 | definitions `entry_count`; optional `first_unexpected_key` |
| 1018 | `definition_position`; closed `actual_value_kind` |
| 1019 | deterministically selected duplicate `contract_name` |
| 1020 | deterministic `owner_contract`; exact `missing_dependency` |
| 1021 | deterministic detected `cycle_contract` |
| 1022 | exact `requested_contract` |
| 1023 | exact `requested_contract`; exact `rejection_reason` |
| 1024 | exact `requested_contract` |
| both 1025 sites | definition `contract_name`; definition `implementation_name` |

Compute a fact once when shared by condition, unchanged message, and context.
Context creation must add no traversal, sorting, autoloading, instantiation, or
reflection.

# 10. PUBLIC MARKER AND INTERNAL BASE

Add exactly to the Public marker:

```php
public function diagnosticContext(): DiagnosticContext;
```

It then declares exactly four methods and remains unrelated to Kernel, Graph,
and Diagnostics markers. It exposes no Internal catalog type.

The Internal base explicitly declares, in order:

```php
private readonly DependencyInjectionDiagnostic $diagnostic;
private readonly DiagnosticContext $diagnosticContext;
```

Do not promote properties. Exact constructor:

```php
final public function __construct(
    DependencyInjectionDiagnostic $diagnostic,
    string $message,
    array $contextValues,
    ?Throwable $previous = null,
)
```

Document `array<string, string|int|bool>`. Assign diagnostic, construct context
eagerly, then call `parent::__construct($message, 0, $previous)`. Do not catch
Diagnostics failure, create fallback, retain the array, or represent both
failures.

The base declares exactly five final public methods. Repeated context access
returns one object; separate exceptions share neither context nor schema.

# 11. PRESERVED BEHAVIOR AND FAILURE BOUNDARIES

Definitions/dependencies remain explicit canonical interfaces; implementations
remain named, canonical, concrete, publicly instantiable, contract-satisfying,
and unable to expose Kongono's resolver. Constructor signatures, deterministic
duplicate/missing/cycle selection, transient top-level and per-edge resolution,
and public resolver shapes remain exact.

Registered-autoloader failures propagate unchanged outside the DI marker.
Consumer constructor failures, including `DualResolverService`'s native
`RuntimeException`, propagate unchanged without DI context or wrapping.

The two 1025 defenses remain deliberately unreachable after valid immutable
definitions. Keep both guards and maps. Prove them by source/static inspection;
do not forge state, serialize an invalid definition, or add a backdoor.

# 12. TEST CONTRACT

Preserve all 17 public methods in `ServiceDefinitionTest` and all 17 in
`DependencyResolverTest`; add assertions without renaming/removing/weakening.

Create `DependencyInjectionDiagnosticContextTest` with exactly ten public tests:

```text
testCatalogMetadataAndContextSchemasAreExact
testCatalogDeclaresOnlyAuthorizedMethodsAndConstructsCompleteSamples
testServiceDefinitionFailureContextMapsAreExact
testResolverDefinitionGraphFailureContextMapsAreExact
testRequestedServiceFailureContextMapsAreExact
testClosedSafeTokenAlgorithmsAndListKeyOmissionAreExact
testManualExceptionPreservesNativeAndDiagnosticBehaviorWithSameContextIdentity
testDependencyInjectionMarkerAndBaseReflectionShapesAreExactAndPackageLocal
testContextValuesAndReturnedArraysAreDetachedAndExceptionsDoNotShareContext
testNativeFailuresRemainNativeAndPublicMarkerDoesNotExposeInternalCatalog
```

Do not use a PHPUnit data provider in this new class. Each named method is
exactly one PHPUnit test case and may use internal loops/tables for exhaustive
coverage. This is what makes the final 194 + 10 = 204 count exact.

Prove all schemas/maps, roles, integer/string mismatch cases, kind tokens, mode
combinations, type-shape precedence, rejection priority, optional omission,
Protected/Safe classification, marker/base shape, identity/detachment, unchanged
messages/codes/causes/priorities/order/resolution, native failure propagation,
and both 1025 source maps without state fabrication.

Update the root architecture test for the DI marker/base, three independent
semantic dependencies on Diagnostics, and four-way marker separation. Production
inventory remains exactly 40 declarations/statuses.

Final inventories:

```text
production declarations/files   40
test PHP files                   73
source plus test PHP files       113
syntax entries                   114 including .php-cs-fixer.dist.php
PHP CS Fixer Finder files        113
PHPUnit tests                    exactly 204
```

Report actual assertions; do not pad to a target.

# 13. ADR 0033

Create ADR 0033 with exactly these second-level headings:

```text
Status
Date
Context
Decision
Rationale
Consequences
Compatibility impact
Security considerations
Performance implications
Rejected alternatives
What remains open
Related decisions
```

Use `Accepted`, date `2026-08-14`. Record the dependency, 25 schemas/26 maps,
algorithms, marker/base changes, eager detached ownership, native failure
propagation, two defensive sites, unchanged resolver behavior, and pre-alpha
compatibility impact.

Reject universal markers, arbitrary maps, inferred reflection metadata, raw
values, new diagnostic splits, locator injection, invalid-state fabrication,
lazy/nullable/fallback context, swallowed construction failure, rendering,
transport, logging, telemetry, scopes, autowiring, and PSR-11.

Open work is simultaneous context-construction failure representation;
disclosure/trusted-mode policy; rendering/transports; serialization,
localization, source, stack, docs providers; logging/telemetry/incidents;
retry/cancellation/cleanup/recovery; and later capabilities. DI context is not
left open.

# 14. DOCUMENTATION RECONCILIATION

Exactly the listed documents must state current reality:

- all 42 original Kernel/Graph/DI failures now own bounded contexts: 6/11/25;
- the two DI invariant guards share schema 1025;
- Diagnostics' own 11 construction failures remain context-free;
- Kernel, Graph, and DI independently require Diagnostics and keep unrelated
  package-local catch markers;
- only those three semantic markers expose context;
- DI fields, omission, and tokens are current;
- Protected names are not production disclosure authorization; Safe also needs
  later policy;
- native autoloader and consumer-constructor failures remain outside DI;
- no renderer, transport, sanitizer, disclosure policy, logging, telemetry,
  scope, autowiring, or PSR-11 exists;
- counts are 40/73/113/114 and style scope is 113.

Historical ADR prose may remain only when explicitly phase-scoped. Remove stale
current/open claims that DI integration remains future. Preserve prior Graph and
Kernel decisions. Add ADR 0033 to the index/relevant links. Keep conceptual error
pages explicitly future and preserve all local links.

# 15. SECURITY, LIFETIME, AND PERFORMANCE

Context is synchronous at the existing failure point and exception-owned. It
enters no definition, resolver state, constructed service, static/global store,
cache, Kernel scope, or Graph.

Safe values are bounded counts, positions, and closed tokens. Protected values
are exact already-available contract/implementation names. Neither classification
authorizes disclosure. Use existing bounded byte representation; do not redact,
hash, classify, normalize, parse, or discover more data.

Never capture objects, instances, constructor values, messages, stacks, files,
source, credentials, secrets, requests, sessions, authorization, or environment.
Successful paths construct no schema/context and retain current complexity.

# 16. PROHIBITED SCOPE

Do not add IDs, cases, exceptions, messages, statuses, fields, kinds,
disclosures, priority changes, Diagnostics context, universal marker, renderer,
error page, transport, sanitizer, trusted mode, disclosure policy, localization,
source/stack/docs provider, serialization, logging, telemetry, AI classification,
autowiring, aliases, factories, providers, lazy/singleton/scoped services,
PSR-11, locator access, bindings, compilation, proxies, decorators, tags,
discovery, Kernel/Graph integration, runtime adapters, cleanup/cancellation/retry,
fixture changes, invalid-state fabrication, test backdoors, later framework
capabilities, third-party changes, suppression/baseline/skip/exclusion, or any
stage/commit/ref/history/Git-configuration mutation.

# 17. IMPLEMENTATION SEQUENCE

1. Read authority and caller records.
2. Verify baseline, hashes, modes, streams, inventories, and caller setup.
3. Run unchanged pre-change `composer quality`; retry only network denial.
4. Inspect all 25 cases, 26 throws, fixtures, tests, precedents, contracts, lock,
   and documents.
5. Create ADR/test, add dependency, run exact scoped Composer update.
6. Stop on unauthorized lock delta.
7. Implement schemas, marker/base, helpers, and maps.
8. Update tests and exactly 32 documents.
9. Run focused, structural, security, link, and canonical quality gates.
10. Verify final Git/file safety.
11. Populate only `Phase_0U.md`, verify identity, return size/hash outside it.
12. Stop unstaged and await review.

# 18. REQUIRED VALIDATION

At minimum:

```text
php -l packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
php -l packages/dependency-injection/src/Exception/ExceptionInterface.php
php -l packages/dependency-injection/src/Exception/DependencyInjectionException.php
php -l packages/dependency-injection/src/Definition/ServiceDefinition.php
php -l packages/dependency-injection/src/DependencyResolver.php
php -l packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php
composer --working-dir=packages/dependency-injection validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/dependency-injection/tests
vendor/bin/phpunit --configuration phpunit.xml.dist packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer analyse
composer cs
composer quality
```

Prove 25 schemas, 26 throws, exact metadata/maps/algorithms, unchanged assignment
and behavior, marker/base shapes, 42 integrated original schemas, context-free
Diagnostics failures, package graph/lock delta, 40/73/113/114 inventories, 53
IDs, 40 statuses, native propagation, defensive guards, no forbidden capture,
and exact authorized paths.

Canonical quality requires valid root/five manifests, zero advisories, platform
pass, 0/113 fixable, PHPStan zero errors, and exactly 204 tests with no warnings,
risky, deprecation, notice, incomplete, empty-suite, or output failure.

Verify all local links; strict UTF-8 no BOM; LF and one final LF; no NUL/trailing
whitespace; ordinary non-reparse files; default stream only; no mode change;
nonzero implementation files; and `git diff --check`.

Final Git state:

```text
branch                    main
HEAD                      75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent               5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                   5
local branches            1
remotes                   0
tags                      0
tracked modified paths    77
untracked paths           62
total status paths        139
staged paths              0
```

The two added tracked modifications are the existing DI unit tests. The four
added untracked paths are the Phase 0U directive/report, ADR 0033, and focused
test. Report actual diff stat and binary Git hash.

# 19. ACCEPTANCE CHECKLIST

- [ ] Baseline, hashes, caller setup, history, refs, and index exact.
- [ ] Changed paths exactly match Section 5 plus report population.
- [ ] DI gains only the Diagnostics edge and authorized lock delta.
- [ ] 25 exact schemas and 26 exact maps are implemented.
- [ ] Token algorithms and diagnostic priority are exact.
- [ ] Marker/base shapes and context ownership are exact.
- [ ] Messages, IDs, statuses, validation/resolution remain unchanged.
- [ ] Native failures remain native and unwrapped.
- [ ] All 42 original diagnostics are integrated independently.
- [ ] Diagnostics construction failures remain context-free.
- [ ] 40 declarations, 73 test files, 113 style, 114 syntax entries.
- [ ] Exactly 204 tests and every quality gate passes.
- [ ] ADR 0033 and 32 documents are reconciled.
- [ ] Links, encoding, streams, modes, scope, and Git state pass.
- [ ] No prohibited capability is introduced.
- [ ] Report is populated only after success and remains untracked/unstaged.

Every item must pass. Partial success is not acceptance.

# 20. REQUIRED EXECUTOR COMPLETION REPORT

Populate `docs/task reports/Phase_0U.md` only after non-report success with
exactly these second-level headings:

```text
1. STATUS
2. SUMMARY
3. BASELINE VERIFIED
4. FILES CREATED
5. FILES MODIFIED
6. ARCHITECTURAL DECISIONS
7. DEVIATIONS FROM DIRECTIVE
8. DEPENDENCIES ADDED/REMOVED
9. ENVIRONMENT
10. COMMANDS RUN
11. VALIDATION RESULTS
12. DOCUMENTATION REVIEW
13. SECURITY AND FILE-SAFETY REVIEW
14. WARNINGS / KNOWN ISSUES
15. QUESTIONS / BLOCKERS
16. GIT DIFF SUMMARY
17. ACCEPTANCE CRITERIA
18. NEXT STEP
```

Final line exactly:

```text
Await architect review before Phase 0V.
```

Report verified facts, retries, lock reference/hash, assertions, Git state,
warnings, and each criterion. On a stop condition leave it zero bytes. After
success return report byte count and SHA-256 outside it. Do not stage, commit,
or begin Phase 0V.

# 21. FINAL OUTCOME

Phase 0U is complete only when DI independently owns exact bounded context for
all 25 diagnostics across 26 deliberate throws; all 42 original semantic
diagnostics are integrated without a universal boundary; Diagnostics failures
remain context-free; native failures and resolver behavior remain unchanged;
all quality, documentation, security, file-safety, and Git gates pass; and the
repository remains entirely unstaged for architect review.
