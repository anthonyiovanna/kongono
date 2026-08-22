# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3E.1

## Final Inventory and Stale Current-Document Correction

**Repository:** `C:\Users\antho\Dev\kongono`
**Role:** executor, not architect
**Required branch:** `main`
**Date:** 2026-08-21
**Phase:** 3E.1 correction only

---

## 1. Mission

Correct one contradictory final-status invariant in the authenticated Phase 3E
directive and finish the already implemented Phase 3E validation/report flow.

The Phase 3E implementation boundary itself is accepted as the exact preserved
starting state for this correction. One stale sentence in the already-modified
current `http-message-model.md` was omitted from the original authorized
reconciliation boundary. Apart from the one exact whole-block replacement in
Section 7, do not edit, regenerate, restore, format, or otherwise change any
implementation, test, manifest, lockfile, ADR, or document byte.

This correction authorizes only:

1. replacing the impossible Phase 3E final Git inventory with the correct
   arithmetic established in Section 6;
2. replacing one exact stale current/future block in
   `docs/architecture/http-message-model.md`;
3. completing the pending documentation, file-safety, environment,
   Git, and canonical-quality gates;
4. populating only the Phase 3E.1 correction report after every non-report gate
   passes; and
5. returning that report's independently calculated byte count and SHA-256.

No architecture is reopened. The prose correction records already implemented
Phase 3E behavior and makes Phase 3F the truthful next phase. The typed operator
profile, immutable bootstrap,
closed prerequisite validation, fail-closed current-delivery permission,
diagnostic contract, package dependency, tests, ADR 0055, and documentation
bytes must remain exactly as observed at this correction's entry.

---

## 2. Why the original invariant is impossible

The authenticated Phase 3E directive began from 21 modified tracked paths and
required changes to exactly 25 existing paths.

At entry to Phase 3E, those 25 paths were distributed as follows:

```text
already modified tracked paths: 12
already untracked paths:          7
clean tracked paths:              6
total authorized existing paths: 25
```

Changing the twelve already-modified paths does not change the modified count.
Changing the seven already-untracked paths does not make them tracked. Changing
the six clean tracked paths necessarily increases the modified count by six:

```text
21 + 6 = 27 modified tracked paths
```

The eight newly created Phase 3E paths necessarily increase the original
post-caller untracked count from 44 to 52:

```text
44 + 8 = 52 untracked paths
```

Therefore the only possible post-implementation Phase 3E inventory is:

```text
modified tracked paths: 27
untracked paths:         52
total status paths:      79
staged paths:             0
```

The original `21/52/73/0` requirement failed to include the six required clean
tracked documents that became modified. Restoring those six files would violate
the exact 25-path modification boundary and the nineteen-document
reconciliation contract. Their Phase 3E bytes are accepted and must not be
restored.

---

## 3. Mandatory stop rules

Stop immediately, preserve the exact reached state, report expected and
observed values, and request the smallest authenticated correction if any
identity, inventory, manifest, validation result, file-safety predicate, or
report predicate differs.

Do not:

- infer or self-authenticate this correction directive;
- edit any repository source, test, configuration, manifest, lockfile, ADR,
  document, directive, or completed report except the one exact
  `http-message-model.md` block authorized by Section 7;
- rerun Composer update or autoload generation;
- apply formatting or an automatic fix;
- add a suppression, baseline, ignore, exclusion, dependency, plugin, or policy
  exception;
- truncate, replace, normalize, repair, or populate the original zero-byte
  `Phase_3E.md` report;
- stage, commit, amend, reset, restore, clean, switch branches, create/delete
  refs, rewrite history, or change the semantic index; or
- begin Phase 3F or later work.

One unchanged retry of literal `composer quality` is permitted only when its
sole failure is managed network/cache denial. Record both attempts. No
non-network retry is authorized.

On any stop:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS, CLEAN UP, OR EXPAND SCOPE
```

---

## 4. Independent directive authentication and caller records

The architect must provide this correction directive's expected byte count and
SHA-256 outside the file body:

```text
docs/dev_plan/kongono_phase_3E_1_codex_directive_correction.md
```

Before reading beyond authentication or running any validation, compare the
local file to that independently supplied identity. The local file is not
self-authenticating.

The caller must also create:

```text
docs/task reports/Phase_3E_1_correction.md
```

It must initially be an ordinary, untracked, unstaged, `HEAD`-absent, zero-byte
file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero bytes until every non-report gate in this correction passes.

The original Phase 3E caller records are immutable:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_3E_codex_directive.md` | 44,585 | `E2F7A114C5DD99F7FBA4E1E7CC4B73C01FA927F477C65EADF0755A93E81FF044` |
| `docs/task reports/Phase_3E.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

The original report remains zero-byte permanently as the historical blocked
caller record. Populate only the Phase 3E.1 correction report.

---

## 5. Required authorities

After authentication, read completely:

```text
AGENTS.md
docs/dev_plan/kongono_phase_3E_codex_directive.md
docs/task reports/Phase_3D_1_correction.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
packages/php-cgi/src/Bootstrap/CgiOperatorProfile.php
packages/php-cgi/src/Bootstrap/CgiOperatorBootstrap.php
packages/php-cgi/src/Exception/CgiOperatorBootstrapException.php
packages/php-cgi/src/Internal/CgiOperatorBootstrapValidation.php
packages/php-cgi/src/Internal/CgiTrustedPresentationPermissionEvaluator.php
packages/php-cgi/tests/Unit/Bootstrap/CgiOperatorBootstrapTest.php
packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
packages/php-cgi/composer.json
composer.lock
tests/Architecture/ApiStatusConventionTest.php
docs/architecture/http-message-model.md
```

The authenticated Phase 3E directive remains authoritative except where this
correction expressly supersedes its impossible status counts and active report
path. Do not reinterpret any other contract.

---

## 6. Authenticated correction entry state

### 6.1 Repository identity

Before the caller copies this correction directive and creates its report
placeholder, the preserved state is:

```text
branch:                  main
local branches:          1
HEAD:                    647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                    3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                  d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:            10
remotes:                 0
tags:                    0
modified tracked paths:  27
untracked paths:         52
total status paths:      79
staged paths:             0
Git-visible files:      588
tracked files:          536
package directories:     14
package manifests:       14
production PHP files:   121
test PHP files:         137
style-covered PHP files: 258
```

After the caller adds the two Phase 3E.1 caller records, the exact Gate A and
final live state must be:

```text
modified tracked paths:  27
untracked paths:         54
total status paths:      81
staged paths:             0
Git-visible files:      590
tracked files:          536
package directories:     14
package manifests:       14
production PHP files:   121
test PHP files:         137
style-covered PHP files: 258
```

Exactly one local branch ref, `refs/heads/main`, is permitted. Existing
application-managed `refs/codex/**` refs are exempt from the branch count but
must not be created, deleted, or changed. No unexpected ref is permitted.

### 6.2 Exact Phase 3E implementation-state manifest

Reconstruct one manifest from exactly the union of the original Phase 3E
directive's eight Section 6.1 created paths and 25 Section 6.2 modified paths.

Normalize paths to `/`, sort with:

```powershell
$paths.Sort([System.StringComparer]::Ordinal)
```

Serialize each record exactly as:

```text
path<TAB>byte-count<TAB>uppercase-SHA-256<LF>
```

Encode as UTF-8 without BOM. The accepted direct implementation state is:

```text
records:               33
manifest bytes:      3695
manifest SHA-256:    80FBAFD1412ED1B11488AF9D2DBF1A0D41E1E6B62BD92BC964AF1742726E6C67
aggregate file bytes: 700590
first path:           ARCHITECTURE.md
last path:            tests/Architecture/ApiStatusConventionTest.php
```

Every Phase 3E implementation byte is frozen. If this manifest differs, stop.

### 6.3 Protected-state manifest

Reconstruct the protected manifest. Exclude the same 33 Phase 3E implementation
paths, the one correction-owned current document, and all four Phase 3E-family
caller records:

```text
docs/dev_plan/kongono_phase_3E_codex_directive.md
docs/task reports/Phase_3E.md
docs/dev_plan/kongono_phase_3E_1_codex_directive_correction.md
docs/task reports/Phase_3E_1_correction.md
docs/architecture/http-message-model.md
```

Use the original exact ordinal ordering and serialization. The result remains:

```text
records:               552
manifest bytes:       68385
manifest SHA-256:     1BCCDE27D1D05EC43850242A9E3FEA651086D2D4BDF7DE74DBC588994BE3F9F2
aggregate file bytes: 5649044
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

Together, the 552 protected records, 33 Phase 3E implementation records, and
one correction-owned current document account for every non-caller Git-visible
file. No path may be silently omitted.

### 6.4 Correction-owned current-document identity

Before the exact replacement, the target is:

```text
path:   docs/architecture/http-message-model.md
bytes:  14793
SHA-256: 642762EB1CCC8231DA77522E3A7385A5493275F2E4D0D3EF189230726DC9949D
```

It is already one of the 27 modified tracked paths, so its authorized prose
replacement does not change any Git status count.

---

## 7. Exact supersessions

This section makes the only two substantive corrections.

The Phase 3E directive Section 17 Gate G block:

```text
modified tracked paths:  21
untracked paths:         52
total status paths:      73
staged paths:             0
```

is superseded by the correct pre-correction-caller implementation inventory:

```text
modified tracked paths:  27
untracked paths:         52
total status paths:      79
staged paths:             0
```

The Phase 3E directive acceptance criterion 29 is corrected from
`21/52/73/0` to `27/52/79/0`.

Because this correction itself adds two immutable untracked caller records, all
Phase 3E.1 live Gate A, final pre-report, and post-report checks use:

```text
27 modified / 54 untracked / 81 total / 0 staged
590 Git-visible / 536 tracked
```

This caller-record adjustment does not change the corrected Phase 3E
implementation arithmetic. Every other Gate G value and every other Phase 3E
acceptance criterion remains unchanged.

### 7.1 Exact stale current-document replacement

In `docs/architecture/http-message-model.md`, replace this exact whole block,
which must occur once:

```text
Arbitrary adapters, proxy trust, concrete delivery, operator bootstrap,
deployment, and broader resource cleanup remain unimplemented. Phase 3E alone
is next and owns operator-profile syntax and trust composition.
```

with exactly:

```text
Arbitrary adapters, proxy trust, concrete delivery, deployment, and broader
resource cleanup remain unimplemented. Phase 3E now implements the immutable
operator profile and independent trust inputs. Phase 3F alone is next and owns
concrete delivery, Web Runtime composition, and the production entry boundary.
```

The old block must then be absent and the new block must occur once. The exact
final file identity must be:

```text
bytes:  14890
SHA-256: 7D87581825D8896E0842495112CA4D89DC4B5FF69BDE215A891EDBD4D9559876
```

Do not change any other byte in that file.

---

## 8. Exact authorized file boundary

No production, test, configuration, package manifest, lockfile, ADR, historical
document, original directive, or original report may be created, modified,
deleted, moved, renamed, formatted, regenerated, or normalized.

The only authorized mutations are:

1. the exact Section 7 whole-block replacement in
   `docs/architecture/http-message-model.md`; and
2. after every non-report gate passes, populating:

```text
docs/task reports/Phase_3E_1_correction.md
```

No other current-document change is authorized.

No staging or commit is authorized.

---

## 9. Resume validation workflow

### Gate A - authenticate and preserve

1. Authenticate this correction directive independently.
2. Read every Section 5 authority completely.
3. Verify both correction caller records and both immutable original Phase 3E
   caller records.
4. Verify exact `27/54/81/0`, 590-visible, 536-tracked live state.
5. Verify branch, HEAD, tree, parent, history, refs, remotes, tags, semantic
   index, modes, streams, ordinary-file status, and absence of staging.
6. Reconstruct both Section 6 manifests and verify the Section 6.4 starting
   identity exactly.
7. Verify normal PHP has `memory_limit=128M` and `PHP_INI_SCAN_DIR` is absent.

If any item differs, stop before broad validation.

### Gate B - exact current-document replacement and integrity proof

Apply only the Section 7 exact whole-block replacement. Verify the old block is
absent, the new block occurs once, and the final file identity is exact.

Then, without changing any other bytes, complete the pending Phase 3E proofs:

- every local Markdown link resolves;
- current/future claims agree and Phase 3F alone is next;
- the fixed Phase 3A-H roadmap table remains exactly 1,329 LF bytes with
  SHA-256 `653BE9BE8A64487C275FCEC935C724F1201D2BCCC398B6CAC4DFCCE0F3ED9B04`;
- no prohibited capability or symbol exists;
- diagnostic metadata, tokens, operator mapping, prerequisite table, and
  permission truth table are exact;
- bootstrap and delivery permission sources remain independent;
- no raw or sentinel value appears on package-owned diagnostic surfaces;
- no environment, file, request, or raw-profile parser exists;
- UTF-8 without BOM, LF-only, one final LF, no trailing whitespace, no NUL,
  ordinary files, default streams, and unchanged tracked modes pass; and
- `git diff --check` passes.

Beyond the exact replacement, this gate is read-only. If another defect is
found, stop; do not fix it under this correction.

### Gate C - canonical quality

Run literal:

```powershell
composer quality
```

If and only if it fails solely because the managed environment cannot reach
the locked advisory service or normal cache, repeat the identical command once
with approved network/cache access.

Require zero advisories, platform pass, zero style findings, zero PHPStan
errors, and a completely passing PHPUnit suite. Record exact versions, file,
test, and assertion counts.

Do not rerun Composer update, autoload generation, focused PHPStan, focused
style, or focused PHPUnit. Their accepted Phase 3E evidence is preserved in the
blocked handoff and frozen implementation manifest.

### Gate D - final preservation and report

Before report population, verify again:

```text
modified tracked paths:  27
untracked paths:         54
total status paths:      81
staged paths:             0
Git-visible files:      590
tracked files:          536
package directories:     14
package manifests:       14
production PHP files:   121
test PHP files:         137
style-covered PHP files: 258
```

Reconstruct both manifests. Verify the correction-owned document's exact final
identity, all four caller records, exact path unions, semantic lock delta,
normal 128M PHP, absent scan overlay, branch, refs, history, semantic index,
modes, streams, encoding, and links.

Only then populate `docs/task reports/Phase_3E_1_correction.md`.

After population, rerun report integrity, both manifests, the corrected
document identity, exact inventories, Git identity/status, no-staging, and
no-history-change proofs. The populated report changes no path count.

---

## 10. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. CONTRADICTION RESOLVED
## 4. BASELINE VERIFIED
## 5. CALLER RECORDS
## 6. FILES CREATED
## 7. FILES MODIFIED
## 8. IMPLEMENTATION STATE PRESERVED
## 9. PACKAGE AND DEPENDENCY CONTRACT
## 10. OPERATOR PROFILE AND BOOTSTRAP
## 11. CURRENT-DELIVERY PERMISSION
## 12. DIAGNOSTICS AND DISCLOSURE
## 13. DOCUMENTATION AND FILE INTEGRITY
## 14. ARCHITECTURAL DECISIONS
## 15. DEVIATIONS FROM DIRECTIVE
## 16. DEPENDENCIES ADDED/REMOVED
## 17. ENVIRONMENT
## 18. COMMANDS RUN
## 19. VALIDATION RESULTS
## 20. SECURITY AND FILE-SAFETY REVIEW
## 21. WARNINGS / KNOWN ISSUES
## 22. QUESTIONS / BLOCKERS
## 23. FINAL GIT STATUS
## 24. ACCEPTANCE CRITERIA
## 25. NEXT STEP
```

Under `FILES CREATED` and `FILES MODIFIED`, distinguish Phase 3E's preserved
implementation inventory from this correction's exact prose replacement and
report mutation. Do not claim this correction recreated or rewrote accepted
implementation bytes.

The final next step must be exactly:

```text
Await architect review before Phase 3F.
```

After completion, return the correction report byte count and SHA-256 outside
the report body. Do not put self-referential identity values inside the report.

---

## 11. Strict acceptance criteria

All must pass:

1. Independent correction-directive authentication matched.
2. Every required authority was read completely.
3. Four caller records matched, including both initially verified zero-byte
   report prerequisites.
4. Repository identity, refs, semantic index, and exact live
   `27/54/81/0` entry state matched.
5. The 33-record Phase 3E implementation manifest matched exactly.
6. The 552-record protected-state manifest matched exactly.
7. The `http-message-model.md` starting identity and one exact whole-block
   replacement matched, producing its required final identity.
8. No implementation, test, configuration, manifest, lockfile, ADR, other
   document, original directive, or original report byte changed.
9. The inventory contradiction was resolved as `27/52/79/0` before correction
   caller records and `27/54/81/0` with them present.
10. Exactly fourteen packages and manifests, 121 production PHP files, 137 test
   PHP files, and 258 style-covered PHP files remain.
11. The package dependency and semantic lock contract remain exact.
12. The accepted focused syntax, analysis, style, PHP-CGI, and architecture
    evidence remains accurately recorded and is not misreported as rerun.
13. Links, current/future documentation, and the fixed roadmap table pass.
14. Diagnostic, disclosure, permission-independence, and prohibited-symbol
    inspections pass.
15. UTF-8, LF, final LF, whitespace, NUL, ordinary-file, stream, mode, and
    `git diff --check` proofs pass.
16. Literal post-change canonical quality passes with zero advisories, platform
    pass, zero style findings, zero PHPStan errors, and complete PHPUnit success.
17. Normal PHP remains 128M and `PHP_INI_SCAN_DIR` remains absent.
18. Final live state is exactly `27/54/81/0`, 590 visible, and 536 tracked,
    with unchanged branch, refs, HEAD, tree, parent, history, and semantic index.
19. Nothing was staged or committed.
20. The original Phase 3E report remains zero-byte and immutable.
21. Only the Phase 3E.1 correction report was populated.
22. Report headings, truthful evidence, exact next step, and independently
    returned report identity pass.
23. No Phase 3F or later work began.

Any failure means Phase 3E is not closed. Preserve the exact reached state,
leave the correction report zero bytes unless all non-report gates passed, and
request the smallest authenticated correction.

---

## 12. Final instruction

Execute Phase 3E.1 only.

Make no implementation edit. Authenticate the preserved state, accept only the
corrected inventory arithmetic, apply only the exact current-document block,
finish the pending integrity and canonical gates, and populate only the
correction report after every non-report gate passes.

Do not stage, commit, or begin Phase 3F.

Stop after returning the completed correction report's independently computed
byte count and SHA-256.
