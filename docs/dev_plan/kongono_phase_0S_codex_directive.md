# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0S

## Atomic Neutral API-Metadata Package Extraction

## Mission

Implement the exact neutral API-metadata ownership decision accepted by ADR 0031.
Create the fifth monorepo package kongono/api-metadata, move the two existing
bootstrap metadata declarations from Kongono\Kernel\Api\ to
Kongono\ApiMetadata\, migrate every production and test import atomically,
replace the three metadata-only Kernel dependency edges with the neutral package,
make Kernel depend on the neutral package, expand root Composer/autoload/tooling
coverage, add focused production tests, and reconcile current documentation.

This is an ownership and dependency-topology correction only. Preserve the exact
semantics of Api and ApiStatus, the exact 40-type production inventory, every
existing API-status assignment, all runtime behavior, all diagnostic metadata and
context, all exception boundaries, and every previously locked doctrine rule.

Do not add Kernel or Dependency Injection diagnostic context. Do not add a
renderer, developer error page, transport, serialization, service, registry,
reflection cache, migration shim, alias, forwarder, or any later framework
capability.

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the implementation executor, not the architect.

Read completely before changing anything:

1. AGENTS.md
2. ARCHITECTURE.md
3. docs/doctrine.md
4. docs/documentation-standard.md
5. docs/adr/0003-monorepo-initial-development.md
6. docs/adr/0004-microkernel-capability-architecture.md
7. docs/adr/0020-kernel-operation-lifecycle-boundary.md
8. docs/adr/0021-machine-checkable-api-status.md
9. docs/adr/0022-immutable-application-graph-structural-snapshot.md
10. docs/adr/0023-explicit-typed-dependency-resolution.md
11. docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
12. docs/adr/0030-application-graph-diagnostic-context-integration.md
13. docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
14. docs/dev_plan/kongono_phase_0R_codex_directive.md
15. docs/task reports/Phase_0R.md
16. docs/dev_plan/kongono_phase_0R_1_codex_directive_correction.md
17. docs/task reports/Phase_0R_1_correction.md
18. this Phase 0S directive
19. the zero-byte Phase 0S completion-report placeholder

The accepted ADRs, current authority documents, this directive, and the locked
caller records are authoritative. Historical phase directives and reports are
evidence, not permission to reimplement or reinterpret prior phases.

## Mandatory stop rule

Stop without guessing, staging, committing, populating the completion report, or
changing unrelated files if any of the following is true:

- the baseline in Section 2 does not match before caller setup;
- a locked caller record in Section 3 differs in bytes, size, or digest;
- the Phase 0S directive is absent or its completion report is not a zero-byte
  ordinary untracked file when execution begins;
- any required source, test, manifest, or authority file differs materially from
  the contracts described here;
- a required change would touch a path outside Section 5;
- Composer proposes any third-party package addition, removal, version change,
  source/dist change, or policy weakening;
- the atomic move cannot remove both old Kernel FQCNs while creating both new
  neutral FQCNs in the same worktree result;
- an API-status value, production declaration count, diagnostic catalog, context
  schema, exception behavior, or public signature would change;
- a test requires an alias, forwarder, duplicate type, suppression, skipped case,
  or weakened assertion to pass;
- an architectural choice remains that this directive and accepted ADRs do not
  resolve.

When blocked, report the exact mismatch, viable options, tradeoffs, and a
recommendation. Do not repair adjacent work or infer permission.

# 2. LOCKED ACCEPTED PRE-PHASE-0S BASELINE

Before the caller copies this directive into the repository or creates the report
placeholder, the accepted Phase 0R.1 repository is:

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
tracked modifications          39
untracked paths                45
total status paths             84
staged paths                   0
tracked diff                   39 files, 1,727 insertions, 126 deletions
binary tracked-diff SHA-256     4C8C77A9FDCD71F15559705F18B7DA7F81CE9D58
production PHP declarations    40
test PHP files                 70
source plus test PHP files     110
syntax inventory               111 including .php-cs-fixer.dist.php
~~~

The Git warning that the managed environment cannot read the user-level global
ignore file is not itself a mismatch if and only if closed status inventories are
independently verified. No other Git warning is pre-accepted.

The accepted pre-change quality evidence is PHP 8.5.9, Composer 2.10.2, Git
2.45.1, PHPUnit 13.3.0, PHPStan 2.2.8, and PHP CS Fixer 3.95.18; root and four
package manifests valid; zero advisories; platform pass; zero of 110 Finder files
fixable; PHPStan zero errors; and 182 tests with 2,257 assertions. Re-run it in
the executor environment rather than treating this recorded evidence as a
substitute for validation.

## 2.1 Exact 39 tracked modifications

~~~text
.php-cs-fixer.dist.php
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
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
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/src/Model/NodeKey.php
packages/application-graph/src/Model/NodeKind.php
packages/application-graph/src/Model/RelationKind.php
packages/application-graph/tests/Unit/ApplicationGraphTest.php
packages/application-graph/tests/Unit/StructuralValueTest.php
packages/dependency-injection/README.md
packages/kernel/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
~~~

## 2.2 Exact 45 untracked paths

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

# 3. LOCKED RECORDS AND CONTENT IDENTITIES

Verify these records before mutation:

~~~text
docs/dev_plan/kongono_phase_0R_codex_directive.md
  39,242 bytes
  SHA-256 372847999BF881D956EF74706577CF011B7FBBDAA722AFC26022F72980E200F8

docs/task reports/Phase_0R.md
  14,925 bytes
  SHA-256 5CE8670DAC97721070981E3028679328188D7D5223C1EA81F31F076AE6902D61

docs/dev_plan/kongono_phase_0R_1_codex_directive_correction.md
  15,678 bytes
  SHA-256 96F0DA2E7C871A3717D8121D199A43F1742F0B5CBEEC0C7D5210381B624BC61E

docs/task reports/Phase_0R_1_correction.md
  12,080 bytes
  SHA-256 C90A9695EF1CDB80CA464869069A43FD45A0AAC79D4EA7633F1CA9A4D091492E

docs/adr/0030-application-graph-diagnostic-context-integration.md
  8,277 bytes
  SHA-256 9240C349AF5B0EC235152DACC5E22F31E45E55ED082093062F524B5A87465D60

docs/architecture/overview.md
  7,227 bytes
  SHA-256 554FC09E8E3C15AB099096DD66DDFD3ED080D651578A120BB492C54545EC4194

docs/engineering/package-layout.md
  4,960 bytes
  SHA-256 B9801F79E280CD622C71EBD15AAF5F13DF22A2FA27C14407EE638625C6B8C1B0

docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
  8,111 bytes
  SHA-256 45ABD75FE56F80485723460B8154307B74EC9F9EF605063F97C83C4D70C83F68

packages/kernel/src/Api/Api.php
  709 bytes
  SHA-256 C8866756BB8E852B1985F37B80C86E9A18008E9A5917A1E1325CE740681A1922

packages/kernel/src/Api/ApiStatus.php
  764 bytes
  SHA-256 7F72C67A4434B32CC9C4481C3D283D234A6F89E2FC40FFA94E7EF115CE12D6B2

composer.json
  2,464 bytes
  SHA-256 914F455AF3CEE2C721E786939497931ABD648802188540DE5AEED216A16548C1

composer.lock
  178,429 bytes
  SHA-256 879ABC517B15C16B7AFD25C4CE2958619C90D56F9F591B070281E2F892E4806D
~~~

## 3.1 Caller setup

The caller adds exactly:

~~~text
docs/dev_plan/kongono_phase_0S_codex_directive.md
docs/task reports/Phase_0S.md
~~~

The directive must be populated, ordinary, untracked, unstaged, absent from HEAD,
and byte-identical to the delivered artifact. The report must be a zero-byte
ordinary untracked file. After only this setup, expect 39 tracked modifications,
47 untracked paths, 86 total status paths, and zero staged paths. Record the
directive's actual size and SHA-256. Never change the directive. Populate the
report only after every gate passes.

# 4. LOCKED PHASE 0S ARCHITECTURE

## 4.1 Exact implemented package graph

~~~text
kongono/api-metadata
  -> php >=8.5

kongono/kernel
  -> kongono/api-metadata: *
  -> php >=8.5

kongono/diagnostics
  -> kongono/api-metadata: *
  -> php >=8.5

kongono/application-graph
  -> kongono/api-metadata: *
  -> kongono/diagnostics: *
  -> php >=8.5

kongono/dependency-injection
  -> kongono/api-metadata: *
  -> php >=8.5

kongono/framework root
  -> kongono/api-metadata: @dev
  -> kongono/application-graph: @dev
  -> kongono/dependency-injection: @dev
  -> kongono/diagnostics: @dev
  -> kongono/kernel: @dev
  -> php >=8.5
~~~

There is no cycle. Graph retains its one-way Diagnostics dependency. Kernel does
not depend on Diagnostics. Graph, DI, and Diagnostics no longer depend on Kernel
merely for compatibility metadata.

## 4.2 Exact namespace migration

Create exactly:

~~~text
Kongono\ApiMetadata\Api
Kongono\ApiMetadata\ApiStatus
~~~

Remove exactly:

~~~text
Kongono\Kernel\Api\Api
Kongono\Kernel\Api\ApiStatus
~~~

Both moves are atomic. The old files and FQCNs must be absent at final state. Do
not create class_alias, a duplicate enum, proxy attribute, deprecated forwarder,
compatibility file, overlapping namespace, or autoload-files shim.

## 4.3 Exact declaration semantics

ApiStatus remains a string-backed enum with exactly, in this order:

~~~php
case Internal = 'internal';
case Experimental = 'experimental';
case Public = 'public';
~~~

It declares no methods, properties, traits, aliases, or extra cases.

Api remains final, readonly, non-repeatable, targeted exactly to
Attribute::TARGET_CLASS, and constructed with exactly one public readonly promoted
property named status typed exactly as ApiStatus. It has no additional method,
property, trait, interface, static state, or behavior.

The two types remain Public bootstrap metadata by definition and the only
production declarations exempt from their own attribute. Status remains
compatibility and supported-use metadata, never authorization, access control,
package visibility, a security boundary, Graph data, diagnostic context, or a
runtime service.

## 4.4 Unchanged repository contracts

Preserve all 38 non-bootstrap declarations and their exact statuses. The total
production inventory remains 40. Preserve all signatures, runtime behavior,
exception inheritance, diagnostic IDs, titles, remediation, Graph contexts,
schemas, disclosure levels, selector order, resolver behavior, Kernel lifecycle
behavior, and consumer-Throwable propagation.

# 5. EXACT FILE-CHANGE BOUNDARY

Only the paths in this section may change.

## 5.1 Create exactly five files

~~~text
packages/api-metadata/README.md
packages/api-metadata/composer.json
packages/api-metadata/src/Api.php
packages/api-metadata/src/ApiStatus.php
packages/api-metadata/tests/Unit/ApiMetadataTest.php
~~~

No other path beneath packages/api-metadata is authorized.

## 5.2 Delete exactly two files

~~~text
packages/kernel/src/Api/Api.php
packages/kernel/src/Api/ApiStatus.php
~~~

Remove the empty packages/kernel/src/Api directory if necessary.

## 5.3 Modify exactly these 38 production import files

Change only the old metadata imports to the new imports. Do not change
declarations, attributes, status values, signatures, bodies, or messages.

~~~text
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
packages/dependency-injection/src/Contract/ResolverInterface.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/src/Exception/InvalidDefinitionException.php
packages/dependency-injection/src/Exception/UnknownServiceException.php
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
~~~

Every file uses exactly:

~~~php
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
~~~

No production file may retain Kongono\Kernel\Api.

## 5.4 Modify exactly four existing tests

~~~text
packages/diagnostics/tests/Unit/DiagnosticDisclosureTest.php
packages/diagnostics/tests/Unit/DiagnosticValueKindTest.php
packages/diagnostics/tests/Unit/StructuralValueTest.php
tests/Architecture/ApiStatusConventionTest.php
~~~

The first two receive only import migration. StructuralValueTest also replaces
its obsolete Kernel-metadata-only assertion with proof that Diagnostics source:

- imports only Api Metadata for framework compatibility metadata;
- has no Kernel, Graph, or DI production import;
- retains every ambient-capability, I/O, static-state, and cache ban;
- has the exact final manifest dependency map from Section 7.

The root architecture test migrates bootstrap imports and inventory keys, retains
all 40 statuses and every exception/diagnostic assertion, discovers all five
package source trees, and proves both legacy Kernel FQCNs are absent. Keep
cross-package enforcement root-owned.

## 5.5 Modify exactly five existing manifests and the lockfile

~~~text
composer.json
composer.lock
packages/application-graph/composer.json
packages/dependency-injection/composer.json
packages/diagnostics/composer.json
packages/kernel/composer.json
~~~

## 5.6 Modify exactly three tooling files

~~~text
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
~~~

## 5.7 Modify exactly 30 documentation files

~~~text
ARCHITECTURE.md
CONTRIBUTING.md
README.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/0021-machine-checkable-api-status.md
docs/adr/0022-immutable-application-graph-structural-snapshot.md
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/doctrine.md
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
packages/dependency-injection/README.md
packages/diagnostics/README.md
packages/kernel/README.md
~~~

## 5.8 Preserve every other path

Do not modify SECURITY.md, docs/adr/README.md, another ADR or guide, a fixture, a
nonlisted test, diagnostic schema/catalog content, historical directive/report,
.git, Git refs, or generated cache path.

The only caller-owned file Phase 0S may populate is
docs/task reports/Phase_0S.md, only after complete success. It is not an
implementation file and is excluded from the zero-byte implementation-file ban.

# 6. EXACT NEW PACKAGE CONTENT

## 6.1 Package manifest

Create packages/api-metadata/composer.json exactly:

~~~json
{
  "name": "kongono/api-metadata",
  "description": "Neutral API compatibility metadata for Kongono Framework.",
  "type": "library",
  "require": {
    "php": ">=8.5"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\ApiMetadata\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\ApiMetadata\\Tests\\": "tests/"
    }
  }
}
~~~

Do not add license, version, minimum-stability, repository, script, plugin,
autoload-files entry, third-party dependency, or another namespace.

## 6.2 Production declarations

Move each accepted declaration mechanically. Change the namespace to exactly
Kongono\ApiMetadata; otherwise preserve declaration, order, values, and semantic
PHPDoc. Wording may change only to describe neutral ownership accurately. Neither
bootstrap type carries its own Api attribute.

## 6.3 Package README

Document only implemented behavior:

- the package owns exactly Api and ApiStatus;
- it requires PHP 8.5 or newer and no Kongono or third-party package;
- exact three statuses and attribute shape;
- the bootstrap types are Public by definition and the only self-classification
  exceptions;
- status means compatibility and supported use, not authorization, visibility,
  security, discovery, Graph data, or runtime access;
- old Kernel FQCNs were removed atomically without a shim;
- root architecture tests own repository-wide enforcement;
- no service, registry, cache, I/O, runtime integration, diagnostic context,
  renderer, or transport exists.

Do not promise releases, stability, external consumption, or future mechanics.

# 7. EXACT COMPOSER AND AUTOLOAD CONTRACT

## 7.1 Final package require maps

~~~text
api-metadata:
  php                         >=8.5

application-graph:
  kongono/api-metadata        *
  kongono/diagnostics         *
  php                         >=8.5

dependency-injection:
  kongono/api-metadata        *
  php                         >=8.5

diagnostics:
  kongono/api-metadata        *
  php                         >=8.5

kernel:
  kongono/api-metadata        *
  php                         >=8.5
~~~

Remove kongono/kernel from Graph, DI, and Diagnostics. Retain every other field.

## 7.2 Root manifest

Add kongono/api-metadata: @dev to root require in sorted order. Retain the other
four local requirements, PHP constraint, policies, scripts, tools, repository
configuration, and plugin policy.

Add the root development mapping:

~~~text
Kongono\ApiMetadata\Tests\ -> packages/api-metadata/tests/
~~~

in lexicographic namespace order.

Add API Metadata first in validate:packages:

~~~text
composer --working-dir=packages/api-metadata validate --no-check-publish
~~~

Then retain Application Graph, Dependency Injection, Diagnostics, and Kernel in
their existing order.

## 7.3 Lock update

After all manifests exist, run exactly:

~~~text
composer update kongono/api-metadata kongono/application-graph kongono/dependency-injection kongono/diagnostics kongono/kernel --with-dependencies --no-interaction
~~~

If managed network access blocks it, retry the identical command once with
existing approved access. Do not change flags, ignore the platform, edit the lock
manually, clear caches, or broaden the update.

Authorized lock changes are limited to:

- root content-hash change;
- one new path-package entry for kongono/api-metadata;
- local requirement maps matching Section 7.1;
- Composer-generated reference changes for the affected five local packages;
- root resolution metadata mechanically required by Composer 2.10.2.

No third-party package name, version, source, dist, checksum, requirement,
advisory policy, plugin allowance, or platform constraint may change.

Accepted warning categories remain limited to missing license, authorized local
unbounded constraints, root local @dev constraints, and the locally accepted
missing-curl performance warning. A network-blocked audit is not a pass.

# 8. TEST AND TOOLING EXPANSION

## 8.1 ApiMetadataTest

Create one final class Kongono\ApiMetadata\Tests\Unit\ApiMetadataTest with exactly
these five public test methods and no provider, fixture declaration, generated
source, or eval:

~~~text
testApiStatusVocabularyIsExact
testApiAttributeContractIsExact
testBootstrapTypesRemainTheOnlySelfClassificationExceptions
testLegacyKernelMetadataTypesAreAbsent
testPackageManifestAndSourceBoundaryAreExact
~~~

Together they prove:

- exact enum case names, values, order, and string-backed behavior;
- exact Api final/readonly/attribute/non-repeatable/constructor/property shape;
- bootstrap types carry no Api attribute;
- both new FQCNs autoload and both old FQCNs do not;
- the package has exactly two source files and declarations;
- no static state, service, cache, registry, I/O, ambient lookup, diagnostic
  context, renderer, or other package import;
- manifest and PSR-4 mappings are exact.

Do not test future behavior or duplicate every root assertion.

## 8.2 Root architecture enforcement

Update ApiStatusConventionTest so:

- bootstrap keys are the new FQCNs;
- the expected map still has exactly 40 declarations;
- the other 38 FQCNs and all values are unchanged;
- discovery covers five packages;
- every non-bootstrap declaration has exactly one new Api attribute;
- both bootstrap declarations have none and are Public by definition;
- old Kernel FQCNs are absent;
- existing marker, 53-ID, exception-shape, Graph-context, and boundary assertions
  remain enforced;
- manifest assertions reflect new Graph and Diagnostics maps.

## 8.3 Tool configuration and counts

Add packages/api-metadata/src and packages/api-metadata/tests in lexicographic
package order to PHP CS Fixer, PHPStan, and PHPUnit testsuite/source sections as
applicable. Remove no existing path.

Final inventories:

~~~text
production PHP declarations/files    40
test PHP files                       71
source plus test PHP files           111
syntax inventory                     112 including .php-cs-fixer.dist.php
PHP CS Fixer Finder PHP files        111
package manifests                    5, plus root = 6
~~~

The five new methods raise 182 tests to exactly 187 if no hidden baseline change
exists. Assertion count is not predeclared; report it exactly. Stop on any other
test count rather than adjusting the contract.

# 9. DOCUMENTATION AND ADR RECONCILIATION

Current-state prose must describe Phase 0S as implemented. Historical statements
may remain only when clearly scoped to the earlier phase. Do not rewrite history.

## 9.1 Root and architecture documents

Update the authorized root, doctrine, architecture, and glossary files to state:

- Phase 0S implements the fifth package and atomic namespace move;
- the current graph is Section 4.1;
- Kernel no longer owns metadata and depends only on API Metadata plus PHP;
- Graph, DI, and Diagnostics no longer depend on Kernel for metadata;
- Graph retains Diagnostics; Kernel and DI context remain unimplemented;
- exactly five packages are implemented;
- old Kernel FQCNs are historical and unavailable;
- no runtime behavior or later capability was added.

CONTRIBUTING.md lists API Metadata first in lexicographic package validation and
retains all contribution and stop policies.

## 9.2 ADR reconciliation

Preserve original status, date, context, and historical meaning. Add or revise
explicit Phase 0S implementation notes:

- ADR 0003: the fifth package is implemented; boundaries beyond it remain open.
- ADR 0004 and 0020: DI uses API Metadata, and metadata is no longer Kernel
  lifecycle responsibility.
- ADR 0021: ownership moved to the new FQCNs with semantics/enforcement unchanged;
  old FQCNs are removed.
- ADR 0022: Graph depends on API Metadata and Diagnostics, not Kernel.
- ADR 0023: DI depends on API Metadata, not Kernel; resolver is unchanged.
- ADR 0029: Diagnostics depends on API Metadata only; behavior is unchanged.
- ADR 0030: Graph and Diagnostics lost metadata-only Kernel edges; Kernel context
  and the future Kernel-to-Diagnostics edge remain future.
- ADR 0031: record Phase 0S implementation on 2026-08-14; distinguish historical
  Phase 0R future tense from current topology; remove exact Phase 0S inventory,
  migration, and lock questions from What remains open; retain later questions.

Do not create ADR 0032. Phase 0S makes no new architectural decision.

## 9.3 Guides and package READMEs

Update current ownership, imports, edges, counts, and tests exactly. Remove or
qualify every stale claim that the package is future, absent, or unimplemented.

- api-status.md names new FQCNs and unchanged semantics.
- package-layout.md shows five exact trees and implemented DAG.
- application-graph-structure.md states Graph requires API Metadata and
  Diagnostics, not Kernel.
- dependency-injection.md states DI requires API Metadata and has no Kernel
  source dependency or special lifecycle integration.
- error-handling.md states extraction is complete but Kernel/DI context,
  rendering, and transports remain absent.
- php-style.md, static-analysis.md, testing.md, and toolchain.md use Section 8.3
  counts and six-manifest validation.
- package READMEs state exact direct dependencies.

Keep all local links valid. Add no speculative API or release promise.

# 10. COMPATIBILITY, SECURITY, AND PERFORMANCE

The only compatibility break is removal of two old FQCNs and creation of their
exact new equivalents. No behavior or status value changes. No transition shim.

Status remains descriptive compatibility metadata. Do not make it authorization,
visibility, trust, disclosure, or permission. Add no ambient read, I/O, secret
handling, reflection registry, serialization, or executable consumer input.
Preserve Safe/Protected disclosure and forbidden-capture doctrine.

The two declarations remain inert language metadata. Add no cache, memoization,
registration, eager discovery, scan, service construction, or runtime lookup.

# 11. PROHIBITED SCOPE

Do not implement or select:

- six Kernel context schemas/values or 25 DI context schemas/values;
- a kernel-to-diagnostics dependency;
- renderer, error page, CLI/HTTP output, transport, negotiation, localization,
  documentation/stack/source provider, sanitizer, trusted mode, or dev switch;
- alias, forwarder, proxy, compatibility shim, duplicate type, migration command,
  or old namespace mapping;
- new DI feature, service locator, lifetime, scope, autowiring, or factory;
- HTTP, router, middleware, request, response, controller, session, or cookie;
- Graph schema, stable IDs, serialization, persistence, traversal, query DSL,
  mutation, index, cache, builder, registry, or runtime integration;
- ORM, database, agents, extensions, capabilities, events, workflows, queues,
  scheduler, CLI, frontend, logging, telemetry, tracing, incident storage, retry,
  cancellation, cleanup, recovery, or simultaneous-failure representation;
- dependency, plugin, version, license, publication, repository split, tag,
  release, compatibility policy, or Stable API;
- suppression, baseline, skip, weakened policy, ignored platform, audit bypass,
  formatter/analyzer exclusion;
- staging, commit, branch, tag, remote, reset, checkout, amend, clean, stash,
  rebase, history rewrite, or Git configuration change.

# 12. IMPLEMENTATION SEQUENCE

1. Read authorities and caller records.
2. Verify Sections 2 and 3 read-only.
3. Verify directive and zero-byte report setup.
4. Run unchanged pre-change composer quality; retry only an environment-blocked
   audit unchanged with approved access.
5. Inspect all affected files.
6. Create the exact five-file package.
7. Move both declarations atomically and delete both old files.
8. Migrate 38 source files and four tests.
9. Update manifests and tooling.
10. Run exact Composer update.
11. Inspect lock delta; stop on anything unauthorized.
12. Implement focused and root test changes.
13. Reconcile exactly 30 documentation files.
14. Run focused validation and optimized strict autoload generation.
15. Run final canonical quality.
16. Run final Git, inventory, hash, link, encoding, stream, and scope proof.
17. Populate only Phase_0S.md.
18. Validate its identity, return size/hash outside its body, and stop unstaged.

# 13. REQUIRED VALIDATION

Record exact commands and outputs.

## 13.1 Baseline

Prove branch, one-branch state, HEAD/tree/parent, commit and tracked counts, zero
remotes/tags/staged, closed status inventories, diff totals/hash, record
sizes/hashes, ordinary-file status, and zero-byte report.

## 13.2 Focused commands

At minimum run:

~~~text
php -l packages/api-metadata/src/Api.php
php -l packages/api-metadata/src/ApiStatus.php
php -l packages/api-metadata/tests/Unit/ApiMetadataTest.php
composer --working-dir=packages/api-metadata validate --no-check-publish
composer dump-autoload --optimize --strict-psr
vendor/bin/phpunit --configuration phpunit.xml.dist packages/api-metadata/tests
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostics/tests/Unit/StructuralValueTest.php
composer analyse
composer cs
~~~

Use the platform-correct local executable form if needed without changing
configuration.

## 13.3 Structural proof

Prove:

- five package directories/manifests and exact acyclic maps;
- two API Metadata source files/declarations;
- 40 production declarations and 71 test files;
- 38 non-bootstrap declarations have one new Api attribute with unchanged status;
- bootstrap types have none and are Public by definition;
- no production import/declaration, manifest, autoload mapping, or tool config
  uses the old namespace; explicitly historical ADR prose and the exact negative
  test string literals may name it;
- class_exists for old Api and enum_exists for old ApiStatus are false after
  optimized autoload;
- no alias, forwarder, files autoload, or overlapping namespace;
- Graph/Diagnostics retain 11 contexts; Kernel/DI retain zero context;
- 53 IDs and four marker boundaries remain exact;
- no protected path changed.

## 13.4 Canonical quality

Run composer quality. It must produce valid root and five package manifests, zero
advisories, platform pass, zero fixable files, PHPStan zero errors, and exactly
187 PHPUnit tests with no warning/risky/deprecation/notice/incomplete/empty/output
failure. Report observed assertions.

If only audit is network-blocked, rerun the identical command with approved
access. Never call an unrun audit passed.

## 13.5 Documentation and file safety

Verify local links, strict UTF-8 no BOM, LF, one final LF, no NUL/trailing
whitespace, ordinary files, no reparse points, no alternate streams, no mode
changes, and no zero-byte implementation file. The report may remain zero only
until complete success.

## 13.6 Final Git state

No staging or commit. Final state must retain:

~~~text
branch                         main
HEAD                           75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                      5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent                    5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                        5
local branches                 1
remotes                        0
tags                           0
staged paths                   0
tracked modified paths         72
untracked paths                52
total status paths             124
~~~

Counts remain the same after report population because it already exists
untracked. Verify closed inventories, not counts alone. If an authorized
documentation path needs no byte change, stop and report the no-op instead of
editing merely to satisfy a count.

# 14. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- baseline, records, and caller setup exact;
- only Section 5 paths changed; no history/index/ref/configuration mutation;
- fifth package exists, requires PHP only, and owns exactly two declarations;
- new FQCNs autoload; old FQCNs, files, aliases, and shims are absent;
- declaration semantics, other statuses, and 40-type count unchanged;
- manifests match Section 7 and graph is acyclic;
- Graph retains Diagnostics and loses Kernel; Diagnostics and DI lose Kernel;
  Kernel gains only API Metadata; root retains all five;
- lock delta is mechanical and local only; no third-party/policy change;
- one exact five-method package test and root enforcement pass;
- tooling covers five packages with exact counts;
- optimized strict PSR-4, focused validation, static analysis, style, advisories,
  platform, and exactly 187 tests pass;
- 30 documents describe current state while preserving history;
- ADR 0031 records implementation and removes Phase 0S mechanics from open work;
- links and file safety pass;
- Kernel/DI context, renderer, transport, and later capabilities remain absent;
- report is complete, accurate, untracked, unstaged, and absent from HEAD.

# 15. REQUIRED EXECUTOR COMPLETION REPORT

Populate docs/task reports/Phase_0S.md only after complete success with exactly:

~~~markdown
# PHASE 0S TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. FILES CREATED

## 5. FILES MODIFIED

## 6. FILES DELETED

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

## 18. ACCEPTANCE CRITERIA

## 19. NEXT STEP
~~~

Requirements:

- Complete only if every gate passed. Otherwise keep the report zero bytes and
  return a blocking TCR outside it.
- List every created, modified, and deleted path.
- State no new architecture was selected; ADR 0031 was implemented.
- Report exact edges added/removed and inspected lock delta.
- Report PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions.
- List material commands and distinguish network-blocked attempts/retries.
- Report pre/post quality, focused counts, exact 187-test assertion count,
  manifests, autoload, advisories, analysis, style, inventories, links, encoding,
  streams, and Git proof.
- State final modified/untracked/staged counts and HEAD/tree preservation.
- End Section 19 exactly:

~~~text
Await architect review before Phase 0T.
~~~

Then verify strict UTF-8/LF, nonzero, ordinary, untracked, unstaged, absent from
HEAD, and exact 19 headings. Return its byte size and SHA-256 outside its body.
Do not insert its own digest into the report.

# FINAL EXPECTED OUTCOME

Kongono has exactly five physical packages and one neutral two-type API Metadata
owner. Kongono\ApiMetadata\Api and ApiStatus retain the exact semantics previously
implemented in Kernel. Every production declaration uses the new identity, old
Kernel identities are absent, the graph is acyclic, and Graph, DI, and Diagnostics
no longer depend on Kernel merely for metadata.

The migration changes ownership, imports, Composer topology, tests, tooling, and
documentation atomically while preserving all 40 declarations, status
assignments, runtime behavior, and accepted diagnostic/security doctrine. Kernel
and DI context, developer rendering, and every later capability remain explicitly
unimplemented.
