# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0J

## Exact Whole-Snapshot Kind Selection

## Mission

Add the smallest useful kind-selection boundary to Kongono's existing immutable Application Graph.

Implement only two Experimental methods on `ApplicationGraph`:

```php
/** @return list<Node> */
public function nodesOfKind(NodeKind $kind): array;

/** @return list<Relation> */
public function relationsOfKind(RelationKind $kind): array;
```

These methods select stored nodes or relations by exact open kind-label value. They do not define a kind vocabulary, combine filters, introduce traversal or a general query API, add indexes, mutate the snapshot, or select Graph schema/identity/serialization/framework integration.

Phase 0J also adds focused production tests, current documentation, and ADR 0025.

Do not begin Phase 0K.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the implementation executor, not the architect.

Before editing, read completely:

1. `AGENTS.md`;
2. all root governance and doctrine documents;
3. every current architecture and engineering document;
4. ADRs 0001 through 0024 and the ADR index;
5. all directives and reports through Phase 0I.2;
6. every package manifest and README;
7. all Application Graph production source and tests;
8. root tooling configuration and architecture tests;
9. this Phase 0J directive.

All previously locked Kongono doctrine remains authoritative.

## Exact supersession

Phase 0D deferred query APIs. Phase 0H authorized only exact incoming/outgoing direct-relation selection and continued to defer kind filtering. Phase 0J supersedes those prohibitions only for the two whole-snapshot kind selectors defined here.

It does not authorize:

- endpoint-and-kind combined selection;
- a generic query or filter method;
- multiple kinds, optional filters, predicates, callbacks, criteria, or a DSL;
- neighbor-node selection or traversal;
- any built-in or reserved kind vocabulary;
- indexes, caches, memoization, preprocessing, or additional retained state;
- any other public method.

Every other Phase 0D, Phase 0H, and Phase 0F prohibition remains in force.

## Mandatory stop rule

If behavior, equality, ordering, API shape, test scope, documentation, repository state, or authority is unclear or conflicting:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact conflict, why it blocks safe progress, viable options, tradeoffs, and a labeled recommendation. Do not implement the recommendation without architect approval.

Do not infer semantics from another graph library, collection API, database, framework, or naming convention.

---

# 2. ACCEPTED BASELINE AND CALLER RECORDS

Phase 0I.2 established:

```text
Branch: main
Only local branch: main
HEAD: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD tree: 9d3326fae7f7e7b8c75f3309af0cb5b5a6f81e8d
HEAD parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD parent count: 1
Commit count: 3
HEAD subject: Establish Kongono framework baseline through Phase 0H
Tracked files: 187
Tracked modes: 187 x 100644
Remotes: 0
Tags: 0
Index: clean
Tracked worktree: clean
```

Before Phase 0J caller setup, one untracked accepted record exists:

```text
docs/task reports/Phase_0I_2_correction.md
20,584 bytes
SHA-256 E78EADAA4FEDC30D3867E00AB636AAC7DF3FD46E3C873F0158150C137FD8D886
```

It is the accepted Phase 0I.2 completion report. Preserve it byte-for-byte. It contains exactly 22 required top-level sections in order, valid UTF-8, LF endings, and a final newline. The archived Markdown copy need not retain the executor response's transport fence.

The caller will add:

```text
docs/dev_plan/kongono_phase_0J_codex_directive.md
docs/task reports/Phase_0J.md
```

The directive must be a non-empty exact caller-supplied copy of this artifact. Read it fully, record its size and SHA-256, and do not edit, stage, commit, rename, or delete it.

The Phase 0J report must be a zero-byte caller-managed placeholder. Do not populate, edit, stage, commit, rename, or delete it. Return the report in the response.

If the placeholder is non-empty before execution, stop.

Preflight status after caller setup must contain exactly these three untracked paths and no tracked or staged change:

```text
docs/dev_plan/kongono_phase_0J_codex_directive.md
docs/task reports/Phase_0I_2_correction.md
docs/task reports/Phase_0J.md
```

## Git restrictions

Phase 0J authorizes worktree content changes only.

Do not stage, commit, amend, reset, restore, checkout, clean, stash, rebase, branch, tag, add a remote, push, or change Git configuration.

At completion, HEAD, tree, all three commits, branch set, index, remotes, and tags remain unchanged.

---

# 3. LOCKED ARCHITECTURAL CONTEXT

Preserve these facts:

1. Kongono remains PHP 8.5+ and pre-alpha.
2. The Application Graph remains an immutable, language-neutral structural snapshot, not the complete authoritative Graph.
3. `NodeKey` remains opaque, graph-local, exact, non-empty, and not a stable identifier.
4. `NodeKind` and `RelationKind` remain separate open exact non-empty labels.
5. No label is built-in, reserved, normalized, parsed, aliased, hierarchical, or given framework-wide meaning.
6. A node remains exactly one key and one node kind.
7. A relation remains exactly source key, relation kind, and target key.
8. Construction validation, canonical ordering, exact node lookup, and Phase 0H direct-relation selection remain unchanged.
9. Objects remain final, readonly, instance-owned, and free of I/O or ambient mutable state.
10. The Graph package uses Kernel only for `Api` and `ApiStatus` metadata.
11. No Dependency Injection/Application Graph integration exists.
12. API status is compatibility metadata, not Graph metadata, authorization, or visibility filtering.

Phase 0J changes only the read surface of `ApplicationGraph`.

---

# 4. EXACT PUBLIC API

Modify only `Kongono\ApplicationGraph\Model\ApplicationGraph` in production source.

Add exactly:

```php
/** @return list<Node> */
public function nodesOfKind(NodeKind $kind): array;

/** @return list<Relation> */
public function relationsOfKind(RelationKind $kind): array;
```

The complete declaration-owned public method inventory, sorted by method name for verification, becomes:

```text
__construct
hasNode
incomingRelations
node
nodes
nodesOfKind
outgoingRelations
relations
relationsOfKind
```

Do not add an interface, trait, class, enum, exception, result wrapper, iterator, generator, collection, overload, optional parameter, array-of-kinds parameter, endpoint parameter, callback, or generic query method.

`ApplicationGraph` remains `final`, `readonly`, and Experimental. No production status changes and no new production declaration are authorized. Repository production inventory remains exactly 27.

---

# 5. EXACT KIND-MATCHING SEMANTICS

`nodesOfKind($kind)` selects every stored node whose node-kind value exactly equals `$kind->value()`.

`relationsOfKind($kind)` selects every stored relation whose relation-kind value exactly equals `$kind->value()`.

Use exact string equality:

```php
$node->kind()->value() === $kind->value()
$relation->kind()->value() === $kind->value()
```

Requirements:

- match kind value, not `NodeKind` or `RelationKind` object identity;
- do not trim, normalize, case-fold, decode, parse, split, join, hash, coerce, alias, or apply locale/Unicode behavior;
- embedded NUL/control-byte, case-related, and prefix-related non-empty values remain distinct;
- `NodeKind` and `RelationKind` remain different parameter types and semantic domains;
- a label used as both a node kind and relation kind creates no cross-type relationship;
- node keys and relation endpoints never participate in kind matching;
- return the exact stored immutable objects, not clones or reconstructions.

These selectors inspect only open label values already present in the snapshot. They do not define or validate a domain vocabulary.

---

# 6. EMPTY AND ABSENT-KIND RESULTS

Both selectors are total for every successfully constructed kind object.

Return an empty list when:

- the graph is empty;
- no stored node has the supplied node-kind value;
- no stored relation has the supplied relation-kind value.

Do not throw `InvalidGraphException` for an absent kind. Do not add a kind registry, existence method, special sentinel, or status value.

An empty result means only that no stored object matched. It establishes no registered-vocabulary concept.

No new deliberate failure category is authorized.

---

# 7. ORDERING AND RESULT OWNERSHIP

Preserve the exact canonical order already stored by `nodes()` and `relations()`.

Do not re-sort selected results.

Therefore:

- `nodesOfKind()` results retain global binary node-key order;
- `relationsOfKind()` results retain global source-kind-target relation order;
- because all selected relation kinds are value-equal, their observable order follows source then target under the existing canonical order;
- constructor input order cannot affect selector output order;
- repeated calls return value-equivalent lists in the same order.

Each call returns a fresh zero-based list detached from internal array storage. Mutating the returned array must not alter the graph, `nodes()`, `relations()`, or later selector results.

Elements are the same immutable `Node` or `Relation` objects stored in the snapshot. Do not clone them.

---

# 8. IMPLEMENTATION AND PERFORMANCE BOUNDARY

Implement each selector as a direct read-only scan of its existing canonical list.

For every call:

1. create a fresh local result list;
2. scan every stored node or relation in existing order;
3. append only exact kind-value matches;
4. return the result list.

Do not use a key-preserving filtering operation that can violate the `list` contract.

`ApplicationGraph` continues to contain exactly two private non-static readonly properties:

```text
nodes
relations
```

Do not add kind indexes, maps, grouped collections, memoized results, caches, weak maps, object storage, static state, globals, lazy mutation, or constructor preprocessing.

Document this non-benchmark performance contract:

- `nodesOfKind()` performs `O(N)` exact comparisons for `N` nodes;
- `relationsOfKind()` performs `O(R)` exact comparisons for `R` relations;
- result allocation is `O(k)` for `k` matches;
- no kind index or query cache is retained;
- no performance budget or optimization promise is selected.

Do not change construction, validation order, exceptions, existing selectors, lookup, canonical sorting, property inventory, or model representation.

---

# 9. EXCEPTIONS, API STATUS, AND PACKAGE BOUNDARIES

No new exception type or selector throw site is authorized.

Existing kind constructors continue to reject only the empty string through the Graph exception family. The new selectors receive already valid kind objects and return lists without deliberate exceptions.

Do not change any exception relationship, package relationship, dependency, or production API status.

The root API-status architecture test remains unchanged because production inventory remains exactly 27.

Production source continues to depend on Kernel only for `Api` and `ApiStatus`. No source or manifest relationship with Dependency Injection is authorized.

---

# 10. REQUIRED PRODUCTION TESTS

Modify only the existing:

```text
packages/application-graph/tests/Unit/ApplicationGraphTest.php
```

Do not create another test file or fixture.

Cover at minimum:

1. both selectors return empty lists for an empty graph;
2. a valid kind value with no matches returns an empty list without exception;
3. a distinct kind object with the same value matches;
4. node-kind selection includes all and only exact matching nodes;
5. relation-kind selection includes all and only exact matching relations;
6. node and relation kind domains do not cross-match merely because their string values are equal;
7. case-related, prefix-related, embedded-NUL, and control-byte kind values remain distinct;
8. node-kind results preserve global canonical node-key order;
9. relation-kind results preserve global canonical source-kind-target order;
10. selector order is independent of constructor input order;
11. returned nodes are the same stored immutable `Node` objects;
12. returned relations are the same stored immutable `Relation` objects;
13. mutating a returned node-kind result array does not affect the graph or later calls;
14. mutating a returned relation-kind result array does not affect the graph or later calls;
15. repeated calls are deterministic and retain no mutable query state;
16. existing `outgoingRelations()` and `incomingRelations()` continue to ignore relation kind and return all endpoint matches;
17. `ApplicationGraph` remains final and readonly with exactly private non-static readonly `nodes` and `relations` properties;
18. the complete declaration-owned public method inventory is exactly Section 4;
19. each new selector has exactly one required parameter of the exact kind class and native `array` return type;
20. all existing construction, validation, lookup, direct-relation selection, ordering, equality, immutability, and exception tests remain passing.

Use identity assertions only to prove returned elements are the stored immutable objects. Never use object identity for kind matching.

Do not inspect a new index or cache because none is authorized. Reflection is permitted only for the exact structural assertions above.

No skipped, incomplete, warning-producing, risky, output-producing, order-dependent, time-dependent, random, filesystem, network, or environment-dependent test is allowed.

---

# 11. DOCUMENTATION AND ADR 0025

Documentation must distinguish exact whole-snapshot kind selection from a built-in kind vocabulary, combined filtering, and a general query system.

## Required documentation updates

Update only the authorized documentation files in Section 12 to record:

- Phase 0J current reality;
- the exact two method names and signatures;
- exact kind-value equality rather than object identity;
- empty results for absent matches;
- separate node-kind and relation-kind domains;
- preservation of canonical global order;
- same stored objects in detached result lists;
- `O(N)` and `O(R)` scans, `O(k)` result allocation, and no retained index/cache;
- open labels without a framework vocabulary or schema;
- no combined endpoint-and-kind selector;
- no traversal, neighbors, predicates, multiple-kind queries, or query DSL;
- every later Graph and framework boundary that remains unresolved.

Do not describe the selectors as search, traversal, discovery, indexing, schema validation, authorization filtering, or stable API behavior.

## ADR 0025

Create exactly:

```text
docs/adr/0025-exact-whole-snapshot-kind-selection.md
```

Use exactly:

```text
ADR 0025: Exact Whole-Snapshot Kind Selection
Status: Accepted
Date: 2026-08-13
```

Record:

1. why exact whole-snapshot kind selection is the next minimal read boundary;
2. the exact two methods and parameter types;
3. exact value equality and separate kind domains;
4. empty absent-kind results;
5. canonical-order preservation;
6. detached arrays containing the stored immutable objects;
7. direct `O(N)`/`O(R)` scanning and `O(k)` result allocation;
8. no kind vocabulary, registry, schema, index, cache, exception, type, or status change;
9. narrow supersession of earlier kind-filter prohibitions only for these two methods;
10. explicit rejection or deferment of combined filters, multiple kinds, predicates, generic queries, neighbors, traversal, schema/identity, serialization, persistence, mutation, discovery/compilation, and framework integration;
11. every materially open decision preserved by this directive.

Update ADR 0024 only with a short later-decision note referencing ADR 0025. Do not rewrite its historical Phase 0H rationale. Add ADR 0025 to the ADR index. Do not create ADR 0026.

---

# 12. AUTHORIZED FILE INVENTORY

Create exactly one implementation-owned file:

```text
docs/adr/0025-exact-whole-snapshot-kind-selection.md
```

Modify only:

```text
README.md
ARCHITECTURE.md
docs/adr/0024-exact-direct-relation-selection.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/doctrine.md
docs/engineering/application-graph-structure.md
packages/application-graph/README.md
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/tests/Unit/ApplicationGraphTest.php
```

Exactly 11 tracked files may be modified and exactly one untracked implementation file may be created.

The three caller-managed records from Section 2 remain untracked and untouched. At completion, status therefore contains exactly:

- 11 modified tracked paths;
- one new ADR;
- three caller-managed paths;
- 15 total status paths.

Do not modify Composer manifests, `composer.lock`, tooling configuration, architecture-status tests, Kernel files, Dependency Injection files, another Graph model file, historical directives/reports, or any other path.

If accurate implementation requires another file, stop and request scope expansion before editing it.

---

# 13. COMPOSER, DEPENDENCY, AND TOOLING IMMUTABILITY

Phase 0J adds no package, dependency, autoload path, source path, test suite, script, plugin, or lockfile change.

Preserve byte-for-byte:

```text
composer.json
composer.lock
packages/application-graph/composer.json
packages/dependency-injection/composer.json
packages/kernel/composer.json
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

Do not run Composer update. Do not add an external graph, query, or collection dependency. Do not authorize a plugin or use an undeclared transitive package.

The existing quality dispatch order, fail-closed dependency-security policy, PHPStan maximum/strict rules, PHPUnit failure settings, and style rules remain unchanged.

---

# 14. EXPLICITLY PROHIBITED SCOPE

Do not implement or scaffold:

## Additional query surface

- endpoint-and-kind combined methods;
- multiple-kind or any/all-kind selection;
- optional filters, null sentinels, wildcards, globbing, regex, or partial matching;
- predicates, callbacks, closures, criteria, specifications, expressions, query objects, or a query DSL;
- neighbor-node APIs;
- descendants, ancestors, reachability, paths, shortest paths, cycles, topological order, BFS, DFS, recursion, or depth limits;
- grouping, aggregation, counts, pagination, cursors, streaming, generators, iterators, or collections;
- reverse or synthesized relations, projections, views, or subgraphs;
- indexes, grouped maps, caches, memoization, query plans, or optimization layers.

## Kind or Graph model expansion

- built-in/reserved node kinds or relation kinds;
- kind enums, constants, registries, hierarchies, aliases, compatibility rules, or validation vocabularies;
- stable Graph IDs or Documentation IDs;
- URI/UUID/hash/fingerprint identity;
- metadata, payloads, provenance, visibility, security filtering, or authorization;
- schema/versioning, serialization, persistence, storage, diffs, merging, mutation, or builders;
- source discovery, reflection frontends, PHP attributes, IR compilation, or generated Graphs.

## Framework integration and later capabilities

- Dependency Injection/Graph relationships;
- Kernel/Graph lifecycle relationships;
- modules, profiles, capabilities, bootstrapping, or composition roots;
- service scopes, cleanup, context, cancellation, or runtime adapters;
- PSR-11 or advanced DI;
- HTTP, routing, middleware, data, ORM, security, events, queues, workflows, agents, extensions, CLI, frontend, logging, telemetry, CI/CD, release, version, license, publishing, or distribution work.

Do not stage, commit, tag, add a remote, push, or begin Phase 0K.

---

# 15. REQUIRED EXECUTION AND VALIDATION SEQUENCE

Follow this sequence exactly. A failed required check is a blocker, not permission to change scope or weaken a rule.

## 15.1 Preflight and immutable baseline

Before editing implementation-owned files:

1. read every authority source required by Section 1;
2. verify sole branch `main`, no remotes/tags, and all exact commit facts from Section 2;
3. verify 187 tracked files, all mode `100644`;
4. verify the index/cached diff and tracked worktree are clean;
5. verify status contains only the three expected caller records;
6. verify the accepted Phase 0I.2 report size, SHA-256, structure, encoding, and final newline;
7. verify the Phase 0J directive is populated and its report is zero bytes;
8. record PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
9. record SHA-256 hashes for every file Section 13 requires to remain byte-identical;
10. run the existing canonical full quality command before editing.

The pre-change quality baseline must pass and include exactly:

```text
119 tests
513 assertions
0 PHPStan errors
89 style-checked files with no required fix
0 security advisories
```

Composer warnings may remain only in the existing categories: missing license and authorized unreleased local `@dev`/`*` constraints. A new warning category is a blocker.

If a restricted environment blocks only the real advisory source or an existing ignored tool cache, rerun the unchanged read-only check with authorized access. Never report a network failure as a successful security audit.

If any repository or quality fact differs materially, stop and report it. Do not clean, restore, normalize, stage, or repair caller state.

## 15.2 Implementation

After preflight passes:

1. add only the two methods from Section 4 to `ApplicationGraph`;
2. implement their exact semantics using fresh local lists and direct scans;
3. extend only the existing `ApplicationGraphTest.php`;
4. create ADR 0025 and make only the authorized documentation reconciliations;
5. format only authorized changed PHP files if needed.

Do not perform opportunistic cleanup, method reordering unrelated to the new API, helper extraction, refactoring, renaming, or unrelated wording changes.

## 15.3 Focused behavioral verification

Run the existing focused `ApplicationGraphTest.php` target and prove every Section 10 behavior.

Also execute a read-only direct probe through Composer autoload that constructs a deliberately noncanonically ordered graph containing:

- at least four nodes and four relations;
- matching and nonmatching node kinds;
- matching and nonmatching relation kinds;
- one kind label used in both kind domains;
- case-related, prefix-related, or control-byte labels;
- distinct-but-value-equal query kind objects.

The probe must prove exact matching, domain separation, canonical order, empty absent results, and returned object identity. It must not create or modify a repository file.

## 15.4 Structural and static verification

Use reflection or an equivalently direct executable check to prove:

- `ApplicationGraph` remains final and readonly;
- declared instance properties remain exactly private non-static readonly `nodes` and `relations`;
- the public declaration-owned method inventory, sorted by name, equals Section 4;
- `nodesOfKind()` has exactly one required `NodeKind` parameter and native `array` return type;
- `relationsOfKind()` has exactly one required `RelationKind` parameter and native `array` return type;
- all 27 production types retain their prior status category;
- no production PHP type was added.

Run PHP syntax checks over all first-party PHP files, focused PHPUnit, PHPStan, and PHP CS Fixer dry-run. All must pass.

Directly inspect production source/dependencies to prove no kind vocabulary, combined filtering, query abstraction, index, cache, additional state, traversal, schema/identity, serializer, persistence, Kernel/DI integration, or I/O behavior was added.

## 15.5 Full quality and dependency verification

Run canonical `composer quality`. It must pass in its locked order.

Final PHPUnit totals must be strictly greater than 119 tests and 513 assertions because Phase 0J adds production behavior tests. Do not require or invent an exact total; report what actually ran.

Also verify independently:

- Composer root and package validation succeeds with only authorized warning categories;
- the locked audit reports zero advisories;
- platform requirements pass;
- optimized strict PSR autoload generation passes;
- PHPStan reports zero errors;
- PHP CS Fixer reports no file requiring a fix;
- `composer.lock` and its dependency graph remain unchanged;
- every Section 13 file retains its preflight SHA-256 hash.

Do not inflate test/assertion counts artificially.

## 15.6 Documentation, file-safety, and prohibited-scope audit

Verify:

- ADR 0025 has the exact title/status/date and all required decisions;
- ADR 0024 received only its short later-decision note;
- the ADR index links ADR 0025;
- every authorized current document describes Phase 0J consistently;
- every local Markdown link in an authorized changed document resolves;
- no historical directive/report was changed;
- no file outside Section 12 changed or appeared;
- all non-empty changed text files are strict UTF-8 without BOM, NUL, replacement characters, or mojibake;
- changed text files use LF, have a final newline, no trailing whitespace, and no tab indentation;
- no implementation-owned file is zero bytes;
- the zero-byte Phase 0J report remains untouched;
- no placeholder, TODO, FIXME, skipped/risky test, suppression, baseline, generated artifact, editor artifact, or temporary file was added;
- every prohibited item in Section 14 remains absent.

## 15.7 Final Git proof

Rerun and report:

- current branch and all local branches;
- HEAD, tree, parent, commit count, subject, tracked count, and modes;
- remotes and tags;
- exact porcelain status;
- ordinary diff summary and name/status;
- cached diff summary and name/status;
- `git diff --check`;
- exact status-path classification and count.

Required final immutable state:

```text
branch: main
only local branch: main
HEAD: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
tree: 9d3326fae7f7e7b8c75f3309af0cb5b5a6f81e8d
parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
commit count: 3
tracked files: 187
tracked modes: all 100644
remotes: none
tags: none
cached diff: empty
status paths: exactly 15, classified as Section 12 requires
```

The ordinary diff may contain only the 11 authorized tracked-file modifications. The untracked set may contain only ADR 0025 and the three caller records. Do not alter caller records or stage anything.

---

# 16. STRICT ACCEPTANCE CRITERIA

Phase 0J is complete only if every item below is true.

## Repository safety

- [ ] All preflight checks matched the accepted Phase 0I.2 baseline.
- [ ] `main` remains the only branch and all immutable commit facts remain exact.
- [ ] No remote or tag exists.
- [ ] The index and cached diff remain empty.
- [ ] Exactly 15 status paths exist with the Section 12 classification.
- [ ] All three caller records remain untouched.
- [ ] No unauthorized file was created, changed, removed, renamed, staged, or committed.

## Public API and behavior

- [ ] `ApplicationGraph` has exactly the two authorized new public methods and no other API change.
- [ ] Node selection uses exact `NodeKind::value()` equality.
- [ ] Relation selection uses exact `RelationKind::value()` equality.
- [ ] Node and relation kind domains remain separate.
- [ ] Empty and absent-kind queries return `[]` without exception.
- [ ] Results preserve the existing global canonical order.
- [ ] Results contain the same stored immutable objects in fresh detached lists.
- [ ] Implementations are direct `O(N)`/`O(R)` scans with `O(k)` allocation and no retained state.
- [ ] Existing construction, lookup, direct-relation selection, ordering, immutability, and exception behavior remains unchanged.

## Architecture and scope

- [ ] `ApplicationGraph` remains final/readonly with exactly its two original private properties.
- [ ] All 27 production types retain existing API statuses.
- [ ] No production type, exception, package, dependency, autoload rule, tool rule, or lockfile change was added.
- [ ] Earlier prohibitions are superseded only for the two exact whole-snapshot kind selectors.
- [ ] No kind vocabulary, combined query, index, traversal, schema, integration, or later capability exists.

## Tests and quality

- [ ] Every Section 10 behavior exists in the existing focused test and passes.
- [ ] Focused, direct-probe, reflection, syntax, static, and source-boundary checks pass.
- [ ] Canonical full quality passes in locked order.
- [ ] Final PHPUnit totals exceed 119 tests and 513 assertions.
- [ ] PHPStan reports zero errors and style validation is clean.
- [ ] Composer validation/platform checks pass under the locked warning policy.
- [ ] The security audit reports zero advisories.
- [ ] Optimized strict PSR autoload generation passes.

## Documentation and report

- [ ] ADR 0025 is Accepted, dated 2026-08-13, indexed, and complete.
- [ ] ADR 0024 and all authorized current documents are narrowly reconciled without rewriting history.
- [ ] File-safety, link, prohibited-scope, and final Git audits pass.
- [ ] The Phase 0J completion report follows Section 17 exactly.

Any unchecked item means `Blocked`, not `Complete`.

---

# 17. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly one report. Put the entire report inside one fenced `text` block so the caller can copy it verbatim to:

```text
docs/task reports/Phase_0J.md
```

Use exactly these 22 numbered top-level sections, in this order, with no prose before or after the fence:

```text
PHASE 0J TASK COMPLETION REPORT

1. STATUS
- Complete or Blocked.

2. SUMMARY
- Exact implemented boundary and outcome.

3. BASELINE OBSERVED
- Tool versions, Git facts, quality totals, warnings, and all caller-record sizes/hashes.

4. FILES CREATED
- Exact path and purpose.

5. FILES MODIFIED
- Exact paths and concise purpose.

6. FILES MOVED OR REMOVED
- Must be None.

7. PUBLIC API AND STRUCTURAL SHAPE
- Exact signatures, method/property inventories, final/readonly state, and type/status inventory.

8. KIND-SELECTION SEMANTICS
- Exact equality, domain separation, absent results, open-label meaning, and exceptions.

9. ORDERING, OWNERSHIP, AND PERFORMANCE
- Canonical order, same objects, detached lists, O(N)/O(R)/O(k), and absence of retained state.

10. EXISTING GRAPH INVARIANTS
- Evidence all behavior through Phase 0H outside the explicit supersession remains unchanged.

11. TEST RESULTS
- Focused cases, direct probe, reflection checks, final PHPUnit totals, and comparison with 119/513.

12. COMPOSER, DEPENDENCY, AND SECURITY
- Validation warnings, audit, platform, optimized strict PSR, lock/dependency immutability, and config hashes.

13. STATIC ANALYSIS, STYLE, AND FULL QUALITY
- Commands/outcomes, PHPStan errors, style count, and canonical quality order/result.

14. DOCUMENTATION AND ADR
- ADR 0025, ADR 0024/index update, current-document reconciliation, and link results.

15. FILE SAFETY AND PROHIBITED-SCOPE AUDIT
- Encoding/line/file inventory and explicit absence of every later capability.

16. COMMANDS RUN
- Every command in execution order with exit code and concise result.

17. ARCHITECTURAL DECISIONS AND DEVIATIONS
- Authorized decisions; deviations must be None for Complete.

18. QUESTIONS OR BLOCKERS
- Must be None for Complete.

19. FINAL GIT STATUS
- Immutable Git facts, exact status/diff/cached-diff/diff-check evidence, and 15-path classification.

20. ACCEPTANCE CHECKLIST
- Reproduce every Section 16 criterion with PASS/FAIL evidence.

21. REPORT STRUCTURE SELF-CHECK
- Confirm exactly 22 sections, correct order, one outer fence, and no content outside it.

22. NEXT STEP
Await architect review before Phase 0K.
```

For Section 22, state only:

> Await architect review before Phase 0K.

Do not write to the zero-byte Phase 0J report placeholder. Return the report for the caller to copy. Do not replace evidence with “all passed,” omit warning text, or claim completion when a required check did not run.

---

# 18. FINAL EXECUTOR INSTRUCTION

Implement only exact whole-snapshot node-kind and relation-kind selection. Preserve every prior Kongono doctrine, security boundary, immutable Git fact, caller record, and unresolved later decision. Make no assumption outside this directive.

If an ambiguity, baseline mismatch, unavailable required validation, or need for expanded scope appears, stop and return a `Blocked` report under Section 17.

Do not begin Phase 0K.
