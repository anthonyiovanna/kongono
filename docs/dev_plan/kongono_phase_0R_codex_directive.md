# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0R

## Neutral API-Metadata Ownership and Acyclic Package-Dependency Doctrine

## Mission

Resolve the package-ownership constraint that prevents the Kernel from adopting
the bounded diagnostic-context foundation implemented in Phases 0P and 0Q.

Today, `kongono/diagnostics` depends on `kongono/kernel` solely for
`Kongono\Kernel\Api\Api` and `Kongono\Kernel\Api\ApiStatus`. A direct Kernel
dependency on Diagnostics would therefore create a Composer package cycle.
Phase 0R must make the future dependency topology explicit before any Kernel
diagnostic context is implemented.

Phase 0R is an architecture-and-documentation phase only. It accepts a future
fifth monorepo package, `kongono/api-metadata`, as the neutral owner of the two
bootstrap compatibility-metadata types. It also accepts the exact future
namespace migration and acyclic package graph defined below.

Phase 0R does **not** create that package, move either PHP type, change a PHP
namespace or import, alter Composer manifests or the lockfile, change tests, add
Kernel or Dependency Injection context, or implement a renderer or error page.
The current four-package runtime remains byte-for-byte unchanged.

---

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the Phase 0R executor, not the architect.

Read completely, in this order:

1. `AGENTS.md`;
2. `README.md`, `ARCHITECTURE.md`, and `SECURITY.md`;
3. `docs/doctrine.md`, `docs/documentation-standard.md`, and
   `docs/glossary.md`;
4. every Accepted ADR through ADR 0030, including the ADR index;
5. `docs/architecture/overview.md`;
6. `docs/engineering/api-status.md`,
   `docs/engineering/package-layout.md`, and
   `docs/engineering/error-handling.md`;
7. the four package READMEs;
8. all Phase 0O, 0P, and 0Q directives and reports, including corrections;
9. the Phase 0R directive copied into the repository by the caller; and
10. the zero-byte Phase 0R report placeholder.

This directive supersedes earlier statements only for the **accepted future
ownership and dependency topology** described here. It does not retroactively
change historical facts or authorize runtime migration.

## Mandatory stop rule

Stop before changing any file if:

- the Git identity, status inventory, diff identity, caller records, or locked
  content differs from Sections 2 and 3;
- the Phase 0R report is absent, nonzero, staged, tracked, or inside `HEAD`;
- any required authority file is missing, malformed, unexpectedly empty, or
  concurrently changed;
- the current package graph differs from Section 4.1;
- another process changes the worktree or index during execution;
- an edit outside the exact Section 6 boundary appears necessary;
- the selected package name, namespace, compatibility treatment, or dependency
  graph appears unsafe or contradictory; or
- any validation command fails for a non-environmental reason.

On a stop:

```text
STOP
ASK
DO NOT GUESS
```

Report:

1. the exact decision or mismatch;
2. why safe progress depends on it;
3. viable options;
4. tradeoffs;
5. a clearly labeled recommendation; and
6. the unchanged Git/index/report state.

Do not repair a caller record, broaden scope, reinterpret a locked statement,
or proceed on a probable match.

---

# 2. LOCKED ACCEPTED PRE-PHASE-0R BASELINE

Before the caller adds Phase 0R records, require exactly:

```text
Branch: main
Local branches: 1
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
Tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
Parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit count: 5
Tracked files in HEAD/index inventory: 214
Remotes: 0
Tags: 0
Tracked modifications: 39
Untracked paths: 40
Total status paths: 79
Staged paths: 0
Tracked diff: 39 files changed, 1,545 insertions, 119 deletions
Binary tracked-diff hash: 8b2d07865cfcd9d827b47366b215f95cb1ac7906
```

The managed environment may warn that the user-level global Git ignore file is
unreadable. That warning is acceptable only when the closed repository
inventories are independently verified. It does not authorize ignoring a path.

## 2.1 Exact 39 tracked modifications

```text
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
```

## 2.2 Exact 40 untracked paths

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0P_codex_directive.md
docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
docs/task reports/Phase_0O_1_correction.md
docs/task reports/Phase_0O_2_correction.md
docs/task reports/Phase_0P.md
docs/task reports/Phase_0P_1_correction.md
docs/task reports/Phase_0Q.md
docs/task reports/Phase_0Q_1_correction.md
docs/task reports/Phase_0Q_2_correction.md
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
```

All accepted Phase 0O through 0Q work remains intentionally uncommitted. Do not
stage, commit, reset, discard, normalize, or otherwise rewrite it.

---

# 3. LOCKED RECORDS AND CONTENT IDENTITIES

Require these exact identities before any edit:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0Q_codex_directive.md` | 50,661 | `F0F29634FB9B4943BD43EC398999CBA9099BAA508CA57CFE70EAADE5E600E3E3` |
| `docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md` | 15,725 | `E9F246B602FFAEFF96244BD67DBDC1F61063574C2110BB0B23C98F6CB16EAD5E` |
| `docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md` | 12,626 | `62B2E2FB1B50B556C06CD31661197465779C11B8F9DC783D322ECBAF0E95D3B8` |
| `docs/task reports/Phase_0Q.md` | 4,001 | `74EC1B831B492C4AEBD6A9F2A072D6608F90CC439AE05818A825790D228FE0FF` |
| `docs/task reports/Phase_0Q_1_correction.md` | 20,471 | `D205F624600F60F5C458279CADF4367598F59397B545A52F51DDFF9CC0A714CB` |
| `docs/task reports/Phase_0Q_2_correction.md` | 9,338 | `E736A0FEDD4A26969F123F22B9E4EC006F1D7630E2A0DDCC250B675C5D087441` |

The exact pre-edit identities of every authorized content path are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `README.md` | 5,723 | `B79409364BC19997A214ACE59A8340B5B624BE856A2D67EA266640CDE4A2830C` |
| `ARCHITECTURE.md` | 7,617 | `24D9220EA500CBB01CBB88567D7213E27B79FD68D6C55EDB27924E8EA00F0630` |
| `docs/doctrine.md` | 12,445 | `40A910233BA64EF5115439D0DFCC06B80CEBF0FD6B72C7134DEBEC38C98FECFD` |
| `docs/adr/0003-monorepo-initial-development.md` | 2,074 | `A12B2282992386F7EF30E349804C5EF3F63C512F193166607AD4D9816CA8966A` |
| `docs/adr/README.md` | 3,292 | `12D8229C126C3D40AABACDA29EAFDFB60189E98926C6E23CE3A1A1D1DEE938C6` |
| `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md` | 5,340 | `A2EC6D406B0CDD0FDD6850EE6383FCA4E7AB81F224AD95F5BBFAFD9C1FDEAFFD` |
| `docs/adr/0030-application-graph-diagnostic-context-integration.md` | 7,759 | `3244D6ECBBA0F7C22D864612E924741663DF487664D5133BF4FB0686E49AF683` |
| `docs/architecture/overview.md` | 6,183 | `FEC761C46F0DE14667B56E0C63D685128B41B3CC38EF96B5509634FF2E2AAB27` |
| `docs/engineering/api-status.md` | 3,134 | `94473E4F5C51320284F8F0603536B8832288BBC0996E26C6612F09FFC7C6AEB6` |
| `docs/engineering/package-layout.md` | 3,699 | `E0141FE7D98D6F27BD49DC77446E0D3175382556D56E1565E5DA66DBF54EE1E8` |
| `docs/engineering/error-handling.md` | 50,405 | `8F4F5F4753F6DB8D3E40D0802BA566346CC8E9C8A364949136DBB7BC627BECD5` |
| `docs/glossary.md` | 16,900 | `1EE24C390B6E4780FBFBD219EE4FEA15DE94720B8919B998AB142D4C959316E8` |
| `packages/application-graph/README.md` | 10,812 | `146714370517647E99DAF27A74BCDAD96C25586F72B02FAF67BB42FD3FC5F82D` |
| `packages/dependency-injection/README.md` | 6,575 | `EAB60A975F5799822C61F13BC5CCD382704F0C452C2363E3B93EF9D4835FE6B2` |
| `packages/diagnostics/README.md` | 4,891 | `D695D63CAD378526E6B2C91DCBC31B2A609FF18557C9FD7E384296AB4C7B6578` |
| `packages/kernel/README.md` | 4,210 | `FA707CF69F7D440DBB605BD456C2AC221788BCAE601F1C57BBD8232A0A2CFDB8` |

Every path outside Section 6 must retain its exact pre-execution bytes. Before
editing, record byte count and SHA-256 for all 79 status paths so that unchanged
paths can be proved after the work.

---

# 4. CURRENT AND ACCEPTED FUTURE PACKAGE TOPOLOGY

## 4.1 Current implemented topology

Phase 0R must document this as current fact, not erase it:

```text
kongono/kernel
  owns Kongono\Kernel\Api\Api and ApiStatus
  requires PHP only

kongono/diagnostics
  requires kongono/kernel only for Api and ApiStatus

kongono/application-graph
  requires kongono/diagnostics for bounded Graph context
  requires kongono/kernel only for Api and ApiStatus

kongono/dependency-injection
  requires kongono/kernel only for Api and ApiStatus
```

Kernel cannot currently require Diagnostics without creating the forbidden
cycle `kernel -> diagnostics -> kernel`.

## 4.2 Accepted future topology

Phase 0R accepts exactly this target for a later implementation phase:

```text
kongono/api-metadata
  requires php >=8.5 only

kongono/kernel
  requires kongono/api-metadata

kongono/diagnostics
  requires kongono/api-metadata

kongono/application-graph
  requires kongono/api-metadata
  requires kongono/diagnostics

kongono/dependency-injection
  requires kongono/api-metadata

kongono/framework root
  requires kongono/api-metadata as @dev
  retains @dev requirements for application-graph, dependency-injection,
  diagnostics, and kernel
```

No existing framework package depends on Kernel merely to obtain compatibility
metadata after the future migration. No package may depend on itself, and the
future graph must be acyclic.

This target makes a later one-way `kongono/kernel -> kongono/diagnostics`
dependency possible without selecting or implementing that edge in Phase 0R.

---

# 5. LOCKED API-METADATA OWNERSHIP DECISION

## 5.1 Package and namespace

The future package is exactly:

```text
Composer package: kongono/api-metadata
Production namespace: Kongono\ApiMetadata\
```

It is a neutral compatibility-metadata foundation, not a Kernel capability,
service container, application capability, extension API, authorization system,
Graph model, runtime adapter, or generic metadata bag.

It will eventually own exactly these two bootstrap types:

```text
Kongono\ApiMetadata\Api
Kongono\ApiMetadata\ApiStatus
```

Phase 0R does not create either type at the new name.

## 5.2 Future namespace migration

A later implementation phase must perform one atomic migration:

```text
Kongono\Kernel\Api\Api
  -> Kongono\ApiMetadata\Api

Kongono\Kernel\Api\ApiStatus
  -> Kongono\ApiMetadata\ApiStatus
```

The semantic contracts remain unchanged:

- `ApiStatus` remains a string-backed enum with exactly
  `Internal = internal`, `Experimental = experimental`, and `Public = public`;
- `Api` remains final, readonly, non-repeatable, and targeted to class-like
  declarations;
- its constructor still accepts exactly one `ApiStatus` through the same public
  readonly promoted property;
- the two types remain Public bootstrap metadata by definition and the only
  self-classification exceptions;
- status remains compatibility/exposure metadata, not authorization, security,
  package visibility, Graph data, or runtime access control; and
- per-member status, new enum cases, deprecation mechanics, release tooling,
  and cross-language representation remain open.

## 5.3 Compatibility treatment

The later migration will remove the two old Kernel FQCNs in the same atomic
change. Do not plan or document aliases, `class_alias`, proxy attributes,
duplicate enums, compatibility shims, deprecated forwarders, overlapping
namespace ownership, or two simultaneous status identities.

This is an explicitly accepted pre-alpha breaking namespace correction. It is
preferred over retaining a permanent Kernel-named bootstrap dependency in every
package. No implementation occurs in Phase 0R.

## 5.4 Future package boundary

The later implementation phase—not Phase 0R—must create an independently owned
PSR-4 package with only the two production declarations and focused tests. It
must migrate every production import, test import, root/package manifest,
autoload contract, tooling inventory, and documentation reference atomically.

Under the existing unreleased-monorepo convention, each of the four package
manifests will use exactly `"kongono/api-metadata": "*"`; the root will use
exactly `"kongono/api-metadata": "@dev"`. The new package will require exactly
`"php": ">=8.5"` as production input and own the
`Kongono\ApiMetadata\` PSR-4 prefix. Exact JSON ordering, test namespace, created
file inventory, Composer command, and generated lock identity belong to the
later implementation directive.

The total production declaration count remains 40 during that migration: the
two types move and are renamed; they are not duplicated or supplemented.

No external dependency, Composer plugin, license, version, release mechanism,
split-repository automation, or publishing promise is selected.

---

# 6. EXACT FILE-CHANGE BOUNDARY

## 6.1 Create exactly one content file

```text
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
```

The caller-created Phase 0R directive and report are not executor-created
implementation content.

## 6.2 Modify exactly these 16 existing content files

```text
README.md
ARCHITECTURE.md
docs/doctrine.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/README.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/architecture/overview.md
docs/engineering/api-status.md
docs/engineering/package-layout.md
docs/engineering/error-handling.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/diagnostics/README.md
packages/kernel/README.md
```

All 16 are already part of the accepted dirty worktree: 13 are tracked
modifications and three are accepted untracked content. Editing them must not
add another status path.

## 6.3 Preserve every other path

In particular, do not modify:

- any PHP source or test;
- any `composer.json` or `composer.lock`;
- `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, or `phpunit.xml.dist`;
- `SECURITY.md`—Phase 0R changes no runtime security behavior;
- any historical directive or report;
- the Phase 0R directive;
- any other ADR or guide; or
- any cache, generated autoload file, Git ref, index entry, or file stream.

---

# 7. REQUIRED ADR 0031

Create ADR 0031 with exactly:

```text
# ADR 0031: Neutral API Metadata Ownership and Acyclic Package Dependencies

## Status

Accepted

## Date

2026-08-13
```

Then include the standard sections:

```text
## Context
## Decision
## Rationale
## Consequences
## Compatibility impact
## Security considerations
## Performance implications
## Rejected alternatives
## What remains open
```

The ADR must record all of the following without ambiguity:

1. the current four-package dependency topology;
2. the exact `kernel -> diagnostics -> kernel` cycle that blocks direct Kernel
   context integration;
3. the accepted future `kongono/api-metadata` package and
   `Kongono\ApiMetadata\` namespace;
4. the exact two future FQCNs;
5. the exact atomic old-to-new FQCN migration;
6. unchanged three-status and attribute semantics;
7. the exact future acyclic dependency graph from Section 4.2;
8. removal of metadata-only Kernel dependencies from Graph, DI, and Diagnostics;
9. exact internal `*` package constraints and the root `@dev` constraint for
   `kongono/api-metadata`;
10. the future ability—but not current authorization—for Kernel to depend on
   Diagnostics;
11. the deliberate pre-alpha compatibility impact and rejection of aliases or
    duplicate identities;
12. continued root ownership of repository-wide API-status enforcement;
13. no implementation in Phase 0R;
14. no runtime, security, diagnostic-context, or presentation behavior change;
15. no external package, plugin, version, license, release, or publishing
    decision; and
16. the remaining open decisions from Section 11.

Rejected alternatives must include:

- adding `kernel -> diagnostics` now and accepting a cycle;
- leaving the metadata types permanently owned by Kernel;
- moving Diagnostics values into Kernel;
- removing status metadata or exempting Diagnostics types;
- duplicating `Api` or `ApiStatus` in multiple packages;
- keeping `Kongono\Kernel\Api\` under a different physical package through a
  split/overlapping namespace;
- compatibility aliases or forwarders for the old FQCNs;
- an external third-party API-status dependency; and
- a broad `foundation`, `common`, `shared`, or `contracts` dumping-ground
  package.

---

# 8. DOCUMENTATION RECONCILIATION

Every changed document must clearly separate:

```text
Current implemented state
Accepted Phase 0R future architecture
Not yet implemented
```

Do not use present tense for the future package or namespace.

## 8.1 Root documents

### `README.md`

- identify Phase 0R as an Accepted architecture/documentation phase;
- state that the current runtime remains the accepted Phase 0Q four-package
  implementation;
- explain the metadata dependency cycle in one concise paragraph;
- name the accepted future neutral package and namespace;
- state that no package, namespace migration, Kernel context, DI context,
  renderer, or error page is implemented by Phase 0R; and
- retain pre-alpha/API-status cautions.

### `ARCHITECTURE.md`

- add the current and accepted future dependency graphs in compact prose or an
  ASCII diagram;
- state that Phase 0R resolves ownership architecture only;
- retain current Graph diagnostic integration exactly;
- keep Kernel and DI diagnostic context unimplemented; and
- avoid claiming the fifth package exists.

### `docs/doctrine.md`

- add Phase 0R to the implemented-versus-accepted phase summary;
- lock neutral API-metadata ownership and acyclic package dependencies as future
  direction; and
- preserve every unrelated locked doctrine statement.

## 8.2 ADR history and index

### `docs/adr/0003-monorepo-initial-development.md`

Add a short Phase 0R amendment pointing to ADR 0031. State that the fifth
package boundary is Accepted but not implemented and that the currently
implemented package count remains four.

### `docs/adr/README.md`

Add ADR 0031 as entry 31 with the exact link and title. Preserve numbering,
ordering, existing links, and introductory process text.

### `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`

Add a narrow Phase 0R note: Diagnostics still currently depends on Kernel only
for status metadata; ADR 0031 accepts a later atomic migration to the neutral
package. Do not rewrite the original decision as if the migration already
occurred.

### `docs/adr/0030-application-graph-diagnostic-context-integration.md`

Record that Phase 0R resolves the previously open Kernel dependency-topology
decision architecturally. Kernel context integration, its six schemas, and the
actual dependency migration remain unimplemented.

## 8.3 Architecture and engineering guides

### `docs/architecture/overview.md`

- retain four packages as the implemented topology;
- describe the accepted future fifth neutral package;
- explain why the future extraction is needed before Kernel context; and
- do not imply broader Kernel responsibilities.

### `docs/engineering/api-status.md`

- retain the current `Kongono\Kernel\Api\*` implementation as current fact;
- document the accepted future `Kongono\ApiMetadata\*` ownership and atomic
  migration;
- preserve the exact three statuses and two bootstrap exceptions; and
- state that Phase 0R changes no reflection behavior or production inventory.

### `docs/engineering/package-layout.md`

- retain the exact four currently implemented package directories;
- add a clearly labeled accepted future layout for `packages/api-metadata`;
- show both current and future dependency graphs;
- reject cycles, overlapping namespaces, and undeclared dependencies;
- state that the future fifth package is not present yet; and
- keep current Composer/autoload facts accurate.

### `docs/engineering/error-handling.md`

- keep all 53 diagnostic IDs, 42 existing package assignments, 11 Graph
  contexts, disclosure rules, and presentation non-goals unchanged;
- mark only Kernel dependency-topology ownership as architecturally resolved;
- keep all six Kernel contexts and all 25 DI contexts unimplemented;
- state that the actual metadata migration precedes Kernel context integration;
  and
- retain the clean development-renderer information architecture as future.

### `docs/glossary.md`

- update `API Status` to distinguish current and accepted future ownership;
- update `Kernel` so API-status metadata is a current historical responsibility
  scheduled for neutral extraction, not a permanent lifecycle responsibility;
- add `API Metadata Package` only if needed for clarity, clearly marked Accepted
  future architecture and not implemented; and
- do not add a generic metadata concept or claim a new runtime service.

## 8.4 Package READMEs

Update all four package READMEs consistently:

- Kernel currently owns the two types but will cease to after the future atomic
  migration;
- Diagnostics currently depends on Kernel only for metadata and will instead
  depend on `kongono/api-metadata`;
- Application Graph will replace its metadata-only Kernel dependency with the
  neutral package while retaining its Diagnostics dependency;
- Dependency Injection will replace its metadata-only Kernel dependency with
  the neutral package;
- none of these manifest changes has happened in Phase 0R;
- Graph remains the only existing exception family with bounded context;
- Kernel and DI context remain absent; and
- no universal exception boundary, runtime integration, renderer, or transport
  is introduced.

---

# 9. COMPATIBILITY, SECURITY, AND LIFECYCLE CONTRACT

Phase 0R changes documentation only. Therefore:

- existing PHP FQCNs and signatures remain current and autoloadable;
- existing Composer dependencies and lock references remain exact;
- existing exception marker shapes remain exact;
- existing Graph diagnostic context remains exact;
- existing messages, codes, causes, throw priority, lifecycle mutation order,
  and resolver behavior remain exact;
- no context captures another value;
- no trusted developer mode or disclosure policy becomes active;
- no error page, renderer, serializer, logger, telemetry hook, incident store,
  source reader, or documentation lookup exists;
- no static, global, ambient, request, environment, clock, random, I/O, network,
  process, fiber-local, or worker-local state is added; and
- no new persistent-runtime retention or cross-operation contamination risk is
  created.

The future namespace migration is intentionally breaking, but Phase 0R itself
does not execute it. Documentation must not tell current consumers to import a
type that does not yet exist.

---

# 10. PROHIBITED SCOPE

Do not implement or design:

- `packages/api-metadata` or any other new package;
- new PHP types, namespaces, aliases, shims, or imports;
- any Composer manifest or lockfile change;
- Kernel `diagnosticContext()` or its six schema instances;
- Dependency Injection `diagnosticContext()` or its 25 schema instances;
- Diagnostics self-context or a universal diagnostic interface;
- changes to Graph context;
- dependency injection/container behavior, scopes, autowiring, PSR-11, aliases,
  providers, factories, decorators, or service-locator behavior;
- HTTP, routing, middleware, requests, responses, controllers, templates,
  sessions, cookies, or RFC 9457 mappings;
- a developer error page, production error surface, renderer, HTML, CSS,
  JavaScript, Markdown or JSON presentation API;
- trusted developer mode, request-controlled debug mode, production allowlists,
  redaction, secret detection, sanitizer, or disclosure engine;
- source excerpts, stack-frame grouping, code navigation, documentation lookup,
  localization, copy buttons, or incident references;
- logging, telemetry, OpenTelemetry integration, tracing, metrics, profiling, or
  incident storage;
- retry, cancellation, recovery, cleanup orchestration, or simultaneous-failure
  representation;
- Application Graph schemas, IDs, serialization, persistence, mutation,
  traversal, query DSLs, compilation, or provenance;
- ORM, database, migrations, transactions, cache, queue, events, workflows,
  agents, extensions, CLI, frontend, Vite, deployment, runtime adapters, or
  capability packages;
- licensing, versioning, publication, repository splitting, CI, signing, tags,
  releases, remotes, or pushes; or
- unrelated cleanup, refactoring, wording normalization, or historical repair.

---

# 11. OPEN DECISIONS AFTER PHASE 0R

The following remain open and must be stated as such:

- exact Phase 0S file inventory and mechanical migration procedure;
- exact Composer-generated lock delta for `kongono/api-metadata`;
- package publication, versioning, split-repository, and release policy;
- promotion, deprecation, and per-member API-status mechanics;
- cross-language representation of API status;
- Kernel's six runtime context schema instances and throw-site values;
- the later `kernel -> diagnostics` integration phase;
- all 25 DI context schema instances and throw-site values;
- context-construction failure handling at existing exception throw sites;
- production disclosure allowlists and trusted developer-mode selection;
- renderer ownership and APIs;
- HTTP, CLI, and other transport mappings;
- serialization, localization, source providers, stack providers, and
  version-matched documentation lookup;
- logging, telemetry, incident storage, retry, cancellation, cleanup, recovery,
  and simultaneous-failure representation; and
- every later framework capability excluded by Section 10.

Do not resolve these by implication.

---

# 12. CALLER RECORDS AND EXPECTED STATUS TRANSITIONS

The caller must supply, before execution:

```text
docs/dev_plan/kongono_phase_0R_codex_directive.md
docs/task reports/Phase_0R.md
```

The directive must be an exact copy of this artifact. The report must be an
ordinary zero-byte file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup and before executor edits, require exactly:

```text
Tracked modifications: 39
Untracked paths: 42
Total status paths: 81
Staged paths: 0
```

After content completion and report population, require exactly:

```text
Tracked modifications: 39
Untracked paths: 43
Total status paths: 82
Staged paths: 0
```

The sole additional implementation path is ADR 0031. Every Section 6 modified
path is already present in the accepted 79-path baseline, so it creates no new
status entry.

The Phase 0R directive and report remain untracked, unstaged, and absent from
`HEAD`. Populate the report only after every other validation passes.

---

# 13. IMPLEMENTATION SEQUENCE

1. Read all authority files completely.
2. Verify the exact Git identity, refs, counts, status inventories, diff hash,
   and caller records.
3. Verify every Section 3 identity and record pre-edit identities for all 79
   accepted status paths.
4. Verify the current package manifests and source imports implement exactly
   Section 4.1.
5. Verify the Phase 0R report is zero-byte, untracked, unstaged, and absent from
   `HEAD`.
6. Run the unchanged canonical pre-change quality gate.
7. Create only ADR 0031.
8. Modify only the exact 16 Section 6 paths.
9. Validate current-versus-future language, links, encoding, whitespace, file
   safety, and exact path closure.
10. Prove every non-authorized path and all production/tooling artifacts retain
    their pre-edit identities.
11. Run the unchanged canonical post-change quality gate.
12. Re-prove Git identity, refs, index, status counts, modes, streams, and the
    exact final inventory.
13. Populate only the Phase 0R report.
14. Validate the completed report and return its final byte count and SHA-256
    separately from the report body.

Do not reorder this sequence in a way that permits report population before all
other gates pass.

---

# 14. REQUIRED VALIDATION

## 14.1 Baseline and repository integrity

Verify at minimum:

```powershell
git branch --show-current
git for-each-ref --format='%(refname)|%(objectname)' refs/heads refs/remotes refs/tags
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
git rev-parse 'HEAD^'
git rev-list --count HEAD
git ls-files
git status --porcelain=v1 -z --untracked-files=all
git diff --shortstat
git diff --binary | git hash-object --stdin
git diff --cached --name-only
git diff --check
```

Use NUL-safe status parsing. Do not infer path counts from human-formatted Git
output.

## 14.2 Current topology proof

Inspect all five existing manifests—the root plus four packages—and prove:

- only four physical `packages/*/composer.json` manifests exist;
- `Kongono\Kernel\Api\Api` and `ApiStatus` exist only under Kernel;
- Diagnostics, Graph, and DI currently require Kernel for metadata;
- Graph additionally requires Diagnostics;
- Kernel currently requires PHP only;
- no current Composer cycle exists;
- `packages/api-metadata` and `Kongono\ApiMetadata\*` do not exist; and
- all imports remain the current Kernel FQCNs after Phase 0R.

## 14.3 Documentation validation

Prove:

- ADR 0031 has `Status: Accepted`, date `2026-08-13`, and every required
  section;
- the ADR index contains exactly one correctly ordered ADR 0031 link;
- the exact package name and namespace are consistent everywhere;
- every changed document distinguishes current from future state;
- no document claims `packages/api-metadata` exists;
- no document claims Kernel or DI context exists;
- no document claims a renderer or error page exists;
- no local Markdown link is broken;
- strict UTF-8 without BOM, LF-only endings, exactly one final LF, and no
  trailing whitespace hold for all created/modified files;
- no NUL, conflict marker, patch marker, temporary note, placeholder prose, or
  accidental tool transcript exists; and
- `git diff --check` passes.

## 14.4 File safety and scope proof

Prove:

- ADR 0031 is the only executor-created content path;
- only the exact 16 existing content paths changed;
- all paths outside Section 6 retain pre-execution bytes;
- every ordinary repository file remains an ordinary file;
- no non-default alternate data stream exists on any final status path;
- no symlink, junction, reparse point, executable mode, or unexpected mode
  change exists;
- the index remains empty; and
- HEAD, tree, parent, history, branches, remotes, and tags remain unchanged.

## 14.5 Canonical quality

Run before and after the documentation edit:

```powershell
composer quality
```

The accepted pre-Phase-0R reference is:

```text
Composer security advisories: 0
PHPStan errors: 0
PHP CS Fixer: 0 of 110 files fixable
PHPUnit: 182 tests, 2,257 assertions
```

Treat these as the expected unchanged reference, not permission to conceal a
different result. Do not skip, exclude, suppress, baseline, weaken policy, use a
platform-ignore flag, or edit configuration.

If the unchanged advisory step fails solely because managed network access is
blocked, rerun the identical command with approved access. Report both attempts.

## 14.6 Final report validation

After every other gate passes, populate the Phase 0R report and prove it is:

- nonzero;
- strict UTF-8 without BOM;
- LF-only;
- exactly one-final-LF terminated;
- free of trailing whitespace;
- untracked and unstaged;
- absent from `HEAD`; and
- structured exactly as Section 16.

Return its final byte count and SHA-256 outside the report body so the digest
does not become self-referential.

---

# 15. STRICT ACCEPTANCE CRITERIA

## Repository boundary

- [ ] Exact pre-caller 39/40/79/0 baseline matches.
- [ ] Exact post-caller 39/42/81/0 state matches.
- [ ] HEAD, tree, parent, five-commit history, sole branch, zero remotes, zero
      tags, modes, and index remain unchanged.
- [ ] All locked record identities match.
- [ ] Final state is exactly 39 modified, 43 untracked, 82 total, zero staged.
- [ ] ADR 0031 is the sole new implementation path.
- [ ] Only the exact 16 authorized existing content paths changed.
- [ ] Every other path retains its pre-execution identity.

## Architecture decision

- [ ] Current four-package topology remains documented accurately.
- [ ] The blocking Kernel/Diagnostics cycle is explained exactly.
- [ ] `kongono/api-metadata` and `Kongono\ApiMetadata\` are the sole accepted
      future neutral ownership boundary.
- [ ] The two exact future FQCNs and atomic old-to-new migration are recorded.
- [ ] The exact future dependency graph is acyclic and consistent everywhere.
- [ ] The three statuses, attribute shape, and two bootstrap exemptions remain
      unchanged.
- [ ] No alias, duplicate identity, overlapping namespace, or third-party
      dependency is planned.
- [ ] Compatibility impact is explicitly recorded as a later pre-alpha breaking
      migration.

## Current behavior preservation

- [ ] No PHP source, test, manifest, lock, or tooling configuration changed.
- [ ] Current imports and FQCNs remain autoloadable and unchanged.
- [ ] The current four physical packages remain the only implemented packages.
- [ ] Graph remains the only existing package family with diagnostic context.
- [ ] Kernel and DI markers retain only ID, title, and remediation.
- [ ] All runtime, security, lifecycle, DI, Graph, and diagnostic behavior is
      unchanged.
- [ ] No renderer, error page, transport, logger, telemetry, incident, or later
      capability was introduced.

## Documentation and quality

- [ ] ADR 0031 is Accepted, dated 2026-08-13, complete, and indexed.
- [ ] All changed docs separate current, accepted future, and unimplemented
      state.
- [ ] No broken local link, encoding defect, whitespace defect, stream, or file
      safety issue exists.
- [ ] Pre-change and post-change `composer quality` pass without policy
      weakening.
- [ ] The Phase 0R report satisfies every format and identity requirement.

Every item must pass. Partial acceptance is not completion.

---

# 16. REQUIRED EXECUTOR COMPLETION REPORT

Populate `docs/task reports/Phase_0R.md` only after all other gates pass, using
exactly these 16 top-level headings in this order:

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

The report must include:

- `Complete`, `Partial`, or `Blocked` accurately;
- exact created and modified paths;
- confirmation that only the decisions already authorized here were recorded;
- `None` for dependency changes and explicit manifest/lock preservation;
- PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
- material commands actually run;
- pre- and post-quality results;
- Git identity, status counts, diff summary, and zero staged paths;
- ADR 0031 status/date/section/link validation;
- exact current and future dependency-topology proof;
- explicit confirmation that no PHP/package migration or Kernel context was
  implemented;
- documentation, link, encoding, whitespace, stream, and file-safety results;
- PASS/FAIL for every Section 15 item; and
- the next authorized handoff only.

For a successful report, Section 16 must say exactly:

```text
Await architect review before Phase 0S.
```

Do not begin Phase 0S. Do not create `packages/api-metadata`. Do not implement
Kernel diagnostic context.

---

# FINAL EXPECTED OUTCOME

Phase 0R ends with one new Accepted ADR and reconciled documentation that make
the package-cycle problem and its resolution explicit.

The implemented runtime remains Phase 0Q: four packages, current
`Kongono\Kernel\Api\*` metadata types, Graph-only diagnostic context, and no
renderer.

The accepted future architecture becomes precise: a neutral
`kongono/api-metadata` package will own `Kongono\ApiMetadata\Api` and
`ApiStatus`; all framework packages will depend on it directly; the graph will
be acyclic; and a later Kernel-to-Diagnostics dependency can be considered
without circular ownership.

Nothing from Phase 0S or any later capability is implemented.
