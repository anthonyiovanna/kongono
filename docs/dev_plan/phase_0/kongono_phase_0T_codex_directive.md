# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0T

## Exact Kernel Lifecycle Diagnostic-Context Integration

## Mission

Implement the second bounded diagnostic-context vertical slice: all six existing
Kernel lifecycle failures. Add the one-way Kernel-to-Diagnostics package edge
made possible by Phase 0S, give LifecycleDiagnostic the six already-Accepted
closed schemas, pass exact Safe values at every existing Kernel throw site, add
DiagnosticContext to the Public Kernel marker and Internal base, and verify eager,
immutable, detached, same-object context ownership.

Preserve every existing diagnostic identifier, title, remediation, message, zero
code, cause, failure priority, validation timing, lifecycle transition, operation
identity rule, state-mutation guarantee, API status, exception boundary, and
persistent-runtime doctrine.

This phase does not integrate Dependency Injection's 25 failures and does not add
a renderer, error page, transport, sanitizer, logger, telemetry, retry, cleanup
orchestration, scope metadata, new Kernel failure, or other later capability.

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the executor, not the architect. Read completely before mutation:

1. AGENTS.md
2. ARCHITECTURE.md
3. SECURITY.md
4. docs/doctrine.md
5. docs/documentation-standard.md
6. docs/adr/0004-microkernel-capability-architecture.md
7. docs/adr/0020-kernel-operation-lifecycle-boundary.md
8. docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
9. docs/adr/0027-package-local-runtime-diagnostic-metadata.md
10. docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
11. docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
12. docs/adr/0030-application-graph-diagnostic-context-integration.md
13. docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
14. docs/engineering/error-handling.md
15. docs/architecture/runtime-model.md
16. docs/dev_plan/kongono_phase_0S_codex_directive.md
17. docs/task reports/Phase_0S.md
18. docs/dev_plan/kongono_phase_0S_1_codex_directive_correction.md
19. docs/task reports/Phase_0S_1_correction.md
20. this Phase 0T directive
21. the zero-byte Phase 0T report

Current authority documents, Accepted ADRs, this directive, and locked caller
records are authoritative. Historical statements remain evidence, not authority
to restore superseded behavior.

## Mandatory stop rule

Stop without guessing, populating the report, staging, committing, or changing an
unlisted path if:

- the baseline or a locked identity differs;
- the directive is absent or the report is not a zero-byte ordinary untracked
  file at execution start;
- the current six diagnostics, six throw sites, Graph precedent, or Diagnostics
  value contracts differ materially;
- any schema field, value token, label, order, disclosure, requirement, operation,
  invariant, signature, or constructor shape is not fully resolved here;
- Composer proposes any third-party or non-Kernel local-package change;
- a context value cannot be captured from already-available bounded state before
  mutation;
- context integration would require adding owner metadata to OperationScope or
  distinguishing inactive, ended, and foreign scope conditions;
- a test requires suppression, skipping, generated declarations, weakened
  assertions, or a behavior change;
- any later architectural decision is required.

When blocked, keep the report zero bytes and return the exact mismatch, options,
tradeoffs, and recommendation outside it. Do not repair adjacent work.

# 2. LOCKED ACCEPTED PRE-PHASE-0T BASELINE

Before caller setup:

~~~text
branch                         main
local branches                 1
HEAD                           75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                      5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent                    5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                        5
tracked files                  214
remotes                        0
tags                           0
tracked modified paths         72
untracked paths                54
total status paths             126
staged paths                   0
tracked diff                   72 files, 1,863 insertions, 263 deletions
binary tracked-diff Git hash    C1F085B66D4600156438EF9DD0F980679CDCEAC3
production declarations        40
test PHP files                 71
source plus test PHP files     111
syntax entries                 112 including .php-cs-fixer.dist.php
PHP CS Fixer Finder files       111
package directories            5
package manifests              5, plus root = 6
~~~

Accepted quality evidence, which must be rerun:

~~~text
PHP                             8.5.9
Composer                        2.10.2
Git                             2.45.1.windows.1
PHPUnit                         13.3.0
PHPStan                         2.2.8
PHP CS Fixer                    3.95.18
advisories                      0
platform                        pass
style                           0 of 111 fixable
analysis                        0 errors
tests                           187 tests, 2,324 assertions
~~~

The managed Git global-ignore warning is accepted only with independent closed
inventory proof. A managed network block is not an audit pass.

## 2.1 Exact 72 tracked status paths

~~~text
.php-cs-fixer.dist.php
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/0021-machine-checkable-api-status.md
docs/adr/0022-immutable-application-graph-structural-snapshot.md
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/application-graph-structure.md
docs/engineering/dependency-injection.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
packages/application-graph/README.md
packages/application-graph/composer.json
packages/application-graph/src/Diagnostic/GraphDiagnostic.php
packages/application-graph/src/Exception/ExceptionInterface.php
packages/application-graph/src/Exception/GraphException.php
packages/application-graph/src/Exception/InvalidGraphException.php
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/src/Model/Node.php
packages/application-graph/src/Model/NodeKey.php
packages/application-graph/src/Model/NodeKind.php
packages/application-graph/src/Model/Relation.php
packages/application-graph/src/Model/RelationKind.php
packages/application-graph/tests/Unit/ApplicationGraphTest.php
packages/application-graph/tests/Unit/StructuralValueTest.php
packages/dependency-injection/README.md
packages/dependency-injection/composer.json
packages/dependency-injection/src/Contract/ResolverInterface.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/src/Exception/InvalidDefinitionException.php
packages/dependency-injection/src/Exception/UnknownServiceException.php
packages/kernel/README.md
packages/kernel/composer.json
packages/kernel/src/Api/Api.php
packages/kernel/src/Api/ApiStatus.php
packages/kernel/src/Contract/KernelInterface.php
packages/kernel/src/Contract/KernelLifecycleInterface.php
packages/kernel/src/Contract/OperationLifecycleInterface.php
packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
packages/kernel/src/Exception/ExceptionInterface.php
packages/kernel/src/Exception/KernelException.php
packages/kernel/src/Exception/LifecycleException.php
packages/kernel/src/Kernel.php
packages/kernel/src/Lifecycle/KernelState.php
packages/kernel/src/Lifecycle/OperationScope.php
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
~~~

The two packages/kernel/src/Api paths are authorized deletions. Every other path
above is modified.

## 2.2 Exact 54 untracked paths

~~~text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0P_codex_directive.md
docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_codex_directive.md
docs/dev_plan/kongono_phase_0R_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0R_codex_directive.md
docs/dev_plan/kongono_phase_0S_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0S_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
docs/task reports/Phase_0O_1_correction.md
docs/task reports/Phase_0O_2_correction.md
docs/task reports/Phase_0P.md
docs/task reports/Phase_0P_1_correction.md
docs/task reports/Phase_0Q.md
docs/task reports/Phase_0Q_1_correction.md
docs/task reports/Phase_0Q_2_correction.md
docs/task reports/Phase_0R.md
docs/task reports/Phase_0R_1_correction.md
docs/task reports/Phase_0S.md
docs/task reports/Phase_0S_1_correction.md
packages/api-metadata/README.md
packages/api-metadata/composer.json
packages/api-metadata/src/Api.php
packages/api-metadata/src/ApiStatus.php
packages/api-metadata/tests/Unit/ApiMetadataTest.php
packages/application-graph/tests/Unit/GraphDiagnosticContextTest.php
packages/diagnostics/README.md
packages/diagnostics/composer.json
packages/diagnostics/src/Context/DiagnosticContext.php
packages/diagnostics/src/Context/DiagnosticContextSchema.php
packages/diagnostics/src/Context/DiagnosticDisclosure.php
packages/diagnostics/src/Context/DiagnosticField.php
packages/diagnostics/src/Context/DiagnosticFieldSchema.php
packages/diagnostics/src/Context/DiagnosticValueKind.php
packages/diagnostics/src/Diagnostic/DiagnosticsDiagnostic.php
packages/diagnostics/src/Exception/DiagnosticsException.php
packages/diagnostics/src/Exception/ExceptionInterface.php
packages/diagnostics/src/Exception/InvalidDiagnosticValueException.php
packages/diagnostics/tests/Unit/DiagnosticContextSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticContextTest.php
packages/diagnostics/tests/Unit/DiagnosticDisclosureTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldTest.php
packages/diagnostics/tests/Unit/DiagnosticValueKindTest.php
packages/diagnostics/tests/Unit/StructuralValueTest.php
~~~

# 3. LOCKED CONTENT IDENTITIES

~~~text
docs/dev_plan/kongono_phase_0S_codex_directive.md
  39,679 bytes
  SHA-256 3F95ED47B136315619462744D46AEFFC099FA5FFBEBA49BF7427F23219F71092

docs/task reports/Phase_0S.md
  18,032 bytes
  SHA-256 C1769308F457575BAD4946AB011D6ACF24E23E20A56A0F798E09CC895568D998

docs/dev_plan/kongono_phase_0S_1_codex_directive_correction.md
  15,096 bytes
  SHA-256 2A63126609D88AC06A36FA3988AC25FDFF1F9159AE9277E5D2BAF2E413B879C1

docs/task reports/Phase_0S_1_correction.md
  10,404 bytes
  SHA-256 2325986DC8AB4168C541ADE5E545539A66FC60526C86CC0DF124550198E3E90A

docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
  5,768 bytes
  SHA-256 E8844507C5A2FA6CC860F81DBAEB178C46AE084933FB882B372F2F0716F18511

docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
  8,169 bytes
  SHA-256 12B60DE40CC0962E3B37ABADD9E3E0E774E5ACB2DBB904CA0E99F447AAD099D4

packages/kernel/composer.json
  367 bytes
  SHA-256 FE4A3CB50D0E6E8DDBD9C9B905074AB1A10827B416FDD384CDDE914DB51B99B3

composer.lock
  179,380 bytes
  SHA-256 22979E7FCFB74EFDF5A48465CF1D8E28B33A1376D8CA659ECF42FE153DF91EB8

packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
  2,228 bytes
  SHA-256 2CD4F9385F8278106DE6DF4296655CBFB025ED7D56A386A3F27EBFAE60E8BF76

packages/kernel/src/Exception/ExceptionInterface.php
  604 bytes
  SHA-256 097FB62E9A0E45A3419E34DF52DB3A12659CBD0E85BD23435A7704B51CD25C63

packages/kernel/src/Exception/KernelException.php
  1,154 bytes
  SHA-256 978D457B80FA0B680450DC7C65F5A8D37B15860C51582A1696D738514FFAB099

packages/kernel/src/Kernel.php
  3,972 bytes
  SHA-256 CE1D51BCB988374C857E8C3C543D07FACF1E94C53B32AE5974FEDAB90828758C

packages/kernel/tests/Unit/KernelTest.php
  14,051 bytes
  SHA-256 872E039C1234DE0DCD54D57F24FCFBC33D7D575BC6C285EE4D6C4D2CB40C477F

tests/Architecture/ApiStatusConventionTest.php
  22,750 bytes
  SHA-256 747BBC7761D258968984A70B598E8013395EDAF5A10275E25EAB12E6CAA711D9

docs/engineering/error-handling.md
  51,352 bytes
  SHA-256 E4019B5404C1A50A1427FCC48E591CBEEC6632EC3F6169FC58939B4905AE9991
~~~

# 4. CALLER SETUP

The caller adds exactly:

~~~text
docs/dev_plan/kongono_phase_0T_codex_directive.md
docs/task reports/Phase_0T.md
~~~

The directive must be populated, ordinary, untracked, unstaged, absent from HEAD,
and byte-identical to the delivered artifact. The report must be a zero-byte
ordinary untracked file. After setup only:

~~~text
tracked modified paths    72
untracked paths           56
total status paths        128
staged paths              0
~~~

Record directive size/hash. Never edit it. Populate the report only after success.

# 5. EXACT ARCHITECTURE AND DEPENDENCY DECISION

## 5.1 Implemented package graph

Add exactly one direct production dependency:

~~~text
kongono/kernel -> kongono/diagnostics: *
~~~

Kernel retains kongono/api-metadata: * and php >=8.5. Diagnostics retains only
API Metadata and PHP. The graph remains acyclic:

~~~text
kernel -> diagnostics -> api-metadata
kernel ----------------> api-metadata

application-graph -> diagnostics -> api-metadata
application-graph ----------------> api-metadata

dependency-injection -> api-metadata
~~~

No other edge changes. This dependency exists only so Kernel can construct and
expose bounded presentation-neutral diagnostic values. It creates no Diagnostics
service, Kernel lifecycle binding, shared exception marker, renderer, or ambient
integration.

## 5.2 Public marker change

Kongono\Kernel\Exception\ExceptionInterface adds exactly:

~~~php
public function diagnosticContext(): DiagnosticContext;
~~~

using Kongono\Diagnostics\Context\DiagnosticContext. Existing three string
methods and Throwable inheritance remain exact. Kernel and Graph markers now each
have four accessors; DI and Diagnostics markers retain three. All four markers
remain pairwise unrelated.

This is an intentional pre-alpha Public signature addition. DiagnosticContext is
already Public. The Internal LifecycleDiagnostic type must not appear in the
Public marker.

## 5.3 Internal base change

KernelException mirrors the accepted Graph pattern:

- exactly two declared private readonly instance properties named diagnostic and
  diagnosticContext, in that order;
- no static property;
- final public constructor parameters exactly:

~~~text
LifecycleDiagnostic diagnostic
string message
array contextValues
?Throwable previous = null
~~~

- contextValues PHPDoc exactly permits array<string, string|int|bool>;
- construct context eagerly before calling the native parent constructor;
- retain only the constructed DiagnosticContext, never the input array;
- expose final public diagnosticContext() returning the same object by identity;
- retain exact ID, title, remediation, message, zero code, and cause behavior;
- do not catch, wrap, suppress, replace, or fall back when Diagnostics context
  construction fails.

An InvalidDiagnosticValueException from a manually invalid context is a visible
internal integration defect and remains in the separate Diagnostics boundary. No
sanctioned production throw may produce it.

# 6. EXACT SIX KERNEL SCHEMAS

LifecycleDiagnostic adds only two public methods:

~~~php
public function contextSchema(): DiagnosticContextSchema;

public function context(array $values): DiagnosticContext;
~~~

The context method has array<string, string|int|bool> PHPDoc and constructs
DiagnosticContext from the case's schema. Existing enum cases, values, titles,
remediation, order, API status, and backed-enum behavior remain unchanged.

All fields are required, Safe, and in written order. Labels are exact.

## 6.1 KGN-KERNEL-1001 BootWhenNotUnbooted

~~~text
operation:  Boot Kernel
invariant:  Boot requires Unbooted
fields:
  actual_state    label Actual state    Text    Safe    required
  expected_state  label Expected state  Text    Safe    required
~~~

## 6.2 KGN-KERNEL-1002 ShutdownWhenNotBooted

~~~text
operation:  Shut down Kernel
invariant:  Shutdown requires Booted
fields:
  actual_state    label Actual state    Text    Safe    required
  expected_state  label Expected state  Text    Safe    required
~~~

## 6.3 KGN-KERNEL-1003 ShutdownWithActiveOperations

~~~text
operation:  Shut down Kernel
invariant:  Shutdown requires zero active operations
fields:
  active_operation_count           label Active operation count           Integer  Safe  required
  expected_active_operation_count  label Expected active operation count  Integer  Safe  required
~~~

## 6.4 KGN-KERNEL-1004 BeginOperationWhenNotBooted

~~~text
operation:  Begin operation
invariant:  Beginning work requires Booted
fields:
  actual_state    label Actual state    Text    Safe    required
  expected_state  label Expected state  Text    Safe    required
~~~

## 6.5 KGN-KERNEL-1005 EndOperationWhenNotBooted

~~~text
operation:  End operation
invariant:  Ending a scope requires Booted
fields:
  actual_state    label Actual state    Text    Safe    required
  expected_state  label Expected state  Text    Safe    required
~~~

## 6.6 KGN-KERNEL-1006 InactiveOrForeignOperationScope

~~~text
operation:  End operation
invariant:  Scope must be active and owned by this Kernel
fields:
  scope_condition  label Scope condition  Text  Safe  required
~~~

Do not add optional or Protected fields. Do not add a schema cache or static
instances. Each schema/context is an eager immutable value.

# 7. EXACT THROW-SITE VALUES

Modify only the six existing throw sites in Kernel.php. Values are constructed
from state already available at the validation point, before any mutation.

## 7.1 Exact maps

~~~text
BootWhenNotUnbooted:
  actual_state   = current KernelState case name
  expected_state = Unbooted

ShutdownWhenNotBooted:
  actual_state   = current KernelState case name
  expected_state = Booted

ShutdownWithActiveOperations:
  active_operation_count          = exact current SplObjectStorage count
  expected_active_operation_count = 0

BeginOperationWhenNotBooted:
  actual_state   = current KernelState case name
  expected_state = Booted

EndOperationWhenNotBooted:
  actual_state   = current KernelState case name
  expected_state = Booted

InactiveOrForeignOperationScope:
  scope_condition = inactive_or_foreign
~~~

State values are exact closed case-name strings: Unbooted, Booted, or Shutdown.
Expected values use KernelState case names, not duplicated free text.

Read the active-operation count once into a local integer for the shutdown
validation, message, and context map so all three observe the same value. This
refactor must not change the condition, message, complexity, or state.

The current Kernel intentionally cannot and must not distinguish an already-ended
scope from a foreign scope. Always use the accepted conservative closed token
inactive_or_foreign. Do not add owner identity, ended-scope retention, scope
properties, a registry, weak map, ID, hash, class name, or object data.

## 7.2 Unchanged behavior

Context observes a deliberate failure only. A failed operation leaves all valid
Kernel state and active-operation ownership unchanged. No context enters Kernel
state, OperationScope, SplObjectStorage, a static/global location, cache, logger,
renderer, transport, or Graph.

# 8. EXACT FILE-CHANGE BOUNDARY

## 8.1 Create exactly two implementation files

~~~text
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
packages/kernel/tests/Unit/KernelDiagnosticContextTest.php
~~~

## 8.2 Modify exactly four production files

~~~text
packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
packages/kernel/src/Exception/ExceptionInterface.php
packages/kernel/src/Exception/KernelException.php
packages/kernel/src/Kernel.php
~~~

Do not modify LifecycleException, OperationScope, KernelState, lifecycle
interfaces, API Metadata, Diagnostics values, Graph, or DI production source.

## 8.3 Modify exactly two dependency files

~~~text
packages/kernel/composer.json
composer.lock
~~~

## 8.4 Modify exactly two existing tests

~~~text
packages/kernel/tests/Unit/KernelTest.php
tests/Architecture/ApiStatusConventionTest.php
~~~

## 8.5 Modify exactly 29 existing documentation files

~~~text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/glossary.md
packages/application-graph/README.md
packages/diagnostics/README.md
packages/kernel/README.md
~~~

## 8.6 Preserve every other path

No other file may change. Do not modify root composer.json, any tool config,
another manifest, Graph/Diagnostics/DI/API Metadata source or tests, fixtures,
historical directives/reports, or Git metadata.

The caller report may be populated only after complete success and is excluded
from the zero-byte implementation-file ban.

# 9. COMPOSER AND LOCK CONTRACT

Kernel's final require map is exactly:

~~~text
kongono/api-metadata   *
kongono/diagnostics    *
php                    >=8.5
~~~

Retain every other Kernel manifest field. Do not add a direct Graph or DI edge.

Run exactly:

~~~text
composer update kongono/kernel --with-dependencies --no-interaction
~~~

If managed network access blocks it, retry the identical command once with
existing approved access. Do not edit the lock manually or change flags.

Authorized lock delta:

- Kernel local path entry gains kongono/diagnostics: *;
- Kernel path reference changes mechanically;
- no root content-hash change;
- no other local package requirement/reference change;
- no third-party package, version, source, dist, checksum, policy, plugin, or
  platform change.

Stop on anything else. Accepted warnings remain limited to license, local
unbounded constraints, root local development constraints, and missing-curl
performance. A blocked audit is not a pass.

# 10. EXACT TEST CONTRACT

## 10.1 Existing KernelTest

Retain its existing 17 public test methods and all lifecycle behavior assertions.
Update construction and the existing exact-diagnostic helper for the new context
constructor argument and exact context maps. Do not add or remove a test method.

Every current lifecycle variant remains covered, including repeated boot,
repeated shutdown, before/after boot, active shutdown, foreign scope, repeated
end, and end after shutdown. Context assertions must not weaken state-preservation
or message assertions.

## 10.2 New KernelDiagnosticContextTest

Create one final class
Kongono\Kernel\Tests\Unit\KernelDiagnosticContextTest with exactly seven public
test methods:

~~~text
testCatalogMetadataAndContextSchemasAreExact
testCatalogDeclaresOnlyAuthorizedMethodsAndConstructsCompleteSamples
testClosedSchemasRejectInvalidValuesThroughDiagnosticsBoundary
testManualExceptionPreservesNativeAndDiagnosticBehaviorWithSameContextIdentity
testKernelMarkerAndBaseReflectionShapesAreExactAndPackageLocal
testContextValuesAndReturnedArraysAreDetachedAndExceptionsDoNotShareContext
testPublicMarkerDoesNotExposeInternalCatalogType
~~~

Prove:

- all six schema operations, invariants, fields, labels, kinds, disclosures,
  required flags, and order are exact;
- LifecycleDiagnostic declares exactly context, contextSchema, remediation, and
  title as user-defined methods;
- complete samples construct; missing, extra, and wrong-native-kind maps
  propagate the Diagnostics failure boundary;
- manual valid LifecycleException preserves message/code/cause and returns the
  same context object repeatedly;
- invalid manual context propagates the Diagnostics marker and not Kernel marker;
- Kernel marker has exactly four methods with Public DiagnosticContext return;
- Kernel base has exactly two private readonly nonstatic properties, five final
  public methods, and the exact four-parameter constructor;
- marker/base source imports Diagnostics only for public context values and do
  not expose Internal Diagnostics or LifecycleDiagnostic in the Public marker;
- arrays returned by fields/values are detached under caller mutation;
- two exception instances never share a context or schema object;
- no static state, I/O, ambient lookup, cache, renderer, logging, telemetry, or
  package-boundary collapse exists.

Closed token correctness for actual_state, expected_state, and scope_condition is
proved against the exact production throw-site outputs. A manually supplied
arbitrary text token remains structurally valid because Diagnostics validates
native kind, key closure, bounds, and order, not Kernel semantics. Do not expect a
wrong text token to throw and do not invent a new validator or failure type.

## 10.3 Root architecture test

Update only the Kernel shape and manifest assertions:

- Kernel and Graph markers each expose four methods including context;
- DI and Diagnostics retain three;
- Kernel and Graph bases each have diagnostic plus diagnosticContext properties,
  five final public methods, and four constructor parameters;
- Kernel constructor contextValues is array and previous remains nullable;
- Kernel manifest requires API Metadata, Diagnostics, and PHP exactly;
- marker separation, 40 statuses, 53 IDs, Graph contracts, DI/Diagnostics shapes,
  legacy namespace absence, and all other assertions remain.

## 10.4 Counts

No tool configuration changes because existing directory scopes discover the new
Kernel test automatically.

~~~text
production declarations/files    40
test PHP files                    72
source plus test PHP files        112
syntax entries                    113 including .php-cs-fixer.dist.php
PHP CS Fixer Finder files         112
PHPUnit tests                     exactly 194
~~~

Assertion count is not predeclared. Report it exactly.

# 11. REQUIRED ADR 0032

Create:

~~~text
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
~~~

Title exactly:

~~~text
# ADR 0032: Kernel Lifecycle Diagnostic Context Integration
~~~

Status Accepted. Date 2026-08-14. Include exactly these second-level sections:

~~~text
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
~~~

Record:

- Phase 0S made the one-way dependency acyclic;
- exact Kernel-to-Diagnostics edge;
- exact six schemas and values from Sections 6 and 7;
- Public marker and Internal base changes;
- eager immutable detached same-object ownership;
- unchanged lifecycle, messages, IDs, state, priorities, and marker separation;
- all fields Safe but not automatically disclosed;
- inactive_or_foreign is intentionally conservative;
- context construction failure propagates unchanged as internal defect;
- pre-alpha compatibility impact;
- allocation/work bounded by at most two fields and Diagnostics text bounds;
- no cache, I/O, ambient state, renderer, transport, logger, or telemetry.

Reject aliases, universal marker, arbitrary maps, optional/Protected Kernel
fields, raw object/scope identity, owner tracking, semantic scope split, lazy or
nullable context, fallback, swallowed construction failure, rendering, and DI
integration.

Open work is exactly DI's 25 contexts, simultaneous construction failure
representation, disclosure policy, trusted developer mode, rendering/transports,
serialization/localization/source/stack/docs providers, logging/telemetry/
incidents, retry/cancellation/cleanup/recovery, and later capabilities.

# 12. DOCUMENTATION RECONCILIATION

Update exactly the listed files so current prose says:

- Phase 0T integrates Kernel's six contexts; Graph's 11 remain integrated;
- DI's 25 remain the only original deliberate-failure family without context;
- Diagnostics' own 11 construction failures remain context-free;
- Kernel now requires API Metadata and Diagnostics;
- Graph and Kernel independently depend on Diagnostics; neither depends on the
  other;
- markers remain package-local and pairwise unrelated;
- only Kernel/Graph markers expose context;
- exact six fields/tokens are current, not future;
- all Kernel fields are Safe eligibility, not disclosure authorization;
- no renderer, transport, sanitizer, logging, telemetry, or presentation exists;
- exact file/test/style/syntax counts are current.

Historical ADR decision text may remain when explicitly scoped to its phase, but
add current implementation notes and remove stale current/open claims. ADR 0031
must remove the completed Kernel-edge and six-context items from open work while
preserving all still-open items. ADR 0029 must say only DI's 25 integrations
remain. ADR 0030 must distinguish its Phase 0Q decision from current Phase 0T.

README, architecture, security, lifecycle, testing, package-layout, API-status,
error-handling, and package README prose must agree. Add ADR 0032 to the ADR
index and relevant links. Preserve all valid local links.

Do not document a rendered example as current output. Existing conceptual
developer-page examples remain explicitly future.

# 13. SECURITY, LIFETIME, AND FAILURE CONTRACT

All Kernel context fields are closed Safe values:

- state case names only;
- bounded native integers only;
- inactive_or_foreign only.

Safe means eligible for a later explicit policy, not automatically public. Do not
capture object identity, hashes, class names, addresses, IDs, operation content,
request/session/auth data, secrets, credentials, tokens, headers, bodies,
environment, files, stack/source, messages, arbitrary strings, or user data.

Context is constructed synchronously at failure detection before state mutation,
then owned by the exception. No input array is retained. No context crosses into
operation state or survives through a static/global registry. Repeated access is
identity-stable. Separate failures have separate contexts.

Do not catch Diagnostics construction failure. Do not replace the deliberate
failure with fallback context or represent both failures. The production maps
must make construction total; simultaneous-failure policy remains open.

# 14. PROHIBITED SCOPE

Do not implement or select:

- any DI diagnostic context or constructor/throw-site change;
- Diagnostics construction-failure context;
- renderer, developer error page, CLI/HTTP output, transport, content negotiation,
  trusted mode, disclosure allowlist, localization, documentation lookup, source
  or stack provider, serialization, incident reference, logging, telemetry;
- new diagnostic ID, title, remediation, message, exception, marker, status,
  context field, schema kind, disclosure, or value type;
- scope owner/ended metadata, identifier, storage, callback, payload,
  serialization, propagation, cleanup, reset hook, cancellation, recovery;
- new lifecycle transition, concurrency primitive, runtime adapter, container
  binding, global current operation, or service;
- Graph, DI, API Metadata, or Diagnostics production behavior;
- HTTP/router, ORM/database, events/workflows/queues, agents/extensions,
  frontend/CLI, Application Graph expansion, or other later framework capability;
- dependency/plugin/version/license/release/repository split;
- suppression, baseline, skipped test, ignored platform, weakened policy, audit
  bypass, formatter/analyzer exclusion;
- stage, commit, branch, tag, remote, reset, checkout, clean, stash, amend, rebase,
  history rewrite, or Git configuration change.

# 15. IMPLEMENTATION SEQUENCE

1. Read all authority and caller records.
2. Verify baseline, hashes, closed inventories, and caller setup.
3. Run unchanged pre-change composer quality; retry only blocked network access
   with the identical command.
4. Inspect all six diagnostics, six throw sites, Graph precedent, Diagnostics
   contracts, tests, manifests, and documents.
5. Create ADR 0032 and the focused test placeholder/content within scope.
6. Add Kernel manifest dependency and run the exact scoped Composer update.
7. Inspect lock delta; stop on any unauthorized change.
8. Implement catalog schemas/context, marker/base, and six maps.
9. Update KernelTest and root architecture test.
10. Reconcile exactly 29 existing documents.
11. Run focused syntax, manifest, autoload, Kernel, architecture, analysis, style,
    structural, security, link, and scope checks.
12. Run final composer quality.
13. Verify final Git and file safety.
14. Populate only Phase_0T.md and validate its identity.
15. Return report size/hash outside it and stop unstaged.

# 16. REQUIRED VALIDATION

## 16.1 Baseline and caller proof

Prove every Section 2 and 3 identity, branch/ref/history/index fact, closed status
inventory, ordinary-file property, directive identity, and zero-byte report.

## 16.2 Focused commands

At minimum:

~~~text
php -l packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
php -l packages/kernel/src/Exception/ExceptionInterface.php
php -l packages/kernel/src/Exception/KernelException.php
php -l packages/kernel/src/Kernel.php
php -l packages/kernel/tests/Unit/KernelDiagnosticContextTest.php
composer --working-dir=packages/kernel validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/kernel/tests
vendor/bin/phpunit --configuration phpunit.xml.dist packages/kernel/tests/Unit/KernelDiagnosticContextTest.php
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer analyse
composer cs
~~~

Use platform-correct executable forms without changing semantics.

## 16.3 Structural proof

Prove:

- six and only six Kernel catalog cases/schemas;
- six and only six Kernel deliberate throw statements;
- exact operation/invariant/field/label/kind/disclosure/order/required contracts;
- exact throw maps and closed tokens;
- Kernel marker/base reflection shapes;
- same context identity, detached values, distinct exception contexts;
- 17 total integrated original schemas: 6 Kernel plus 11 Graph;
- 25 DI schemas still unintegrated;
- Diagnostics failures remain context-free;
- exact acyclic package graph and lock delta;
- 40/72/112/113 inventories;
- 53 IDs, four markers, 40 statuses, all messages/priorities/state behavior exact;
- no forbidden capture, static state, ambient access, I/O, renderer, or later API;
- exact authorized path set only.

## 16.4 Canonical quality

Run composer quality. Require valid root/five manifests, zero advisories, platform
pass, zero of 112 fixable, PHPStan zero errors, and exactly 194 tests with no
warning, risky, deprecation, notice, incomplete, empty-suite, or output failure.
Report assertion count.

If only the audit is network-blocked, rerun unchanged with approved access.

## 16.5 Documentation and file safety

Verify local Markdown links, strict UTF-8 no BOM, LF, one final LF, no NUL or
trailing whitespace, ordinary non-reparse files, default data stream only, no
mode change, no zero-byte implementation file, and git diff --check clean.

## 16.6 Final Git state

No staging or commit. Final:

~~~text
branch                    main
HEAD                      75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent               5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                   5
local branches            1
remotes                   0
tags                      0
tracked modified paths    75
untracked paths           58
total status paths        133
staged paths              0
~~~

Counts remain after report population. Closed path inventories are required.
If an authorized existing path needs no byte change, stop and report the no-op
rather than editing to satisfy a count.

# 17. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- baseline, locked records, caller setup, history, refs, and index exact;
- changed paths exactly Section 8;
- one Kernel-to-Diagnostics edge and only authorized lock delta;
- six exact schemas and six exact maps;
- Public marker and Internal base shapes exact;
- messages, codes, causes, IDs, statuses, lifecycle/state behavior unchanged;
- all fields Safe closed values; no forbidden capture;
- no scope semantic split or added state;
- Kernel and Graph integrated independently; DI remains unintegrated;
- 40 declarations, 72 test files, 112 Finder files, 113 syntax entries;
- exactly 194 tests; focused suites, autoload, analysis, style, audit, platform pass;
- ADR 0032 Accepted and exact; 29 documents reconciled;
- links, encoding, streams, modes, scope, Git state pass;
- no prohibited capability;
- report populated after success, untracked, unstaged, absent from HEAD.

# 18. REQUIRED EXECUTOR COMPLETION REPORT

Populate docs/task reports/Phase_0T.md only after complete success:

~~~markdown
# PHASE 0T TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. FILES CREATED

## 5. FILES MODIFIED

## 6. ARCHITECTURAL DECISIONS

## 7. DEVIATIONS FROM DIRECTIVE

## 8. DEPENDENCIES ADDED/REMOVED

## 9. ENVIRONMENT

## 10. COMMANDS RUN

## 11. VALIDATION RESULTS

## 12. DOCUMENTATION REVIEW

## 13. SECURITY AND FILE-SAFETY REVIEW

## 14. WARNINGS / KNOWN ISSUES

## 15. QUESTIONS / BLOCKERS

## 16. GIT DIFF SUMMARY

## 17. ACCEPTANCE CRITERIA

## 18. NEXT STEP
~~~

Requirements:

- Complete only if every criterion passes; otherwise leave report zero and return
  a blocking TCR outside it.
- List exactly two created implementation files, all modified files, and caller
  report population separately.
- State ADR 0032 records the directive's architecture; no executor decision.
- Report exact dependency/lock delta and absence of third-party changes.
- Report PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions.
- List material commands and network retries truthfully.
- Report pre/post quality, focused counts, final 194-test assertion count, all
  schema/map/reflection/behavior/security/inventory/link/file/Git proofs.
- Report exact final status counts and actual diff stat/hash.
- End Section 18 exactly:

~~~text
Await architect review before Phase 0U.
~~~

Then verify report nonzero strict UTF-8/LF, ordinary, untracked, unstaged, absent
from HEAD, and exact 18 headings. Return size and SHA-256 outside its body. Do not
embed its own digest.

# FINAL EXPECTED OUTCOME

All six deliberate Kernel lifecycle failures expose exact eager bounded Safe
diagnostic context through their existing package-local Public marker. Kernel and
Application Graph independently consume Diagnostics in an acyclic graph, while
their recovery boundaries remain unrelated. DI's 25 failures remain the only
original deliberate family without context.

Lifecycle state, operation identity, valid-state preservation, messages, IDs,
titles, remediation, causes, and persistent-runtime behavior remain unchanged.
No renderer, error page, transport, owner tracking, cleanup system, logging,
telemetry, or later framework capability exists.
