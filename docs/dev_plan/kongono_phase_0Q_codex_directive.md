# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0Q

## Application Graph Diagnostic Context Integration

## Mission

Implement the first complete existing-exception integration with Kongono's
presentation-neutral diagnostics foundation.

Phase 0Q integrates exactly the 11 deliberate Application Graph failures with
their Accepted closed diagnostic-context schemas and exact throw-site values.
Every Graph package failure must continue to expose its existing identifier,
title, remediation, native message, zero code, and optional cause while also
returning one eager immutable `DiagnosticContext` through the Graph package's
own Public exception marker.

This is a deliberately narrow vertical slice. It does **not** integrate the six
Kernel or 25 Dependency Injection diagnostics. It does not add a universal
exception or diagnostic marker, renderer, development error page, production
surface, trusted-mode selector, disclosure policy, transport, serialization,
source lookup, documentation lookup, logging, telemetry, incident storage,
retry, cancellation, cleanup orchestration, HTTP, CLI, agents, extensions,
frontend behavior, Application Graph schema expansion, or any later capability.

---

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the implementation executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `docs/doctrine.md`;
5. `docs/documentation-standard.md`;
6. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
7. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`;
8. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
9. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`;
10. `docs/engineering/error-handling.md`;
11. `docs/dev_plan/kongono_phase_0P_codex_directive.md`;
12. `docs/task reports/Phase_0P.md`;
13. `docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md`;
14. `docs/task reports/Phase_0P_1_correction.md`;
15. this directive; and
16. every production and test file authorized in Sections 5 and 6.

Accepted ADRs and prior directives remain authoritative except where this
directive explicitly advances their future-only wording for the 11 Graph
diagnostics. Historical directives and task reports are records; do not edit
them.

If any repository identity, caller record, byte/hash, path inventory, API
contract, schema row, throw-site value, message-preservation requirement,
dependency direction, or security rule is unclear or mismatched:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact mismatch and the smallest decision required. Do not improvise,
weaken a check, widen scope, or clean up adjacent work.

---

# 2. LOCKED ACCEPTED BASELINE

## 2.1 Git identity before caller setup

The accepted repository state before the caller copies this directive is:

```text
Branch: main
Local branches: 1
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit count: 5
Tracked files: 214
Tracked modes: all 100644
Tracked modifications: 28
Untracked paths: 32
Total status paths: 60
Staged paths: 0
Tracked diff: 28 files changed, 771 insertions, 82 deletions
Remotes: 0
Tags: 0
Non-default alternate data streams: 0
```

Do not create, switch, rename, merge, rebase, reset, amend, stage, commit, tag,
push, fetch, or add a remote. `HEAD`, its tree and parent, the index, refs,
tracked modes, and all history must remain unchanged.

The inaccessible user-level global-ignore warning is an accepted managed-
environment warning only when exact closed inventories are independently
proved. It does not authorize ignoring an inventory mismatch.

## 2.2 Exact accepted tracked modifications

Before caller setup, `git status --porcelain=v1 -uall` must contain exactly these
28 tracked modifications:

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
packages/dependency-injection/README.md
packages/kernel/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

## 2.3 Exact accepted untracked inventory

Before caller setup, the untracked inventory must be exactly:

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0P_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
docs/task reports/Phase_0O_1_correction.md
docs/task reports/Phase_0O_2_correction.md
docs/task reports/Phase_0P.md
docs/task reports/Phase_0P_1_correction.md
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

Paths with spaces may be quoted by Git. Normalize only the status presentation,
not the filesystem paths.

## 2.4 Locked caller and authority records

The following identities must match before implementation:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `AGENTS.md` | 7,520 | `AE2EFEB19D0E70094F89FEC97D018D5A319F996FB56C044FDB157189989C8D46` |
| `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md` | 21,183 | `B7AE4AF899E2402825CD24004CA72D617A580DEEFED8AB16208F306263CCF9AA` |
| `docs/adr/0027-package-local-runtime-diagnostic-metadata.md` | 13,097 | `692420EBE25E6F24AFEEB0C9FB50BD0C66CD8D12E34B08AFF95F3928177D57F7` |
| `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` | 10,151 | `78E1DC7F4058A798EB411867FE06C817B1BABDD20BB7C6E58EF17A842ADF7016` |
| `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md` | 5,340 | `A2EC6D406B0CDD0FDD6850EE6383FCA4E7AB81F224AD95F5BBFAFD9C1FDEAFFD` |
| `docs/dev_plan/kongono_phase_0P_codex_directive.md` | 47,365 | `305EB2499DAFF7297222414EAF802E82808777BA364C17A271C3ED1F3455689D` |
| `docs/task reports/Phase_0P.md` | 17,852 | `3196A3FC87B4BDB42F09A910A2BC0E1C3E68C65B9C15D076A558ACD70B21579A` |
| `docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md` | 11,545 | `B3ECDB4E8D2669AB63E638442F4421B6119F1EF5417ECDA082B0307558C85430` |
| `docs/task reports/Phase_0P_1_correction.md` | 10,805 | `5629ED4FC794A793DDD5FCBDDFB86DC547A174D233C89FFC4C9B276A354597CF` |
| `packages/diagnostics/composer.json` | 388 | `B34CFD0C6BB31746BAE3061A495061D29B6C5A6B47BDF9EAF592C261FA1024CD` |
| `packages/application-graph/composer.json` | 404 | `80EFC7B2264164B1BC89C08089E98E206A25563E59BB84C30BA9CCA6DA5E0094` |
| `composer.lock` | 178,385 | `88C701552763F09DB8EFB6FEEB8E2604F4C6475DB2D62A5450CDA9A27EAD8087` |

## 2.5 Caller setup

The caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_0Q_codex_directive.md
```

and create this exact zero-byte placeholder:

```text
docs/task reports/Phase_0Q.md
```

Before editing, verify the copied directive is byte-identical to the delivered
artifact and record its byte count and SHA-256. Verify the report is exactly zero
bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup, the pre-edit state must therefore be exactly 28 tracked
modifications, 34 untracked paths, 62 total status paths, and zero staged paths.
Do not populate the report until every non-report acceptance gate passes.

---

# 3. PHASE 0Q IMPLEMENTATION BOUNDARY

Phase 0Q authorizes exactly:

1. an Application Graph package dependency on `kongono/diagnostics`;
2. exact runtime schema ownership for the 11 `GraphDiagnostic` cases;
3. one Public `diagnosticContext(): DiagnosticContext` method on the Graph
   package exception marker only;
4. eager context creation by the Internal Graph exception base from the owning
   diagnostic and a closed value map;
5. exact context values at all 11 existing Graph failure sites;
6. focused production, package, architecture, and documentation tests;
7. ADR 0030 and current-state documentation reconciliation; and
8. the Composer lock metadata change required by the new local dependency.

This directive supersedes Phase 0P's no-existing-exception-integration rule
only for the 11 Graph diagnostics. It does not supersede any Graph structural,
selector, immutability, ordering, package-ownership, or no-query boundary.

The six Kernel and 25 DI diagnostics remain byte-for-byte behaviorally
unintegrated. Their markers, bases, constructors, catalogs, throw sites,
messages, dependencies, and tests may not be changed except for the exact
documentation and root architecture-test reconciliation authorized here.

---

# 4. EXACT CREATED FILE INVENTORY

The executor may create exactly these two implementation artifacts:

```text
docs/adr/0030-application-graph-diagnostic-context-integration.md
packages/application-graph/tests/Unit/GraphDiagnosticContextTest.php
```

The Phase 0Q directive and report are caller-created records, not
executor-created artifacts.

No other file, directory, fixture, generated source, cache record, baseline,
configuration, or helper artifact may be created. Ordinary ignored tool caches
may change only as a side effect of required tools and must remain Git-invisible.

---

# 5. EXACT MODIFIED FILE INVENTORY

The executor may modify exactly these production, package, test, lock, and
documentation paths:

```text
ARCHITECTURE.md
README.md
SECURITY.md
composer.lock
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
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
packages/diagnostics/README.md
packages/kernel/README.md
tests/Architecture/ApiStatusConventionTest.php
```

All other files must retain their preflight bytes. In particular, do not edit:

- any prior directive or task report;
- ADRs 0001 through 0029;
- root `composer.json`;
- Diagnostics production types or tests;
- Kernel or DI production source, tests, or manifests;
- Graph `Node.php`, `Relation.php`, selectors, public model APIs, or value
  storage; or
- tooling configuration.

---

# 6. PACKAGE AND COMPOSER CONTRACT

Modify `packages/application-graph/composer.json` so its exact runtime
requirements are:

```json
"require": {
  "kongono/diagnostics": "*",
  "kongono/kernel": "*",
  "php": ">=8.5"
}
```

Retain Composer's canonical sorted order. Do not change package name,
description, type, autoload, autoload-dev, or any other manifest field.

The dependency direction is exactly:

```text
kongono/application-graph -> kongono/diagnostics
kongono/application-graph -> kongono/kernel       (Api/ApiStatus only)
kongono/diagnostics       -> kongono/kernel       (Api/ApiStatus only)
```

There must be no reverse Diagnostics-to-Graph dependency and no cycle. Kernel
and DI must not gain a Diagnostics dependency in Phase 0Q.

Regenerate `composer.lock` through Composer; never edit it manually. Run exactly:

```text
composer update kongono/application-graph --with-dependencies --no-interaction
```

Do not add flags that bypass scripts, plugins, platform checks, security policy,
or normal dependency resolution. The root manifest and root lock content hash
must remain unchanged because root requirements do not change. The lock delta
may contain only:

- the `kongono/application-graph` package's added
  `kongono/diagnostics: *` requirement; and
- the mechanically generated local path-package reference change caused by
  that manifest content change.

No third-party package identity, version, source, distribution, stability flag,
plugin policy, advisory policy, platform requirement, or dependency may change.

Accepted Composer warnings remain limited to the missing license and the
already authorized local `@dev` or `*` constraints. Report every unexpected
warning and stop.

---

# 7. API AND COMPATIBILITY CONTRACT

## 7.1 Graph package marker

Add exactly this method to
`Kongono\ApplicationGraph\Exception\ExceptionInterface`:

```php
public function diagnosticContext(): DiagnosticContext;
```

Import the Public Diagnostics type explicitly. The Graph marker must declare
exactly these four methods:

```text
diagnosticContext
diagnosticId
diagnosticRemediation
diagnosticTitle
```

It remains Public, extends only `Throwable`, and remains unrelated to the
Kernel, DI, and Diagnostics markers. This is a pre-alpha Public signature
addition authorized by ADR 0030. It is not a universal diagnostic contract.

The Kernel, DI, and Diagnostics markers continue to declare exactly their
existing three string accessors. Diagnostics construction failures must not
gain recursive context.

## 7.2 Graph exception base

`Kongono\ApplicationGraph\Exception\GraphException` remains abstract,
Internal, and a `RuntimeException`. It must declare exactly two private readonly
non-static properties:

```text
diagnostic        GraphDiagnostic
diagnosticContext DiagnosticContext
```

Its exact constructor contract becomes:

```php
/** @param array<string, string|int|bool> $contextValues */
final public function __construct(
    GraphDiagnostic $diagnostic,
    string $message,
    array $contextValues,
    ?Throwable $previous = null,
)
```

The constructor must:

1. create the context synchronously through the supplied owning diagnostic;
2. store the resulting immutable context;
3. call `parent::__construct($message, 0, $previous)`;
4. retain no copy of `$contextValues`; and
5. perform no catch, wrapping, fallback, logging, I/O, or ambient lookup.

It declares exactly constructor plus these four final public accessors:

```text
diagnosticContext
diagnosticId
diagnosticRemediation
diagnosticTitle
```

`diagnosticContext()` returns the exact stored context object. Repeated calls
must return the same immutable object by identity. `getMessage()`, `getCode()`,
`getPrevious()`, identifier, title, and remediation behavior remain unchanged.

`InvalidGraphException` remains final, Experimental, and otherwise unchanged.

## 7.3 Catalog API

`GraphDiagnostic` remains Internal with the same 11 case names, backed IDs,
titles, and remediation strings. Add exactly:

```php
public function contextSchema(): DiagnosticContextSchema;

/** @param array<string, string|int|bool> $values */
public function context(array $values): DiagnosticContext;
```

Together with `title()` and `remediation()`, these are the enum's only declared
methods. `context()` must construct and return:

```php
new DiagnosticContext($this->contextSchema(), $values)
```

The catalog owns semantic schema selection. A throw site supplies values only;
it cannot provide a schema, field label, kind, disclosure, order, operation, or
invariant. Do not add a registry, static cache, global schema map, reflection,
serialization, file read, environment lookup, service lookup, or AI call.

Schema objects may be freshly allocated per failure. Do not claim cached or
constant-time schema lookup.

---

# 8. EXACT GRAPH CONTEXT SCHEMAS

Use the existing Public Diagnostics value constructors. Every field is required
unless explicitly marked optional. Field order below is exact schema order.

| Graph diagnostic | Operation | Violated invariant or expectation | Ordered field schemas |
| --- | --- | --- | --- |
| `EmptyNodeKey` / `KGN-GRAPH-1001` | `Create node key` | `Node-key value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `EmptyNodeKind` / `KGN-GRAPH-1002` | `Create node kind` | `Node-kind value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `EmptyRelationKind` / `KGN-GRAPH-1003` | `Create relation kind` | `Relation-kind value must be non-empty` | `actual_length` / `Actual length` / Integer / Safe / required |
| `NodesNotList` / `KGN-GRAPH-1004` | `Construct Application Graph` | `Nodes input must be a PHP list` | `entry_count` / `Entry count` / Integer / Safe / required; `first_unexpected_key` / `First unexpected key` / Text / Protected / optional |
| `RelationsNotList` / `KGN-GRAPH-1005` | `Construct Application Graph` | `Relations input must be a PHP list` | `entry_count` / `Entry count` / Integer / Safe / required; `first_unexpected_key` / `First unexpected key` / Text / Protected / optional |
| `InvalidNodeEntry` / `KGN-GRAPH-1006` | `Validate node entry` | `Every nodes-list entry must be a Node` | `entry_position` / `Entry position` / Integer / Safe / required; `actual_value_kind` / `Actual value kind` / Text / Safe / required |
| `InvalidRelationEntry` / `KGN-GRAPH-1007` | `Validate relation entry` | `Every relations-list entry must be a Relation` | `entry_position` / `Entry position` / Integer / Safe / required; `actual_value_kind` / `Actual value kind` / Text / Safe / required |
| `DuplicateNodeKey` / `KGN-GRAPH-1008` | `Validate node uniqueness` | `Node keys must be unique within a snapshot` | `node_key` / `Node key` / Text / Protected / required |
| `MissingRelationSource` / `KGN-GRAPH-1009` | `Validate relation source` | `Relation source must reference an existing node` | `relation_position` / `Relation position` / Integer / Safe / required; `source_key` / `Source key` / Text / Protected / required |
| `MissingRelationTarget` / `KGN-GRAPH-1010` | `Validate relation target` | `Relation target must reference an existing node` | `relation_position` / `Relation position` / Integer / Safe / required; `target_key` / `Target key` / Text / Protected / required |
| `DuplicateRelationTriple` / `KGN-GRAPH-1011` | `Validate relation uniqueness` | `Source/kind/target triples must be unique` | `source_key` / `Source key` / Text / Protected / required; `relation_kind` / `Relation kind` / Text / Protected / required; `target_key` / `Target key` / Text / Protected / required |

The strings, keys, labels, kinds, disclosures, required flags, and ordering are
exact. Do not normalize, translate, infer, abbreviate, or add metadata.

All 11 schemas must satisfy Phase 0P's structural bounds. The existing normative
42-row table in `docs/engineering/error-handling.md` must retain all row bytes
and order exactly; its joined-row SHA-256 remains:

```text
E72CFA5E0E55E03B53E6B95618FB3E9BE9DC5355D3B79C3880C41D01E79DCD6A
```

Update only surrounding current/future prose to state that the 11 Graph rows are
implemented while six Kernel and 25 DI rows remain future.

---

# 9. EXACT THROW-SITE VALUE CONTRACT

Preserve every existing exception class, diagnostic case, failure-selection
order, message expression, state guarantee, model value, and Graph structural
behavior. Add only the exact third constructor argument described here and the
minimum indexing/helpers required to derive it.

| Throw site | Exact context values |
| --- | --- |
| empty `NodeKey` | `actual_length => strlen($value)` |
| empty `NodeKind` | `actual_length => strlen($value)` |
| empty `RelationKind` | `actual_length => strlen($value)` |
| nodes input is not a list | `entry_count => count($nodes)` plus optional `first_unexpected_key` under Section 9.1 |
| relations input is not a list | `entry_count => count($relations)` plus optional `first_unexpected_key` under Section 9.1 |
| invalid node entry | `entry_position =>` its zero-based list position; `actual_value_kind =>` Section 9.2 token |
| invalid relation entry | `entry_position =>` its zero-based list position; `actual_value_kind =>` Section 9.2 token |
| duplicate node key | `node_key =>` exact duplicate key bytes selected by the existing canonical validation order |
| missing relation source | `relation_position =>` position in the validated caller-supplied relation list; `source_key =>` exact source-key bytes |
| missing relation target | `relation_position =>` position in the validated caller-supplied relation list; `target_key =>` exact target-key bytes |
| duplicate relation triple | exact duplicate `source_key`, `relation_kind`, and `target_key` bytes selected after existing canonical relation sorting |

## 9.1 First unexpected list key

Add one private static helper to `ApplicationGraph` for this algorithm. It may be
used only after `array_is_list()` has returned false.

1. Iterate the array's keys in insertion order while tracking the expected
   zero-based integer key beginning at `0`.
2. Stop at the first key that is not identical to the expected integer.
3. If that actual key is an integer, return its canonical base-10 string using
   `(string) $key`.
4. If that actual key is a string, return `null`; arbitrary caller-controlled
   string keys are omitted because this phase has no forbidden-material detector
   and cannot prove they are safe to retain.
5. Do not inspect later keys after the first mismatch.

When the helper returns `null`, omit `first_unexpected_key` entirely. Never use
`null` as a context value. Do not label, hash, redact, truncate before capture,
or otherwise retain an omitted string key. Numeric PHP-string keys already
normalized by PHP to integers follow the integer branch.

This rule is conservative secret exclusion, not a sanitizer. The stored numeric
representation remains Protected and is not automatically disclosable.

## 9.2 Broad PHP value-kind token

Add one private static helper to `ApplicationGraph` that maps the invalid entry
value to exactly one of these non-identifying Safe tokens:

```text
null
boolean
integer
float
string
array
object
resource
closed_resource
unknown
```

Use native kind checks or `gettype()` with an exhaustive match. Never use
`get_debug_type()` or any operation that exposes an object class, resource type,
callable target, file path, or value content. `double` maps to `float` and
`resource (closed)` maps to `closed_resource`. A defensive otherwise branch maps
to `unknown`.

## 9.3 Context-construction totality

For all runtime inputs that reach an existing Graph deliberate failure, these
algorithms must supply only schema-approved keys and exact native kinds. Context
creation is therefore total over the authorized throw-site domain. Tests must
exercise adversarial strings, binary bytes, large text, string array keys,
gapped and negative integer keys, objects, resources where safely constructible,
and multiple-invalid-input ordering.

Do not catch or replace a Diagnostics construction failure. Such a failure would
mean the closed package-owned schema/value implementation is internally wrong;
it is not an alternate Graph outcome. Do not invent a fallback context, nullable
accessor, secondary exception, new Graph diagnostic ID, suppression, or
simultaneous-failure container.

---

# 10. IMMUTABILITY, SECURITY, AND PERSISTENT-RUNTIME CONTRACT

Every Graph exception context must be:

- constructed synchronously and eagerly at the deliberate failure boundary;
- owned by that one exception;
- detached from the supplied value array and any referenced caller slots;
- immutable through returned field arrays and repeated access;
- schema ordered regardless of value-map insertion order;
- bounded by the existing 16-field and 512-represented-byte contracts;
- free of global, static per-failure, request, user, container, thread-local,
  fiber-local, environment, clock, random, filesystem, network, database,
  logger, tracer, renderer, transport, and AI state; and
- eligible for later policy only according to its fixed field disclosure.

Safe remains eligibility, not automatic disclosure. Protected remains
unavailable to untrusted surfaces. Phase 0Q performs no disclosure and adds no
trusted mode.

Do not capture passwords, credentials, tokens, cookies, authorization values,
session identifiers, keys, connection strings, environment dumps, bodies,
headers, SQL values, provider payloads, file/source contents, serialized
objects, memory dumps, or uncertain secret material. The exact Graph fields in
Sections 8 and 9 are the complete authorized capture set.

Graph keys and kind labels are explicitly schema-approved structural values and
remain Protected. Arbitrary non-list string keys are deliberately omitted.
Context text representation bounds and escapes bytes but does not detect,
redact, or sanitize secrets.

No context value or schema may be stored in a static property, global registry,
singleton, service container, Kernel state, Graph snapshot, or process cache.
No exception may log or render itself.

---

# 11. APPLICATION GRAPH BEHAVIOR PRESERVATION

All pre-Phase-0Q Graph semantics remain exact:

- node and relation inputs must be PHP lists;
- keys and kinds remain opaque byte strings with only non-empty validation;
- nodes retain binary key ordering;
- relations retain binary source/kind/target ordering;
- duplicate node keys and exact duplicate triples remain rejected;
- missing endpoints remain rejected before relation sorting;
- self-relations, cycles, and distinct kinds between endpoints remain allowed;
- lookup and exact selectors retain their current APIs, order, identity, and
  complexity;
- no schema vocabulary, stable ID, metadata, provenance, serialization,
  persistence, mutation, discovery, traversal, reachability, neighbor query,
  generic query, index, cache, hash, diff, or framework integration is added.

Diagnostic context observes an existing failure. It must not alter Graph
success behavior, failure priority, sorting, validation timing, or retained
state.

---

# 12. TEST CONTRACT

## 12.1 New `GraphDiagnosticContextTest`

Create one focused test class with named tests that prove at minimum:

1. all 11 enum cases retain exact case names, IDs, titles, and remediation;
2. `contextSchema()` returns all 11 exact operations, invariants, ordered field
   keys, labels, kinds, disclosures, and required flags from Section 8;
3. `title()`, `remediation()`, `contextSchema()`, and `context()` are the only
   declared catalog methods;
4. every authorized complete value sample constructs successfully;
5. contexts use schema order even when input values are reversed;
6. omission of each optional `first_unexpected_key` is accepted while unknown
   keys, missing required values, and wrong kinds still fail through the
   Diagnostics package marker;
7. manual `InvalidGraphException` construction preserves exact diagnostic
   metadata, message, zero code, previous-cause identity, context, and repeated
   context object identity;
8. the Graph marker and base have the exact Section 7 reflection shapes;
9. the Graph marker remains pairwise unrelated to the other three package
   markers;
10. no Internal `GraphDiagnostic` enum is returned through the Public marker;
11. returned context fields and their arrays cannot mutate exception state; and
12. two exceptions retain distinct context objects with no shared mutable state.

Test expectations may duplicate the exact authorized schemas. Production code
must not read Markdown or tests.

## 12.2 Existing Graph tests

Modify `StructuralValueTest.php` to assert exact context for all three empty
value failures, including field metadata/value/truncation state and unchanged
messages.

Modify `ApplicationGraphTest.php` to exercise and assert exact context for all
eight `ApplicationGraph` failures. Coverage must include:

- numeric unexpected key captured as Protected text;
- arbitrary string unexpected key omitted entirely;
- gapped, negative, and mixed keys with exact first-mismatch behavior;
- schema order independent of context-value insertion order;
- all Section 9.2 value-kind tokens that can be safely produced in a test;
- object values never reveal their class name;
- binary and longer-than-512-byte Graph keys/kinds use the existing exact
  representation and truncation marker;
- relation position is caller-list position before sorting;
- canonical duplicate selection remains deterministic;
- multiple invalid inputs preserve the existing first-failure selection; and
- successful Graph construction and selectors remain unchanged.

Do not use source-text assertions as a substitute for reachable behavior except
for a narrow prohibited-import or exact throw-site inventory proof.

## 12.3 Root architecture test

Modify `tests/Architecture/ApiStatusConventionTest.php` so it proves:

- the production declaration inventory remains exactly 40 with unchanged
  statuses;
- only the Graph marker has the fourth `diagnosticContext()` method;
- its return type is exactly `DiagnosticContext` and it has no parameters;
- the other markers retain exactly three string accessors;
- the Graph base has exactly two private readonly properties and the exact
  four-parameter constructor;
- other package bases retain their Phase 0P shapes;
- all four markers remain pairwise unrelated;
- the catalog split remains 6/11/25/11 and 53 IDs remain globally unique; and
- the Graph production package has no prohibited dependency or reverse import.

## 12.4 Counts

The accepted baseline is 170 tests and 1,908 assertions, including 48 Graph
tests and 273 Graph assertions. Final counts must increase materially through
the behavior above. Require at least:

```text
Graph package: 55 tests and more than 350 assertions
Root suite: 177 tests and more than 2,000 assertions
```

Do not game counts with duplicate or meaningless assertions. Report exact
actual totals for the focused Graph suite, root architecture test, Diagnostics
regression suite, and complete root suite.

The final repository inventories must be exactly:

```text
Production declarations/files: 40
Test PHP files: 70
Syntax entries: 111
PHP CS Fixer files: 110
Package manifests beneath packages/*: 4
```

---

# 13. ADR 0030

Create:

```text
docs/adr/0030-application-graph-diagnostic-context-integration.md
```

Use exact metadata:

```text
Title: ADR 0030: Application Graph Diagnostic Context Integration
Status: Accepted
Date: 2026-08-13
```

Record:

1. why Graph is the first integration slice;
2. why Kernel and DI remain future;
3. exact Graph-to-Diagnostics dependency direction and absence of a cycle;
4. the Public Graph-marker accessor and pre-alpha compatibility impact;
5. package-owned schemas and value-only throw-site construction;
6. exact first-unexpected-key omission rule;
7. exact broad value-kind token vocabulary;
8. eager immutable exception-owned context and same-object repeated access;
9. preservation of IDs, titles, remediation, messages, codes, causes, failure
   priority, and Graph success semantics;
10. why sanctioned Graph context construction is total over reachable values;
11. why no fallback, nullable context, recursive Diagnostics context, or
   simultaneous-failure representation is selected;
12. Safe versus Protected semantics and forbidden-material exclusion;
13. persistent-runtime, allocation, and linear bounded-work implications;
14. rejection of arbitrary context, universal markers, renderers, transports,
   logging, telemetry, source access, and ambient capture; and
15. exact future work, including Kernel dependency-topology resolution, DI
   integration, disclosure policy, renderers, transports, and documentation
   lookup.

Add ADR 0030 to `docs/adr/README.md` with its exact title. Do not rewrite or
retroactively edit ADRs 0026 through 0029.

---

# 14. DOCUMENTATION RECONCILIATION

Documentation must distinguish implemented Graph behavior from the remaining
31 future exception integrations and all future presentation policy.

## 14.1 Root and architecture documents

Update `README.md`, `ARCHITECTURE.md`, `docs/architecture/overview.md`,
`docs/architecture/application-graph.md`, and `docs/doctrine.md` to state:

- Phase 0Q integrates all 11 Graph failures with exact bounded context;
- six Kernel and 25 DI failures still expose only ID/title/remediation;
- Diagnostics construction failures still expose no recursive context;
- Graph package ownership, schemas, messages, and catch boundary remain local;
- Safe and Protected classifications do not render or disclose anything; and
- every later capability remains planned.

Do not describe Phase 0Q as completing the Application Graph, all diagnostics,
or a developer error page.

## 14.2 Runtime, security, and documentation doctrine

Update `docs/architecture/runtime-model.md` to describe eager exception-owned
Graph context with no per-operation or global accumulation.

Update `SECURITY.md` with the exact numeric-key capture/string-key omission
rule, Protected Graph structural values, and absence of disclosure policy.

Update `docs/documentation-standard.md` so current examples may show Graph
context access but may not imply rendering, universal access, or integration for
Kernel/DI.

Update `docs/glossary.md` entries for diagnostic, diagnostic context, schema,
Safe, Protected, and forbidden material to reflect the partial 11/42
integration accurately.

## 14.3 Engineering guides

Update `docs/engineering/error-handling.md` comprehensively:

- current status becomes Phase 0Q partial existing-exception integration;
- Graph marker's exact fourth method is shown;
- other markers retain three methods;
- the 11 Graph schemas and throw-site values are current;
- the exact 42-row normative schema table remains byte-identical;
- the Section 9 algorithms and closed token vocabulary are documented;
- the previous Graph pseudocode context becomes an implemented structural
  example while its combined rendering remains future;
- six Kernel and 25 DI schemas/accessors remain future;
- Diagnostics construction failures remain context-free;
- renderer, trusted-mode, production allowlist, transport, serialization,
  source, documentation lookup, logging, telemetry, incidents, retry,
  cancellation, and cleanup remain future; and
- testing expectations reflect Phase 0Q.

Update `docs/engineering/api-status.md` for the Graph marker's Public context
return type without changing the 40-type/status inventory.

Update `docs/engineering/package-layout.md` for the exact Graph-to-Diagnostics
dependency and continued absence of Kernel/DI integration.

Update `docs/engineering/testing.md` for the exact 70-test-file inventory and
Graph context coverage.

## 14.4 Package READMEs

Update:

- `packages/application-graph/README.md` with the exact public accessor,
  schemas, algorithms, lifecycle, security, calling contract, costs, and
  non-goals;
- `packages/diagnostics/README.md` to identify Graph as the first semantic
  consumer while preserving Diagnostics' lack of Graph ownership or reverse
  dependency;
- `packages/kernel/README.md` to state that its six failures remain
  unintegrated and that the Phase 0Q Graph dependency creates no Kernel
  lifecycle integration; and
- `packages/dependency-injection/README.md` to state that its 25 failures remain
  unintegrated and DI still has no Diagnostics dependency.

No document may call context sanitized, safe to publish, rendered, logged,
serialized, transported, or universally catchable.

---

# 15. PROHIBITED SCOPE

Do not implement or select any of the following:

- Kernel or DI context schemas, accessor, dependency, throw-site values, or
  constructor changes;
- Diagnostics-package context on its own construction failures;
- a universal Kongono exception, diagnostic, context, or catch interface;
- arbitrary metadata/context bags or consumer-selected schemas;
- secret detection, redaction, sanitization, classification inference, or
  allowlists;
- trusted developer mode or production disclosure mode;
- HTML, CSS, templates, Markdown/JSON rendering, development error pages, or
  production error surfaces;
- HTTP status/problem details, CLI formatting/exit codes, transports, adapters,
  serialization, process transfer, localization, or negotiation;
- stack inspection, source files/excerpts, frame grouping, source maps, version
  lookup, documentation lookup, copy actions, or remote calls;
- logging, OpenTelemetry instrumentation, exporters, incident IDs/storage,
  deduplication, or recording policy;
- retry, timeouts, cancellation, cleanup orchestration, recovery, worker restart,
  or simultaneous-failure containers;
- Graph schema vocabulary, stable IDs, metadata, provenance, visibility,
  serialization, compilation, persistence, mutation, traversal, reachability,
  neighbors, generic queries, indexing, caching, hashing, or diffs;
- DI/container/autowiring/PSR-11/lifetimes;
- HTTP/router, ORM/database, agents, extensions, CLI, frontend/Vite, telemetry,
  deployment, or any other later framework capability;
- new third-party dependencies, plugins, suppressions, PHPStan baselines,
  exclusions, ignored errors, warning bypasses, or weakened security policy; or
- staging, commits, refs, remotes, tags, pushes, or history changes.

---

# 16. IMPLEMENTATION SEQUENCE

Use this order:

1. verify all authority, Git, caller-record, hash, mode, stream, and closed-path
   preconditions;
2. record environment versions and run unchanged baseline focused tests plus
   `composer quality`;
3. update the Graph package manifest and regenerate the lock narrowly;
4. implement exact catalog schemas/context construction;
5. update the Graph marker and exception base;
6. integrate exact values at all 11 throw sites without changing messages;
7. add and update focused tests;
8. create ADR 0030;
9. reconcile only the authorized documents;
10. run focused, architecture, dependency, lock, documentation, safety, and
    complete quality validation;
11. prove the exact final Git/path boundary with an empty index; and
12. only then populate the Phase 0Q report and return its final digest outside
    the report.

Stop immediately on any mismatch. Do not continue on a partial implementation
merely because later validation might reveal the same issue.

---

# 17. REQUIRED VALIDATION

## 17.1 Preflight

Record and verify:

- branch, all local branches, HEAD, tree, parent, commit count, tracked count,
  modes, remotes, tags, status counts, exact modified/untracked inventories,
  staged state, and tracked diff statistic;
- all Section 2 sizes and SHA-256 values;
- strict UTF-8/LF/final-newline state for caller records;
- zero non-default alternate data streams;
- exact 40/69/110/109 baseline inventories;
- exact 11 Graph cases and 11 deliberate throw sites;
- exact pre-change marker/base/catalog shapes;
- exact pre-change messages; and
- baseline Graph, Diagnostics, root architecture, PHPStan, style, and canonical
  quality results.

## 17.2 Composer and lock

Run the relevant exact commands, including:

```text
composer validate --no-check-publish
composer --working-dir=packages/application-graph validate --no-check-publish
composer --working-dir=packages/diagnostics validate --no-check-publish
composer check-platform-reqs
composer audit --locked
composer dump-autoload --optimize --strict-psr
composer dump-autoload --no-dev --optimize --strict-psr
composer dump-autoload --optimize --strict-psr
```

Prove development autoload resolves Graph and Diagnostics production/test types,
and optimized no-dev autoload resolves production types but not test types.
Restore normal development autoload before final tests.

Compare pre/post `composer.lock` semantically and prove Section 6's exact delta.
Record before/after lock byte counts and SHA-256. Prove the root content hash and
every third-party package identity/version are unchanged.

If the unchanged locked advisory check alone is blocked by managed network/cache
access, rerun the identical command once with authorized normal access. Do not
change flags, policy, dependencies, or lock content.

## 17.3 Focused PHP validation

Run syntax checks across the exact 111 PHP/configuration entries and run:

```text
vendor/bin/phpunit --configuration phpunit.xml.dist packages/application-graph/tests
vendor/bin/phpunit --configuration phpunit.xml.dist packages/diagnostics/tests
vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
```

Also directly prove:

- exact catalog/schema equality;
- exact throw-site mapping and value derivation;
- all old messages are byte-identical as expressions and runtime strings;
- same-object repeated context access;
- value-array and reference detachment;
- text bounding/escaping/truncation inherited from Diagnostics;
- first-unexpected-key capture/omission;
- closed kind tokens and absence of class names;
- failure priority and Graph state/success behavior unchanged;
- marker separation and exact base/interface reflection shapes; and
- no prohibited imports, APIs, state, or dependencies.

## 17.4 Documentation, safety, and repository integrity

Run deterministic checks for:

- all local Markdown links;
- strict UTF-8 without BOM;
- LF-only line endings and exactly one final LF;
- no trailing whitespace, NUL, replacement characters, mojibake markers, or
  conflict markers;
- no credentials, tokens, private material, environment dumps, or forbidden
  captured values;
- no non-default alternate data streams;
- no zero-byte Phase 0Q implementation artifact, excluding the caller report
  only before authorized population;
- exact 42 table-row preservation and joined-row hash;
- current/future wording accuracy; and
- `git diff --check` plus empty `git diff --cached`.

## 17.5 Canonical quality

Run the unchanged:

```text
composer quality
```

It must pass all stages with:

- only authorized Composer warning categories;
- zero security advisories;
- platform requirements satisfied;
- 0 of 110 style files fixable;
- zero PHPStan errors; and
- at least 177 tests and more than 2,000 meaningful assertions.

## 17.6 Expected final Git state

Before report population, the exact final state must be:

```text
Branch: main
Local branches: 1
HEAD/tree/parent/history: unchanged
Tracked files in HEAD: 214
Tracked modifications: 39
Untracked paths: 36
Total status paths: 75
Staged paths: 0
Remotes: 0
Tags: 0
Tracked modes: unchanged
Non-default alternate data streams: 0
```

The 39 modified tracked paths are the original 28 plus exactly these 11 newly
modified tracked paths:

```text
docs/architecture/application-graph.md
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
```

The 36 untracked paths are the original 32 plus exactly:

```text
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/dev_plan/kongono_phase_0Q_codex_directive.md
docs/task reports/Phase_0Q.md
packages/application-graph/tests/Unit/GraphDiagnosticContextTest.php
```

After every non-report gate passes, populate the existing report placeholder.
Its population must not change the 36-path count. Re-prove its text integrity,
untracked/unstaged state, absence from `HEAD`, and final byte count/hash.

---

# 18. STRICT ACCEPTANCE CRITERIA

## Repository and dependency boundary

- [ ] Every Section 2 precondition and caller record matches exactly.
- [ ] Only Section 4 files are created and only Section 5 files are modified.
- [ ] HEAD, tree, parent, history, refs, index, modes, remotes, and tags remain
  unchanged.
- [ ] Final state is exactly 39 modified, 36 untracked, 75 total, zero staged.
- [ ] Graph adds exactly one local Diagnostics dependency with no reverse edge or
  cycle.
- [ ] Lock delta is exact and no third-party dependency or policy changes.

## API and schema

- [ ] The Graph marker adds exactly `diagnosticContext(): DiagnosticContext`.
- [ ] Other markers retain exactly three string methods and remain unrelated.
- [ ] Graph base has the exact two properties, constructor, and five methods.
- [ ] All 11 cases, IDs, titles, and remediation strings remain exact.
- [ ] All 11 schemas exactly match Section 8.
- [ ] Catalog owns schemas; throw sites supply only approved values.
- [ ] Context accessor returns the same immutable exception-owned object.
- [ ] No Internal enum leaks through a Public signature.

## Throw sites, behavior, and security

- [ ] All 11 existing throw sites capture the exact Section 9 values.
- [ ] Numeric unexpected keys are captured exactly; arbitrary string keys are
  omitted without retention.
- [ ] Value-kind tokens are closed, deterministic, and never expose class names.
- [ ] Every pre-Phase-0Q message, code, cause, diagnostic mapping, failure order,
  and state guarantee is unchanged.
- [ ] Successful Graph construction, lookup, selectors, ordering, identity, and
  complexity contracts are unchanged.
- [ ] Context creation is eager, detached, bounded, schema ordered, and free of
  ambient/global/per-failure retained state.
- [ ] No forbidden material, renderer, disclosure, transport, logging, telemetry,
  source, documentation lookup, or future capability is introduced.
- [ ] Kernel, DI, and Diagnostics failures remain unintegrated exactly as scoped.

## Tests and documentation

- [ ] Exact 40/70/111/110/4 inventories are proved.
- [ ] Graph suite has at least 55 tests and more than 350 assertions.
- [ ] Root suite has at least 177 tests and more than 2,000 assertions.
- [ ] Diagnostics regression and root architecture suites pass.
- [ ] Composer validation, lock audit, platform, autoload, syntax, style,
  PHPStan, focused suites, and `composer quality` all pass.
- [ ] ADR 0030 is Accepted and records every required decision.
- [ ] The 42-row table and joined-row hash remain exact.
- [ ] All authorized documents distinguish 11 implemented Graph contexts from 31
  future Kernel/DI contexts and all future presentation policy.
- [ ] Links, text integrity, stream, secret, prohibited-scope, and diff checks
  pass.

## Report

- [ ] The Phase 0Q report was zero bytes until all non-report gates passed.
- [ ] It follows the exact 16-section contract in Section 19.
- [ ] It reports verified results, exact counts/hashes/inventories, warnings, and
  any deviations plainly.
- [ ] It is nonzero, strict UTF-8, LF-only, exactly one-final-LF terminated,
  untracked, unstaged, and absent from `HEAD`.
- [ ] Its final byte count and SHA-256 are returned separately.
- [ ] Its next step is exact.

Every item must pass. Partial implementation is not acceptance.

---

# 19. STANDARD EXECUTOR COMPLETION REPORT

Write `docs/task reports/Phase_0Q.md` with exactly these headings, once each and
in order:

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

- exact created and modified inventories;
- exact pre/post Git and caller-record evidence;
- exact manifest and lock delta, before/after bytes/hashes, root content hash,
  and third-party version audit;
- exact Graph marker/base/catalog reflection shapes;
- exact 11 schema and throw-site-value proof;
- message/cause/code/failure-order preservation;
- first-unexpected-key and value-kind algorithm evidence;
- immutability, detachment, bounds, disclosure, forbidden-material, persistent-
  runtime, and no-ambient-state evidence;
- exact 40/70/111/110/4 inventories;
- focused and complete test/assertion totals;
- every validation result and accepted warning;
- documentation/current-versus-future review;
- exact final 39/36/75/0 Git state; and
- final report digest returned outside the report.

Section 16 must say exactly:

```text
Await architect review before Phase 0R.
```

---

# 20. FINAL EXECUTOR INSTRUCTION

Implement only the 11-diagnostic Application Graph context integration. Preserve
all existing Graph identity, messages, behavior, selectors, ordering, and package
ownership. Keep Kernel, DI, and Diagnostics construction failures unintegrated.
Do not render, disclose, log, serialize, transport, inspect source, or implement
the developer error page or any later capability. Preserve the exact repository
boundary, validate every contract, populate the report only after success, and
stop before Phase 0R.
