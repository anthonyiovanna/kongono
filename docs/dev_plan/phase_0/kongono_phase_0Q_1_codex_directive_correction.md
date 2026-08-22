# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0Q.1

## Authorize Exact Composer Path-Reference Refresh and Resume Phase 0Q

## Mission

Resume Phase 0Q from its mandatory Composer stop without reverting, repeating,
or widening the implementation.

The required Phase 0Q Composer command correctly added the Application Graph
dependency on `kongono/diagnostics`. Composer 2.10.2 also refreshed the existing
Diagnostics local path-package reference so it matches the already accepted
Diagnostics manifest. Phase 0Q's lock-delta wording did not authorize that
second mechanical refresh, so the executor correctly stopped.

This correction authorizes the exact generated lock state now present. It also
reconciles the fact that the caller record `docs/task reports/Phase_0Q.md` is now
populated despite Phase 0Q requiring it to remain zero bytes until completion.
That file becomes an immutable blocked-run record. Do not edit or replace it.
The successful resumed run must be reported only in the new caller-created
`Phase_0Q_1_correction.md` placeholder.

All Phase 0Q production, schema, API, security, test, documentation, prohibited-
scope, and validation contracts remain unchanged except where this correction
explicitly supersedes repository counts, lock-delta wording, and report
ownership.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before continuing:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0Q_codex_directive.md`;
3. `docs/task reports/Phase_0Q.md`;
4. this correction directive;
5. the Phase 0P and Phase 0P.1 directives and reports named by Phase 0Q;
6. ADRs 0026 through 0029;
7. `docs/engineering/error-handling.md`;
8. the current `composer.json`, `composer.lock`,
   `packages/application-graph/composer.json`, and
   `packages/diagnostics/composer.json`; and
9. all Phase 0Q authorized production and test files before editing them.

The original Phase 0Q directive remains authoritative unless this correction
explicitly says otherwise.

If any value, hash, path, reference, report state, Git fact, dependency,
inventory, or source boundary below differs:

```text
STOP
ASK
DO NOT GUESS
```

Do not repair, normalize, revert, regenerate, rerun the update, or clean up an
unexpected difference.

---

# 2. EXACT STOPPED BASELINE

Before the caller adds the Phase 0Q.1 records, verify exactly:

```text
Branch: main
Local branches: 1
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit count: 5
Tracked files in HEAD: 214
Tracked modes: all 100644
Tracked modifications: 29
Untracked paths: 34
Total status paths: 63
Staged paths: 0
Tracked diff: 29 files changed, 774 insertions, 83 deletions
Remotes: 0
Tags: 0
```

The 29 tracked modifications are the exact 28-path Phase 0Q Section 2.2
inventory plus:

```text
packages/application-graph/composer.json
```

`composer.lock` was already one of the 28 accepted modified paths. No other
tracked path may have joined the inventory.

The 34 untracked paths are the exact 32-path Phase 0Q Section 2.3 inventory plus:

```text
docs/dev_plan/kongono_phase_0Q_codex_directive.md
docs/task reports/Phase_0Q.md
```

The index, refs, tree, parent, history, modes, remotes, and tags must remain
unchanged throughout this correction and the resumed implementation.

---

# 3. EXACT STOPPED ARTIFACT IDENTITIES

Verify:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0Q_codex_directive.md` | 50,661 | `F0F29634FB9B4943BD43EC398999CBA9099BAA508CA57CFE70EAADE5E600E3E3` |
| `docs/task reports/Phase_0Q.md` | 4,001 | `74EC1B831B492C4AEBD6A9F2A072D6608F90CC439AE05818A825790D228FE0FF` |
| `composer.json` | 2,464 | `914F455AF3CEE2C721E786939497931ABD648802188540DE5AEED216A16548C1` |
| `packages/application-graph/composer.json` | 436 | `2FC1ECD9E6E05796F222993EFE96E0AC175E546D5B0124318884BC9F2A9A154B` |
| `packages/diagnostics/composer.json` | 388 | `B34CFD0C6BB31746BAE3061A495061D29B6C5A6B47BDF9EAF592C261FA1024CD` |
| `composer.lock` | 178,429 | `879ABC517B15C16B7AFD25C4CE2958619C90D56F9F591B070281E2F892E4806D` |

Also retain every Phase 0Q Section 2.4 authority identity not explicitly
superseded above.

All six files above must be strict UTF-8 where textual, have ordinary file mode,
and have no non-default alternate data stream. The Phase 0Q directive and report
are untracked, unstaged, and absent from `HEAD`.

---

# 4. CORRECTION CALLER SETUP

The caller will copy this correction directive to:

```text
docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md
```

and create:

```text
docs/task reports/Phase_0Q_1_correction.md
```

Before resuming, verify the copied correction directive is byte-identical to the
architect-delivered artifact. Record its bytes and SHA-256.

The new correction report must be exactly zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup, the exact pre-resume state is:

```text
Tracked modifications: 29
Untracked paths: 36
Total status paths: 65
Staged paths: 0
```

Do not populate the correction report until every non-report Phase 0Q and 0Q.1
gate passes.

---

# 5. ARCHITECT AUTHORIZATION: EXACT LOCK STATE

The current generated lock state is accepted.

## 5.1 Application Graph package

`packages/application-graph/composer.json` must retain exactly the authorized
runtime requirements:

```json
"require": {
  "kongono/diagnostics": "*",
  "kongono/kernel": "*",
  "php": ">=8.5"
}
```

Its current bytes and hash in Section 3 are the accepted post-update identity.

The `kongono/application-graph` lock entry must be:

```text
version: dev-main
dist.type: path
dist.url: packages/application-graph
dist.reference: 02e2bf168519fff9a164e863c082480117e4e106
require.kongono/diagnostics: *
require.kongono/kernel: *
require.php: >=8.5
```

## 5.2 Diagnostics reference refresh

The `kongono/diagnostics` manifest is unchanged from accepted Phase 0P bytes.
Its lock entry must retain the same version, URL, requirements, type, autoload,
autoload-dev, description, and transport options while using this refreshed
reference:

```text
b0deed9216f789bbed4f294ca2991d9b1683f0de
```

This reference is not an architectural or source change. Under Composer 2.10.2,
the root path repository has the effective option `relative => true`; its
config/auto reference is mechanically:

```php
sha1($manifestBytes . serialize(['relative' => true]))
```

Applied to the exact Section 3 manifests, this must independently reproduce:

```text
packages/application-graph/composer.json
02e2bf168519fff9a164e863c082480117e4e106

packages/diagnostics/composer.json
b0deed9216f789bbed4f294ca2991d9b1683f0de
```

The stale pre-update Diagnostics reference beginning `767bbae` is not retained
or restored.

## 5.3 Entire accepted lock result

The current `composer.lock` identity is locked by Section 3. It must also have:

```text
root content-hash: f81764535f56b46162cab590e05a0072
third-party package entries: 66
```

No third-party package identity, version, source, distribution, requirement,
stability, or policy changed during the stopped update. No root manifest,
plugin policy, advisory policy, platform requirement, or package source changed.

The exact Phase 0Q lock delta is now authorized as:

1. Graph's added `kongono/diagnostics: *` requirement;
2. Graph's mechanically refreshed local path reference;
3. Diagnostics' mechanically refreshed local path reference; and
4. no other change relative to the accepted Phase 0P lock state.

Do **not** rerun `composer update`, edit either manifest, edit the lock manually,
restore either old reference, or generate another lock identity. Continue from
the exact accepted current lock.

Later validation commands may read the lock, validate it, audit it, or generate
autoload metadata; they may not change it. Re-prove its Section 3 bytes/hash at
the end.

---

# 6. BLOCKED PHASE 0Q REPORT RECONCILIATION

`docs/task reports/Phase_0Q.md` is now a historical blocked-run record. Preserve
it byte-for-byte at the Section 3 identity. Do not fix its headings, prose, or
internal contradictions.

Its statements that the Phase 0Q report remained zero bytes and that no report
mutation occurred are not accepted as factual final-state evidence. The file is
4,001 bytes and was populated during the blocked run. Phase 0Q.1 records that
discrepancy explicitly rather than rewriting history.

The original Phase 0Q requirements that `Phase_0Q.md` remain zero until success,
become the successful final report, follow the 16-section contract, or receive
later population are superseded and cannot be satisfied from the current state.

The only successful completion report for the resumed implementation is:

```text
docs/task reports/Phase_0Q_1_correction.md
```

That new placeholder must remain zero bytes until all non-report checks pass.

---

# 7. RESUME BOUNDARY

Resume Phase 0Q at original implementation-sequence step 4:

```text
implement exact catalog schemas/context construction
```

Do not repeat the already completed manifest edit or Composer update. Do not
repeat baseline quality merely to restart; the blocked report records a passing
baseline of 170 tests and 1,908 assertions. All required focused and final
validation from Phase 0Q Sections 17.2 through 17.5 still applies after the
implementation.

All original Phase 0Q Sections 3 through 15 remain exact, including:

- Graph-only integration for 11 diagnostics;
- exact marker, base, catalog, schema, throw-site, value-token, omission,
  immutability, security, and persistent-runtime contracts;
- exact two executor-created implementation artifacts;
- exact authorized modification inventory;
- preservation of Graph messages, success behavior, failure priority, ordering,
  selectors, and package ownership;
- exact test and documentation requirements;
- ADR 0030;
- exact 40/70/111/110/4 inventories;
- Kernel, DI, and Diagnostics construction failures remaining unintegrated; and
- every prohibited later capability.

No new architecture, production API, schema, dependency, file, test fixture, or
documentation scope is authorized by this correction.

---

# 8. VALIDATION ADDITIONS AND SUPERSESSIONS

Perform all original Phase 0Q validation except the superseded pre-update lock
expectation and `Phase_0Q.md` report requirements.

Additionally prove:

1. current lock bytes/hash remain exactly Section 3 before and after all work;
2. both path references independently equal the Section 5 formula;
3. Diagnostics manifest bytes/hash remain unchanged;
4. root content hash remains exact;
5. all 66 third-party lock entries remain unchanged from the stopped state;
6. no Composer update command was rerun;
7. `Phase_0Q.md` remains exactly 4,001 bytes with its locked hash;
8. only the new correction report was zero before final population; and
9. no file outside the combined Phase 0Q/0Q.1 boundary changed.

The canonical final `composer quality` expectations remain:

```text
zero advisories
platform requirements pass
0 of 110 files fixable
zero PHPStan errors
at least 177 tests
more than 2,000 meaningful assertions
```

If only the unchanged advisory network/cache access is blocked, rerun the
identical quality or audit command once with authorized normal access. Do not
change flags, dependencies, lock content, or security policy.

---

# 9. EXACT FINAL GIT STATE

Before populating the correction report, and again afterward, require:

```text
Branch: main
Local branches: 1
HEAD/tree/parent/history: unchanged
Tracked files in HEAD: 214
Tracked modifications: 39
Untracked paths: 38
Total status paths: 77
Staged paths: 0
Remotes: 0
Tags: 0
Tracked modes: unchanged
Non-default alternate data streams: 0
```

The 39 tracked modifications are exactly the final inventory defined by Phase
0Q Section 17.6.

The 38 untracked paths are the 36-path Phase 0Q final inventory plus exactly:

```text
docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md
docs/task reports/Phase_0Q_1_correction.md
```

The old Phase 0Q report stays populated, untracked, unstaged, and absent from
`HEAD`. Population of the new correction report does not change path counts.

The exact tracked diff statistic after full Phase 0Q implementation is not
preselected. Report the actual statistic. Exact closed path inventories,
unchanged Git identity/index, lock identity, and content contracts are the
authoritative boundary.

---

# 10. CORRECTION ACCEPTANCE CRITERIA

- [ ] Exact 29/34/63/0 stopped state and Section 3 identities match before
  caller setup.
- [ ] Correction directive matches delivered bytes/hash and its report begins
  as the exact zero-byte placeholder.
- [ ] The current Graph manifest and 178,429-byte lock are preserved exactly.
- [ ] Both accepted local path references are independently reproduced by the
  Composer 2.10.2 formula.
- [ ] Diagnostics manifest and source remain unchanged.
- [ ] Root content hash and all 66 third-party entries remain unchanged.
- [ ] No Composer update is rerun and no lock/manual-reference edit occurs.
- [ ] Historical `Phase_0Q.md` remains exactly 4,001 bytes with the locked hash.
- [ ] The correction report explicitly records the old report-state discrepancy.
- [ ] Every non-report Phase 0Q implementation and validation criterion passes.
- [ ] Kernel, DI, and Diagnostics construction failures remain unintegrated.
- [ ] No renderer, error page, disclosure policy, transport, logging, telemetry,
  source lookup, or later capability is added.
- [ ] Final state is exactly 39 modified, 38 untracked, 77 total, zero staged.
- [ ] The new correction report follows Section 11, is nonzero, strict UTF-8,
  LF-only, exactly one-final-LF terminated, untracked, unstaged, and absent from
  `HEAD`.
- [ ] Its final byte count and SHA-256 are returned outside the report.
- [ ] Its next step is exact.

Every criterion must pass. Stop on any new mismatch.

---

# 11. STANDARD CORRECTION COMPLETION REPORT

After all non-report checks pass, populate
`docs/task reports/Phase_0Q_1_correction.md` with exactly these headings, once
each and in order:

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

The report must contain every item required by Phase 0Q Section 19, adjusted for
the accepted lock state and final 39/38/77/0 inventory. It must also state:

- the exact mechanical reference formula and both reproduced references;
- the exact accepted lock bytes/hash and unchanged root content hash;
- the unchanged Diagnostics manifest bytes/hash;
- that no Composer update was rerun;
- that `Phase_0Q.md` is a preserved blocked-run record;
- that its zero-byte/no-report-mutation claims were inaccurate and not used as
  final evidence;
- that only `Phase_0Q_1_correction.md` records successful completion; and
- the correction report's final digest is returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0R.
```

---

# 12. FINAL EXECUTOR INSTRUCTION

Accept and preserve the exact Composer-generated lock state already present.
Do not rerun the update or edit the historical blocked report. Resume Phase 0Q
at Graph diagnostic-context implementation, satisfy every unchanged production,
security, test, documentation, and prohibited-scope contract, write only the new
correction report after complete success, and stop before Phase 0R.
