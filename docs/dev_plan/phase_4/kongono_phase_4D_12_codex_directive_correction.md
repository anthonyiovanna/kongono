# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.12

## Accept Successful Focused Assertion Count and Resume Final Gates

## 1. Purpose and correction

This is the architect-authenticated evidence correction and final-gate resume for Phase 4D.11.

Phase 4D.11 applied exactly three authorized architecture-test expectation changes. Syntax and focused style passed. Focused PHPUnit then completed successfully:

- 36 Chie tests passed;
- eight architecture tests passed;
- 44 total tests passed;
- 7192 assertions completed;
- zero failures and zero errors;
- all six former disclosure failures passed.

Phase 4D.11 incorrectly locked the assertion count at 4183. That value came from the preceding run containing three architecture failures; early failures prevented later assertions in the same test methods from executing. A fully successful run necessarily executes the remaining assertions and produced 7192.

This correction accepts 7192 as the exact successful focused assertion count, authenticates the corrected architecture-test identity and manifest, and authorizes resumption at the adversarial construction/disclosure gate without rerunning syntax, focused style, focused PHPStan, or focused PHPUnit.

It does not authorize any further source or test edit. All substantive Phase 4D.2 rules as corrected through Phase 4D.11 remain controlling except the superseded focused assertion scalar.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_12_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_12_correction.md`

The architect will supply this directive's byte count and SHA-256 independently outside this file. Do not self-authenticate from locally observed values. Stop before further validation if the supplied identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Caller records

Reauthenticate every caller record required by Phase 4D.11. Additionally authenticate:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_11_codex_directive_correction.md` | 11246 | `2B35FC35A8B3BBB55F6B97A780FBA6B65239116FDFB3E881BDCD4C7D18409581` |
| `docs/task_reports/phase_4/Phase_4D_11_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier Phase 4D directives and reports are immutable. Populate only the Phase 4D.12 report after every remaining non-report gate passes.

## 4. Exact entry state

After the caller places the Phase 4D.12 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 110 untracked paths;
- 136 total status paths;
- zero staged paths;
- 739 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 168 production PHP files;
- 163 test PHP files;
- 333 files in canonical style scope;
- 64 ADR files;
- 19 files in `docs/dev_plan/phase_4`;
- 19 files in `docs/task_reports/phase_4`.

No source, test, dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized during Phase 4D.12.

## 5. Exact final implementation identities

Authenticate the nine package implementation files exactly as listed in Phase 4D.11/Phase 4D.10. Authenticate the corrected architecture test as:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `tests/Architecture/ApiStatusConventionTest.php` | 105051 | `A290FA39324F9A372D957FEA3F88FD2C3938418BCB4EBD2E37D8ECAC2A0FFA91` |

The architecture-test delta from its Phase 4D.11 entry must consist of exactly:

1. production declaration count `167` to `168`;
2. factory reflection expectation `'self'` to `InvalidChieValueException::class`;
3. production file count `167` to `168`.

All six production factories must remain source-declared with return type `self`. Stop if any identity or semantic condition differs.

## 6. Exact corrected manifests

Exclude the twenty-six Phase 4D caller paths—thirteen directives and thirteen reports. Use the locked ordinal/no-terminal-LF construction.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete corrected implementation | 713 | 89388 | `2DF7FBA5732B322F963EBD6C3C29575002C4611BE429076A7C2A827BC2FBC5B3` | 7710140 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding twenty-six callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. Ignored cache state remains outside every Git-visible manifest under Phase 4D.10's corrected ephemeral-cache policy.

## 7. Accepted validation evidence

Accept without rerun:

### Static analysis

- exact Phase 4D.9 sequential command with process-local 512 MB;
- complete analysis;
- exit code 0;
- zero PHPStan errors;
- normal PHP remained 128 MB before and after.

### Focused style

- Phase 4D.11/4D.10 cache-disabled focused style;
- exit code 0;
- 0 of 32 files fixable.

### Focused tests

- command: `php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/chie/tests tests/Architecture/ApiStatusConventionTest.php`;
- Chie tests: 36 passed;
- architecture tests: eight passed;
- total: 44 passed;
- assertions: 7192;
- failures/errors: zero;
- all six former disclosure failures passed.

The 7192 assertion count supersedes only Phase 4D.11's erroneous 4183 requirement. Do not reduce, delete, skip, or rewrite assertions to reproduce the failed-run count.

## 8. Exact resume point

After Sections 2 through 7 pass, do not rerun syntax, focused PHPStan, focused style, or focused PHPUnit. Resume exactly at Phase 4D.10's adversarial construction/disclosure proof.

Run, in order:

1. all adversarial construction and disclosure proofs required by Phase 4D.8 and Phase 4D.2;
2. all valid CLI response/state regressions;
3. Knowledge artifact SHA-256, source-set SHA-256, and exact 31-source proof;
4. final literal `composer quality`;
5. final complete, protected, and historical manifests;
6. exact created/modified boundary, API status, autoload, diagnostic/disclosure, Git, index, history, refs, configuration, dependency, documentation, encoding, alternate-stream, ordinary-file, and ignored-cache preservation;
7. Phase 4D.12 report population.

Final canonical quality must report:

- zero security advisories;
- platform success;
- 0 of 333 style files fixable;
- zero PHPStan errors;
- exactly 633 tests;
- the actual successful assertion count, reported but not pre-guessed.

The only permitted retry remains one identical approved-access retry when the locked Composer audit is blocked solely by managed network/cache access. Stop without retry on any other failure.

## 9. Final artifact and disclosure invariants

Require:

- Knowledge artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- Knowledge source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
- exactly 31 Knowledge sources;
- `InvalidChieValueException` remains Internal, final, an immediate native `InvalidArgumentException` subtype, privately constructed, property-free, and outside diagnostic markers;
- its six zero-argument factories and deterministic final stringification remain exact;
- all six Chie validation throw families use those factories;
- raw rejected values remain absent from package messages, `(string) $exception`, diagnostic context, and canonical JSON;
- native trace APIs are not claimed sanitized or used as a presentation surface;
- valid Chie JSON/CLI behavior remains byte-identical.

## 10. Mutation boundary and prohibitions

Phase 4D.12 authorizes no Git-visible non-report mutation. Do not edit or format any source, test, configuration, Composer, documentation, artifact, dependency, or caller file.

Ignored `.cache/phpstan` may change incidentally during authorized canonical PHPStan execution under Phase 4D.10's policy. Do not manually edit, delete, restore, stage, commit, or treat it as semantic authority.

Do not rerun completed focused gates. Do not stage, commit, tag, create/delete refs, alter history/configuration, clean, reset, restore, stash, populate an earlier report, or begin Phase 4E.

Only `docs/task_reports/phase_4/Phase_4D_12_correction.md` may change, and only after all remaining non-report gates pass.

## 11. Completion report

Populate the Phase 4D.12 report only after complete success. Use the standard 16 sections and include:

- independent directive and every earlier caller identity;
- exact entry state, ten implementation identities, and corrected manifests;
- three architecture-test replacements and corrected file identity;
- accepted sequential PHPStan, focused style, and `44 tests / 7192 assertions` evidence;
- adversarial, valid CLI, artifact, final canonical quality, final manifest, and preservation evidence;
- actual final canonical assertion count without a predeclared exact scalar;
- ignored-cache classification and confirmation all cache paths remain ignored/untracked/unstaged;
- confirmation no non-report file changed during Phase 4D.12;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix must be exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 12. Acceptance criteria

Phase 4D.12 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, implementation identities, and corrected manifests match.
3. The architecture-test delta is exactly three replacements.
4. Accepted focused evidence remains 44 tests and 7192 assertions with zero failures.
5. No completed focused gate is rerun.
6. Adversarial construction/disclosure proofs pass.
7. Valid CLI regressions pass.
8. Artifact/source-set identities and 31-source count remain exact.
9. Final canonical quality passes with zero advisories, zero PHPStan errors, 0 of 333 style findings, and 633 tests.
10. Protected/historical manifests remain exact and complete manifest remains closed.
11. No unauthorized Git-visible mutation, staging, commit, ref, history, dependency, documentation, or configuration change occurs.
12. Only the Phase 4D.12 report is populated after full non-report success.
13. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
