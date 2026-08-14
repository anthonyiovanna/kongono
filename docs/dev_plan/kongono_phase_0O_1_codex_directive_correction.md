# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0O.1

## Diagnostic Example Schema Reconciliation

## Mission

Correct one documentation contradiction in the otherwise accepted Phase 0O
worktree. The future `KGN-GRAPH-1004` rendered example currently presents an
entire received key sequence even though the Accepted schema authorizes only a
Safe `entry_count` and one optional Protected `first_unexpected_key`.

Make the example derive only from those two authorized fields, preserve the
rest of Phase 0O byte-for-byte, rerun the complete validation boundary, and
write the standard correction completion report.

Do not begin Phase 0P.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `docs/doctrine.md`;
5. `docs/documentation-standard.md`;
6. `docs/engineering/error-handling.md`;
7. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
8. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`;
9. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
10. `docs/dev_plan/kongono_phase_0O_codex_directive.md`;
11. `docs/task reports/Phase_0O.md`; and
12. this correction directive.

The original Phase 0O directive remains authoritative except for the single
example correction explicitly defined here. This directive does not reopen any
Phase 0O architecture decision or authorize a broader cleanup.

Stop without editing if any locked baseline, path, byte count, SHA-256 digest,
Git invariant, file-safety condition, or caller-record condition below does not
match exactly. Do not repair, normalize, regenerate, stage, commit, amend,
reset, restore, clean, or otherwise reconcile a mismatch by assumption.

If any post-edit validation fails for a reason outside the exact authorized
replacement, stop and report it. Do not broaden scope.

---

# 2. ACCEPTED REVIEW FINDING

Phase 0O is not yet accepted because
`docs/engineering/error-handling.md` retains this future rendering:

```text
Expected list keys 0..2; received a safe representation of keys 0, 2, 3.
```

That sentence cannot be produced from the Accepted `KGN-GRAPH-1004` schema:

```text
entry_count:integer:Safe
first_unexpected_key:text:Protected:optional
```

It presents the complete received key sequence, treats that representation as
safe, and conflicts with the locked rule that multiple facts require multiple
approved fields rather than a list smuggled into delimited text.

The architectural decision is unchanged. The example must be reconciled to the
schema; the schema must not be expanded to fit the example.

---

# 3. LOCKED CURRENT BASELINE

Before the caller copies this directive or creates its report placeholder, the
accepted live state is:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
tracked modes: all 100644
tracked modifications: 13
staged paths: 0
untracked paths: 4
total status paths: 17
remotes: 0
tags: 0
non-default alternate data streams: 0
```

The exact 13 modified tracked paths are:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/error-handling.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

The exact four untracked paths are:

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
```

Locked records and Phase 0O artifacts:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` | 10,151 | `78E1DC7F4058A798EB411867FE06C817B1BABDD20BB7C6E58EF17A842ADF7016` |
| `docs/dev_plan/kongono_phase_0O_codex_directive.md` | 42,924 | `4FD514E5D92B2AD0AAAF852FABD48844C0C3F5CCE189F9DC4F9DBA2F9070FB5A` |
| `docs/task reports/Phase_0N_4_correction.md` | 17,247 | `8AFD9BC46D29B0DB0570B87C8B947C65AE82FD9C967DCCBDEDB68C3CEC62661C` |
| `docs/task reports/Phase_0O.md` | 21,340 | `5913B03A4CF6ADC22E298AF81B1819E462742F3A5982E9899202D87BE4AB17E6` |
| `docs/engineering/error-handling.md` | 41,779 | `0E7243074DD8D4834B0AD94B68B265C669EA5D827916CCC7D3C6B87182E20D41` |

The Phase 0O diff is exactly 13 files changed, 493 insertions, and 28
deletions. `composer quality` passes with 134 tests and 1,099 assertions.

After the caller copies this directive to the repository and creates the report
placeholder, preflight must additionally prove:

```text
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/task reports/Phase_0O_1_correction.md
```

The copied correction directive must match the byte count and SHA-256 digest
provided by the architect with delivery. The correction report must be exactly
zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

At that point the exact pre-edit state is 13 modified tracked paths, zero
staged paths, six untracked paths, and 19 total status paths. The six untracked
paths are the four listed above plus the correction directive and correction
report.

---

# 4. EXACT AUTHORIZED CHANGE

Modify exactly one existing file:

```text
docs/engineering/error-handling.md
```

No file may be created by the executor. The caller-created correction report is
populated only after every other gate passes.

In the `### Before and after` subsection, replace exactly:

```text
keys were not zero-based and consecutive. Expected list keys 0..2; received a
safe representation of keys 0, 2, 3. Reindex the node array as a PHP list.
```

with exactly:

```text
keys were not zero-based and consecutive. Expected 3 zero-based consecutive
entries; first unexpected key 2. Reindex the node array as a PHP list.
```

Then replace exactly:

```text
metadata. The combined rendering, safe key representation, and formatting shown
above remain future work.
```

with exactly:

```text
metadata. The combined rendering, Safe entry-count representation, Protected
first-unexpected-key representation, and formatting shown above remain future work.
```

Both old blocks must occur exactly once before editing. Both new blocks must
occur exactly once afterward. Do not change any other byte in the file.

The resulting file must be exactly:

```text
bytes: 41828
SHA-256: C71C78E0DDCB2FE58FD29C9937C2ACAACD9864A842401F37100DAEBC8D55B4CA
```

The corrected example then uses only:

- Safe `entry_count = 3`, rendered as the expected count of zero-based
  consecutive entries; and
- Protected `first_unexpected_key = 2`.

It must not display the complete actual key sequence, claim that arbitrary key
material is Safe, add a field, change optionality, alter disclosure, or select a
runtime representation algorithm.

---

# 5. PRESERVED PHASE 0O CONTRACT

Preserve every other Phase 0O decision and artifact, including:

- ADR 0028 remains Accepted and dated 2026-08-13;
- `kongono/diagnostics` remains future and presentation-neutral;
- no diagnostics package, PHP type, accessor, renderer, or transport exists;
- Kernel, Graph, and DI retain semantic ownership and separate catch markers;
- `Safe` and `Protected` remain the only two disclosures;
- forbidden material remains uncaptured and is not a third disclosure;
- the exact 42-row schema remains byte-for-byte identical to the Phase 0O
  directive, split 6 Kernel, 11 Graph, and 25 DI;
- the four and only optional fields remain `first_unexpected_key` for
  `KGN-GRAPH-1004`, `KGN-GRAPH-1005`, `KGN-DI-1009`, and `KGN-DI-1017`;
- every controlled Safe token, bound, order, persistence rule, disclosure rule,
  and open decision remains unchanged;
- no production PHP, test, fixture, manifest, lockfile, dependency, tooling
  configuration, mode, index, branch, tag, remote, or history change occurs;
  and
- the original Phase 0O report remains immutable even though this correction
  supersedes its acceptance conclusion.

Do not edit the 42-row table, ADR 0028, another documentation file, the Phase
0O directive, the Phase 0O report, or any earlier caller record.

---

# 6. PROHIBITED SCOPE

Do not:

- add or change production code, tests, fixtures, Composer files, dependencies,
  autoloading, or quality configuration;
- create `kongono/diagnostics` or any runtime diagnostic-context type;
- implement a context accessor, schema catalog, sanitizer, truncator, redactor,
  serializer, renderer, error page, template, transport, logger, telemetry hook,
  incident store, source reader, or AI integration;
- add a received-key list, array, map, delimited list, or other arbitrary bag to
  the schema or example;
- reclassify `first_unexpected_key` as Safe;
- alter any diagnostic identifier, title, remediation, message, marker, throw
  site, schema row, or Public/Internal/Experimental status;
- implement DI/container, HTTP/router, Application Graph schema/IDs/
  serialization, ORM, agents, extensions, CLI, frontend, telemetry, or another
  later capability;
- stage or commit any path;
- create, rename, delete, switch, or merge branches;
- create remotes or tags;
- amend, reset, rebase, restore, checkout, clean, stash, or rewrite history;
- normalize historical files or terminal blank-line exceptions; or
- populate the correction report before all other validation succeeds.

---

# 7. REQUIRED VALIDATION

## 7.1 Preflight

Before editing, prove and record:

- branch, ref, HEAD, tree, parent, commit count, subject, tracked count, and
  modes;
- zero staged paths, zero remotes, zero tags, 13 modified tracked paths, six
  exact untracked paths, and 19 status paths;
- exact byte counts and hashes for every record in Section 3 plus the delivered
  correction directive and zero-byte correction report;
- zero non-default alternate data streams;
- exact current hash and byte count of `docs/engineering/error-handling.md`;
- exact one-occurrence presence of each old block; and
- unchanged canonical quality: 134 tests and 1,099 assertions.

The sandbox may deny Composer advisory network or cache access. If and only if
the failure is clearly caused by that managed restriction, rerun the identical
fail-closed command once with authorized normal access. Do not change flags,
policy, dependencies, or configuration.

## 7.2 Post-edit focused proof

After the exact replacement, prove:

- the guide is 41,828 bytes with SHA-256
  `C71C78E0DDCB2FE58FD29C9937C2ACAACD9864A842401F37100DAEBC8D55B4CA`;
- neither old block remains;
- both new blocks occur exactly once;
- the normative schema table is still byte-for-byte identical to the Phase 0O
  directive table;
- the table still has exactly 42 unique rows: 6 Kernel, 11 Graph, and 25 DI;
- the example exposes no received-key sequence and uses only the authorized
  count and first key;
- no executable API or present-tense implementation claim was added;
- `git diff --check` passes;
- every changed/new text file remains strict UTF-8 without BOM, LF-only, with
  exactly one final LF and no extra terminal blank line, NUL, replacement
  character, mojibake, conflict marker, or trailing whitespace;
- all Markdown local links resolve under the existing historical-record
  exception;
- zero non-default alternate data streams exist; and
- no path outside the already authorized Phase 0O inventory changed.

## 7.3 Canonical quality

Run the unchanged canonical validation and record its exact results:

```powershell
composer quality
```

Expected substantive result:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 92 files fixable
PHPStan: 0 errors
PHPUnit: 134 tests, 1099 assertions
```

Only the already accepted missing-license and authorized local `@dev` or `*`
constraint warning categories may remain.

## 7.4 Final Git proof before report population

Prove:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
tracked modifications: 13
staged paths: 0
untracked paths: 6
total status paths: 19
remotes: 0
tags: 0
```

The exact 13 modified and six untracked paths must be the closed inventories in
Section 3. The Phase 0O tracked diff stat remains 13 files changed, 493
insertions, and 28 deletions. No Git object or index state changes.

Only after every preceding gate passes may the executor populate:

```text
docs/task reports/Phase_0O_1_correction.md
```

Then re-prove the same final Git state, report integrity, and caller-record
hashes. Return the report's final byte count and SHA-256 outside the report so
the report does not attempt to hash itself.

---

# 8. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] The exact Phase 0O baseline and caller records match.
- [ ] The delivered correction directive matches its architect-provided bytes
  and SHA-256; its report began as the exact zero-byte placeholder.
- [ ] Exactly one existing content file is edited by the executor.
- [ ] Only the two exact prose blocks are replaced.
- [ ] The corrected guide has the exact required byte count and SHA-256.
- [ ] The future example derives only from `entry_count` and optional
  `first_unexpected_key`.
- [ ] The example no longer exposes a received-key sequence or calls arbitrary
  key material Safe.
- [ ] The complete 42-row schema and all other Phase 0O doctrine remain exact.
- [ ] ADR 0028 and every other Phase 0O file remain byte-identical.
- [ ] No code, API, dependency, test, fixture, tooling, or later capability is
  introduced.
- [ ] Text integrity, links, streams, secrets, and prohibited-scope checks pass.
- [ ] Canonical quality passes with 134 tests and 1,099 assertions.
- [ ] HEAD, tree, history, index, branches, remotes, tags, modes, and prior
  records remain unchanged.
- [ ] Final state is exactly 13 modified tracked paths, six untracked paths,
  zero staged paths, and 19 total status paths.
- [ ] The correction report follows Section 9, is nonzero, strict UTF-8,
  LF-only, exactly one-final-LF terminated, untracked, unstaged, and absent from
  `HEAD`.
- [ ] The next step is exact.

---

# 9. CORRECTION COMPLETION REPORT

Write `docs/task reports/Phase_0O_1_correction.md` with exactly these numbered
sections:

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

The report must state plainly:

- whether the correction is Complete or Blocked;
- that no file was created by the executor other than populating the
  caller-created report;
- that exactly `docs/engineering/error-handling.md` was modified;
- the old and final guide byte counts and SHA-256 digests;
- that the accepted schema was preserved rather than expanded;
- exact caller-record hashes and final Git counts;
- every validation command and substantive result;
- the sandbox retry, warning, deviation, and blocker state;
- that no dependency or executable behavior changed; and
- the final report byte count and digest are returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0P.
```

---

# 10. FINAL EXECUTOR INSTRUCTION

Apply only the exact two-block documentation correction. Preserve the full
Phase 0O architecture, source tree, caller records, and Git state. Validate the
closed schema and complete quality contract. Stop rather than guess. Do not
begin Phase 0P.
