# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2A

## Runnable Web Framework Milestone Architecture and Fixed A-H Roadmap

**Directive type:** architecture and documentation only

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-16

---

## 1. Mission

Formally close Phase 1 at its accepted Phase 1H checkpoint and define Phase 2 as Kongono's first runnable web-framework milestone with a fixed main sequence from Phase 2A through Phase 2H.

Phase 2A is documentation-only. It records the milestone, security invariants, persistent-runtime constraints, required end-to-end acceptance scenarios, planned sequence, and still-open decisions. It implements no request, response, router, middleware, adapter, bootstrap, emitter, diagnostic delivery, configuration, source declaration, package, dependency, or test.

The phase must make the project's finish line more legible without pretending Kongono is already runnable or release-ready.

### 1.1 Required outcome

After successful Phase 2A:

- Phase 1 is documented as complete at local commit `d7846f137a1d53f5306f48d19196b916fde44ac2`;
- Phase 2 is documented as the bounded A-H first-runnable-web milestone;
- the end-of-Phase-2 black-box acceptance boundary is explicit;
- Phase 2A itself remains architecture and documentation only;
- no ninth package, namespace, PHP declaration, Composer dependency, lockfile change, tooling change, test, or runtime behavior is introduced;
- PSR-7, PSR-15, PSR-17, package names, and exact HTTP APIs remain unselected for later explicit decisions;
- all trusted-diagnostic fail-closed rules remain locked;
- current documentation says exactly eight packages and distinguishes implemented Phase 1 behavior from planned Phase 2 work;
- canonical quality remains 254 tests and 4,279 assertions; and
- no staging, commit, branch, tag, remote, or Phase 2B work occurs.

---

## 2. Governing authority and mandatory stop rule

### 2.1 Authority order

Read completely, in this order, before broad validation or editing:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. every Accepted ADR in `docs/adr/`
6. `docs/architecture/overview.md`
7. `docs/architecture/runtime-model.md`
8. `docs/engineering/package-layout.md`
9. `docs/engineering/error-handling.md`
10. `SECURITY.md`
11. the four Phase 1I-family caller records in Section 3.2
12. this Phase 2A directive
13. the zero-byte `docs/task reports/Phase_2A.md` placeholder

Historical directives and reports remain evidence, not current implementation instructions. Do not reopen accepted Phase 0 or Phase 1 decisions.

### 2.2 Independent directive identity

The architect will supply the byte count and uppercase SHA-256 of this directive outside this file. Compare those independently supplied values with:

```text
docs/dev_plan/kongono_phase_2A_codex_directive.md
```

Do not derive the expected identity from the local file. The file is not self-authenticating.

The Phase 2A report placeholder must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_2A.md
```

Initial SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.3 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, changed protected byte, failed gate, unauthorized warning, or prohibited side effect:

1. stop immediately;
2. preserve the repository in its current state;
3. do not repair, normalize, widen scope, stage, commit, reset, restore, or guess;
4. leave the Phase 2A report zero bytes unless Section 13 authorizes population; and
5. report the exact expected and observed values, viable options, and a recommendation.

No undocumented architectural choice is authorized. If wording would select an exact API, package dependency, protocol behavior, trust signal, or runtime mechanism not specified here, stop and ask.

### 2.4 Environment-only retry rule

One unchanged retry is allowed only when `composer quality` is blocked solely by managed network, advisory, or Composer-cache access. The retry may request approved host access but must use the identical command and must not change configuration, dependencies, lock data, or source.

A functional validation failure is not retryable under this rule.

---

## 3. Locked starting repository state

Before the caller adds the two Phase 2A records, the accepted repository state is:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `d7846f137a1d53f5306f48d19196b916fde44ac2` |
| `HEAD^{tree}` | `bfa1908c619069b699bc9b08668db17ea2555eb3` |
| `HEAD^` | `8fe793b124384744ac43887037360154c149241a` |
| Commit count | `9` |
| Tracked files | `358` |
| Stage-zero index entries | `358` |
| Higher-stage entries | `0` |
| Tracked/index modes | all `100644` |
| Modified tracked paths | `0` |
| Staged paths | `0` |
| Untracked paths with `-uall` | `4` |
| Total status paths with `-uall` | `4` |
| Remotes | `0` |
| Tags | `0` |
| Package directories | `8` |
| Package manifests | `8` |
| Composer manifests including root | `9` |
| Production PHP files | `53` |
| Test PHP files | `85` |
| PHP CS Fixer-scoped PHP files | `138` |
| Production declarations | `53` |
| Explicit `Api` attributes | `51` |

The accepted checkpoint commit is unsigned, has one parent, and has subject:

```text
Establish Kongono trusted diagnostic handling through Phase 1H
```

The accepted `composer.lock` identity is:

```text
bytes: 182894
SHA-256: 804B276750164A634D25ED97A613D9B51FC1E59F9D170A9D345C733299B3498B
```

### 3.1 Protected implementation and tooling set

The Phase 2A protected set contains exactly:

- root `composer.json` and `composer.lock`;
- all eight `packages/*/composer.json` manifests;
- `.php-cs-fixer.dist.php`, `phpstan.neon.dist`, and `phpunit.xml.dist`;
- all 53 production PHP files under package `src/` directories;
- all 85 PHP test and fixture files under root or package `tests/` directories.

Construct an in-memory manifest over these paths using:

```text
<repository-relative path with /><NUL><decimal byte length><NUL><uppercase SHA-256><LF>
```

Use an explicitly typed string array and `StringComparer.Ordinal`. Required result:

```text
entries: 151
manifest bytes: 20081
manifest SHA-256: D28E569D6D0F1D2DFC98856BC387F279767D79AC1972923294BD28DF3DFB5622
```

Do not use culture-sensitive sorting and do not create a repository manifest file.

### 3.2 Immutable Phase 1I-family caller records

The four starting untracked paths are exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1I_codex_directive.md` | 30470 | `85E3293A9FF52D35FBFF775E45E77C5BEDDD2D6EF125A31DA469EAADDCF6013B` |
| `docs/task reports/Phase_1I.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md` | 20148 | `2A1687E99B78B7FFE5E877E26E40B8DA73F69F44DA8714FACC82411121923936` |
| `docs/task reports/Phase_1I_1_correction.md` | 18259 | `B53903B9E8D3808D9F13A100D85598786DADAA13539CE2F1EAC824CCFA2BD6A4` |

These records remain untracked, unstaged, absent from `HEAD`, and byte-identical throughout Phase 2A. The original Phase 1I report remains zero bytes.

### 3.3 Expected state after Phase 2A caller setup

After the caller copies this directive and creates the zero-byte report placeholder, before validation or edits:

```text
modified tracked paths: 0
staged paths: 0
untracked paths with -uall: 6
total status paths with -uall: 6
```

The only two new paths are:

```text
docs/dev_plan/kongono_phase_2A_codex_directive.md
docs/task reports/Phase_2A.md
```

---

## 4. Phase 2 milestone decision

Phase 2 is the first runnable server-side web-framework milestone. It is not the full Kongono framework, a 1.0 release, or Laravel feature parity.

Phase 2 ends at Phase 2H unless a later architect-approved correction or ADR explicitly changes the roadmap. Main phases do not automatically continue to `Z`.

### 4.1 Fixed Phase 2 sequence

Record this exact planned sequence:

| Phase | Planned boundary |
|---|---|
| `2A` | Accept the runnable-web milestone, security invariants, A-H roadmap, and end-to-end acceptance boundary; documentation only. |
| `2B` | Decide the minimal HTTP message and interoperability architecture, including an explicit PSR-7/PSR-17 decision; no broader router or runtime implementation. |
| `2C` | Implement only the accepted minimal HTTP message foundation and its production tests. |
| `2D` | Establish and implement deterministic route declaration and matching in bounded slices. |
| `2E` | Establish and implement the explicit middleware and terminal-handler pipeline in bounded slices. |
| `2F` | Establish and implement application bootstrap/composition with Kernel operation-scope ownership and persistent-runtime cleanup rules. |
| `2G` | Establish and implement the first outer web-runtime adapter, response delivery boundary, fail-closed trusted diagnostics integration, and generic non-trusted failure path. |
| `2H` | Build the minimal fixture application, run black-box framework acceptance tests, reconcile documentation, and create the Phase 2 checkpoint. |

This table fixes milestone ownership and order, not exact APIs, package names, file inventories, or implementation size. A boundary may require a narrow architecture subphase and implementation subphase under the same letter only through a later explicit directive. Corrections use decimal suffixes and do not expand the milestone.

### 4.2 End-of-Phase-2 definition of runnable

Phase 2H may call Kongono runnable only when a minimal fixture application can be exercised through a public outer entry boundary and independently proves all of these scenarios:

1. the application bootstraps through an explicit composition root;
2. one inbound web operation becomes one explicit framework request value;
3. the Kernel owns one operation scope for that delivery;
4. deterministic routing selects one terminal application handler;
5. ordered middleware surrounds the terminal handler without ambient current-request state;
6. the handler returns an explicit framework response value;
7. the outer adapter converts or delivers that response without making transport a Kernel dependency;
8. an unmatched route follows a deterministic non-success path;
9. a normal application failure follows a generic non-trusted path by default;
10. trusted developer diagnostics appear only when both typed permissions are affirmative and current-delivery evidence has been established by the outer boundary;
11. denial occurs before diagnostic source access and never falls back to trusted output;
12. at least two sequential operations in one process prove no request, response, permission, route-match, middleware, exception, or application state leaks between operations;
13. failure paths still close the owned Kernel operation scope exactly once;
14. canonical tests require no external network listener and remain deterministic; and
15. the entire path is covered by black-box tests from outside the internal packages.

Exact HTTP status codes, headers, body types, URI model, stream model, route syntax, middleware signature, emitter protocol, and adapter runtime are not selected in Phase 2A.

### 4.3 What Phase 2 does not mean

Phase 2 does not promise:

- ORM, database, migrations, authentication, authorization, sessions, cookies, CSRF, templates, frontend tooling, CLI, events, queues, cache, storage, mail, notifications, scheduling, realtime, agents, extensions, telemetry, deployment, production hardening, or ecosystem parity;
- a public Stable API;
- a network server owned by the Kernel;
- PSR conformance before an explicit decision;
- framework-wide configuration or service-container completion;
- full Application Graph integration;
- release packaging or semantic versioning; or
- production readiness.

---

## 5. Locked Phase 2 architectural invariants

All Phase 2 documentation must preserve these invariants.

### 5.1 Persistent-runtime-first

- A process may serve multiple sequential or overlapping operations.
- No core API may depend on process teardown for cleanup.
- There is no ambient global, static, singleton, thread-local, fiber-local, or container-current request, response, route, principal, tenant, permission, or operation.
- Every inbound delivery has explicit ownership and an explicit Kernel operation scope.
- Request-specific state cannot be retained by long-lived framework objects unless a later explicit scoped-lifetime contract owns and clears it.
- Runtime integrations remain outer adapters; the Kernel does not depend on PHP-FPM, FrankenPHP, RoadRunner, OpenSwoole, SAPI globals, or a specific server.

### 5.2 Trust and input handling

- Every inbound request component is untrusted input until validated for its use.
- Client-controlled query, path, header, cookie, form, body, session, token, or other value cannot enable trusted diagnostics or upgrade either permission.
- Parsing, normalization, limits, duplicate handling, invalid syntax, and rejection behavior must be explicit before implementation.
- Header and body handling must prevent injection, response splitting, unbounded buffering, confused canonicalization, and accidental secret disclosure.
- No request data enters diagnostic Safe fields merely because it is syntactically valid.

### 5.3 Request, routing, middleware, and response separation

- Message values, route matching, middleware orchestration, terminal application handling, and transport delivery remain separately owned concerns.
- Routing is deterministic for the same immutable inputs and declarations.
- Middleware order is explicit and inspectable; no discovery or priority guessing is allowed.
- A response value is not emitted merely by constructing it.
- Response emission remains adapter-owned I/O and cannot occur inside pure message or routing components.
- Business logic must not be structurally trapped in controllers or transport adapters.

### 5.4 Error and diagnostic behavior

- Production failure output is generic and separately modeled; it never reuses or redacts the trusted developer model.
- Trusted HTML remains Protected developer output, not a production response model.
- Bootstrap permission and current-delivery permission remain independent affirmative inputs.
- A true eligibility value or non-null rendered HTML is not authorization proof.
- The outer adapter must establish current-delivery evidence before diagnostic source access.
- Failure in authorization, evidence, activation, projection, rendering, transport, or cleanup must not recursively enable trusted output.
- Exact simultaneous-failure representation remains open until explicitly selected.

### 5.5 Interoperability without architectural surrender

- Phase 2A does not adopt or reject PSR-7, PSR-15, or PSR-17.
- Phase 2B must make an explicit, evidence-backed interoperability decision before message implementation.
- If adopted, external interfaces remain interoperability contracts and do not make a third-party framework Kongono's architecture.
- If not adopted directly, the decision must document adapter cost, ecosystem impact, and future compatibility.
- Symfony, Laravel, another framework, or a runtime server may not become a production foundation through an implicit dependency.

---

## 6. Exact documentation scope

Only the paths in this section may change. Do not infer adjacent edits.

### 6.1 Create exactly two files

```text
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/architecture/phase-2-roadmap.md
```

### 6.2 Modify exactly eleven tracked files

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
```

No other path may change except final population of the caller-created Phase 2A report.

### 6.3 ADR 0043 required contract

Create `docs/adr/0043-phase-2-runnable-web-framework-milestone.md` with:

```text
Title: ADR 0043: Phase 2 Runnable Web Framework Milestone
Status: Accepted
Date: 2026-08-16
```

Use the repository's ADR structure. It must record:

1. the accepted Phase 1 checkpoint identity and eight-package starting point;
2. why Phase 2 is bounded A-H rather than automatically continuing to Z;
3. the exact Section 4.1 sequence;
4. the exact end-of-Phase-2 runnable definition;
5. persistent-runtime ownership and no-ambient-current-state rules;
6. separation of message, routing, middleware, terminal handling, adapter, and emission concerns;
7. fail-closed trusted diagnostics and separate generic production failure behavior;
8. explicit deferral of package names and exact APIs;
9. explicit deferral, not rejection or adoption, of PSR-7, PSR-15, and PSR-17 to Phase 2B;
10. why no implementation occurs in Phase 2A;
11. security, lifecycle, performance, testing, and interoperability consequences;
12. rejected alternatives; and
13. open decisions.

Rejected alternatives must include:

- implementing HTTP before the architecture boundary is accepted;
- placing transport behavior in the Kernel;
- ambient current-request or current-response state;
- making the diagnostic handler an HTTP adapter;
- treating trusted HTML as the production failure model;
- selecting a client-controlled debug flag;
- prematurely locking all Phase 2 APIs or package names;
- assuming every numbered phase must reach Z; and
- claiming Phase 2 is the full framework or 1.0.

Open decisions must include exact message types, PSR decisions, package names, dependency graph, URI/header/body/stream rules, route grammar and conflicts, middleware contract, composition mechanism, runtime adapter, delivery evidence, production failure model, status/header/body mappings, limits, cleanup, simultaneous failures, and API status.

### 6.4 Phase 2 roadmap document

Create `docs/architecture/phase-2-roadmap.md` as the approachable execution roadmap. It must contain:

- purpose and current starting point;
- the A-H table from Section 4.1;
- a dependency sequence showing that later work cannot outrun earlier contracts;
- the end-of-Phase-2 black-box acceptance scenarios;
- security and persistent-runtime gates that apply to every subphase;
- current versus planned behavior;
- what Phase 2 deliberately excludes;
- how corrections are labeled without extending the main roadmap; and
- links to ADR 0043, runtime model, error-handling guide, package-layout guide, doctrine, and ADR index.

The roadmap must not contain estimated calendar dates, unsupported effort estimates, marketing promises, or invented API signatures.

### 6.5 Required reconciliation by file

Apply only the following conceptual updates:

- `ARCHITECTURE.md`: record Phase 1 closure, Phase 2A's documentation-only milestone, the A-H finish line, and continued absence of HTTP implementation.
- `README.md`: add a concise current-status and roadmap link; state that Kongono is tested component architecture, not yet a runnable web framework.
- `SECURITY.md`: add Phase 2 request-boundary and diagnostic-delivery invariants as future requirements, clearly labeled unimplemented.
- `docs/doctrine.md`: correct the stale current-runtime statement from seven to eight packages, include the implemented Diagnostic Handler and Phase 1 checkpoint, and add the bounded Phase 2 direction without claiming implementation.
- `docs/adr/0004-microkernel-capability-architecture.md`: add a Phase 2A current-reality amendment, correct the stale open-boundary count to eight implemented packages, and link ADR 0043.
- `docs/adr/README.md`: index ADR 0043 in exact sequence with status and title.
- `docs/architecture/overview.md`: distinguish the eight-package current topology from the planned Phase 2 web flow and link the roadmap.
- `docs/architecture/runtime-model.md`: add the planned per-web-delivery ownership and cleanup acceptance requirements without inventing scoped storage.
- `docs/engineering/error-handling.md`: add the planned Phase 2 outer-error integration boundary while retaining all current absence statements and open decisions.
- `docs/engineering/package-layout.md`: state that Phase 2A creates no ninth package and package names/dependencies remain for Phase 2B and later explicit decisions.
- `docs/glossary.md`: add concise entries for `Phase 2`, `Runnable fixture application`, and `Web runtime adapter`, each clearly distinguishing planned from implemented behavior.

Do not rewrite historical phase evidence merely because it used an accurate past package count.

---

## 7. Prohibited scope

Phase 2A must not:

- create or edit any PHP file, test, fixture, Composer manifest, lockfile, tool configuration, package README, or generated artifact;
- create a package directory or select a package name or namespace;
- add, remove, update, or authorize any dependency or Composer plugin;
- adopt, implement, reject permanently, or claim conformance with PSR-7, PSR-15, or PSR-17;
- define exact request, response, URI, header, body, stream, route, middleware, handler, emitter, adapter, or bootstrap APIs;
- implement HTTP, routing, middleware, transport, emission, configuration, application composition, diagnostic delivery, or a production error model;
- use Symfony, Laravel, a PSR implementation, a server runtime, or another framework as an implicit foundation;
- change the Kernel, Dependency Injection, Application Graph, Diagnostics, Activation, Presentation, or Handler runtime contract;
- change API status metadata;
- add CI, benchmarks, network listeners, subprocess servers, browser tests, fixtures, or golden files;
- populate the Phase 2A report before all non-report gates pass;
- stage, commit, push, tag, branch, add a remote, or mutate history;
- edit or stage any Phase 1I-family caller record;
- begin Phase 2B; or
- claim Kongono is runnable, production-ready, full-featured, or released.

---

## 8. Gate A - identity and baseline

Before broad validation or editing, prove:

1. all required authorities were read completely;
2. the independently supplied Phase 2A directive identity matches;
3. the Phase 2A report is the required zero-byte placeholder;
4. all four Phase 1I-family caller records match Section 3.2;
5. branch, history, tree, parent, index, modes, refs, counts, and exact `-uall` inventory match Section 3 after caller setup;
6. there are no modified tracked or staged paths;
7. `composer.lock` matches its locked identity;
8. the 151-entry protected-set manifest matches exactly;
9. every current status path is an ordinary default-stream non-reparse file; and
10. the thirteen authorized documentation targets are the only paths eligible for Phase 2A changes.

If any item fails, stop before `composer quality` and before editing.

---

## 9. Gate B - pre-change quality

Run:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Accepted warnings remain limited to missing license metadata, unbounded local development constraints, and the optional curl performance warning if actually emitted. Report exact warnings. Any new warning category is a stop condition.

After quality, re-prove Gate A. No accepted byte may have changed.

---

## 10. Gate C - documentation implementation

Create and modify only the exact Section 6 paths. Use `apply_patch` for every repository edit.

Requirements:

- strict UTF-8 without BOM;
- LF-only;
- exactly one final LF for every non-empty file;
- no trailing whitespace, NUL, conflict marker, machine-specific absolute path, secret, credential, key, or private email;
- valid local Markdown links;
- repository-standard headings and relative-link style;
- current and future behavior explicitly separated;
- no zero-byte created documentation file; and
- no generated content, binary file, executable bit, symlink, reparse point, or alternate data stream.

The executor may make wording decisions needed to satisfy the exact conceptual requirements, but may not create new architecture beyond Sections 4-6.

---

## 11. Gate D - focused architecture validation

Before final quality, prove:

1. exactly two non-caller documentation files were created;
2. exactly eleven tracked files were modified;
3. no other path changed except the still-zero Phase 2A report;
4. ADR 0043 has exact Accepted status and date;
5. the ADR index is sequential through 0043;
6. the A-H table is consistent between ADR 0043 and the roadmap;
7. all fifteen runnable acceptance scenarios are present and materially equivalent;
8. all Phase 2 invariants from Section 5 are represented;
9. package names and exact APIs remain unselected;
10. PSR-7, PSR-15, and PSR-17 are deferred to Phase 2B rather than adopted or rejected;
11. no document claims Phase 2A implements HTTP or makes Kongono runnable;
12. no document claims Phase 2 is the full framework or 1.0;
13. current-reality package counts are eight except accurate historical statements;
14. `docs/doctrine.md` no longer says the current runtime has seven packages;
15. ADR 0004 no longer says boundaries beyond six implemented packages remain open;
16. the protected 151-entry implementation/tooling manifest remains exact;
17. `composer.lock` remains exact;
18. all four Phase 1I-family caller records remain exact;
19. all local Markdown links resolve;
20. strict text and file-safety checks pass; and
21. `git diff --check` passes.

Do not add a test or script merely to validate prose. Use read-only focused inspection.

---

## 12. Gate E - final quality and Git proof

Run final:

```powershell
composer quality
```

Required result remains exactly:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Then prove:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
commit count: 9
tracked modified paths: 11
untracked paths with -uall before report population: 8
total status paths with -uall: 19
staged paths: 0
remotes: 0
tags: 0
```

The eight untracked paths must be:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/task reports/Phase_1I_1_correction.md
docs/dev_plan/kongono_phase_2A_codex_directive.md
docs/task reports/Phase_2A.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/architecture/phase-2-roadmap.md
```

No staging or commit is authorized. Re-prove all Gate D items after final quality.

---

## 13. Gate F - completion report

Populate `docs/task reports/Phase_2A.md` only after every non-report gate passes.

The report remains untracked, unstaged, absent from `HEAD`, strict UTF-8 without BOM, LF-only, free of NUL and trailing whitespace, and terminated by exactly one final LF.

Report population changes no status-path count. Final status remains:

```text
tracked modified paths: 11
untracked paths with -uall: 8
total status paths with -uall: 19
staged paths: 0
```

Return the report's final byte count and uppercase SHA-256 outside the report body.

---

## 14. Acceptance criteria

Phase 2A is accepted only if every item passes:

1. all authorities were read completely;
2. independent directive identity and zero-byte report matched;
3. the checkpointed Git baseline and four caller records matched;
4. the 151-entry protected manifest and lockfile identity matched;
5. baseline quality passed with 254 tests and 4,279 assertions;
6. exactly two authorized documentation files were created;
7. exactly eleven authorized tracked files were modified;
8. no other path changed except final report population;
9. ADR 0043 is Accepted, dated 2026-08-16, indexed, and complete;
10. the roadmap fixes Phase 2A-H and the end-of-Phase-2 acceptance boundary;
11. documentation clearly closes Phase 1 and does not claim Phase 2 implementation;
12. the current package count is reconciled to eight;
13. persistent-runtime and no-ambient-current-state rules are preserved;
14. inbound trust, parsing, limit, and disclosure requirements are explicit;
15. message, routing, middleware, terminal handling, adapter, and emission concerns remain separated;
16. trusted diagnostics remain fail-closed and production failure output remains separate;
17. PSR and exact API/package decisions remain deferred to Phase 2B;
18. Phase 2 is not called the full framework, 1.0, or production-ready;
19. no source, test, manifest, lockfile, tooling, package README, dependency, or runtime behavior changed;
20. final quality passed with 254 tests and 4,279 assertions;
21. documentation links, encoding, whitespace, stream, reparse, mode, secret, and file-safety checks passed;
22. Git history, refs, index, caller records, protected bytes, and lockfile remained unchanged;
23. no path was staged or committed;
24. the report was populated only after all non-report gates passed; and
25. Phase 2B was not started.

No partial result is a pass.

---

## 15. Standard executor completion report

Use exactly these headings, in this order:

```markdown
# PHASE 2A TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. PHASE 2 ROADMAP DECISION

## 8. ARCHITECTURAL DECISIONS

## 9. DEVIATIONS FROM DIRECTIVE

## 10. DEPENDENCIES ADDED OR REMOVED

## 11. ENVIRONMENT

## 12. COMMANDS RUN

## 13. VALIDATION RESULTS

## 14. DOCUMENTATION REVIEW

## 15. SECURITY AND FILE-SAFETY REVIEW

## 16. WARNINGS AND KNOWN ISSUES

## 17. QUESTIONS OR BLOCKERS

## 18. GIT DIFF SUMMARY

## 19. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- `Complete` only if all 25 criteria pass;
- independently verified directive identity;
- starting Git and caller-record evidence;
- protected manifest and lockfile proof;
- pre-change and final quality evidence;
- exact created and modified path inventories;
- concise A-H roadmap and runnable acceptance summary;
- confirmation that PSR, package, and API decisions remain open;
- explicit current-versus-planned reconciliation;
- documentation, security, file-safety, link, and Git evidence;
- exact warnings and any authorized unchanged retry;
- confirmation that no implementation or dependency changed; and
- explicit pass or fail for every acceptance criterion.

The report's final nonblank line must be exactly:

```text
Await architect review before Phase 2B.
```

Return the report byte count and uppercase SHA-256 separately after completion.

---

## 16. Required final architectural outcome

Phase 2A leaves Kongono's runtime exactly as checkpointed through Phase 1H: eight first-party packages, 53 production declarations, 254 tests, and no HTTP implementation.

It adds a bounded, inspectable destination:

```text
Phase 2A architecture
    -> Phase 2B HTTP/interoperability decision
    -> Phase 2C message foundation
    -> Phase 2D deterministic routing
    -> Phase 2E middleware and terminal handling
    -> Phase 2F bootstrap and operation ownership
    -> Phase 2G outer adapter and safe failure delivery
    -> Phase 2H fixture app, black-box acceptance, and checkpoint
```

At the end of Phase 2, Kongono may be called a first runnable web framework only if the complete black-box acceptance boundary passes. It is still not the full framework or a 1.0 release.

Do not begin Phase 2B.
