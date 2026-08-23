# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.13

## Raw-Byte External CLI Proof on Windows

## 1. Purpose and correction

This is the architect-authenticated correction for Phase 4D.12's external CLI line-ending proof failure.

Phase 4D.12 required LF-only canonical CLI JSON. The proof process reported CRLF and stopped. Independent architect verification invoked the real extensionless CLI through redirected operating-system pipes and copied `StandardError.BaseStream` directly into a byte buffer. For invalid command usage the observed external result was:

- exit code 64;
- stdout length 0;
- stderr length 190;
- final four bytes in hexadecimal `65227D0A`;
- final byte `0A`;
- penultimate byte `7D` (`}`);
- no `0D0A` sequence.

The production CLI therefore emits LF-only canonical bytes. The failed Phase 4D.12 proof used a text-mode capture path that normalized the process stream to Windows CRLF before checking it. This is a proof-harness defect, not a Chie production defect.

This correction accepts the failed text-mode result only as invalid harness evidence and authorizes one fresh external CLI-state proof using raw redirected process streams. It authorizes no source/test/configuration change and does not weaken the LF-only contract.

All Phase 4D.2 rules as corrected through Phase 4D.12 remain controlling except that CLI byte evidence must be captured by the raw procedure below.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_13_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_13_correction.md`

The architect will supply this directive's byte count and SHA-256 independently outside this file. Do not self-authenticate from local values. Stop before validation if the supplied identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Caller records

Reauthenticate every caller record required by Phase 4D.12. Additionally authenticate:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_12_codex_directive_correction.md` | 10970 | `B2B090793BAEE7F584B26CBDDE4378689429C099FCCDD552F77379E86DAD208A` |
| `docs/task_reports/phase_4/Phase_4D_12_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier Phase 4D directives and reports are immutable. Populate only the Phase 4D.13 report after every remaining non-report gate passes.

## 4. Exact entry state

After the caller places the Phase 4D.13 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 112 untracked paths;
- 138 total status paths;
- zero staged paths;
- 741 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 168 production PHP files;
- 163 test PHP files;
- 333 files in canonical style scope;
- 64 ADR files;
- 20 files in `docs/dev_plan/phase_4`;
- 20 files in `docs/task_reports/phase_4`.

No source, test, dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized.

## 5. Exact implementation and manifests

Authenticate the complete ten-file implementation exactly as required by Phase 4D.12. In particular:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/Exception/InvalidChieValueException.php` | 1278 | `1FEF75BC83EEBFC33B7DB13849AB4D8482169903681F52452E7A15E641DA754C` |
| `tests/Architecture/ApiStatusConventionTest.php` | 105051 | `A290FA39324F9A372D957FEA3F88FD2C3938418BCB4EBD2E37D8ECAC2A0FFA91` |

The remaining eight package identities must match Phase 4D.12/4D.10 exactly.

Exclude the twenty-eight Phase 4D caller paths—fourteen directives and fourteen reports. Use the locked ordinal/no-terminal-LF construction.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete corrected implementation | 713 | 89388 | `2DF7FBA5732B322F963EBD6C3C29575002C4611BE429076A7C2A827BC2FBC5B3` | 7710140 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding twenty-eight callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. Ignored PHPStan cache remains governed by Phase 4D.10. Stop on any mismatch.

## 6. Accepted prior gates

Accept without rerun:

- complete 512 MB sequential PHPStan with zero errors;
- normal PHP unchanged at 128 MB;
- focused style 0 of 32 fixable;
- focused PHPUnit: 44 tests, 7192 assertions, zero failures/errors;
- all 36 Chie tests and eight architecture tests passed;
- all six former exception-stringification disclosure failures passed;
- Phase 4D.12 adversarial construction/disclosure gate, if and only if its recorded gate evidence is complete and passing;
- no Git-visible file changed during Phase 4D.12.

If Phase 4D.12 did not complete and record a passing adversarial gate before attempting the CLI proof, stop and report that missing evidence rather than infer it. Do not rerun any other accepted focused gate.

## 7. Mandatory raw process-capture mechanism

For every external CLI scenario, use `System.Diagnostics.ProcessStartInfo` and `System.Diagnostics.Process` directly:

1. Set `FileName` to the exact active PHP executable, expected `C:\php\php.exe`.
2. Add `packages/chie/bin/kongono-chie` and every CLI argument as a separate `ArgumentList` element. Do not compose one shell command string.
3. Set `UseShellExecute = false`.
4. Set `CreateNoWindow = true`.
5. Set `RedirectStandardOutput = true`.
6. Set `RedirectStandardError = true`.
7. Start the process.
8. Copy `StandardOutput.BaseStream` directly into one `System.IO.MemoryStream`.
9. Copy `StandardError.BaseStream` directly into another `System.IO.MemoryStream`.
10. Wait for exit and obtain each buffer with `ToArray()`.
11. Perform byte-level canonical checks before any UTF-8 decoding.
12. Decode only after byte checks, using strict UTF-8, for JSON/schema assertions.

The outputs are small and bounded. Dispose processes and streams after each scenario.

## 8. Prohibited text-mode mechanisms

Do not capture, redirect, save, or transform CLI output through:

- PowerShell's pipeline;
- `>` or `2>` redirection;
- `Out-File`, `Set-Content`, `Add-Content`, `Tee-Object`, or `Get-Content`;
- `Start-Process -RedirectStandardOutput` file paths;
- `StandardOutput.ReadToEnd()` or `StandardError.ReadToEnd()` before byte checks;
- string arrays returned from native-command invocation;
- line enumeration, `Write-Output`, joining, splitting, trimming, or newline replacement;
- `cmd.exe`, another shell, or a temporary text file;
- text re-encoding before the byte assertions.

These mechanisms can normalize newline bytes on Windows and are not evidence of the external CLI byte contract.

## 9. Byte-level acceptance for every scenario

For the selected JSON output stream in each scenario require:

- byte length greater than zero and within the existing response limit;
- final byte exactly `0A`;
- penultimate byte exactly `7D`;
- exactly one byte equal to `0A`;
- zero bytes equal to `0D`;
- no UTF-8 BOM prefix (`EF BB BF`);
- strict UTF-8 decoding succeeds;
- decoded content before the final LF is exactly one JSON object;
- re-encoding/decoding checks do not alter the original raw buffer;
- canonical schema, version, role, status, operation, keys, diagnostic ID, claims, citations, uncertainties, and disclosure expectations match the selected scenario.

The non-selected output stream must contain zero bytes. Exit code must exactly match the established CLI contract.

Do not accept a string-level `EndsWith("\n")` check as a substitute for raw-byte proof.

## 10. Required external CLI states

Re-run the same external CLI-state inventory attempted by Phase 4D.12, but through Sections 7 through 9. At minimum retain and prove every state required by Phase 4D.2/4D.8:

- answered explanation: exit 0, canonical stdout, empty stderr;
- answered diagnosis: exit 0, canonical stdout, empty stderr;
- unsupported explanation: exit 65, canonical stdout, empty stderr;
- unsupported diagnosis: exit 65, canonical stdout, empty stderr;
- unavailable artifact: exit 66, canonical stdout, empty stderr;
- stale artifact: exit 65, canonical stdout, empty stderr;
- invalid/blocked artifact: exit 65, canonical stdout, empty stderr;
- invalid command usage: exit 64, empty stdout, canonical safe stderr;
- any externally reachable unexpected-failure scenario already defined by the accepted proof: exit 70, empty stdout, canonical safe stderr.

Use only the existing deterministic Knowledge artifact and temporary artifacts required by the accepted proof. Temporary files/directories must be created outside the repository in a fresh OS temporary directory, must contain no credential or private caller data, and must be removed in `finally`. Confirm their absolute resolved paths are outside `C:\Users\antho\Dev\kongono` before cleanup.

Do not invent an unsafe way to force an unreachable failure state. If a scenario is covered only through the accepted in-process focused test because external triggering would require mutation or unsafe I/O, report that distinction explicitly while preserving its passing focused proof.

## 11. One corrected proof attempt

The complete raw-capture CLI proof is authorized once. If its harness fails before producing evidence, or if any production result fails a byte, exit-code, schema, state, or disclosure condition, stop without retry or mutation.

After a complete pass, continue without rerunning prior gates:

1. Knowledge artifact SHA-256/source-set SHA-256/31-source proof;
2. final literal `composer quality`;
3. final complete/protected/historical manifests;
4. exact file boundary, API status, autoload, diagnostic/disclosure, Git, index, history, refs, configuration, dependency, documentation, encoding, alternate-stream, ordinary-file, and ignored-cache preservation;
5. Phase 4D.13 report population.

Final canonical quality must report zero advisories, 0 of 333 style files fixable, zero PHPStan errors, and exactly 633 tests. Report the actual assertion count without pre-guessing it.

The only permitted retry remains one identical approved-access retry if the locked Composer audit is blocked solely by managed network/cache access. Stop without retry on any other failure.

## 12. Artifact and preservation invariants

Require:

- Knowledge artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
- exactly 31 Knowledge sources;
- all ten implementation identities unchanged;
- no Git-visible non-report mutation during Phase 4D.13;
- no temporary path remains;
- ignored cache paths remain ignored, untracked, and unstaged;
- native traces are not claimed sanitized;
- valid Chie canonical output remains byte-identical and LF-only.

## 13. Prohibited scope

Do not edit production, tests, Composer, configuration, documentation, artifacts, dependencies, caller records, or tooling settings. Do not add a test harness or temporary file to the repository.

Do not weaken LF-only validation or accept CRLF. Do not change `fwrite`, `CanonicalJson`, the CLI executable, or Windows PHP configuration. Do not set binary/text stream modes; raw redirected OS pipes already provide the required evidence.

Do not rerun completed focused gates, stage, commit, tag, create/delete refs, alter history/configuration, clean, reset, restore, stash, populate an earlier report, or begin Phase 4E.

Only `docs/task_reports/phase_4/Phase_4D_13_correction.md` may change, and only after every non-report gate passes.

## 14. Completion report

Populate the Phase 4D.13 report only after full success. Use the standard 16 sections and include:

- independent directive and every earlier caller identity;
- entry state, ten implementation identities, and three manifests;
- accepted focused PHPStan/style/test and disclosure evidence;
- the invalid Phase 4D.12 text-mode capture and architect raw invalid-usage evidence;
- exact raw process-capture mechanism;
- per-scenario exit code, raw stdout/stderr byte lengths, last bytes, LF/CR counts, UTF-8/JSON/state/disclosure results;
- explicit distinction for any failure state proved only in-process;
- temporary-path creation, outside-repository proof, and cleanup evidence;
- artifact, canonical quality, final manifests, and preservation evidence;
- confirmation no non-report file changed;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix must be exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 15. Acceptance criteria

Phase 4D.13 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, implementation identities, and manifests match.
3. Prior focused evidence remains accepted without rerun.
4. Raw process streams—not text-mode capture—are used.
5. Every externally reachable required CLI state passes exact exit/stream rules.
6. Every selected JSON stream contains one terminal LF and zero CR bytes.
7. Strict UTF-8, canonical JSON, state, and disclosure proofs pass.
8. Temporary paths remain outside the repository and are fully removed.
9. Artifact/source-set identities and 31-source count remain exact.
10. Final canonical quality passes with 333 style files and 633 tests.
11. Protected/historical manifests remain exact and complete manifest remains closed.
12. No unauthorized mutation, staging, commit, ref, history, dependency, documentation, or configuration change occurs.
13. Only the Phase 4D.13 report is populated after success.
14. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
