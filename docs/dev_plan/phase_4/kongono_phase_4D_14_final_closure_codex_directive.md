# CODEX FINAL CLOSURE DIRECTIVE — KONGONO PHASE 4D.14

## Bounded Harness Repair, Raw CLI Proof, and Final Acceptance

## 1. Purpose

This is the final closure authority for Phase 4D.

Phase 4D's production correction, static analysis, style, focused tests, and disclosure tests are complete. The Phase 4D.13 raw CLI proof stopped because its ephemeral PowerShell harness compared the rejected literal `schemA` with output using `OrdinalIgnoreCase`. That falsely matched the mandatory JSON key `schema`.

The production contract is exact-byte disclosure, not case-insensitive word similarity. `schemA` and `schema` are different byte sequences. The corrected predicate is ordinal case-sensitive comparison.

This directive authorizes:

1. one corrected raw CLI proof using `StringComparison.Ordinal`;
2. bounded repair and rerun of the ephemeral, non-repository harness if the harness itself fails to implement this directive;
3. immediate stop on any genuine production-output failure;
4. completion of artifact, canonical quality, manifest, preservation, and report gates.

It authorizes no Git-visible non-report mutation and no Phase 4E work.

## 2. Process correction adopted here

For this closure only, distinguish:

- **Product failure:** raw CLI bytes, exit code, JSON, state, disclosure, source, test, artifact, or quality result violates the fixed contract. Stop immediately; no product retry or edit.
- **Harness failure:** the ephemeral checker has a parser, API-call, predicate, capture, cleanup, or assertion-implementation defect before it produces complete product evidence. It may be corrected and rerun within Section 8 without new architect authority, provided no semantic acceptance rule changes and no repository file is created or modified.
- **Environmental audit failure:** the locked Composer audit alone cannot reach its advisory source. The existing one identical approved-access retry remains permitted.

This prevents an ephemeral checker defect from being mislabeled as a framework defect while preserving fail-closed product validation.

## 3. Independent authentication

Directive:

`docs/dev_plan/phase_4/kongono_phase_4D_14_final_closure_codex_directive.md`

Active report:

`docs/task_reports/phase_4/Phase_4D_14_final_closure.md`

The architect will independently supply this directive's byte count and SHA-256 outside the file. Do not self-authenticate from local values.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

Reauthenticate all caller records inherited through Phase 4D.13 and additionally:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_13_codex_directive_correction.md` | 14453 | `529630D48427CBBE827484F9D60974F8E615AE672F68A0A6B077A8CB9455F54E` |
| `docs/task_reports/phase_4/Phase_4D_13_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier directives and reports remain immutable.

## 4. Entry state

After the caller places the Phase 4D.14 directive and report, require:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 114 untracked paths;
- 140 total status paths;
- zero staged paths;
- 743 Git-visible paths;
- 629 tracked files;
- 168 production PHP files;
- 163 test PHP files;
- 333 canonical style files;
- 64 ADR files;
- 21 files in `docs/dev_plan/phase_4`;
- 21 files in `docs/task_reports/phase_4`.

Authenticate all ten Phase 4D implementation identities from Phase 4D.12/4D.13. In particular:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/Exception/InvalidChieValueException.php` | 1278 | `1FEF75BC83EEBFC33B7DB13849AB4D8482169903681F52452E7A15E641DA754C` |
| `tests/Architecture/ApiStatusConventionTest.php` | 105051 | `A290FA39324F9A372D957FEA3F88FD2C3938418BCB4EBD2E37D8ECAC2A0FFA91` |

The other eight identities remain exactly as locked by Phase 4D.12.

## 5. Entry manifests

Exclude the thirty Phase 4D caller paths—fifteen directives and fifteen reports. Use ordinal case-sensitive ordering, UTF-8 without BOM, LF between records, and no terminal LF.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete corrected implementation | 713 | 89388 | `2DF7FBA5732B322F963EBD6C3C29575002C4611BE429076A7C2A827BC2FBC5B3` | 7710140 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding thirty callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. Stop on a Git-visible mismatch.

## 6. Accepted completed evidence

Accept without rerun:

- sequential focused PHPStan at process-local 512 MB: complete, exit 0, zero errors;
- normal PHP unchanged at 128 MB;
- focused style: 0 of 32 fixable;
- focused PHPUnit: 44 tests, 7192 assertions, zero failures/errors;
- all 36 Chie tests and eight architecture tests passed;
- all six exception-stringification disclosure failures passed;
- all ten implementation files pass syntax;
- ignored `.cache/phpstan` mutation is permitted ephemeral tooling state under Phase 4D.10;
- Phase 4D.12 adversarial construction/disclosure proof, only if its recorded evidence is complete and passing;
- Phase 4D.13 used raw BaseStreams and cleaned all outside-repository temporary artifacts.

Do not rerun these accepted gates. If the adversarial proof was not completed before Phase 4D.12 stopped, run it once before the CLI proof under its already fixed acceptance rules.

## 7. Exact disclosure comparison correction

Every prohibited literal comparison in the raw CLI harness must use exact ordinal case-sensitive matching:

```powershell
$decoded.IndexOf($forbidden, [System.StringComparison]::Ordinal) -ge 0
```

Equivalent raw UTF-8 byte subsequence comparison is permitted if it is also exact and case-sensitive.

Do not use:

- `OrdinalIgnoreCase`;
- culture-sensitive comparison;
- regex ignore-case options;
- lowercasing or uppercasing either value;
- wildcard matching;
- approximate, normalized, stemmed, or Unicode-folded comparison.

For the known invalid-artifact sentinel:

- prohibited exact literal: `schemA`;
- mandatory JSON key: `schema`;
- expected result: `schemA` is absent while `schema` is present.

Do not change either literal to make the proof pass.

## 8. Raw CLI harness authority

Retain Phase 4D.13's mandatory raw `System.Diagnostics.ProcessStartInfo`/`Process` mechanism, separate `ArgumentList` entries, redirected `StandardOutput.BaseStream` and `StandardError.BaseStream`, and byte assertions before strict UTF-8 decoding.

Retain all Phase 4D.13 product assertions:

- exact exit codes and selected output stream;
- non-selected stream empty;
- final bytes `7D0A`;
- exactly one `0A` and zero `0D` bytes;
- no BOM;
- strict UTF-8;
- one canonical JSON object;
- exact schema/state/operation/diagnostic/citation/uncertainty/disclosure rules;
- temporary artifacts outside the repository and removed in `finally`.

### Bounded harness repair

The raw CLI proof may have up to three total Phase 4D.14 harness executions.

After an execution that fails solely because the ephemeral harness does not implement the fixed rules above—for example a parser error, wrong .NET overload, incorrect harness variable, cleanup predicate error, or checker logic contradicting this directive—the executor may correct only the in-memory/temporary harness and rerun it.

Conditions:

- no harness file may be written inside the repository;
- no product source/test/configuration/artifact may change;
- acceptance predicates may not be deleted, weakened, skipped, or reinterpreted;
- each harness failure and correction must be reported;
- a genuine product-output mismatch consumes the proof and requires immediate stop;
- after three incomplete harness attempts, stop and report;
- a completed passing proof ends the harness loop immediately.

This authority does not permit three product retries. It permits bounded correction of the ephemeral checker only.

## 9. Required CLI states

Prove all externally reachable states from Phase 4D.13:

- answered explain: exit 0, canonical stdout;
- answered diagnose: exit 0, canonical stdout;
- unsupported explain: exit 65, canonical stdout;
- unsupported diagnose: exit 65, canonical stdout;
- unavailable artifact: exit 66, canonical stdout;
- stale artifact: exit 65, canonical stdout;
- invalid/blocked artifact: exit 65, canonical stdout;
- invalid usage: exit 64, canonical safe stderr;
- any accepted externally reachable unexpected-failure scenario: exit 70, canonical safe stderr.

Do not invent unsafe I/O to force an unreachable state. Identify any state proved only by the accepted in-process test.

## 10. Final gates

After the raw CLI proof passes:

1. Verify the Knowledge artifact:
   - artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
   - source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
   - exactly 31 sources.
2. Run final literal `composer quality`.
3. Require zero advisories, platform success, 0 of 333 style files fixable, zero PHPStan errors, and exactly 633 tests. Report the actual assertion count without pre-locking it.
4. Reconstruct final complete/protected/historical manifests. With no non-report changes, they must equal Section 5.
5. Verify the ten-file boundary, API status, autoload, diagnostic/disclosure behavior, Git/index/history/refs/configuration, dependencies, documentation, encoding, alternate streams, ordinary files, ignored-cache state, and absence of temporary artifacts.

One identical approved-access retry is permitted only when the locked Composer audit is blocked solely by managed network/cache access. Ignored PHPStan cache updates caused by authorized quality tooling remain permitted and non-authoritative.

## 11. Prohibited scope

Do not modify any Git-visible non-report file. Do not change production, tests, expected JSON, forbidden literals, PHP/Composer/tooling configuration, dependencies, documentation, ADRs, artifacts, or caller records.

Do not weaken LF-only or disclosure validation. Do not use text-mode CLI capture. Do not manually mutate ignored caches.

Do not stage, commit, tag, create/delete refs, alter history/configuration, clean, reset, restore, stash, populate an earlier report, or begin Phase 4E.

Only `docs/task_reports/phase_4/Phase_4D_14_final_closure.md` may change after all non-report gates pass.

## 12. Completion report

Use the standard 16 sections. Include:

- independent directive and caller authentication;
- entry identities and manifests;
- all accepted prior evidence;
- each Phase 4D.14 harness attempt, whether it was a harness or product result, and any bounded harness correction;
- per-state raw byte, exit, stream, UTF-8, JSON, state, and exact case-sensitive disclosure evidence;
- artifact and final canonical quality results;
- final manifests and preservation proof;
- confirmation no Git-visible non-report file changed;
- report bytes and uppercase SHA-256.

The final line before the report identity appendix must be exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.14 final closure report; Phase 4E was not begun.`

## 13. Acceptance criteria

Phase 4D.14 completes only when:

1. Directive, callers, report placeholder, entry state, identities, and manifests authenticate.
2. Accepted prior gates remain preserved without unnecessary rerun.
3. Raw OS-pipe capture is used.
4. Exact ordinal case-sensitive disclosure matching is used.
5. Any harness repair stays within Section 8 and no product assertion is weakened.
6. Every required reachable CLI state passes raw byte, exit, JSON, and disclosure rules.
7. Temporary artifacts are outside the repository and fully removed.
8. Artifact/source-set identities and 31-source count remain exact.
9. Final canonical quality passes with zero advisories/findings and 633 tests.
10. Final manifests equal Section 5.
11. No unauthorized Git-visible mutation, staging, commit, ref, history, dependency, documentation, or configuration change occurs.
12. Only the Phase 4D.14 final report is populated after success.
13. Phase 4E is not begun.

STOP ON PRODUCT FAILURE

REPAIR ONLY THE EPHEMERAL HARNESS WITHIN SECTION 8

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND PRODUCT SCOPE
