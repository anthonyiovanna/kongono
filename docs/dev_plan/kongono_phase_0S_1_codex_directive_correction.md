# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0S.1

## Exact ADR Current-State Reconciliation

## Mission

Preserve the accepted Phase 0S runtime, package, namespace, Composer, lockfile,
test, tooling, security, and file-safety implementation exactly. Correct only:

1. ADR 0029 still describes integration of all 42 original Kernel, Graph, and DI
   failures as open, although Phase 0Q integrated all 11 Graph failures.
2. ADR 0031 mixes historical Phase 0R topology with wording that says the old
   cycle would form now and already-completed Phase 0S effects will occur later.

Apply only the six literal whole-block replacements in Section 5. Do not modify
runtime source, tests, manifests, lockfile, tooling, another document, a prior
directive/report, or Git state.

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect. Read completely:

1. AGENTS.md
2. ARCHITECTURE.md
3. docs/doctrine.md
4. docs/documentation-standard.md
5. docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
6. docs/adr/0030-application-graph-diagnostic-context-integration.md
7. docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
8. docs/dev_plan/kongono_phase_0S_codex_directive.md
9. docs/task reports/Phase_0S.md
10. this Phase 0S.1 directive
11. the zero-byte Phase 0S.1 report

Stop without changing anything, populating the report, staging, committing, or
guessing if:

- the baseline or identities in Sections 2 and 3 do not match;
- an old block is not present exactly once or a new block is already present;
- a replacement requires changing surrounding prose or another file;
- Git, file safety, package/runtime state, or caller records differ;
- the correction cannot be literal;
- any editorial or architectural choice remains.

When blocked, keep the report zero bytes and return the exact mismatch, options,
tradeoffs, and recommendation outside it.

# 2. LOCKED PRE-CALLER PHASE 0S BASELINE

Before the caller adds this directive and report:

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
untracked paths                52
total status paths             124
staged paths                   0
tracked diff                   72 files, 1,863 insertions, 263 deletions
binary tracked-diff Git hash    C1F085B66D4600156438EF9DD0F980679CDCEAC3
production declarations        40
test PHP files                 71
source plus test PHP files     111
syntax entries                 112
package directories            5
package manifests              5, plus root = 6
~~~

The accepted managed Git global-ignore warning is tolerable only with closed
inventory verification. No other mismatch is accepted.

The architect independently reran canonical quality:

~~~text
Composer advisories            0
platform                       pass on PHP 8.5.9
PHP CS Fixer                    0 of 111 files fixable
PHPStan                         0 errors
PHPUnit                         187 tests, 2,324 assertions
~~~

This evidence does not replace correction validation.

# 3. LOCKED CONTENT IDENTITIES

~~~text
docs/dev_plan/kongono_phase_0S_codex_directive.md
  39,679 bytes
  SHA-256 3F95ED47B136315619462744D46AEFFC099FA5FFBEBA49BF7427F23219F71092

docs/task reports/Phase_0S.md
  18,032 bytes
  SHA-256 C1769308F457575BAD4946AB011D6ACF24E23E20A56A0F798E09CC895568D998

docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
  5,742 bytes
  SHA-256 42C1FCDC99CCC4B299535B9871330637532CA91F326B0705B3ED6ECF1AF578F9

docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
  8,159 bytes
  SHA-256 185642DB3A0961A4B8587D4BFCDE4088561E00C232E9311A3ABC2EE3FC244F41
~~~

The Phase 0S report identity is the independently verified final identity omitted
from the attached handoff envelope. Do not edit that report.

# 4. CALLER SETUP

The caller adds exactly:

~~~text
docs/dev_plan/kongono_phase_0S_1_codex_directive_correction.md
docs/task reports/Phase_0S_1_correction.md
~~~

The directive is populated, ordinary, untracked, unstaged, absent from HEAD, and
byte-identical to the delivered artifact. The report is a zero-byte ordinary
untracked file, unstaged and absent from HEAD.

After only caller setup:

~~~text
tracked modified paths    72
untracked paths           54
total status paths        126
staged paths              0
~~~

Record the directive size and SHA-256. Never modify it.

# 5. EXACT WHOLE-BLOCK REPLACEMENTS

Preserve every byte outside these old blocks. Do not reflow surrounding prose,
normalize unrelated whitespace, change headings, or run a formatter.

## 5.1 ADR 0029 remaining integration scope

File:

~~~text
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
~~~

Replace exactly:

~~~markdown
The following remain open: integration with the 42 existing exceptions; owning
catalog instances for their Accepted schemas; representation of simultaneous
primary and context-capture failure; renderer and transport APIs; production
allowlists; serialization and cross-process representation; localization; source
providers; documentation lookup; logging and telemetry; and incident storage.
~~~

With:

~~~markdown
The following remain open: integration with the remaining six Kernel and 25
Dependency Injection failures and catalog-owned schema instances for them;
representation of simultaneous primary and context-capture failure; renderer and
transport APIs; production allowlists; serialization and cross-process
representation; localization; source providers; documentation lookup; logging
and telemetry; and incident storage.
~~~

This records exactly 31 unintegrated Kernel and DI failures. It does not change
Graph's 11 contexts or Diagnostics' context-free construction failures.

## 5.2 ADR 0031 historical cycle wording

File:

~~~text
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
~~~

Replace exactly:

~~~markdown
That implemented topology prevented Kernel from consuming Diagnostics for a
future bounded diagnostic context: adding that edge now would form the exact
Composer cycle `kernel -> diagnostics -> kernel`. The ownership problem must be
resolved without moving diagnostic values into Kernel, weakening API-status
enforcement, or creating overlapping metadata identities.
~~~

With:

~~~markdown
That Phase 0R topology prevented Kernel from consuming Diagnostics for a future
bounded diagnostic context: adding that edge then would have formed the exact
Composer cycle `kernel -> diagnostics -> kernel`. The ownership problem therefore
had to be resolved without moving diagnostic values into Kernel, weakening
API-status enforcement, or creating overlapping metadata identities.
~~~

## 5.3 ADR 0031 implemented root requirement

Replace exactly:

~~~markdown
Each of the four consuming package manifests uses exactly
`"kongono/api-metadata": "*"`; the root will use exactly
`"kongono/api-metadata": "@dev"`. Application Graph, Dependency Injection,
and Diagnostics no longer depend on Kernel merely for compatibility metadata.
Repository-wide API-status inventory and enforcement remain a
root architecture-suite responsibility.
~~~

With:

~~~markdown
Each of the four consuming package manifests uses exactly
`"kongono/api-metadata": "*"`; the root uses exactly
`"kongono/api-metadata": "@dev"`. Application Graph, Dependency Injection,
and Diagnostics no longer depend on Kernel merely for compatibility metadata.
Repository-wide API-status inventory and enforcement remain a
root architecture-suite responsibility.
~~~

## 5.4 ADR 0031 completed semantic and count wording

Replace exactly:

~~~markdown
The two declarations remain Public bootstrap metadata by definition and
the only self-classification exceptions. Status will remain compatibility and
exposure metadata, not authorization, security, package visibility, Graph data,
or runtime access control.
~~~

With:

~~~markdown
The two declarations remain Public bootstrap metadata by definition and
the only self-classification exceptions. Status remains compatibility and
exposure metadata, not authorization, security, package visibility, Graph data,
or runtime access control.
~~~

Replace exactly:

~~~markdown
Phase 0S is an atomic, pre-alpha namespace correction across
production imports, tests, manifests, autoload contracts, tooling inventory,
and documentation. The total production declaration count will remain 40
because the two declarations move and are renamed rather than duplicated.
~~~

With:

~~~markdown
Phase 0S implements an atomic, pre-alpha namespace correction across
production imports, tests, manifests, autoload contracts, tooling inventory,
and documentation. The total production declaration count remains 40
because the two declarations moved and were renamed rather than duplicated.
~~~

## 5.5 ADR 0031 completed compatibility wording

Replace exactly:

~~~markdown
Phase 0S removes both old Kernel FQCNs in the same atomic change. It is an
intentionally breaking pre-alpha correction. No alias,
`class_alias`, proxy attribute, deprecated forwarder, compatibility shim,
duplicate enum, overlapping namespace ownership, or simultaneous second status
identity will be provided.
~~~

With:

~~~markdown
Phase 0S removes both old Kernel FQCNs in the same atomic change. It is an
intentionally breaking pre-alpha correction. No alias,
`class_alias`, proxy attribute, deprecated forwarder, compatibility shim,
duplicate enum, overlapping namespace ownership, or simultaneous second status
identity is provided.
~~~

# 6. PRESERVED IMPLEMENTATION BOUNDARY

Modify exactly the two ADR files and, after every gate passes, populate the
correction report. Do not modify:

- PHP source or tests;
- another README, guide, ADR, directive, or prior report;
- composer.json, composer.lock, a package manifest, vendor metadata, or tooling;
- diagnostic catalogs, schemas, values, markers, or context behavior;
- Git index, history, refs, branches, tags, remotes, or configuration.

Do not implement Kernel or DI context, the future Kernel-to-Diagnostics edge, a
renderer, error page, transport, or any later capability.

# 7. REQUIRED VALIDATION

## 7.1 Pre-edit

Verify Sections 2 through 4. Prove each old block occurs once and each new block
zero times. Verify both ADRs are ordinary strict UTF-8/LF files without BOM, NUL,
trailing whitespace, alternate streams, or reparse points.

## 7.2 Post-edit text proof

Prove:

- each old block occurs zero times and each new block once;
- ADR 0029 names six remaining Kernel and 25 remaining DI failures, not all 42;
- ADR 0031 scopes the cycle to Phase 0R and uses current wording for the root
  dependency, status meaning, 40-type count, and absence of shims;
- Graph's 11 contexts remain implemented;
- Kernel and DI context remain unimplemented;
- local links in both ADRs resolve;
- no byte outside authorized blocks changed.

## 7.3 Runtime and repository preservation

Run unchanged:

~~~text
composer quality
~~~

If only managed network access blocks the audit, retry the identical command with
existing approved access. It must report zero advisories, platform pass, zero of
111 fixable, PHPStan zero errors, and 187 tests with 2,324 assertions.

Prove the API Metadata package, exact graph, 40 declarations, 71 tests, new/old
FQCN results, manifests, lockfile, and all protected hashes remain unchanged.

## 7.4 Final Git and file safety

Verify strict UTF-8 without BOM, LF only, one final LF, no NUL/trailing
whitespace, ordinary files, no reparse points, no alternate streams, no mode
change, and git diff --check clean.

Final state:

~~~text
branch                    main
HEAD                      75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent               5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                   5
local branches            1
remotes                   0
tags                      0
tracked modified paths    72
untracked paths           54
total status paths        126
staged paths              0
~~~

Counts remain after report population. Verify closed inventories and exact
two-file scope; counts alone are insufficient.

# 8. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- baseline, setup, and identities exact;
- six literal old blocks replaced once with six new blocks;
- only ADR 0029 and ADR 0031 changed;
- Phase 0S directive/report remain byte-identical;
- ADR 0029 no longer calls all 42 failures unintegrated;
- ADR 0031 has no corrected current/future contradiction;
- Phase 0S runtime, dependencies, lock, tests, security, and scope unchanged;
- canonical quality passes at 187 tests and 2,324 assertions;
- links, encoding, file safety, exact Git state, and zero staged paths pass;
- no later capability or architecture decision introduced;
- correction report populated only after success, untracked and unstaged.

# 9. REQUIRED CORRECTION COMPLETION REPORT

Populate docs/task reports/Phase_0S_1_correction.md only after success:

~~~markdown
# PHASE 0S.1 CORRECTION TASK COMPLETION REPORT

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

- Complete only if every gate passes; otherwise leave report zero and return a
  blocking TCR outside it.
- FILES CREATED says none by executor and distinguishes caller records.
- FILES MODIFIED lists exactly ADR 0029 and ADR 0031 plus report population as
  caller record, not implementation.
- State no architecture or dependency changed.
- Report occurrence counts, reconciliation, links, safety, quality, versions,
  Git state, and protected hashes.
- Report PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions.
- End Section 18 exactly:

~~~text
Await architect review before Phase 0T.
~~~

Verify the report is nonzero strict UTF-8/LF, ordinary, untracked, unstaged,
absent from HEAD, and has 18 exact headings. Return its size and SHA-256 outside
the report. Do not embed its own digest.

# FINAL EXPECTED OUTCOME

Phase 0S implementation remains untouched. ADR 0029 accurately identifies the
remaining 31 Kernel and DI integrations after Graph's 11. ADR 0031 cleanly
distinguishes the historical Phase 0R cycle from implemented Phase 0S results.
No runtime, manifest, lock, test, tool, security, Git, or later-capability change
occurs.
