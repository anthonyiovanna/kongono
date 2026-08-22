# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0X.1

## Exact ADR 0034 Current-State Reconciliation

## Mission

Correct three stale Phase 0W future-tense blocks in
`docs/adr/0034-trusted-developer-diagnostic-projection.md` after the otherwise
accepted Phase 0X implementation.

Phase 0X production code, tests, package graph, Composer lock, tooling,
documentation outside ADR 0034, and quality results are accepted and must not be
reopened. This is an exact documentation-only correction. Make only the three
whole-block replacements in Section 4, validate the closed result, and populate
the separate Phase 0X.1 correction report.

# 1. AUTHORITY AND STOP RULE

Read completely before acting:

1. `AGENTS.md`
2. `docs/adr/0034-trusted-developer-diagnostic-projection.md`
3. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`
4. `docs/dev_plan/kongono_phase_0X_codex_directive.md`
5. `docs/task reports/Phase_0X.md`
6. this correction directive.

The accepted ADRs, Phase 0X directive, and this correction are authority. The
executor is not authorized to reinterpret or expand them.

Stop without mutation if:

- any baseline identity or status count in Section 2 differs;
- any old block in Section 4 is absent, duplicated, or not byte-exact;
- any new block already exists;
- the correction directive does not match the architect-delivered identity;
- the correction report is absent, populated, staged, tracked, or in `HEAD`;
- the target is not an ordinary UTF-8/LF file with one default data stream;
- another path would need to change; or
- a Git, PHP, Composer, dependency, code, test, runtime, security, or
  architectural decision appears necessary.

On a stop, preserve the zero-byte report and return the exact blocker outside
it. Do not guess, normalize, stage, commit, reset, clean, or repair adjacent
work.

# 2. LOCKED PRE-CORRECTION BASELINE

Before caller setup, the accepted Phase 0X worktree is:

```text
branch                         main
only local branch              main
HEAD                           7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
tree                           b1d0057925158e0f0f37ca13694d1d0d525ce5f0
parent                         75749a37a1844f65cc4aa6e9bcb2579ea30235f4ce
commit count                   6
remotes                        0
tags                           0
staged paths                   0
tracked modifications          26
untracked paths                25
total status paths             51
tracked diff                   26 files, 613 insertions, 124 deletions
package directories            6
package manifests              6, plus root = 7
production declarations        48
test PHP files                 79
```

Locked identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 11600 | `976B99663863B241B8BE10704399E583762D56C8A84305C8CA0000AC9EC672EE` |
| `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md` | 7149 | `C9F8522CAA058A77681D129878463CEA1E02EA658AF3F4A35F5EA66B8BF0775C` |
| `docs/dev_plan/kongono_phase_0X_codex_directive.md` | 47300 | `2F21C87ED746FE616469A638C187D911D91023B206373F25594EFBF0BC33481A` |
| `docs/task reports/Phase_0X.md` | 19614 | `6035EFA808A8C86477CF6AF3E60C7B8E427A8DC0BF2C3DA645DDC2D5CBDF8FC7` |
| `composer.lock` | 180654 | `FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387` |

The independently repeated final Phase 0X quality result is accepted evidence:

```text
security advisories     0
platform                pass on PHP 8.5.9
PHP CS Fixer            0 of 127 files fixable
PHPStan                 0 errors
PHPUnit                 231 tests, 3973 assertions
```

Do not rerun Composer, PHPUnit, PHPStan, PHP CS Fixer, autoload generation, or
syntax checks in this correction. No PHP, manifest, lock, tool configuration,
or dependency changes are authorized.

# 3. REQUIRED CALLER SETUP

The caller adds exactly:

```text
docs/dev_plan/kongono_phase_0X_1_codex_directive_correction.md
docs/task reports/Phase_0X_1_correction.md
```

The directive must match the architect-delivered byte count and SHA-256. The
report must be an ordinary untracked zero-byte file with SHA-256
`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`,
must be absent from `HEAD`, and must not be staged.

After setup and before correction, require exactly 26 tracked modifications, 27
untracked paths, 53 total status paths, and zero staged paths. The only new
status paths relative to Section 2 are the two caller paths above.

# 4. EXACT WHOLE-BLOCK REPLACEMENTS

Use literal ordinal whole-block replacement. Each old block must occur exactly
once and each new block must occur zero times before editing. After editing,
each old block must occur zero times and each new block exactly once.

## 4.1 Current root consumption

Replace exactly:

```text
No existing package may depend on Diagnostic Presentation. There is no reverse
edge, service registration, Kernel binding, DI definition, Graph node, ambient
discovery, or universal exception boundary. The graph remains acyclic. A later
root manifest may consume the leaf only for monorepo testing after an
implementation directive creates it.
```

with exactly:

```text
No existing package may depend on Diagnostic Presentation. There is no reverse
edge, service registration, Kernel binding, DI definition, Graph node, ambient
discovery, or universal exception boundary. The graph remains acyclic. The
framework root consumes the leaf through its exact `@dev` requirement for
monorepo integration and testing; this creates no reverse package edge or
runtime binding.
```

## 4.2 Implemented calling and failure boundary

Replace exactly:

```text
This union creates no universal marker. It excludes arbitrary `Throwable`, the
context-free Diagnostics marker, raw metadata strings, arrays, maps, and generic
objects. Direct arbitrary construction is not part of the planned Public calling
contract. A source satisfying more than one marker is ambiguous and must be
rejected before any metadata or context accessor is called. Exact package-local,
context-free construction failures and identifiers remain for implementation.
```

with exactly:

```text
This union creates no universal marker. It excludes arbitrary `Throwable`, the
context-free Diagnostics marker, raw metadata strings, arrays, maps, and generic
objects. Direct arbitrary construction is not part of the Public calling
contract. A source satisfying more than one marker is ambiguous and must be
rejected before any metadata or context accessor is called. Phase 0X implements
the exact package-local, context-free construction failures and identifiers
recorded in ADR 0035.
```

## 4.3 Historical architecture-only rejection

Replace exactly:

```text
- Empty or speculative package, manifest, namespace, type, test, or source stubs
  are rejected in this architecture-only phase.
```

with exactly:

```text
- Empty or speculative package, manifest, namespace, type, test, or source stubs
  were rejected in Phase 0W; Phase 0X creates only the exact implemented
  projection boundary authorized by ADR 0035.
```

Do not reflow, wrap, reorder, rephrase, normalize, or edit any other byte in ADR
0034.

# 5. REQUIRED FINAL ADR IDENTITY

After the three replacements, ADR 0034 must be exactly:

```text
bytes       11736
SHA-256     5602E6257E5107F69AD87662EFFF4228E30B6E3F9D30E85123D8A5AA50161551
```

It must remain strict UTF-8 without BOM, LF-only, no CR/NUL/trailing whitespace,
exactly one final LF, an ordinary file, one default data stream, no reparse
point, and untracked/unstaged/absent from `HEAD` as it was before correction.

# 6. CLOSED FILE BOUNDARY

Executor content changes are limited to:

```text
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/task reports/Phase_0X_1_correction.md
```

The correction report may be populated only after every validation gate passes.
Preserve every other tracked, untracked, ignored, caller-managed, source, test,
manifest, lock, configuration, documentation, and report path byte-for-byte.

In particular, do not change or replace the accepted Phase 0X report. Its
Complete claims are historical executor evidence; Phase 0X.1 records the
architect-discovered ADR reconciliation separately.

# 7. VALIDATION

Perform only read-only/document validation:

1. prove each old/new occurrence count before and after replacement;
2. prove the final ADR identity from Section 5;
3. confirm ADR 0034 now states current root consumption, current implemented
   failure foundation, current non-Public direct construction, and the
   historical Phase 0W stub rejection;
4. confirm ADR 0034 and ADR 0035 agree on the current six-package graph and
   deferred activation/rendering/production/transport work;
5. search current nonhistorical documentation for the stale phrases `A later
   root manifest may consume`, `remain for implementation`, `planned Public
   calling contract`, and `this architecture-only phase`; require zero matches;
6. validate all local Markdown links;
7. verify strict encoding, newline, whitespace, ordinary-file, stream, reparse,
   mode, and secret-pattern safety for the two correction paths;
8. run `git diff --check` and direct untracked-file validation;
9. prove every locked identity except ADR 0034 remains exact; and
10. prove final Git state.

Do not run `composer quality` or another PHP/Composer command. The correction
cannot affect executable behavior, and the accepted Phase 0X quality evidence
must remain historical rather than being replaced by a later unrelated run.

# 8. FINAL GIT STATE

Before and after report population require:

```text
branch                  main
HEAD                    7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
staged paths            0
tracked modifications   26
untracked paths         27
total status paths      53
tracked diff            26 files, 613 insertions, 124 deletions
remotes                 0
tags                    0
```

Only the byte identities of ADR 0034 and the correction report change. The
path inventory, tracked diff, index, `HEAD`, tree, parent, history, branch,
refs, remotes, tags, modes, and all other file identities remain unchanged.

# 9. PROHIBITED SCOPE

Do not:

- edit a second architecture or documentation file;
- edit, replace, append to, or correct the Phase 0X report;
- change PHP, tests, fixtures, manifests, lock, autoload, tooling, dependencies,
  APIs, runtime behavior, package structure, or output behavior;
- add an ADR, package, type, test, renderer, trusted selector, production model,
  adapter, integration, or future capability;
- run Composer, PHP quality tools, dependency commands, formatters, or code
  generators;
- stage, commit, tag, branch, push, reset, clean, stash, checkout, amend, move,
  delete, or mutate Git history, refs, index, configuration, remotes, or hooks;
- normalize unrelated line endings, whitespace, ordering, prose, or file bytes;
  or
- begin Phase 0Y.

# 10. ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Baseline, caller setup, directive identity, zero-byte report, Git state,
  and locked identities are exact.
- [ ] Each old block occurs exactly once before and zero times after correction.
- [ ] Each new block occurs zero times before and exactly once after correction.
- [ ] ADR 0034 is exactly 11,736 bytes with the required SHA-256.
- [ ] Only ADR 0034 and the correction report receive content changes.
- [ ] Phase 0X source, tests, dependencies, lock, tooling, implementation docs,
  and report remain byte-identical.
- [ ] Current root consumption and implemented failure foundation are stated as
  current; Phase 0W's architecture-only status is historical.
- [ ] ADRs 0034 and 0035 are consistent, while activation, rendering,
  production presentation, transports, and runtime integration remain future.
- [ ] The four stale phrases have zero current nonhistorical matches.
- [ ] Links, encoding, whitespace, newline, ordinary-file, stream, reparse,
  mode, secret-pattern, and `git diff --check` gates pass.
- [ ] No PHP/Composer/quality command or unauthorized mutation was performed.
- [ ] Final Git state is exactly 26 modified, 27 untracked, 53 total, zero
  staged, and the tracked diff remains 613 insertions/124 deletions.
- [ ] The correction report has the exact headings and final external identity.

# 11. REQUIRED CORRECTION REPORT

Populate `docs/task reports/Phase_0X_1_correction.md` only after all gates pass.
Use exactly:

```text
# PHASE 0X.1 CORRECTION TASK COMPLETION REPORT

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
```

Report the exact old/new occurrence counts, final ADR identity, preserved locked
identities, non-execution of PHP/Composer commands, link/file-safety results,
and final Git state. Distinguish the correction from the accepted Phase 0X
implementation and do not claim new runtime work.

The exact next step is:

```text
Await architect review before Phase 0Y.
```

After population, verify strict UTF-8/LF, exact heading order, one final LF,
zero staged paths, absence from `HEAD`, and unchanged 26/27/53 status counts.
Return the report byte count and SHA-256 outside its body; do not embed a
self-digest.

# 12. FINAL OUTCOME

Correct only ADR 0034's stale future-tense description of work completed by
Phase 0X. Preserve the accepted implementation and all later presentation,
trust, production, transport, and runtime boundaries unchanged.
