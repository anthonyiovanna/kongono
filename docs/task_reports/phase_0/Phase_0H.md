1. STATUS
- Complete.
- Every Phase 0H acceptance criterion passed.

2. SUMMARY
- Added exact read-only outgoing and incoming relation selection to the immutable Application Graph.
- Selection uses graph-local endpoint-key values, preserves canonical relation order, returns detached lists containing the stored immutable Relation objects, and retains no index or cache.
- Added focused production tests, reconciled only authorized documentation, and created ADR 0024.
- Phase 0I was not started.

3. BASELINE OBSERVED
- PHP: 8.5.9 ZTS; loaded configuration: C:\php\php.ini.
- Composer: 2.10.2.
- Git: 2.45.1.windows.1.
- PHPUnit: 13.3.0.
- PHPStan: 2.2.8.
- PHP CS Fixer: 3.95.18.
- Branch and only local branch: main.
- HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c.
- Tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f.
- Parent: 136822352a0caac199506cbddb4c9a539001c592.
- Parent count: 1.
- Commit count: 2.
- Subject: Establish Kongono framework baseline through Phase 0F.
- Tracked files: 178; modes: 178 x 100644.
- Remotes: none.
- Tags: none.
- Index and cached diff: clean and empty.
- Preflight status contained exactly the three caller-managed records.
- Pre-change quality: PASS after the unchanged command was rerun with authorized network/cache access.
- Pre-change PHPUnit: 111 tests, 472 assertions.
- Pre-change PHPStan: zero errors.
- Pre-change PHP CS Fixer: zero of 89 files requiring changes.
- Pre-change locked audit: zero advisories.
- Accepted Composer warnings: intentionally missing license; unreleased local @dev root constraints; authorized internal kongono/kernel: * constraints.
- docs/task reports/Phase_0G.md: 22,011 bytes; SHA-256 46D045825169CB35BDC4080704B8D05896B601473AD18CAFFE512D997D43DD06.
- docs/dev_plan/kongono_phase_0H_codex_directive.md: 32,191 bytes; SHA-256 9070F0E7C72D98578275DFA8C20900CB4B50140B334E8F649D2092B8E515F25A.
- docs/task reports/Phase_0H.md: zero bytes; SHA-256 E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855.

4. FILES CREATED
- docs/adr/0024-exact-direct-relation-selection.md - 4,446-byte Accepted ADR recording the exact Phase 0H boundary.

5. FILES MODIFIED
- README.md - recorded Phase 0H current reality and narrow selector boundary.
- ARCHITECTURE.md - recorded direct selection while preserving future Graph boundaries.
- docs/adr/0022-immutable-application-graph-structural-snapshot.md - added only a short later-decision note referencing ADR 0024.
- docs/adr/README.md - indexed ADR 0024.
- docs/architecture/application-graph.md - documented selector semantics, ownership, ordering, performance, and non-goals.
- docs/architecture/overview.md - reconciled current repository reality.
- docs/doctrine.md - recorded the exact Phase 0H supersession without expanding doctrine.
- docs/engineering/application-graph-structure.md - documented signatures, usage, guarantees, and performance.
- packages/application-graph/README.md - documented the package-level direct-selection contract.
- packages/application-graph/src/Model/ApplicationGraph.php - added the two authorized direct scans.
- packages/application-graph/tests/Unit/ApplicationGraphTest.php - added required behavior and structural-shape coverage.

6. FILES MOVED OR REMOVED
- None.

7. PUBLIC API AND STRUCTURAL SHAPE
- Added exactly:
  /** @return list<Relation> */
  public function outgoingRelations(NodeKey $source): array;
  /** @return list<Relation> */
  public function incomingRelations(NodeKey $target): array;
- ApplicationGraph remains final, readonly, and Experimental.
- Exact declared instance properties: nodes and relations.
- Both properties remain private, non-static, and readonly.
- Exact declaration-owned public-method inventory:
  __construct
  hasNode
  incomingRelations
  node
  nodes
  outgoingRelations
  relations
- Each selector has exactly one required NodeKey parameter and native array return type.
- Production inventory remains exactly 27 class-like declarations with unchanged API statuses.

8. SELECTOR SEMANTICS
- outgoingRelations() compares each stored source value with $source->value() using exact === equality.
- incomingRelations() compares each stored target value with $target->value() using exact === equality.
- Matching does not depend on NodeKey, Node, or Relation object identity.
- No trimming, normalization, case folding, coercion, decoding, hashing, delimiter joining, or aliasing occurs.
- Selection is directional and relation kind does not filter results.
- Embedded NUL, control-byte, and prefix-related endpoint values remain distinct.
- Empty graphs, missing keys, and isolated nodes return [] without exception.
- hasNode() or node() remains available when missing-versus-isolated distinction matters.
- Self-relations appear in both selectors for their key.
- No new deliberate throw category or exception type exists.
- Direct selection returns immediately stored relations only; it is not traversal.

9. ORDERING, OWNERSHIP, AND PERFORMANCE
- Both selectors scan the existing canonical source-kind-target relation list.
- No selected result is re-sorted.
- Outgoing results are therefore kind-then-target ordered for one source.
- Incoming results preserve global source-kind-target order.
- Constructor input order does not affect selector output order.
- Every call starts with a fresh local list.
- Returned lists are detached from internal array storage.
- Elements are the exact stored immutable Relation objects, not clones or reconstructions.
- Repeated calls are deterministic and retain no mutable query state.
- Each call performs O(R) endpoint comparisons for R stored relations.
- Result allocation is O(k) for k matches.
- No adjacency map, endpoint/kind index, cache, memoization, weak map, static state, or other retained state was added.
- No benchmark, performance budget, or optimization promise was selected.

10. EXISTING GRAPH INVARIANTS
- Constructor algorithms, validation order, and publication behavior are unchanged.
- Runtime list and element validation remains unchanged.
- Duplicate node keys, dangling endpoints, and duplicate relation triples remain rejected.
- Self-relations, cycles, and different kinds between the same endpoints remain accepted.
- Node and relation canonical strcmp() ordering remains unchanged.
- Exact node lookup and missing-node behavior remain unchanged.
- Nodes, relations, and structural values remain final, readonly, immutable, and instance-owned.
- Graph exception hierarchy and marker separation remain unchanged.
- Existing Application Graph and repository tests remained passing.
- The root architecture test independently confirmed all 27 production declarations and statuses.

11. TEST RESULTS
- Focused command:
  vendor\bin\phpunit.bat --configuration phpunit.xml.dist packages\application-graph\tests\Unit\ApplicationGraphTest.php
- Focused result: PASS - 28 tests, 78 assertions.
- Complete Application Graph package result during implementation: PASS - 38 tests, 146 assertions.
- Required coverage includes empty, isolated, and missing keys; distinct value-equal NodeKey objects; exact source/target matching; directionality; multiple kinds/endpoints; self-relations; object identity; outgoing and incoming order; constructor-order independence; NUL/control/prefix distinctions; detached arrays; repeated calls; exact class/property/method shape; and all pre-existing invariants.
- Direct Composer-autoload behavior probe: PASS.
- Direct probe covered three nodes, outgoing, incoming, unrelated and self relations, deliberately noncanonical input, distinct value-equal query keys, missing keys, canonical order, and object identity.
- Reflection probe: PASS - two exact properties, seven exact public methods, and two exact selector signatures.
- Root architecture target: PASS - 5 tests, 154 assertions.
- All first-party PHP syntax checks: PASS - 90 files, zero failures.
- Final complete PHPUnit result through composer quality: PASS - 119 tests, 513 assertions.
- Final totals exceed the required baseline by 8 tests and 41 assertions.
- No errors, failures, warnings, risky tests, skipped tests, incomplete tests, deprecations, notices, or unexpected output occurred.

12. COMPOSER, DEPENDENCY, AND SECURITY
- composer validate: PASS with only accepted warning categories.
- composer validate:composer: PASS.
- composer validate:packages: PASS for all three packages.
- composer audit:locked: PASS; zero security advisories.
- composer platform: PASS on PHP 8.5.9.
- composer dump-autoload --optimize --strict-psr: PASS; 3,059 optimized classes.
- Locked package count remains 69.
- composer.lock remains byte-identical and its dependency graph is unchanged.
- Preflight/final immutable hashes:
  composer.json - E6B1F4A04A7200B2CA8B734ED681822604A8395307098EAB9D1A9DBAC8B7FFB3
  composer.lock - 9AB5928904039B26A0C7EE7B0CEF25FAB78848978D158F33201F2FE06DAE5190
  packages/application-graph/composer.json - 80EFC7B2264164B1BC89C08089E98E206A25563E59BB84C30BA9CCA6DA5E0094
  packages/dependency-injection/composer.json - A4F5C16D57F101C2E867E0B2D68CEEB38F8EB5321A04A303C4C5E4421A7B7E79
  packages/kernel/composer.json - 551D3931124FDFDAAC933CFEDCFCB96907FB4940FB27D81A9B5C30415E396875
  .php-cs-fixer.dist.php - EA4F027012A490D033F8D122F68A1160F67AE5943D3C9FD8FD681D915E42DA53
  phpstan.neon.dist - CCCD5D325970AAF213C014E356A9DB179BCD0C338C68251D78A681203E170AB8
  phpunit.xml.dist - 4B2AC7A80B10A80FBC2A7383CDBE297868144E0D17F5678E3884D3EDB779497C
  tests/Architecture/ApiStatusConventionTest.php - AE19E3C736E8472948540BAC92C589E63CE37D4EF818B9152481F886E6C6B1B9
- No dependency, plugin, repository, autoload rule, package script, security-policy exception, or lockfile mutation occurred.

13. STATIC ANALYSIS, STYLE, AND FULL QUALITY
- Focused PHPStan over Application Graph source/tests: PASS, zero errors.
- Independent composer analyse: PASS, zero errors at maximum level with strict rules.
- Independent composer cs: PASS, zero of 89 files requiring changes.
- Canonical composer quality: PASS.
- Locked quality order remained:
  validate:composer
  validate:packages
  audit:locked
  platform
  cs
  analyse
  test
- Final quality result: 119 tests, 513 assertions; zero PHPStan errors; zero of 89 style fixes; zero advisories.
- No PHPStan baseline, ignored error, inline suppression, excluded path, reduced level, warning allowance, risky-test suppression, or weakened gate was added.

14. DOCUMENTATION AND ADR
- ADR 0024 title: ADR 0024: Exact Direct-Relation Selection.
- ADR 0024 status: Accepted.
- ADR 0024 date: 2026-08-12.
- ADR 0024 records the exact methods, value equality, empty missing/isolated behavior, canonical ordering, detached arrays with stored objects, O(R)/O(k), no index/cache, no new type/status/exception, and the narrow Phase 0D supersession.
- ADR 0024 rejects or defers traversal, neighbors, reachability, paths, kind filters, predicates, DSLs, indexes, mutation, schema/identity, discovery/compilation, persistence, and framework integration.
- ADR 0022 received only a nine-line later-decision note; its historical rationale was not rewritten.
- The ADR index links ADR 0024.
- All authorized current documents distinguish immediate direct selection from traversal and the structural snapshot from the future authoritative Graph.
- Local Markdown validation: PASS - 60 local links checked, zero failures.
- No document advertises a complete query API, Stable behavior, or production readiness.

15. FILE SAFETY AND PROHIBITED-SCOPE AUDIT
- Twelve implementation-owned changed/new text files checked.
- Strict UTF-8: PASS.
- UTF-8 BOM absence: PASS.
- NUL and replacement-character absence: PASS.
- Mojibake scan: PASS.
- LF-only line endings: PASS.
- Final newline: PASS.
- Trailing whitespace and tab indentation: PASS.
- Non-empty implementation files: PASS.
- Phase 0H report placeholder remains exactly zero bytes.
- Placeholder/TODO/FIXME/skipped-test/risky-test/suppression/baseline scan: PASS.
- Exact inventory: 11 modified tracked paths, one new ADR, and three untouched caller records.
- No unauthorized file changed or appeared.
- Production source-boundary scan found no WeakMap, object storage, generator, iterator, array_filter query shortcut, memoization, adjacency state, reflection, serialization, I/O, environment, randomness, time, Dependency Injection, or other later capability.
- ApplicationGraph production Kernel imports remain exactly Api and ApiStatus.
- No new production declaration, property, exception, schema, stable identity, serializer, persistence mechanism, discovery/compiler mechanism, mutation, traversal, query abstraction, Kernel integration, or Dependency Injection integration exists.
- No editor, generated, temporary, cache, or package artifact entered Git status.

16. COMMANDS RUN
- All commands ran from C:\Users\antho\Dev\kongono.
- Authority/memory lookup:
  rg -n -i "kongono|phase 0h|phase 0a|architect review" C:\Users\antho\.codex\memories\MEMORY.md - PASS.
- Complete authority reads used Get-Content/Get-Content -Raw over AGENTS.md, root governance files, doctrine/glossary/documentation standard, every architecture and engineering document, ADRs 0001-0023 and index, all Phase 0A-0G directives/corrections/reports, all package manifests/READMEs, all Application Graph source/tests, root tooling configuration, architecture tests, and the 804-line Phase 0H directive - PASS.
- rg --files, Get-ChildItem, Get-Item, and Get-FileHash were used to enumerate and size the 95 required authority/source files - PASS.
- git status --short and repository file inventory commands - PASS.
- php -v; php --ini; composer --version; git --version; PHPUnit/PHPStan/PHP-CS-Fixer version commands - PASS.
- Git branch/refs/HEAD/tree/parent/count/subject/tracked-mode/remotes/tags/index/status commands - PASS.
- Caller-record and Section 13 SHA-256 preflight - PASS.
- Initial composer quality - exit 1 only because sandbox network/cache access blocked Packagist during audit:locked; no repository mutation.
- Unchanged composer quality through authorized access - PASS, 111 tests/472 assertions.
- apply_patch added the two selector methods and tests - PASS.
- Focused PHPUnit plus focused PHPStan - PASS, 38 tests/146 assertions and zero errors.
- apply_patch corrected reflection typing and PHPUnit identity assertions - PASS.
- apply_patch reconciled the nine authorized documents and added ADR 0024 - PASS.
- composer cs; status; git diff --check; diff stat/name-status - PASS.
- Focused ApplicationGraphTest.php - PASS, 28 tests/78 assertions.
- Read-only direct behavior probe through vendor/autoload.php - PASS.
- Read-only reflection probe - PASS.
- Initial package/test PHP lint - PASS, 89 files.
- Final composer quality through authorized access - PASS, 119 tests/513 assertions.
- composer validate; composer validate:composer; composer validate:packages; composer platform; composer dump-autoload --optimize --strict-psr; locked inventory/hash check - PASS.
- composer audit:locked through authorized access - PASS, zero advisories.
- composer analyse; composer cs - PASS.
- Full first-party PHP lint including .php-cs-fixer.dist.php - PASS, 90 files.
- UTF-8/BOM/NUL/mojibake/LF/newline/whitespace audit - PASS, 12 files.
- Local Markdown-link audit - PASS, 60 links.
- Placeholder/TODO/FIXME/suppression/test-artifact scan - PASS.
- One exploratory source-boundary regex used unsupported lookahead and was discarded without mutation.
- Two exploratory Kernel-import diagnostics had quoting/pattern failures and were replaced without mutation.
- Corrected source-boundary and exact Kernel-import inspection - PASS.
- Exact 15-path porcelain classification - PASS.
- Final immutable and caller-record hash comparison - PASS.
- Full source/test/ADR diff review - PASS.
- Root ApiStatusConventionTest.php - PASS, 5 tests/154 assertions.
- ADR required-content/index/narrow-ADR-0022 audit - PASS.
- Final Git branch/HEAD/tree/parent/count/subject/modes/remotes/tags/status/diff/cached-diff/diff-check proof - PASS.
- No staging, commit, branch, tag, remote, push, reset, restore, checkout, clean, stash, rebase, or Git-configuration mutation command ran.

17. ARCHITECTURAL DECISIONS AND DEVIATIONS
- Implemented only decisions explicitly authorized by the Phase 0H directive and ADR 0024.
- Architectural decisions introduced by executor: none.
- Deviations from directive: none.
- The initial network-restricted quality failure and discarded diagnostic quoting attempts were environmental diagnostics, not validation omissions or implementation deviations.

18. QUESTIONS OR BLOCKERS
- None.

19. FINAL GIT STATUS
- Branch: main.
- Only local branch: main.
- HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c.
- Tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f.
- Parent: 136822352a0caac199506cbddb4c9a539001c592.
- Parent count: 1.
- Commit count: 2.
- Subject: Establish Kongono framework baseline through Phase 0F.
- Tracked files: 178; all modes 100644.
- Remotes: none.
- Tags: none.
- Cached diff: empty; exit 0.
- git diff --check: PASS; exit 0.
- Ordinary tracked diff: 11 files changed, 323 insertions, 22 deletions.
- Exact porcelain status:
  M ARCHITECTURE.md
  M README.md
  M docs/adr/0022-immutable-application-graph-structural-snapshot.md
  M docs/adr/README.md
  M docs/architecture/application-graph.md
  M docs/architecture/overview.md
  M docs/doctrine.md
  M docs/engineering/application-graph-structure.md
  M packages/application-graph/README.md
  M packages/application-graph/src/Model/ApplicationGraph.php
  M packages/application-graph/tests/Unit/ApplicationGraphTest.php
  ?? docs/adr/0024-exact-direct-relation-selection.md
  ?? docs/dev_plan/kongono_phase_0H_codex_directive.md
  ?? "docs/task reports/Phase_0G.md"
  ?? "docs/task reports/Phase_0H.md"
- Classification: exactly 11 modified tracked paths, one implementation-owned ADR, three caller-managed untracked paths, and 15 total status paths.
- No path is staged.

20. ACCEPTANCE CHECKLIST
    Repository safety:
- PASS - All Section 15 preflight checks matched the accepted Phase 0G baseline.
- PASS - main remains the only local branch and immutable commit facts remain exact.
- PASS - No remote or tag exists.
- PASS - Index and cached diff remain empty.
- PASS - Exactly 15 status paths exist with the required classification.
- PASS - Phase 0G report and both Phase 0H caller records remain untouched.
- PASS - No unauthorized file was created, changed, removed, renamed, staged, or committed.

Public API and behavior:
- PASS - ApplicationGraph has exactly the two authorized new public methods and no other public API change.
- PASS - Both selectors use exact NodeKey::value() equality, not identity or normalization.
- PASS - Missing, isolated, and empty-graph queries return [] without exception.
- PASS - Self-relations appear in both applicable selectors.
- PASS - Results preserve global canonical relation order.
- PASS - Results contain stored immutable Relation objects in fresh detached arrays.
- PASS - Implementation is an O(R) scan with O(k) result allocation and no new state.
- PASS - Constructor, node, relation, ordering, immutability, lookup, and exception behavior remain unchanged.

Architecture and scope:
- PASS - ApplicationGraph remains final/readonly with exactly nodes and relations.
- PASS - All 27 production API types retain their existing statuses.
- PASS - No production type, exception, package, dependency, autoload rule, tool rule, or lockfile change was added.
- PASS - Phase 0D is superseded only for the two exact direct-relation selectors.
- PASS - Every prohibited later capability remains absent.

Tests and quality:
- PASS - Every Section 10 case exists in ApplicationGraphTest.php and passes.
- PASS - Focused behavior, direct probe, reflection, syntax, static, and source-boundary checks pass.
- PASS - Canonical full quality passes in its locked order.
- PASS - Final PHPUnit totals are 119 tests/513 assertions, strictly greater than 111/472.
- PASS - PHPStan reports zero errors and style validation is clean.
- PASS - Composer validation and platform checks pass under the locked warning policy.
- PASS - Locked security audit reports zero advisories.
- PASS - Optimized strict PSR autoload generation passes.

Documentation and report:
- PASS - ADR 0024 is Accepted, dated 2026-08-12, indexed, and records every required decision.
- PASS - ADR 0022 and all authorized current documents are narrowly reconciled without rewriting history.
- PASS - File-safety, link, prohibited-scope, and final Git audits pass.
- PASS - The Phase 0H completion report is returned with the required 22-section contract.

21. REPORT STRUCTURE SELF-CHECK
- PASS - This report contains exactly 22 numbered top-level sections.
- PASS - Sections appear once and in the required order.
- PASS - The complete report is contained in one fenced text block.
- PASS - No required section was omitted, merged, renamed, or renumbered.
- PASS - Complete status is supported by executed evidence.
- PASS - Section 22 contains only the required next-step sentence.

22. NEXT STEP
    Await architect review before Phase 0I.
