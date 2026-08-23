# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.3

## Exact Authority-Path Correction for Phase 4D.2

## 1. Status and purpose

This is the architect-authenticated correction to the Phase 4D.2 directive. Phase 4D.2 correctly identified the public Chie value-boundary defect, but Section 2 named five architecture/ADR authorities using titles that do not exist in the accepted repository. The similarly purposed files reported by the executor are the intended authorities.

This correction changes only those five required authority paths, authenticates their current identities, adjusts caller-record counts and exclusions for the Phase 4D.3 records, and authorizes resumption at Phase 4D.2 Gate A. Every production rule, eight-file mutation boundary, test requirement, validation gate, prohibition, stop rule, acceptance criterion, and completion-report requirement in Phase 4D.2 remains controlling.

The executor is an executor, not an architect. Do not infer any further substitution. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope.

Do not begin Phase 4E.

## 2. Independent authentication

The authoritative path for this correction is:

`docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md`

The active report path is:

`docs/task_reports/phase_4/Phase_4D_3_correction.md`

The architect will supply this directive's byte count and SHA-256 outside the file. Do not self-authenticate from locally observed values. If the independent identity is absent or differs, stop before reading further authorities, quality, or mutation.

Before any non-report mutation, the active Phase 4D.3 report must be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Locked Phase 4D caller records

Authenticate these earlier caller records exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md` | 21229 | `EBAA4F85E60F205DC943B6A78186E1F3B6B49044392C56FB49136036066844E0` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |
| `docs/task_reports/phase_4/Phase_4D_2_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier reports and directives are immutable historical caller records. Do not populate Phase 4D or Phase 4D.2 reports. Populate only the Phase 4D.3 report, and only after every non-report gate passes.

## 4. Exact authority-path replacements

In Phase 4D.2 Section 2, replace only the following five nonexistent path requirements with the exact existing paths shown here:

| Phase 4D.2 nonexistent path | Exact intended authority path |
|---|---|
| `docs/architecture/chie.md` | `docs/architecture/read-only-chie.md` |
| `docs/architecture/knowledge-artifact.md` | `docs/architecture/framework-knowledge-artifact.md` |
| `docs/adr/0061-chie-framework-intelligence-boundary.md` | `docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md` |
| `docs/adr/0062-knowledge-artifact-build-and-validation.md` | `docs/adr/0062-deterministic-framework-knowledge-artifact.md` |
| `docs/adr/0063-chie-v1-read-only-explain-and-diagnose-surface.md` | `docs/adr/0063-read-only-chie-explanation-and-diagnosis.md` |

The exact accepted identities of the five replacement authorities are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/architecture/read-only-chie.md` | 7415 | `64C2E25AFEEE4C7AA82B0BF4D9C9EC9497233958BA17EA8D1F229815F203A9CF` |
| `docs/architecture/framework-knowledge-artifact.md` | 7747 | `68BC8275BE2647990613D377E527EE828086964E647AEEA9CDE3BD45DD88AE1D` |
| `docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md` | 16932 | `61981330F0164752CFBBE53EEBFDA9578BD141829F13111AAF36197A09B87EE5` |
| `docs/adr/0062-deterministic-framework-knowledge-artifact.md` | 4018 | `811134FF0164E85C11242879884DD9E1D2FDF023560052EC6C070B3253CAF2A9` |
| `docs/adr/0063-read-only-chie-explanation-and-diagnosis.md` | 4914 | `161D36CF08EA7E1DE2043987E874E7F4D144BA9382262A6366C4A7EF9D89C267` |

Read all five files completely after their identities match. These are replacements, not additions: do not seek, create, rename, copy, or restore the five nonexistent names. Do not use number-based or subject-based substitution for any other authority path.

## 5. Corrected entry state

After the caller places the Phase 4D.3 directive and zero-byte report, the exact expected entry state is:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 91 untracked paths;
- 117 total status paths;
- zero staged paths;
- 720 Git-visible paths;
- 629 tracked files;
- 16 package directories and 16 package manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 PHP files in the canonical style scope;
- 64 ADR files;
- 10 files in `docs/dev_plan/phase_4`;
- 10 files in `docs/task_reports/phase_4`.

No ref, branch, tag, remote, index, history, Git configuration, dependency, Composer, artifact, or lockfile mutation is authorized.

## 6. Corrected manifest caller exclusions

For every Phase 4D.3 manifest reconstruction, exclude exactly these eight caller paths:

1. `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md`
2. `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md`
3. `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md`
4. `docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md`
5. `docs/task_reports/phase_4/Phase_4D.md`
6. `docs/task_reports/phase_4/Phase_4D_1_correction.md`
7. `docs/task_reports/phase_4/Phase_4D_2_correction.md`
8. `docs/task_reports/phase_4/Phase_4D_3_correction.md`

Use the exact manifest construction algorithm from Phase 4D.2: ordinal case-sensitive path ordering, UTF-8 without BOM, LF between records, and no terminal LF. Each record is the repository-relative forward-slash path, a tab, decimal file byte length, a tab, and uppercase SHA-256.

Because the two new caller paths are excluded, the accepted content manifests remain exactly:

| Set | Records | Manifest bytes | SHA-256 | Aggregate file bytes |
|---|---:|---:|---|---:|
| Complete Git-visible content excluding eight callers | 712 | 89258 | `DA2029AB2803A225941943FB56DEB10004B2137A82D44A8298355755554E5381` | 7681304 |
| Protected content under the Phase 4D rules | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical caller archive excluding eight callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Stop before quality or mutation if any count, byte total, hash, endpoint, mode, ordinary-file status, alternate-stream state, or caller identity differs. Do not repair or adopt a local mismatch.

## 7. Resumption authority

After Sections 2 through 6 pass:

1. Treat Phase 4D.2 Gate A's missing-authority-path failure as corrected only by the five replacements in Section 4.
2. Resume Phase 4D.2 at the remaining Gate A authority reads and preservation checks.
3. Then execute Phase 4D.2 Gates B through D exactly as written.
4. Modify exactly the eight files authenticated in Phase 4D.2 Section 5 and no others.
5. Preserve the exact public-boundary invariants, adversarial proofs, focused test count, canonical test count, Knowledge artifact identities, prohibited scope, and stop rules in Phase 4D.2.
6. Use the Phase 4D.3 report as the only active completion report.

This correction does not authorize changing the five authority files. Their identities must remain exact through final validation.

## 8. Report correction

Where Phase 4D.2 names `docs/task_reports/phase_4/Phase_4D_2_correction.md` as the active report, substitute:

`docs/task_reports/phase_4/Phase_4D_3_correction.md`

Populate it only after all Phase 4D.2 non-report gates pass under this corrected authority set. Use the same standard 16 sections and evidence requirements from Phase 4D.2, plus:

- this directive's independently supplied identity;
- identities and complete-read confirmation for all five corrected authority files;
- the corrected 26/91/117/0 entry state and 720/629 visible/tracked counts;
- all eight caller-record identities;
- explicit confirmation that the nonexistent paths were not created;
- explicit confirmation that no authority file was modified.

The final line before the report identity appendix must remain exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

This historical wording intentionally names the substantive Phase 4D.2 value-boundary correction. Do not rewrite it to Phase 4D.3.

## 9. Acceptance criteria

Phase 4D.3 is complete only when all are true:

1. This directive matches the independently supplied identity.
2. The Phase 4D.3 report authenticates as the required zero-byte placeholder before work.
3. All six earlier caller records match exactly.
4. Corrected entry counts and eight-path caller exclusions match.
5. All three entry manifests match exactly.
6. The five replacement authorities match their locked identities and are read completely.
7. None of the five nonexistent paths is created or substituted elsewhere.
8. Phase 4D.2 Gate A completes under the corrected authority set.
9. Every Phase 4D.2 Gate B through D criterion passes.
10. Exactly the eight Phase 4D.2-authorized files are modified and none is created.
11. All five replacement authority files remain byte-identical.
12. Earlier reports and directives remain immutable.
13. Only the Phase 4D.3 report is populated after non-report success.
14. The completion report contains all required correction and substantive evidence.
15. Nothing is staged or committed and Phase 4E is not begun.

## 10. Stop rule

On the first mismatch or failed gate:

- STOP;
- REPORT EXPECTED AND OBSERVED VALUES;
- PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS;
- DO NOT GUESS OR EXPAND SCOPE;
- do not populate any report;
- do not begin Phase 4E.

