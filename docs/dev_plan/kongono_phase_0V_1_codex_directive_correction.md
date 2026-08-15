# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0V.1

## Reconcile the Implemented Dependency Injection-to-Diagnostics Package Edge

## Mission

Correct one accepted documentation file whose Composer-integration paragraph
contradicts both the live manifest and its own dependency graph.

`docs/engineering/package-layout.md` correctly lists
`dependency-injection -> diagnostics` in its graph, but an earlier paragraph
still says Dependency Injection requires API Metadata only and does not depend
on Diagnostics. The same document also labels the implemented graph as Phase 0T
even though Phase 0U added the DI-to-Diagnostics edge.

Apply exactly one whole-paragraph replacement and exactly two `Phase 0T` to
`Phase 0U` replacements defined below. Make no other content change. Do not
stage, commit, rerun PHP or Composer quality tooling, or begin Phase 0W.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `docs/doctrine.md`;
4. `docs/adr/0003-monorepo-initial-development.md`;
5. `docs/adr/0004-microkernel-capability-architecture.md`;
6. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`;
7. `docs/adr/0033-dependency-injection-diagnostic-context-integration.md`;
8. `docs/engineering/package-layout.md`;
9. `packages/dependency-injection/composer.json`;
10. `composer.json`;
11. `composer.lock`;
12. `docs/dev_plan/kongono_phase_0U_codex_directive.md`;
13. `docs/task reports/Phase_0U.md`;
14. `docs/task reports/Phase_0U_1_correction.md`;
15. `docs/dev_plan/kongono_phase_0V_codex_directive.md`;
16. `docs/task reports/Phase_0V.md`;
17. this Phase 0V.1 directive; and
18. the zero-byte Phase 0V.1 correction report.

The Phase 0V report remains immutable historical evidence of the executor's
checkpoint validation. This correction report must identify the later
architect-review finding and supersede only the inaccurate documentation-
consistency claim concerning `docs/engineering/package-layout.md`.

If any Git fact, caller record, exact old block, occurrence count, byte/hash,
manifest dependency, or protected-file identity differs:

```text
STOP
REPORT
DO NOT GUESS
DO NOT BROADEN THE EDIT
DO NOT STAGE OR COMMIT
```

---

# 2. LOCKED PRE-CALLER BASELINE

Before the caller installs the Phase 0V.1 records, require exactly:

```text
branch                         main
only local branch              main
HEAD                           7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                      b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                    75749a37a184996203e569e039bddbc3ec10cdf7
HEAD subject                   Establish Kongono framework baseline through Phase 0U
reachable commits              6
tracked files                  277
tracked modified paths         0
staged paths                   0
untracked paths                1
total status paths             1
remotes                        0
tags                           0
```

The sole untracked path is:

```text
docs/task reports/Phase_0V.md
```

Its canonical identity is:

```text
bytes       24,353
SHA-256     6C5053A469664994C5494598AF0D29E53089F0EACAD08D7ADA744838F404A4A3
```

It must be strict UTF-8 without BOM, LF-only, exactly one final LF, untracked,
unstaged, and absent from `HEAD`.

Raw pre-caller inventory hashes are:

```text
git status --porcelain=v1 -z -uall | git hash-object --stdin
  a1a707c5e890ebd395420db037589464f354b983

git ls-files -o --exclude-standard -z | git hash-object --stdin
  1b96d7f71da0fad1be5f291f767c7021ff8af9ce

git diff --name-only -z | git hash-object --stdin
  e69de29bb2d1d6434b8b29ae775ad8c2e48c5391
```

Hash raw command output without sorting or normalization. The known managed
warning concerning `C:\Users\antho\.config\git\ignore` may be recorded only
after exact inventories independently pass.

---

# 3. CALLER SETUP

The caller copies this directive byte-for-byte to:

```text
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
```

and creates an ordinary zero-byte file at:

```text
docs/task reports/Phase_0V_1_correction.md
```

The copied directive must equal the architect-delivered artifact's byte count
and SHA-256. The new report must initially be zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup require exactly:

```text
tracked modified paths         0
staged paths                   0
untracked paths                3
total status paths             3
```

The three untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
docs/task reports/Phase_0V.md
docs/task reports/Phase_0V_1_correction.md
```

Do not populate the correction report before every non-report gate passes.

---

# 4. LOCKED DEFECT AND AUTHORITATIVE REALITY

The current Dependency Injection manifest contains exactly:

```json
"require": {
  "kongono/api-metadata": "*",
  "kongono/diagnostics": "*",
  "php": ">=8.5"
}
```

Accepted ADRs 0031 and 0033, the Phase 0U directive, the root and package
manifests, and the later graph in `package-layout.md` all require the independent
one-way edge:

```text
dependency-injection -> diagnostics
```

Diagnostics has no reverse dependency on DI. DI does not depend on Kernel or
Application Graph. The package graph remains acyclic.

The stale paragraph incorrectly says both that DI requires API Metadata only and
that DI does not depend on Diagnostics. The two Phase 0T labels also predate the
Phase 0U edge. Those statements are documentation defects, not open
architectural questions.

---

# 5. LOCKED ORIGINAL FILE

Before editing, require:

```text
path        docs/engineering/package-layout.md
bytes       5,000
lines       133
SHA-256     8BB6D655D87A88018B03A3E7174B71A4C431CBA5999BBA83D4534EC480736E17
Git class   tracked and clean
```

It must be strict UTF-8 without BOM, LF-only, exactly one final LF, ordinary
mode `100644`, non-reparse, and have no alternate data stream.

The exact old paragraph in Section 6 must occur once and the replacement must
occur zero times. The exact phrase `Phase 0T dependency graph` must occur twice;
`Phase 0U dependency graph` must occur zero times.

---

# 6. EXACT AUTHORIZED WHOLE-PARAGRAPH REPLACEMENT

Replace this exact whole block, including its final LF:

```text
`kongono/dependency-injection` owns
`Kongono\DependencyInjection\` and requires `kongono/api-metadata: *` only for
the same metadata. `kongono/diagnostics` owns `Kongono\Diagnostics\` and
requires `kongono/api-metadata: *`; Diagnostics has no Kernel or reverse Graph
dependency. Kernel requires API Metadata and Diagnostics; Dependency Injection
does not depend on Diagnostics. These unbounded
internal constraints are authorized while the packages are unreleased and
developed atomically; inter-package version policy remains open. Dependency
Injection and Application Graph do not depend on each other. The independent
Graph-to-Diagnostics and Kernel-to-Diagnostics edges create no cross-package
lifecycle integration or universal exception boundary. Package manifests
duplicate no root tool dependencies.
```

with exactly:

```text
`kongono/dependency-injection` owns
`Kongono\DependencyInjection\` and requires `kongono/api-metadata: *` for
compatibility metadata plus `kongono/diagnostics: *` for exact DI-owned bounded
context. `kongono/diagnostics` owns `Kongono\Diagnostics\` and requires
`kongono/api-metadata: *`; Diagnostics has no Kernel or reverse Graph dependency.
Kernel requires API Metadata and Diagnostics. These unbounded internal constraints
are authorized while the packages are unreleased and developed atomically;
inter-package version policy remains open. Dependency Injection and Application
Graph do not depend on each other. The independent Graph-, Kernel-, and
DI-to-Diagnostics edges create no cross-package lifecycle integration or
universal exception boundary. Package manifests duplicate no root tool
dependencies.
```

Do not reflow adjacent paragraphs or change any additional word, punctuation,
space, or line ending.

---

# 7. EXACT TWO PHASE-LABEL REPLACEMENTS

Replace exactly once:

```text
The five physical package boundaries and the Phase 0T dependency graph are
```

with:

```text
The five physical package boundaries and the Phase 0U dependency graph are
```

Replace exactly once:

```text
## Implemented Phase 0T dependency graph
```

with:

```text
## Implemented Phase 0U dependency graph
```

After all three replacements, `Phase 0T dependency graph` occurs zero times and
`Phase 0U dependency graph` occurs exactly twice.

---

# 8. LOCKED CORRECTED FILE

The corrected file must be exactly:

```text
path        docs/engineering/package-layout.md
bytes       5,004
lines       133
SHA-256     1F890ED538A5248F75E8627DA21C8533503054B517D1914DD497ED7E36637B07
Git class   tracked modified, unstaged
```

It remains strict UTF-8 without BOM, LF-only, exactly one final LF, ordinary
mode `100644`, non-reparse, and stream-safe.

Prove the old paragraph is absent, the new paragraph occurs exactly once, the
two phase-label replacements are exact, and reverse replacement reconstructs
the original 5,000-byte file and original SHA-256.

Every byte outside the three authorized spans must be unchanged.

---

# 9. PROTECTED SCOPE

Every path other than these two report records and the one authorized document
is protected:

```text
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
docs/engineering/package-layout.md
docs/task reports/Phase_0V_1_correction.md
```

The copied directive is caller-owned and must remain byte-identical. The report
is executor-owned only after non-report success. Therefore the only pre-report
content edit is the exact `package-layout.md` correction.

In particular, do not modify:

- the Phase 0V report or any prior directive/report;
- any ADR, root document, other guide, README, source, test, fixture, manifest,
  lock, or configuration file;
- any API status, package edge, namespace, dependency, diagnostic schema,
  exception contract, test count, or quality policy; or
- Git history, refs, index, modes, attributes, ignore rules, hooks, signing, or
  identity.

Do not stage, commit, reset, restore, amend, checkout, switch, branch, merge,
rebase, revert, tag, add a remote, or push.

---

# 10. VALIDATION CONTRACT

This is a documentation-only correction. Do not run Composer, PHPUnit, PHPStan,
PHP CS Fixer, PHP syntax, autoload generation, dependency update/install, or any
network command. The independently accepted Phase 0V result remains:

```text
204 tests
3,377 assertions
zero PHPStan errors
0 of 113 files fixable
zero security advisories
platform pass
```

Run only read-only validation sufficient to prove:

1. exact Git baseline and caller setup;
2. exact original and corrected identities;
3. exact old/new occurrence counts before and after;
4. byte-span and reverse-reconstruction proof;
5. manifest and ADR statements still require the DI-to-Diagnostics edge;
6. no `Dependency Injection does not depend on Diagnostics` statement remains
   in current nonhistorical documentation;
7. no contradictory `Phase 0T dependency graph` label remains in the corrected
   file;
8. local Markdown links still have zero broken targets;
9. `git diff --check` passes;
10. only `package-layout.md` is tracked modified;
11. all three caller records are the only untracked paths; and
12. index, `HEAD`, branches, remotes, tags, modes, and history remain unchanged.

Historical directives and reports may preserve superseded statements as
historical evidence. Do not rewrite them or count them as current documentation.

After the document edit and before report population require:

```text
tracked modified paths         1
staged paths                   0
untracked paths                3
total status paths             4
```

---

# 11. CORRECTION REPORT

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_0V_1_correction.md
```

Use the standard 16 headings from `AGENTS.md` exactly once and in order:

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

For completion, record:

- the exact stale claims and their authoritative contradiction;
- the exact one-file, three-span correction;
- original and corrected byte counts and SHA-256 values;
- reverse-reconstruction and protected-scope evidence;
- confirmation that the Phase 0V report remains immutable historical evidence;
- confirmation that no implementation, dependency, lock, API, test, quality,
  stage, commit, or history operation occurred;
- the preserved Phase 0V quality result without claiming it was rerun;
- final Git state of one tracked modification, three untracked paths, four total
  status paths, and zero staged; and
- the report's final byte count and SHA-256 returned outside the report.

For a successful report, Sections 5, 6, 7, and 13 say `None`. Section 16 must be
exactly:

```text
Await architect review before Phase 0W.
```

The populated correction report must remain untracked, unstaged, absent from
`HEAD`, strict UTF-8 without BOM, LF-only, exactly one final LF, ordinary,
non-reparse, and stream-safe.

---

# 12. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Locked sixth-checkpoint Git baseline and sole Phase 0V report are exact.
- [ ] Caller setup adds only the exact Phase 0V.1 directive and zero-byte report.
- [ ] Original package-layout identity and three old spans are exact.
- [ ] Live manifest and accepted ADRs prove the DI-to-Diagnostics edge.
- [ ] Only the exact whole paragraph and two phase labels change.
- [ ] Corrected package-layout identity is exactly 5,004 bytes and the locked
  SHA-256.
- [ ] Reverse replacement reconstructs the exact original file.
- [ ] Every byte outside the three authorized spans remains unchanged.
- [ ] Current documentation no longer contradicts the DI-to-Diagnostics edge or
  mislabels the implemented graph as Phase 0T.
- [ ] Local links, encoding, final newline, whitespace, ordinary-file,
  reparse-point, mode, and alternate-stream checks pass.
- [ ] No protected source, test, fixture, ADR, guide, README, manifest, lock,
  configuration, directive, or historical report changes.
- [ ] No PHP, Composer, network, quality, dependency, staging, commit, ref,
  configuration, or history operation runs.
- [ ] Final state before and after report population is one tracked modification,
  three untracked paths, four total status paths, and zero staged paths.
- [ ] Report has the exact 16 headings, evidence, integrity, and NEXT STEP.
- [ ] Final report byte count and SHA-256 are returned outside the report.

Any failed or unreached item makes status `Blocked` or `Partial`, never
`Complete`.

---

# 13. FINAL EXECUTOR INSTRUCTION

Correct only the stale Dependency Injection dependency paragraph and the two
Phase 0T graph labels in `docs/engineering/package-layout.md`. Preserve every
other byte and every historical record. Validate the exact corrected identity,
populate the correction report only after success, and stop before Phase 0W.
