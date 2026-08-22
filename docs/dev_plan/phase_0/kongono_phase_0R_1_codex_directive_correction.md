# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0R.1

## Phase 0R Documentation Reconciliation Correction

## Mission

Correct exactly three stale or over-broad Phase 0R documentation statements
without changing ADR 0031, the accepted architecture, any runtime artifact, or
the accepted Phase 0R quality evidence.

Phase 0R correctly accepted `kongono/api-metadata` and
`Kongono\ApiMetadata\` as future neutral ownership and correctly preserved the
current four-package runtime. However:

1. ADR 0030 says Phase 0R resolves the Kernel dependency-topology question and
   later still lists that same resolution as open;
2. the architecture overview says “every framework package” will depend on API
   Metadata, which literally includes the API Metadata package itself and
   conflicts with the no-self-dependency graph; and
3. the package-layout guide calls further package boundaries open immediately
   before documenting the already accepted future fifth boundary.

Phase 0R.1 replaces only those exact three prose blocks. It makes no new
architectural decision.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0R.1 correction executor, not the architect.

Read completely:

1. `AGENTS.md`;
2. the Phase 0R directive;
3. the Phase 0R completion report;
4. ADR 0031;
5. the three correction targets;
6. this correction directive copied by the caller; and
7. the zero-byte Phase 0R.1 report placeholder.

This directive supersedes Phase 0R only for the exact three replacements in
Section 5. All other Phase 0R decisions, evidence, restrictions, and protected
identities remain controlling.

Stop before editing if:

- any Section 2 Git identity, count, diff, record, or file identity differs;
- any old block does not occur exactly once;
- any new block already occurs;
- the correction report is absent, nonzero, tracked, staged, or inside `HEAD`;
- any path outside Section 4 appears to require modification;
- another process changes the worktree or index; or
- an exact output identity from Section 5 cannot be produced.

On mismatch:

```text
STOP
ASK
DO NOT GUESS
```

Report the exact mismatch, why it blocks, viable options, tradeoffs, a clearly
labeled recommendation, and the unchanged Git/index/report state. Do not repair
a caller record or broaden this correction.

---

# 2. LOCKED PRE-CORRECTION BASELINE

Before Phase 0R.1 caller setup, require exactly:

```text
Branch: main
Local branches: 1
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
Tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
Parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit count: 5
Tracked files: 214
Remotes: 0
Tags: 0
Tracked modifications: 39
Untracked paths: 43
Total status paths: 82
Staged paths: 0
Tracked diff: 39 files changed, 1,721 insertions, 124 deletions
Binary tracked-diff hash: 6eb180f148cf33f3d68e147e8d43a20f7b06084c
```

The exact 82-path inventory is the Phase 0R pre-caller 79-path inventory plus:

```text
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/dev_plan/kongono_phase_0R_codex_directive.md
docs/task reports/Phase_0R.md
```

Require these identities:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0R_codex_directive.md` | 39,242 | `372847999BF881D956EF74706577CF011B7FBBDAA722AFC26022F72980E200F8` |
| `docs/task reports/Phase_0R.md` | 14,925 | `5CE8670DAC97721070981E3028679328188D7D5223C1EA81F31F076AE6902D61` |
| `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md` | 8,111 | `45ABD75FE56F80485723460B8154307B74EC9F9EF605063F97C83C4D70C83F68` |
| `docs/adr/0030-application-graph-diagnostic-context-integration.md` | 8,218 | `6EF3825923C11B617314B3F2E8C48D0E83F328216280023ABA1F5E42839C8AB6` |
| `docs/architecture/overview.md` | 7,136 | `3AA7859345CAC5ADCA5A8AB640CB460A66AB0F94834B483F7A4673A9FA6A812B` |
| `docs/engineering/package-layout.md` | 4,850 | `DA5E79AAA8B97905D99F0284BE5C778E810C9B4FF0983BCD016051EBF27900BD` |

The Phase 0R report is historical evidence. Preserve it byte-for-byte. Its
canonical repository copy is strict UTF-8 and LF-only; a CRLF-converted pasted
attachment is not authority over the repository file.

The independently reverified accepted Phase 0R quality result remains:

```text
Security advisories: 0
PHPStan errors: 0
PHP CS Fixer: 0 of 110 files fixable
PHPUnit: 182 tests, 2,257 assertions
```

---

# 3. CALLER RECORDS AND STATUS CONTRACT

The caller must supply:

```text
docs/dev_plan/kongono_phase_0R_1_codex_directive_correction.md
docs/task reports/Phase_0R_1_correction.md
```

The directive must be an exact copy of this artifact. The report must begin as
an ordinary zero-byte file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup and before editing, require exactly:

```text
Tracked modifications: 39
Untracked paths: 45
Total status paths: 84
Staged paths: 0
```

After the three replacements and report population, those counts remain
exactly 39/45/84/0. All three correction targets are already status paths, and
Phase 0R.1 creates no implementation content path.

The directive and report remain untracked, unstaged, and absent from `HEAD`.

---

# 4. EXACT FILE BOUNDARY

Modify exactly:

```text
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/architecture/overview.md
docs/engineering/package-layout.md
docs/task reports/Phase_0R_1_correction.md
```

The report is caller-created and may be populated only after all other gates
pass.

Create no executor content file. Preserve every other path byte-for-byte,
including:

- ADR 0031;
- the Phase 0R directive and report;
- every prior directive, report, and ADR;
- every PHP source, test, fixture, manifest, lockfile, and tooling file;
- `README.md`, `ARCHITECTURE.md`, `SECURITY.md`, and all other guides; and
- every Git ref, index entry, mode, stream, cache contract, and history object.

---

# 5. EXACT REPLACEMENTS AND OUTPUT IDENTITIES

Use exact ordinal, whole-block replacement. Do not reflow surrounding prose,
normalize another line, add optional links, or perform cleanup.

## 5.1 ADR 0030 open-work correction

In
`docs/adr/0030-application-graph-diagnostic-context-integration.md`, replace
exactly:

```text
Kernel dependency-topology resolution and its six-context integration;
Dependency Injection integration; disclosure and production allowlist policy;
trusted developer mode; renderers and development or production surfaces; HTTP,
CLI, and other transports; serialization; source and stack providers;
version-matched documentation lookup; logging and telemetry; incidents; retry,
cancellation, cleanup, and simultaneous-failure representation all remain
future work.
```

with exactly:

```text
Implementation of the accepted API-metadata migration, a later
Kernel-to-Diagnostics dependency, and its six-context integration; Dependency
Injection integration; disclosure and production allowlist policy; trusted
developer mode; renderers and development or production surfaces; HTTP, CLI,
and other transports; serialization; source and stack providers;
version-matched documentation lookup; logging and telemetry; incidents; retry,
cancellation, cleanup, and simultaneous-failure representation all remain
future work.
```

Required final identity:

```text
Bytes: 8,277
SHA-256: 9240C349AF5B0EC235152DACC5E22F31E45E55ED082093062F524B5A87465D60
```

This removes only the already-resolved architectural decision from open work.
The actual migration, dependency edge, six schemas, throw-site values, and
Kernel context integration remain future.

## 5.2 Architecture-overview consumer correction

In `docs/architecture/overview.md`, replace exactly:

```text
`Kongono\ApiMetadata\`. A later atomic migration will make every framework
package depend directly on it, while Graph retains its Diagnostics dependency.
```

with exactly:

```text
`Kongono\ApiMetadata\`. A later atomic migration will make Kernel, Diagnostics,
Application Graph, and Dependency Injection depend directly on it, while the
framework root consumes all five packages and Graph retains its Diagnostics
dependency.
```

Required final identity:

```text
Bytes: 7,227
SHA-256: 554FC09E8E3C15AB099096DD66DDFD3ED080D651578A120BB492C54545EC4194
```

This names the exact four package consumers and root relationship. It must not
imply that `kongono/api-metadata` depends on itself.

## 5.3 Package-layout boundary correction

In `docs/engineering/package-layout.md`, replace exactly:

```text
The four physical package boundaries are implemented. Further package boundaries,
dependency rules, independent publishing, splitting, versioning, and release
automation remain open and require authorization.
```

with exactly:

```text
The four physical package boundaries are implemented. Beyond the accepted but
unimplemented Phase 0R API Metadata package and dependency graph, further
package boundaries and dependency rules remain open. Independent publishing,
splitting, versioning, and release automation also remain open and require
authorization.
```

Required final identity:

```text
Bytes: 4,960
SHA-256: B9801F79E280CD622C71EBD15AAF5F13DF22A2FA27C14407EE638625C6B8C1B0
```

This keeps every boundary beyond the exact Phase 0R decision open.

For each replacement, the old block must occur exactly once before editing and
zero times afterward. The new block must occur zero times before editing and
exactly once afterward.

---

# 6. PROHIBITED ACTIONS

Do not:

- modify ADR 0031 or reinterpret its decision;
- modify the Phase 0R report to conceal the original inconsistency;
- add `packages/api-metadata`, PHP types, namespaces, aliases, imports, tests,
  manifests, requirements, or lock changes;
- implement Kernel or DI diagnostic context;
- change Graph context or Diagnostics values;
- add a renderer, error page, trusted mode, disclosure policy, transport,
  logger, telemetry, incident, source, stack, or documentation-lookup system;
- implement DI/container, HTTP/router, Graph schema/IDs/serialization, ORM,
  agents, extensions, CLI, frontend, runtime adapters, or later capabilities;
- rerun Composer update, autoload generation, PHPUnit, PHPStan, PHP CS Fixer,
  `composer quality`, or any implementation gate;
- stage, commit, reset, restore, checkout, switch, branch, tag, push, add a
  remote, alter Git configuration, or rewrite history;
- alter modes, streams, encodings, line endings, caches, or ignored artifacts;
  or
- fix optional wording, add links, or clean up anything outside Section 5.

This correction explicitly retains the independently reverified Phase 0R
quality evidence. It validates preservation rather than rerunning unchanged
implementation work.

---

# 7. REQUIRED VALIDATION

## 7.1 Before editing

Verify:

- all Section 2 and 3 identities and counts;
- the exact 84-path post-caller inventory;
- all three old/new occurrence counts;
- the report placeholder contract;
- empty index and unchanged refs/history;
- ADR 0031 and the Phase 0R report identities; and
- current four-package runtime and absence of API Metadata implementation.

Record byte count and SHA-256 for every status path so preservation can be
proved.

## 7.2 After replacement

Verify:

- all three exact Section 5 output identities;
- all old blocks absent and all new blocks present exactly once;
- ADR 0030 no longer calls topology resolution open while still leaving
  migration and Kernel context implementation future;
- the overview names exactly four package consumers, the root, and no
  self-dependency;
- the package-layout guide distinguishes the accepted fifth boundary from all
  additional open boundaries;
- ADR 0031 remains unchanged;
- every path outside the three content targets remains byte-identical;
- no local Markdown link is broken;
- strict UTF-8 without BOM, LF-only endings, exactly one final LF, and no
  trailing whitespace hold for all three targets;
- no NUL, replacement character, conflict marker, patch marker, temporary
  prose, reparse point, non-default stream, mode change, or cached path exists;
- `git diff --check` passes;
- the index remains empty; and
- Git identity, refs, history, and 39/45/84/0 status counts remain exact.

Do not run canonical quality or focused PHP checks. Prove instead that every
PHP, test, manifest, lock, and tooling artifact retains its Phase 0R identity.

## 7.3 Report validation

Only after all non-report gates pass, populate the correction report and prove
it is nonzero, strict UTF-8 without BOM, LF-only, one-final-LF terminated,
free of trailing whitespace, untracked, unstaged, and absent from `HEAD`.

Return the final report byte count and SHA-256 outside its body.

---

# 8. STRICT ACCEPTANCE CRITERIA

- [ ] Exact pre-caller 39/43/82/0 baseline and binary diff identity match.
- [ ] Exact post-caller and final 39/45/84/0 state matches.
- [ ] All caller and accepted Phase 0R records match.
- [ ] Exactly three existing content files change.
- [ ] All three output byte counts and hashes match Section 5.
- [ ] ADR 0030 treats topology architecture as resolved and implementation as
      future.
- [ ] The overview cannot imply API Metadata self-dependency.
- [ ] Package layout distinguishes the accepted fifth boundary from later open
      boundaries.
- [ ] ADR 0031 and the Phase 0R report remain byte-identical.
- [ ] Every non-authorized path remains byte-identical.
- [ ] No runtime, test, manifest, lock, tooling, dependency, API, or later
      capability changes.
- [ ] No quality rerun; accepted Phase 0R results remain the evidence.
- [ ] Encoding, whitespace, links, modes, streams, file safety, Git identity,
      refs, history, and index all pass.
- [ ] The correction report satisfies Section 9 and its digest is returned
      separately.

Every item must pass.

---

# 9. REQUIRED COMPLETION REPORT

Populate `docs/task reports/Phase_0R_1_correction.md` using exactly these 16
top-level headings in order:

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

- accurate Complete/Partial/Blocked status;
- no executor-created content file;
- exact three corrected document paths plus the caller report path;
- no new architectural decision;
- no dependency or runtime change;
- relevant environment versions;
- exact read-only commands and the three edits;
- old/new occurrence and output-identity results;
- preservation evidence for ADR 0031, Phase 0R report, runtime, tooling, Git,
  modes, streams, links, encoding, and index;
- explicit confirmation that quality was not rerun under this correction;
- final status counts and observed tracked diff summary/hash;
- PASS/FAIL for every Section 8 item; and
- the exact next-step handoff.

For success, Section 16 must say exactly:

```text
Await architect review before Phase 0S.
```

Do not begin Phase 0S.

---

# FINAL EXPECTED OUTCOME

Phase 0R.1 removes the three remaining documentation ambiguities. ADR 0030
treats the package topology as architecturally resolved while keeping its
implementation and Kernel context future. The architecture overview names the
exact package consumers without self-dependency. Package layout distinguishes
the accepted future fifth boundary from every still-open boundary.

ADR 0031, all runtime artifacts, the current four-package implementation, and
the accepted Phase 0R quality evidence remain unchanged.
