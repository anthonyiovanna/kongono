# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0H

## Exact Direct-Relation Selection

## Mission

Add the smallest useful read-only relation-selection boundary to Kongono's existing immutable Application Graph.

Implement only two methods on `ApplicationGraph`:

```php
/** @return list<Relation> */
public function outgoingRelations(NodeKey $source): array;

/** @return list<Relation> */
public function incomingRelations(NodeKey $target): array;
```

These methods select immediate stored relations by exact graph-local endpoint-key value. They do not introduce traversal, a query language, indexes, mutation, domain schema, stable identity, discovery, compilation, or Kernel/Dependency Injection integration.

Phase 0H also adds focused production tests, accurate documentation, and ADR 0024.

Do not begin Phase 0I.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the implementation executor, not the architect.

Before editing, read completely:

1. `AGENTS.md`;
2. all root governance and doctrine documents;
3. every architecture and engineering document;
4. ADRs 0001 through 0023 and the ADR index;
5. all accepted directives and reports through Phase 0G;
6. every package manifest and README;
7. all Application Graph source and tests;
8. root tooling configuration and architecture tests;
9. this Phase 0H directive.

All previously locked doctrine remains authoritative.

## Explicit supersession

Phase 0D correctly prohibited query and traversal APIs for its structural-snapshot boundary. Phase 0H supersedes that prohibition only for the two exact selectors defined here.

It does not authorize:

- a generic query API;
- traversal beyond one stored relation selection;
- neighbor-node selection;
- reachability, paths, recursion, breadth-first or depth-first search;
- relation-kind filtering;
- predicates, callbacks, criteria, or a query DSL;
- indexes, caches, memoization, precomputed adjacency, or additional retained state;
- any other method.

Every other Phase 0D and Phase 0F prohibition remains in force.

## Mandatory stop rule

If behavior, ordering, equality, missing-key handling, API shape, file scope, tests, documentation, repository state, or architecture is unclear or conflicting:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact conflict, why progress depends on it, viable options, tradeoffs, and a labeled recommendation. Do not implement the recommendation without architect approval.

Do not use another graph library, database, collection package, framework convention, or inferred standard graph behavior as missing authority.

---

# 2. ACCEPTED BASELINE AND CALLER RECORDS

Phase 0G established:

```text
Branch: main
Only branch: main
HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f
HEAD parent: 136822352a0caac199506cbddb4c9a539001c592
HEAD parent count: 1
Commit count: 2
HEAD subject: Establish Kongono framework baseline through Phase 0F
Tracked files: 178
Tracked modes: 178 x 100644
Remotes: 0
Tags: 0
Index: clean
Tracked worktree: clean
```

Before Phase 0H caller records, one untracked record exists:

```text
docs/task reports/Phase_0G.md
22,011 bytes
SHA-256 46D045825169CB35BDC4080704B8D05896B601473AD18CAFFE512D997D43DD06
```

It is the accepted Phase 0G report. Preserve it byte-for-byte.

The caller will add:

```text
docs/dev_plan/kongono_phase_0H_codex_directive.md
docs/task reports/Phase_0H.md
```

The directive must be a non-empty caller-supplied copy of this file. Read it fully, record size/hash, and do not edit, stage, commit, rename, or delete it.

The Phase 0H report must be a zero-byte caller-managed placeholder. Do not populate, edit, stage, commit, rename, or delete it. Return the report in the response.

If the placeholder is non-empty before execution, stop.

Preflight status after these files are supplied must contain exactly:

```text
docs/dev_plan/kongono_phase_0H_codex_directive.md
docs/task reports/Phase_0G.md
docs/task reports/Phase_0H.md
```

No tracked, staged, deleted, renamed, or other untracked path is authorized before implementation.

## Git restrictions

Phase 0H authorizes worktree content changes only.

Do not stage, commit, amend, reset, restore, checkout, clean, stash, rebase, branch, tag, add a remote, push, or change Git configuration.

At completion, HEAD, tree, both commits, branch set, index, remotes, and tags remain unchanged.

---

# 3. LOCKED ARCHITECTURAL CONTEXT

Preserve these facts:

1. Kongono remains PHP 8.5+ and pre-alpha.
2. The Application Graph remains an immutable language-neutral structural snapshot, not the complete authoritative Graph.
3. `NodeKey` remains opaque, graph-local, exact, non-empty, and not a stable ID, Documentation ID, URI, UUID, package coordinate, or PHP symbol.
4. Node and relation kinds remain open exact non-empty labels with no domain vocabulary.
5. A relation remains exactly source key, kind, and target key.
6. Construction still rejects malformed lists, duplicate node keys, dangling endpoints, and duplicate relation triples.
7. Self-relations, cycles, and different kinds between the same endpoints remain valid.
8. Objects remain final, readonly, instance-owned, and canonically ordered by binary `strcmp()` semantics.
9. No global, ambient, static mutable, I/O, environment, time, randomness, process, logging, or telemetry state exists.
10. The Graph package uses Kernel only for `Api` and `ApiStatus` metadata.
11. No Dependency Injection/Application Graph dependency or integration exists.
12. API status remains compatibility metadata, not Graph metadata, authorization, or visibility filtering.

Phase 0H changes only the read surface of `ApplicationGraph`.

---

# 4. EXACT PUBLIC API

Modify only `Kongono\ApplicationGraph\Model\ApplicationGraph` in production source.

Add exactly:

```php
/** @return list<Relation> */
public function outgoingRelations(NodeKey $source): array;

/** @return list<Relation> */
public function incomingRelations(NodeKey $target): array;
```

The complete declaration-owned public method inventory, sorted by method name for verification, becomes:

```text
__construct
hasNode
incomingRelations
node
nodes
outgoingRelations
relations
```

Do not add another interface, trait, class, enum, exception, result wrapper, iterator, generator, collection, overload, optional parameter, kind parameter, callback, or generic query method.

`ApplicationGraph` remains `final`, `readonly`, and Experimental. No production status changes and no new production declaration are authorized. Repository production inventory remains exactly 27.

---

# 5. ENDPOINT MATCHING SEMANTICS

`outgoingRelations($source)` selects every stored relation whose source value exactly equals `$source->value()`.

`incomingRelations($target)` selects every stored relation whose target value exactly equals `$target->value()`.

Use exact string equality:

```php
$relation->source()->value() === $source->value()
$relation->target()->value() === $target->value()
```

Requirements:

- match key value, not `NodeKey` object identity;
- do not trim, normalize, case-fold, decode, join with delimiters, hash, coerce, or alias;
- embedded NUL/control-byte and prefix-related non-empty values remain distinct;
- kind never participates in endpoint selection;
- selection remains directional;
- a self-relation appears in both selectors for its key;
- return the exact stored `Relation` objects, not clones or reconstructions.

Do not use `Node`, `Relation`, or `NodeKey` object identity to decide endpoint equality.

---

# 6. MISSING AND ISOLATED KEYS

Both selectors are total for every successfully constructed `NodeKey`.

Return an empty list when:

- the endpoint key does not exist;
- the endpoint node exists but has no relation in that direction;
- the graph is empty.

Do not throw `InvalidGraphException` for a missing endpoint query. Do not add a node or mutate the graph.

An empty result alone intentionally does not distinguish missing from isolated. Callers may use `hasNode()` or `node()` when needed.

No new deliberate failure category is authorized.

---

# 7. ORDERING AND RESULT OWNERSHIP

Preserve the exact canonical order already stored by `relations()`:

```text
source value
then kind value
then target value
```

Do not re-sort a selected result.

Therefore:

- outgoing results for one source are kind-then-target ordered;
- incoming results preserve global source-kind-target order, not target-first order;
- constructor input order cannot affect selector order;
- repeated calls return value-equivalent lists in the same order.

Each call returns a normal list detached from internal array storage. Mutating it must not alter the graph, `relations()`, or later selector results.

Elements are the same immutable `Relation` objects stored in the snapshot. Do not clone them.

---

# 8. IMPLEMENTATION AND PERFORMANCE BOUNDARY

Implement each selector as a direct read-only scan/filter of the existing canonical `$relations` list.

For each call, start with a fresh local list, scan every stored relation in order, append only exact matches, and return that list. Do not return a key-preserving filtered array that can violate the `list<Relation>` contract.

`ApplicationGraph` continues to contain exactly two private non-static properties:

```text
nodes
relations
```

Do not add:

- adjacency maps or indexes;
- indexes by kind or endpoint;
- memoized results or caches;
- weak maps, object storage, static state, globals, or context;
- lazy mutation inside the readonly object;
- constructor preprocessing beyond existing behavior.

Document this non-benchmark performance contract:

- each selector performs `O(R)` endpoint comparisons for `R` relations;
- result allocation is proportional to match count;
- no index or query cache is retained;
- no performance budget or optimization promise is selected.

Do not change construction algorithms, validation order, exceptions, node lookup, canonical sorting, property inventory, or node/relation representation.

---

# 9. EXCEPTIONS AND API STATUS

No new exception type or selector throw site is authorized.

Existing construction/value validation remains in the Graph exception family. Selectors called with a valid `NodeKey` return a list and do not deliberately throw.

Do not change any package exception relationship or production API status.

The root API-status architecture test remains unchanged because production inventory remains exactly 27.

---

# 10. REQUIRED PRODUCTION TESTS

Modify only the existing:

```text
packages/application-graph/tests/Unit/ApplicationGraphTest.php
```

Do not create another test file or fixture.

Cover at minimum:

1. both selectors return empty lists for an empty graph;
2. an existing isolated node returns empty lists;
3. a missing key returns empty lists without an exception;
4. a distinct `NodeKey` object with the same value matches;
5. outgoing selection includes only exact matching sources;
6. incoming selection includes only exact matching targets;
7. direction is not reversed or treated as undirected;
8. multiple kinds and endpoints select correctly;
9. a self-relation appears in both selectors;
10. the same stored relation object is returned;
11. outgoing order preserves canonical relation order;
12. incoming order preserves global source-kind-target order rather than target-first reordering;
13. selector order is independent of constructor input order;
14. embedded NUL/control-byte and prefix-related key values do not collide;
15. mutating a returned selector array does not alter later results or `relations()`;
16. repeated calls are deterministic and do not retain mutable query state;
17. the graph remains final/readonly with exactly `nodes` and `relations` private non-static properties;
18. the complete declaration-owned public method inventory is exactly Section 4;
19. existing construction, validation, lookup, ordering, equality, immutability, and exception tests remain passing.

Use object-identity assertions only to prove selected elements are the same stored immutable relations. Do not make endpoint matching depend on identity.

Do not inspect a new index or cache because none is authorized. Reflection is allowed only for exact class/property/method shape assertions.

No skipped, incomplete, warning-producing, risky, output-producing, order-dependent, time-dependent, random, filesystem, network, or environment-dependent test is allowed.

---

# 11. DOCUMENTATION AND ADR 0024

Documentation must distinguish direct selection from traversal and the structural snapshot from the future authoritative Graph.

## Update documentation

Update only the authorized documentation files in Section 12 to record:

- Phase 0H current reality;
- the exact two selector names and signatures;
- endpoint value-equality semantics;
- empty results for missing and isolated keys;
- directionality and self-relation behavior;
- preservation of canonical global relation order;
- detached result arrays containing the same immutable relation objects;
- `O(R)` scan cost and absence of indexes/caches;
- use `hasNode()` when missing-versus-isolated distinction matters;
- direct selection is not traversal, reachability, paths, neighbor selection, kind filtering, or a generic query system;
- all schema, stable identity, metadata, discovery, compilation, persistence, mutation, and integration decisions remain open.

Do not advertise a complete Graph query API or production readiness.

## ADR 0024

Create:

```text
docs/adr/0024-exact-direct-relation-selection.md
```

Title:

```text
ADR 0024: Exact Direct-Relation Selection
```

Set:

```text
Status: Accepted
Date: 2026-08-12
```

Record:

1. why the structural snapshot needs minimal direct relation selection;
2. the exact outgoing/incoming method boundary;
3. endpoint value equality rather than object identity;
4. empty missing/isolated results and use of node lookup for distinction;
5. preservation of canonical global relation order;
6. same immutable relation objects in detached arrays;
7. direct `O(R)` scanning with no retained index/cache;
8. no new exception/status/type;
9. explicit supersession of Phase 0D only for these two selectors;
10. rejection/deferment of traversal, neighbor queries, kind filters, DSLs, predicates, indexes, mutation, Graph schema/identity, discovery/compilation, and framework integration;
11. every materially open decision preserved by this directive.

Update ADR 0022 only with a short later-decision note referencing ADR 0024. Do not rewrite its historical Phase 0D rationale. Add ADR 0024 to the ADR index. Do not create ADR 0025.

---

# 12. AUTHORIZED FILE INVENTORY

Create exactly one implementation-owned file:

```text
docs/adr/0024-exact-direct-relation-selection.md
```

Modify only:

```text
README.md
ARCHITECTURE.md
docs/adr/0022-immutable-application-graph-structural-snapshot.md
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

The three caller-managed records from Section 2 remain untracked and untouched. At completion, status must therefore contain exactly:

- 11 modified tracked paths;
- one new ADR;
- three caller-managed paths;
- 15 total status paths.

Do not modify Composer manifests, `composer.lock`, tooling configuration, architecture-status tests, Kernel source/tests, Dependency Injection source/tests, another Graph model, or any other path.

If accurate implementation requires another file, stop and request scope expansion before editing it.

---

# 13. COMPOSER, DEPENDENCY, AND TOOLING IMMUTABILITY

Phase 0H adds no package, dependency, autoload path, source path, test suite, script, plugin, or lockfile change.

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

Do not run Composer update. Do not add an external graph/collection dependency. Do not authorize a plugin or use a transitive package as an undeclared dependency.

The existing quality dispatch order, fail-closed security policy, PHPStan maximum/strict rules, PHPUnit failure settings, and style rules remain unchanged.

---

# 14. EXPLICITLY PROHIBITED SCOPE

Do not implement or scaffold:

## Graph query expansion

- generic query objects or query interfaces;
- relation-kind filters or multiple selector arguments;
- node-kind filters;
- predicates, closures, callbacks, criteria, specifications, or expressions;
- neighbor-node APIs;
- descendants, ancestors, reachability, paths, shortest paths, cycles, topological order, BFS, DFS, recursion, or depth limits;
- grouping, aggregation, pagination, cursors, streaming, generators, iterators, or collections;
- reverse relations, synthesized relations, projections, views, or subgraphs;
- indexes, adjacency maps, caches, memoization, query plans, or optimization layers.

## Graph model expansion

- stable Graph IDs or Documentation IDs;
- URI/UUID/hash/fingerprint identity;
- built-in node/relation kinds or domain enums;
- metadata, payloads, provenance, visibility, security filtering, or authorization;
- schema/versioning, serialization, persistence, storage, diffs, merging, mutation, or builders;
- source discovery, reflection frontends, PHP attributes, IR compilation, or generated Graphs.

## Framework integration and later capabilities

- Dependency Injection/Graph relationships;
- Kernel/Graph lifecycle relationships;
- modules, profiles, capabilities, application bootstrapping, or composition roots;
- service scopes, cleanup, context, cancellation, or runtime adapters;
- PSR-11 or advanced DI;
- HTTP, routing, middleware, data, ORM, security, events, queues, workflows, agents, extensions, CLI, frontend, logging, telemetry, CI/CD, release, version, license, publishing, or distribution work.

Do not stage, commit, tag, add a remote, push, or begin Phase 0I.

---

# 15. REQUIRED EXECUTION AND VALIDATION SEQUENCE

Follow this sequence. A failed required check is a blocker, not a reason to weaken a rule or silently change scope.

## 15.1 Preflight and immutable baseline

Before editing implementation-owned files:

1. read `AGENTS.md` and every document or package contract that governs the authorized files;
2. verify the branch is exactly `main` and that it is the only local branch;
3. verify there are no remotes and no tags;
4. verify `HEAD`, its tree, parent, commit count, tracked-file count, and file modes against Section 2;
5. verify the index is clean and the cached diff is empty;
6. verify the worktree contains only the three expected untracked caller records;
7. verify the Phase 0G report size and SHA-256 from Section 2;
8. verify the Phase 0H directive is populated and the Phase 0H report is zero bytes;
9. record PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
10. record SHA-256 hashes for every file Section 13 requires to remain byte-for-byte unchanged;
11. run the existing full quality command without editing first.

The pre-change quality baseline must pass and must include exactly:

```text
111 tests
472 assertions
0 PHPStan errors
89 style-checked files with no required fix
0 security advisories
```

Composer validation may emit only warning categories already accepted by current repository doctrine, including the missing-license warning and authorized local `@dev`/wildcard package-constraint warnings. Do not treat a new warning category as accepted. If a security audit fails solely because the sandbox cannot reach the advisory source, rerun that same read-only audit with authorized network access; never report a network failure as a successful security audit.

If the repository or quality baseline differs materially, stop and report the discrepancy. Do not normalize, clean, restore, stage, or otherwise rewrite caller or repository state.

## 15.2 Implementation

After preflight passes:

1. add only the two methods in Section 4 to the existing `ApplicationGraph`;
2. implement their exact semantics through direct relation scanning;
3. extend only the existing `ApplicationGraphTest.php` with the required cases;
4. create ADR 0024 and make only the documentation reconciliations in Sections 11 and 12;
5. format only authorized changed PHP files if formatting is needed.

Do not perform opportunistic cleanup, renaming, reordering, type extraction, helper extraction, or unrelated wording changes.

## 15.3 Focused behavioral verification

Run the existing Application Graph test target and prove all of Section 10. The focused run must pass.

Also execute a small, read-only direct-behavior probe through Composer autoload that constructs a graph with:

- at least three nodes;
- outgoing, incoming, unrelated, and self relations;
- relation input deliberately outside canonical order;
- a distinct-but-value-equal `NodeKey` query object;
- a missing key.

The probe must prove exact matching, self-relation inclusion, canonical order preservation, empty missing results, and returned object identity. It must not create or modify a repository file.

## 15.4 Structural and static verification

Use reflection or an equivalently direct executable check to prove:

- `ApplicationGraph` remains final and readonly;
- its declared instance properties remain exactly `nodes` and `relations`, both private, non-static, and readonly;
- its public declared method inventory, sorted by method name, is exactly Section 4;
- both new methods have exactly one non-optional parameter of type `NodeKey` and return type `array`;
- all 27 production API types retain their existing status category;
- no new production PHP type exists.

Run PHP syntax checks over all first-party PHP files, the focused PHPUnit target, PHPStan, and PHP CS Fixer dry-run. All must pass.

Perform direct source/dependency inspection proving the new production implementation contains no index, cache, additional state, traversal, query abstraction, Graph schema/identity, serializer, persistence, Kernel/DI integration, or I/O behavior.

## 15.5 Full quality and dependency verification

Run the repository's canonical full quality command. It must pass in its locked order.

The final PHPUnit totals must be strictly greater than 111 tests and 472 assertions because Phase 0H adds production behavior tests. Do not require or invent an exact new total; report the observed values.

Also verify independently:

- Composer manifest validation succeeds subject only to the authorized warning categories;
- the locked dependency security audit reports zero advisories;
- platform requirements pass;
- optimized strict PSR autoload generation passes;
- PHPStan reports zero errors;
- PHP CS Fixer reports no file requiring a fix;
- `composer.lock` content and dependency graph remain unchanged;
- every file listed in Section 13 retains its preflight SHA-256 hash.

Do not edit the implementation merely to inflate test or assertion counts.

## 15.6 Documentation, file-safety, and prohibited-scope audit

Verify:

- ADR 0024 has the exact status/date and required decisions from Section 11;
- ADR 0022 received only its narrow later-decision note;
- the ADR index links ADR 0024;
- every changed non-historical document describes the exact Phase 0H boundary consistently;
- every local Markdown link in an authorized changed document resolves;
- no historical directive or completion report was changed;
- no file outside Section 12 changed or appeared;
- all non-empty changed text files are valid UTF-8 without BOM, NUL, replacement characters, or mojibake;
- changed text files have LF line endings, a final newline, no trailing whitespace, and no tab indentation;
- no implementation-owned file is zero bytes;
- the zero-byte Phase 0H caller report placeholder is preserved;
- no placeholder, TODO, FIXME, skipped test, risky-test suppression, warning suppression, baseline, generated artifact, editor artifact, or temporary file was added;
- every item in Section 14 remains absent.

## 15.7 Final Git proof

At completion, rerun and report evidence for:

- current branch and all local branches;
- `HEAD`, tree, parent, commit count, subject, tracked-file count, and tracked modes;
- remotes and tags;
- porcelain status;
- ordinary diff summary and name/status list;
- cached diff summary and name/status list;
- `git diff --check`;
- exact status-path classification and count.

Required final state:

```text
branch: main
only local branch: main
HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f
parent: 136822352a0caac199506cbddb4c9a539001c592
commit count: 2
tracked files: 178
tracked modes: all 100644
remotes: none
tags: none
cached diff: empty
status paths: exactly 15, classified as Section 12 requires
```

The ordinary diff may contain only the 11 authorized tracked-file modifications. The untracked set may contain only ADR 0024 and the three caller records. Do not alter the caller records, and do not stage anything.

---

# 16. STRICT ACCEPTANCE CRITERIA

Phase 0H is complete only if every item below is true:

## Repository safety

- [ ] All Section 15 preflight checks matched the accepted Phase 0G baseline.
- [ ] `main` remains the only local branch and the immutable commit facts remain exact.
- [ ] No remote or tag exists.
- [ ] The index and cached diff remain empty.
- [ ] Exactly 15 status paths exist with the classification in Section 12.
- [ ] The Phase 0G report and both Phase 0H caller records remain untouched.
- [ ] No unauthorized file was created, changed, removed, renamed, staged, or committed.

## Public API and behavior

- [ ] `ApplicationGraph` has exactly the two authorized new public methods and no other public API change.
- [ ] Both selectors use exact `NodeKey::value()` equality, not object identity or normalization.
- [ ] Missing, isolated, and empty-graph queries return `[]` without exception.
- [ ] Self-relations appear in both applicable selectors.
- [ ] Results preserve the graph's global canonical relation order.
- [ ] Results contain the same stored immutable `Relation` objects in fresh detached arrays.
- [ ] The implementation is a direct `O(R)` scan with `O(k)` result allocation and retains no new state.
- [ ] Existing constructor, node, relation, ordering, immutability, lookup, and exception behavior remains unchanged.

## Architecture and scope

- [ ] `ApplicationGraph` remains final, readonly, and has exactly its two original private properties.
- [ ] All 27 production API types retain their existing status categories.
- [ ] No production type, exception, package, dependency, autoload rule, tool rule, or lockfile change was added.
- [ ] Phase 0D is superseded only for the two exact direct-relation selectors.
- [ ] Every later capability prohibited by Section 14 remains absent.

## Tests and quality

- [ ] Every Section 10 case exists in the existing focused test file and passes.
- [ ] Focused behavioral, direct-probe, reflection, syntax, static, and source-boundary checks pass.
- [ ] Canonical full quality passes in its locked order.
- [ ] Final PHPUnit totals are strictly greater than 111 tests and 472 assertions.
- [ ] PHPStan reports zero errors and style validation is clean.
- [ ] Composer validation and platform checks pass under the locked warning policy.
- [ ] The security audit reports zero advisories.
- [ ] Optimized strict PSR autoload generation passes.

## Documentation and report

- [ ] ADR 0024 is Accepted, dated 2026-08-12, indexed, and records every required decision.
- [ ] ADR 0022 and all authorized current documents are narrowly reconciled without rewriting history.
- [ ] File-safety, link, prohibited-scope, and final Git audits pass.
- [ ] The Phase 0H completion report is returned in exactly the Section 17 contract.

Any unchecked item means the status is `Blocked`, not `Complete`.

---

# 17. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly one report. Put the entire report inside one fenced `text` block so the architect can copy it verbatim to:

```text
docs/task reports/Phase_0H.md
```

Use exactly these 22 numbered top-level sections, in this order, with no prose before or after the fence:

```text
1. STATUS
- Complete or Blocked.
- If Blocked, state the precise unmet acceptance item and leave later work unstarted.

2. SUMMARY
- Concise account of the implemented boundary and outcome.

3. BASELINE OBSERVED
- PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions.
- Branch, branches, HEAD, tree, parent, subject, commit count, tracked count/modes, remotes, tags, index, and cached diff.
- Pre-change quality totals and accepted warning categories.
- Caller-record paths, sizes, and Phase 0G report SHA-256.

4. FILES CREATED
- Exact implementation-owned path and purpose.

5. FILES MODIFIED
- Exact paths and concise purpose for each.

6. FILES MOVED OR REMOVED
- Must be None.

7. PUBLIC API AND STRUCTURAL SHAPE
- Exact signatures, final/readonly state, property inventory, and public-method inventory.

8. SELECTOR SEMANTICS
- Equality, missing/isolated behavior, self-relations, exceptions, and direct relation meaning.

9. ORDERING, OWNERSHIP, AND PERFORMANCE
- Canonical order, same object instances, detached arrays, O(R)/O(k), and absence of retained indexes/caches/state.

10. EXISTING GRAPH INVARIANTS
- Evidence that all prior behavior through Phase 0G outside the explicit supersession remains unchanged.

11. TEST RESULTS
- Focused command/result, cases covered, direct probe, exact final PHPUnit tests/assertions, and comparison with 111/472.

12. COMPOSER, DEPENDENCY, AND SECURITY
- Validation, warnings, audit result, platform result, optimized strict PSR result, lock/dependency immutability, and manifest/config hashes.

13. STATIC ANALYSIS, STYLE, AND FULL QUALITY
- Exact commands and outcomes, PHPStan error count, style file count/outcome, and canonical quality result/order.

14. DOCUMENTATION AND ADR
- ADR 0024 status/date/decisions, ADR 0022/index update, current-document reconciliation, and link validation.

15. FILE SAFETY AND PROHIBITED-SCOPE AUDIT
- UTF-8/LF/final-newline/whitespace/zero-byte checks, artifact scan, authorized inventory proof, and explicit later-capability absence.

16. COMMANDS RUN
- Every command in execution order with exit code and concise result.

17. ARCHITECTURAL DECISIONS AND DEVIATIONS
- Decisions implemented from this directive.
- Deviations must be None for Complete; otherwise status must be Blocked.

18. QUESTIONS OR BLOCKERS
- Must be None for Complete.

19. FINAL GIT STATUS
- Branch/branches, immutable commit facts, remotes/tags, exact porcelain status, diff summary/name-status, cached diff proof, diff-check result, and exact 15-path classification.

20. ACCEPTANCE CHECKLIST
- Reproduce every Section 16 item and mark each pass/fail with evidence.

21. REPORT STRUCTURE SELF-CHECK
- Confirm exactly 22 numbered top-level sections, correct order, one fenced text block, and no content outside it.

22. NEXT STEP
Await architect review before Phase 0I.
```

Do not write to the zero-byte Phase 0H report placeholder. Return the report in the executor response for the caller to copy. Do not replace exact evidence with “all passed,” omit warning text, or claim completion when a required check was not run.

---

# 18. FINAL EXECUTOR INSTRUCTION

Implement only the exact read-only direct-relation selection boundary described here. Preserve every prior Kongono doctrine, security rule, immutable repository fact, caller-managed record, and unresolved later decision. Make no assumption outside this directive. If an ambiguity, baseline mismatch, unavailable required validation, or need for expanded scope appears, stop and return a `Blocked` report under Section 17.

Do not begin Phase 0I.
