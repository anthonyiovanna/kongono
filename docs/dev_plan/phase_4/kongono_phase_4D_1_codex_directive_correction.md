# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 4D.1

## Diagnostics Authority-Path Correction and Phase 4D Resume

---

## 1. Purpose and exact scope

Phase 4D stopped correctly at Gate A because five Diagnostics authority paths
named in Section 3 of the authenticated Phase 4D directive do not exist. The
names expressed the intended concepts but not the repository's actual package
layout.

This correction:

1. authenticates the preserved stopped state;
2. replaces only those five nonexistent authority references with the five
   exact existing production paths;
3. names two direct supporting Diagnostics value types that must also be read;
4. adjusts active-caller exclusions and status counts for this correction
   pair;
5. makes this correction report the active completion report; and
6. resumes the unchanged Phase 4D implementation at required-authority reading.

It changes no Phase 4D architecture, API, dependency, content path, test,
diagnostic, command, security, acceptance, or prohibited-scope contract.

Do not begin Phase 4E.

---

## 2. Independent authentication

The architect must provide the expected byte count and SHA-256 of this
correction outside the file. Do not authenticate it from its local bytes.

Exact correction path:

```text
C:\Users\antho\Dev\kongono\docs\dev_plan\phase_4\kongono_phase_4D_1_codex_directive_correction.md
```

Exact active report path:

```text
C:\Users\antho\Dev\kongono\docs\task_reports\phase_4\Phase_4D_1_correction.md
```

Before any broad validation or mutation:

- require the independently supplied identity to match the correction file;
- read this correction completely;
- require the active report to be ordinary, untracked, unstaged, absent from
  `HEAD`, zero bytes, and SHA-256
  `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`;
- require the original Phase 4D directive and report identities below; and
- stop on any mismatch without guessing, editing, retrying implementation, or
  populating either report.

---

## 3. Preserved original Phase 4D caller records

Require:

| Path | Bytes | SHA-256 | Required state |
|---|---:|---|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` | ordinary, untracked, unstaged, absent from `HEAD`, immutable |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` | ordinary, untracked, unstaged, absent from `HEAD`, immutable zero-byte historical stop record |

All twelve earlier Phase 4 caller identities in original Section 4 must still
match. All 230 earlier historical records remain immutable.

The original directive remains the implementation authority except where this
correction explicitly replaces or overrides text.

---

## 4. Corrected entry state

After this authenticated correction and its zero-byte report are present,
require:

```text
branch                   main
HEAD                     6aacafc92f07b567818173d660e2e182dfa83d23
HEAD tree                d7fda17d27a15954cdd213a1c323aaeb37a2b574
HEAD parent              647053d4bddc4c76090bca9bf3d693faa5880c52
commit count             11
tracked modifications    26
untracked paths          53
total status paths       79
staged paths              0
Git-visible paths       682
tracked files           629
package directories      15
package manifests        15
production PHP files    144
test PHP files          157
style-covered PHP       302
ADR Markdown files       63
phase_4 dev-plan files    8
phase_4 task reports      8
```

Also require the original Phase 4D evidence already reached by the stopped
execution:

- all twelve earlier caller identities matched;
- all 26 starting identities matched;
- repository identity, history, index, refs, lockfile, and counts matched;
- the complete, protected, and historical manifests matched;
- no implementation file was created or modified;
- `composer quality` was not run;
- no stage, commit, ref, history, configuration, or permanent-environment
  mutation occurred; and
- Phase 4E did not begin.

Reconfirm these facts read-only. Do not demand that the stopped executor repeat
already completed file reading solely to manufacture duplicate evidence.

---

## 5. Exact authority-path replacement

In original Phase 4D Section 3, treat only these five references as replaced:

| Nonexistent reference | Exact authoritative existing path | Bytes | SHA-256 |
|---|---|---:|---|
| `packages/diagnostics/src/Diagnostic.php` | `packages/diagnostics/src/Diagnostic/DiagnosticsDiagnostic.php` | 3425 | `B8401E4E3D1CD504CAFE3F5194D6ABEB2F6A2194B176FFB9A70385B4833835A7` |
| `packages/diagnostics/src/DiagnosticContext.php` | `packages/diagnostics/src/Context/DiagnosticContext.php` | 4671 | `79DEF086619FF9A4BC8163F7839A2C89CD6B195AD385E70681F9FF625539DC05` |
| `packages/diagnostics/src/DiagnosticContextField.php` | `packages/diagnostics/src/Context/DiagnosticField.php` | 3833 | `E6D9871719CBFBA9352760FD616EE91E12BD3E8DC10B7CE6E28E78E1AFC97822` |
| `packages/diagnostics/src/DiagnosticContextSchema.php` | `packages/diagnostics/src/Context/DiagnosticContextSchema.php` | 3529 | `4AE2DB2218E056AC2A6DE51671639B976333CCBA2F0D441C9D2FA8B5D6A6A08B` |
| `packages/diagnostics/src/DisclosureClassification.php` | `packages/diagnostics/src/Context/DiagnosticDisclosure.php` | 505 | `1411BC531D8D311F894F622BF0DE868C1CA7B3017A509BC0957D038E1BACEE86` |

Read those five files completely.

The actual context schema directly depends on these two existing value types.
Read them completely as supporting authorities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/diagnostics/src/Context/DiagnosticFieldSchema.php` | 2257 | `95E93678C5049449FCC20596E52F721CBF46B6B3FD5D9D093376197831CCAB9B` |
| `packages/diagnostics/src/Context/DiagnosticValueKind.php` | 362 | `999CF55E5808338BA1E8BEA9AC8C8046D70B873F9452C65B66D724796C4D0D8C` |

No similarly numbered, named, or conceptually related path may be substituted.
The seven corrected/supporting paths are read-only and remain outside the
Phase 4D content boundary.

Every other original required-authority path remains exact and mandatory.

---

## 6. What this correction does not change

The following original Phase 4D contracts remain exact:

- ADR 0063 decision and read-only Chie boundary;
- `kongono/chie` ownership and dependency direction;
- 23 production types and their API statuses;
- six exact topic records;
- request, diagnostic-observation, current-artifact, explanation, citation,
  response, limit, artifact-reader, command, diagnostic, and exception
  contracts;
- 34 created and 26 modified content paths;
- six test files and 36 focused tests;
- 633 complete tests;
- Composer update, lock boundary, tooling, documentation, and autoload work;
- correction authority for defects inside the exact content boundary;
- all prohibited scope and security rules;
- no staging or commit; and
- all 42 original acceptance criteria, subject only to the caller/count/report
  overrides in this correction.

The correction authorizes no production edit before original Gate C and no
edit to `packages/diagnostics` at any point.

---

## 7. Caller-family exclusions and manifest interpretation

For every original Phase 4D complete, protected, and historical preservation
manifest, exclude all four active Phase 4D-family caller paths:

```text
docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md
docs/task_reports/phase_4/Phase_4D.md
docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4D_1_correction.md
```

With those four caller exclusions:

- original Section 6.2 remains exactly 678 records, 85,391 manifest bytes,
  SHA-256
  `22926EDD96685D2DA09A59E51B46319E12DE79F60F643CA3E530671637647E81`,
  and 7,572,326 aggregate file bytes;
- original Section 6.3 remains exactly 652 records, 82,872 manifest bytes,
  SHA-256
  `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56`,
  and 6,865,953 aggregate file bytes; and
- original Section 6.4 remains exactly 230 records, 28,846 manifest bytes,
  SHA-256
  `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB`,
  and 4,768,984 aggregate file bytes.

Construction, ordinal ordering, separators, record shape, UTF-8 encoding, and
no-terminal-LF requirements remain unchanged.

Do not adopt a new manifest hash merely because the correction caller pair is
present. Exclude it exactly as authorized.

---

## 8. Resume point and validation authority

After Sections 2-7 pass:

1. resume original Section 3 required-authority reading using the exact
   replacements in Section 5 above;
2. run original Gate B unchanged pre-change quality;
3. implement original Phase 4D exactly;
4. use the original bounded correction authority to fix implementation, test,
   documentation, formatting, typing, and read-only checker defects within the
   exact content boundary;
5. run all original focused, command, canonical, preservation, and security
   gates; and
6. populate only this correction report after every non-report gate passes.

If the unchanged Composer audit or exact Composer update encounters the
environmental network/cache condition described in the original directive,
the original approved-access retry authority remains available.

Do not populate `Phase_4D.md`. It remains the immutable zero-byte record of the
stopped original execution.

---

## 9. Corrected final state

Before populating the active correction report, require:

```text
tracked modifications    26
untracked paths          87
total status paths      113
staged paths              0
Git-visible paths       716
tracked files           629
package directories      16
package manifests        16
production PHP files    167
test PHP files          163
style-covered PHP       332
ADR Markdown files       64
phase_4 dev-plan files    8
phase_4 task reports      8
```

Require exactly 34 Phase 4D created content paths and 26 authorized modified
paths. The only count differences from original Phase 4D Section 23 Gate F are
the two additional untracked correction caller records.

Reproduce all three manifests using Section 7. Require the original report to
remain zero-byte. Only after all other gates pass may
`Phase_4D_1_correction.md` be populated.

After report population, status path counts remain unchanged; only the active
correction report byte identity changes.

---

## 10. Correction completion report

Use:

```text
# PHASE 4D.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. FILES CREATED
## 4. FILES MODIFIED
## 5. ARCHITECTURAL DECISIONS
## 6. DEVIATIONS FROM DIRECTIVE
## 7. DEPENDENCIES ADDED/REMOVED
## 8. ENVIRONMENT
## 9. COMMANDS RUN
## 10. VALIDATION RESULTS
## 11. DOCUMENTATION REVIEW
## 12. WARNINGS / KNOWN ISSUES
## 13. QUESTIONS / BLOCKERS
## 14. GIT DIFF SUMMARY
## 15. ACCEPTANCE CRITERIA
## 16. NEXT STEP
```

The report must distinguish:

- the original five missing conceptual paths;
- the five exact replacements and two supporting authorities;
- completed original Gate A evidence;
- correction authentication and entry evidence;
- resumed Phase 4D implementation evidence;
- all original focused and canonical gates;
- four-caller manifest exclusions;
- original zero-byte report preservation;
- final correction report bytes and SHA-256; and
- every original and corrected acceptance criterion.

Its final sentence must be exactly:

```text
Await architect review before Phase 4E.
```

---

## 11. Corrected acceptance criteria

Phase 4D.1 is Complete only when:

1. this correction and its report authenticated exactly;
2. original Phase 4D directive and zero-byte report remained exact;
3. all earlier caller records remained exact;
4. corrected entry state matched;
5. the five nonexistent references were replaced only by the five exact
   existing paths in Section 5;
6. the two direct supporting value types were read completely;
7. no Diagnostics package file was modified;
8. all other original authorities were read completely;
9. all three original manifests reproduced with the four exact caller-family
   exclusions;
10. original pre-change quality passed unchanged;
11. every original Phase 4D architecture, implementation, security,
    disclosure, dependency, test, documentation, and validation requirement
    passed;
12. all 42 original acceptance criteria passed under the explicit
    caller/count/report overrides here;
13. final corrected counts matched Section 9;
14. exactly the original 60 content paths formed the implementation boundary;
15. original `Phase_4D.md` remained zero-byte;
16. this report remained zero-byte until all non-report gates passed;
17. this report records its final bytes and SHA-256;
18. no path outside the original content boundary or active correction report
    changed under Phase 4D.1;
19. no stage, commit, ref, branch, remote, tag, history, Git configuration, or
    permanent-environment mutation occurred;
20. the final sentence is exact; and
21. Phase 4E did not begin.

On a genuine authority, protected-state, dependency, architecture, or
out-of-boundary conflict: stop, preserve state, report expected and observed
evidence, and ask one narrow question. Do not guess or expand scope.

