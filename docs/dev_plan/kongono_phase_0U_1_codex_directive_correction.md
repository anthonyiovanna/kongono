# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0U.1

## Correct the Successful Completion Report's Public-API Statement

## Mission

Correct exactly one contradictory paragraph in
`docs/task reports/Phase_0U.md`.

The Phase 0U implementation is substantively accepted. Independent inspection
and execution confirmed the exact 25 DI schemas, 26 throw-site maps, authorized
DI-to-Diagnostics dependency and lock delta, unchanged resolver behavior, native
failure propagation, documentation, file safety, and full quality result of 204
tests with 3,377 assertions.

The completion report correctly states that the Public DI exception marker gains
`diagnosticContext()`, but its Summary later says all “public API shapes” remain
unchanged. That broad statement is false. The ServiceDefinition and resolver
public shapes remain unchanged; the Public exception marker has the one
intentional pre-alpha signature addition authorized by Phase 0U.

This is a report-only correction. Do not rerun implementation or quality work.

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect. Read completely before editing:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0U_codex_directive.md`;
3. `docs/task reports/Phase_0U.md`;
4. `packages/dependency-injection/src/Exception/ExceptionInterface.php`;
5. `packages/dependency-injection/src/Contract/ResolverInterface.php`;
6. `packages/dependency-injection/src/Definition/ServiceDefinition.php`;
7. `packages/dependency-injection/src/DependencyResolver.php`;
8. `docs/adr/0033-dependency-injection-diagnostic-context-integration.md`;
9. this correction directive; and
10. the zero-byte Phase 0U.1 correction report.

If any Git fact, caller record, byte/hash, exact old paragraph, source contract,
or closed inventory differs:

```text
STOP
ASK
DO NOT GUESS
```

Do not repair adjacent wording or reformat the report.

# 2. LOCKED PRE-CORRECTION BASELINE

Before caller setup verify exactly:

```text
branch                         main
local branches                 1
HEAD                           75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                      5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent                    5027939d3de2dd508c74ae6007b9dc5bb524e3a8
commits                        5
tracked files                  214
remotes                        0
tags                           0
tracked modified paths         77
untracked paths                62
total status paths             139
staged paths                   0
tracked diff                   77 files, 2,666 insertions, 293 deletions
binary tracked-diff Git hash    A91233E096CCD37C6F5947426F3334942092918C
```

Closed raw NUL-delimited inventory hashes:

```text
git status --porcelain=v1 -z -uall | git hash-object --stdin
  56498ED203F6BB573E8360F0674600F68A730D8A

git diff --name-only -z | git hash-object --stdin
  57CEC769BC44C5C8FD704E44FA77DD1BC6C8B6B4

git ls-files -o --exclude-standard -z | git hash-object --stdin
  C0877C45F6CC8274EAD51D95AFDB4B29D6ACE3F8
```

Do not sort, normalize, or replace the raw command input before hashing.

# 3. LOCKED ARTIFACT IDENTITIES

Verify:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0U_codex_directive.md` | 35,487 | `24E16C9398B8A63E8FAE3EB7FA2CBC52E63E299C3238DD215749688D23E01103` |
| `docs/task reports/Phase_0U.md` | 17,574 | `92493225CCC7BF8F3F0942857D422E202515B837951DD9861F48C60052445005` |
| `composer.lock` | 179,468 | `197D310AB802AF3992ED0566E710AABD551A6A06BA5188D3E5D1C360C36374E9` |
| `packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php` | 20,521 | `9FEDF8A98BE9D86C4F35BD1078DD88624384E09CCD79B04E00C8A7CFF9633D48` |
| `packages/dependency-injection/src/Exception/ExceptionInterface.php` | 791 | `CD7EA50F4EEB1D2A6DF3B072B0CCADD950DD9943570D38B20D478A3545FB5395` |
| `packages/dependency-injection/src/Exception/DependencyInjectionException.php` | 1,688 | `15A8FE05EA901201F37E97CA0981797A9E8DD14DF1F8F54A309BAF0B6257680B` |
| `packages/dependency-injection/src/Definition/ServiceDefinition.php` | 14,811 | `4E489A44E3DAB804E173CE76CC21714AE8BADD57FD75DE9CDC93EC6CC5315340` |
| `packages/dependency-injection/src/DependencyResolver.php` | 9,713 | `1F34F7F8662A0C9B2A8D2316AEF1AA82958F2CC1CDC30733A6611389A4B28AE3` |
| `packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php` | 46,742 | `DBDCFF3060E27ED7B46D320773FAE46C73388F9D3B4B3D8BEB586661894CF204` |
| `docs/adr/0033-dependency-injection-diagnostic-context-integration.md` | 7,329 | `EB4193588859712125E0CC80A9720831EBC5F9C25E7E4A380FE327376426BDB5` |

The Phase 0U report must be strict UTF-8 without BOM, LF-only, one-final-LF,
untracked, unstaged, absent from HEAD, and contain all 18 headings once in order.

# 4. CALLER SETUP

The caller copies this directive to:

```text
docs/dev_plan/kongono_phase_0U_1_codex_directive_correction.md
```

and creates:

```text
docs/task reports/Phase_0U_1_correction.md
```

The copied directive must be byte-identical to the architect artifact. The new
report must initially be zero bytes with SHA-256
`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`.

After caller setup require exactly:

```text
tracked modified paths    77
untracked paths           64
total status paths        141
staged paths              0
```

The only additions to Section 2 are the two caller records. Do not populate the
new correction report until every non-report check passes.

# 5. EXACT AUTHORIZED EDIT

Modify exactly:

```text
docs/task reports/Phase_0U.md
```

Replace this exact whole paragraph, including its final LF:

```text
Existing identifiers, titles, remediations, messages, zero
codes, causes, validation and rejection priorities, deterministic graph
selection, resolver behavior, public API shapes, and native failure boundaries
remain unchanged. No universal diagnostic boundary or presentation/runtime
capability was added.
```

with exactly:

```text
Existing identifiers, titles, remediations, messages, zero
codes, causes, validation and rejection priorities, deterministic graph
selection, resolver behavior, `ServiceDefinition` and resolver public API shapes,
and native failure boundaries remain unchanged. The Public DI exception marker
intentionally gains only `diagnosticContext()`, the authorized pre-alpha
signature addition. No universal diagnostic boundary or presentation/runtime
capability was added.
```

Before editing, the old paragraph must occur exactly once and the replacement
zero times. After editing, the inverse must hold. No other byte may change.

Corrected report identity must be exactly:

```text
bytes    17,731
SHA-256  25D9877099DE9230241A4EBD6E42C909468A12EB8AF40C9E9DCC362AEC092CB2
```

It retains all 18 headings, all other prose, strict UTF-8 without BOM, LF-only
endings, exactly one final LF, untracked/unstaged state, and absence from HEAD.

# 6. PROHIBITED ACTIONS

Do not modify production source, tests, fixtures, ADRs, guides, READMEs,
manifests, lock, configuration, directives, historical reports, or any byte of
the Phase 0U report outside the exact paragraph.

Do not rerun Composer update, autoload generation, PHPUnit, PHPStan, PHP CS
Fixer, `composer quality`, network audit, or implementation validation. Do not
reimplement, refactor, format, clean caches, stage, commit, reset, amend, switch
branches, create refs, add remotes, tag, push, or alter history.

The independently verified Phase 0U result remains accepted:

```text
204 tests
3,377 assertions
zero PHPStan errors
0 of 113 files fixable
zero security advisories
```

# 7. REQUIRED READ-ONLY VALIDATION

Prove:

- baseline and caller setup counts/inventories exact;
- every locked identity exact before the edit;
- old/new paragraph occurrence counts exact before and after;
- reverse replacement reconstructs the original 17,574-byte report and SHA;
- every byte outside the one paragraph span is unchanged;
- corrected report identity, headings, encoding, line endings, and Git class;
- production, tests, ADR 0033, manifests, lock, configs, prior records, refs,
  index, modes, streams, and tracked diff remain byte/state identical;
- `git diff --check` remains clean;
- final state is 77 tracked modified, 64 untracked, 141 total, zero staged.

This validation is read-only except for the one report paragraph and later
population of the correction report.

# 8. CORRECTION REPORT

After every non-report check passes, populate only
`docs/task reports/Phase_0U_1_correction.md` with the standard 16 sections from
`AGENTS.md`. State clearly:

- Phase 0U implementation is substantively accepted;
- exactly one Phase 0U Summary paragraph was corrected;
- `ServiceDefinition` and resolver public shapes remain unchanged;
- the Public DI marker intentionally gained only `diagnosticContext()`;
- no implementation, documentation, dependency, lock, or quality result changed;
- no Composer/PHP quality command was rerun;
- the accepted 204-test/3,377-assertion result was preserved;
- exact corrected report identity and final Git state.

The final NEXT STEP line must be exactly:

```text
Await architect review before Phase 0V.
```

Return the correction report byte count and SHA-256 outside it. Do not stage,
commit, or begin Phase 0V.

# 9. ACCEPTANCE CHECKLIST

- [ ] Baseline, hashes, caller setup, refs, index, and inventories exact.
- [ ] Only the exact Phase 0U report paragraph changed.
- [ ] Corrected Phase 0U report is 17,731 bytes with the exact SHA-256.
- [ ] Reverse reconstruction proves all other report bytes unchanged.
- [ ] Production, tests, docs, manifests, lock, configs, and prior records exact.
- [ ] No implementation or quality command was rerun.
- [ ] Tracked diff/hash and all Git history/ref facts remain unchanged.
- [ ] Final state is 77 modified, 64 untracked, 141 total, zero staged.
- [ ] Correction report is populated only after success and remains untracked.

Every item must pass. This correction is report-only.
