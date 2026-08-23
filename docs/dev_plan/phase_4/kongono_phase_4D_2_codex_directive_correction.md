# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.2

## Public Chie Value-Boundary Validation and Provenance Closure

## 1. Authority and objective

This is the architect-authenticated correction for Phase 4D. It supersedes only the Phase 4D.1 completion claim and authorizes a narrow correction in eight existing Chie files.

Phase 4D.1 is **not accepted as closed**. Its implementation passes the existing quality suite, but independent review proved that public Experimental constructors and factories can create and serialize states that violate the Phase 4D provenance, disclosure, and bounded-output contracts. The Chie service design, package boundary, artifact design, CLI command, documentation, dependency graph, and all other Phase 4D work remain accepted pending this correction.

The executor is an executor, not an architect. Apply only the rules and file boundary stated here. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope.

Do not begin Phase 4E.

## 2. Authenticated authorities and caller records

Read completely before mutation:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/documentation-standard.md`
4. `docs/engineering/api-status.md`
5. `docs/engineering/error-handling.md`
6. `docs/architecture/chie.md`
7. `docs/architecture/knowledge-artifact.md`
8. `docs/adr/0061-chie-framework-intelligence-boundary.md`
9. `docs/adr/0062-knowledge-artifact-build-and-validation.md`
10. `docs/adr/0063-chie-v1-read-only-explain-and-diagnose-surface.md`
11. `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md`
12. `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md`
13. `docs/task_reports/phase_4/Phase_4D.md`
14. `docs/task_reports/phase_4/Phase_4D_1_correction.md`
15. this Phase 4D.2 directive
16. the zero-byte Phase 4D.2 report placeholder

Locked caller identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |

The Phase 4D.2 report path is:

`docs/task_reports/phase_4/Phase_4D_2_correction.md`

Before any non-report mutation it must be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with the empty-file SHA-256 above. The Phase 4D.2 directive must also be untracked, unstaged, and absent from `HEAD`.

The architect will supply the Phase 4D.2 directive byte count and SHA-256 outside this file. Do not derive or accept those values from this file itself. If the independently supplied identity is absent or differs, stop before quality or mutation.

## 3. Accepted entry state

After the caller places the two Phase 4D.2 caller records, the expected entry state is:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 89 untracked paths;
- 115 total status paths;
- zero staged paths;
- 718 Git-visible paths;
- 629 tracked files;
- 16 package directories and 16 package manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 PHP files in the canonical style scope;
- 64 ADR files;
- 9 files in `docs/dev_plan/phase_4`;
- 9 files in `docs/task_reports/phase_4`.

No ref, branch, tag, remote, index, history, Git configuration, dependency, or lockfile mutation is authorized.

When reconstructing manifests, exclude exactly these six caller paths:

1. `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md`
2. `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md`
3. `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md`
4. `docs/task_reports/phase_4/Phase_4D.md`
5. `docs/task_reports/phase_4/Phase_4D_1_correction.md`
6. `docs/task_reports/phase_4/Phase_4D_2_correction.md`

Use ordinal case-sensitive path ordering, UTF-8 without BOM, LF between records, and no terminal LF. Each record is the repository-relative forward-slash path, one tab, decimal file byte length, one tab, and uppercase SHA-256.

The accepted entry manifests are:

| Set | Records | Manifest bytes | SHA-256 | Aggregate file bytes |
|---|---:|---:|---|---:|
| Complete Git-visible content excluding six callers | 712 | 89258 | `DA2029AB2803A225941943FB56DEB10004B2137A82D44A8298355755554E5381` | 7681304 |
| Protected content under the Phase 4D rules | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical caller archive excluding six callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

If counts, endpoints, byte totals, hashes, file modes, alternate-stream state, ordinary-file state, or caller identities do not match, stop before mutation. Do not repair or adopt a local mismatch.

## 4. Defect proven by architect review

The existing public Experimental value boundaries permit all of the following:

- a citation reference that is not a canonical `kgn-source-v1` reference;
- an absolute private filesystem path;
- an authority class that is outside the accepted current-source boundary;
- a malformed digest and impossible line range;
- an accepted framework-fact claim with no citation;
- arbitrary observation or uncertainty text;
- answered and unsupported responses with malformed artifact/source-set digests;
- an arbitrary unsupported-reason token;
- a citation whose embedded source-set digest does not match its response;
- arbitrary diagnostic context violation values and invalid exception/diagnostic pairings;
- arbitrary error-envelope diagnostic and uncertainty pairings.

Those invalid values serialize through the public response surface. This violates exact provenance, safe disclosure, closed state modeling, and deterministic machine-readable output even though the existing tests pass.

The correction must fail closed at construction/factory boundaries and must never echo rejected values in exception messages, exception strings, diagnostic context, or serialized error output.

## 5. Exact authorized file boundary

Modify exactly these eight existing files and no others:

| Path | Starting bytes | Starting SHA-256 |
|---|---:|---|
| `packages/chie/src/ChieCitation.php` | 1558 | `9C1558C276CAEE109A0B98F08CFA4907DA1500DF18D0936C5AB97C3495E33999` |
| `packages/chie/src/ChieClaim.php` | 1843 | `80B26B61B0A9FE6B97349EBF1EA579445CA257818BDF95CF373379F78CC680ED` |
| `packages/chie/src/ChieResponse.php` | 3402 | `A2F059246302D7D75042FAD526849F9E7B36F2A590B286EE5293CF3F9E269C70` |
| `packages/chie/src/Diagnostic/ChieDiagnostic.php` | 5808 | `F3D5D346EB63A17F45CE7A9188A4F3F98499E4F15BC3627179C42451342CAC21` |
| `packages/chie/src/Exception/ChieException.php` | 1473 | `0B2FD4054939C84C19A59E54B24C8D9CAD4910EEB38FC0AC42CCBCD1785CA9B9` |
| `packages/chie/src/Internal/CanonicalJson.php` | 1483 | `75A7CD15853EEF2F4D9C0633D628D3775236C603A8FD8C18EC60061CAC2FD17E` |
| `packages/chie/tests/Unit/ChieResponseTest.php` | 4117 | `ABD3334244052CEFCCBD7172F45EB09A1275A984DC07C58B7F6F9CF3945F68FF` |
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 3989 | `C3866D7ABE09C8061C80BC4776700C92DD97CD654F9D466AEDFB3C7BA71A2821` |

No new production or test file is authorized. The only other mutable file is the active Phase 4D.2 report, and only after every non-report gate passes.

## 6. Required production corrections

### 6.1 `ChieCitation`

Reject any construction unless all conditions hold:

- `reference` exactly matches `^kgn-source-v1:[a-f0-9]{64}:[0-9]{4}$`;
- `path` is 1 through 240 ASCII bytes and matches `^(?:[A-Za-z0-9._-]+)(?:/[A-Za-z0-9._-]+)*$`;
- no path segment is `.` or `..`;
- paths containing backslashes, colons, absolute prefixes, URLs, stream wrappers, control bytes, or non-ASCII bytes are rejected;
- `sha256` exactly matches lowercase `[a-f0-9]{64}`;
- `lineStart >= 1` and `lineEnd >= lineStart`;
- authority is exactly one of `GoverningAuthority`, `ImplementationEvidence`, or `CurrentExplanatoryDocumentation`.

The source-set digest embedded in `reference` is validated for syntax here and for equality with the owning response in Section 6.3.

Throw a fixed, non-disclosing `InvalidArgumentException` for invalid direct construction. Do not include any rejected value.

### 6.2 `ChieClaim`

For every accepted claim text:

- valid UTF-8;
- 1 through 32768 bytes;
- no BOM, carriage return, or NUL.

Enforce the following exact closed matrix:

| Claim class | Text | Citations |
|---|---|---:|
| `AcceptedFrameworkFact` | caller-provided bounded text | exactly 1 |
| `CurrentWorkspaceObservation` | exactly `The caller supplied one Safe diagnostic identifier for read-only diagnosis.` | 0 |
| `UnresolvedUncertainty` | exactly `The requested information is unresolved in the exact validated framework state.` | 0 |

Reject `Inference`, `Recommendation`, `Generated`, or any other non-accepted state. Preserve the existing public signature and serialized shape. Throw a fixed, non-disclosing `InvalidArgumentException` for invalid direct construction.

### 6.3 `ChieResponse`

For `answered(...)`:

- artifact digest and source-set digest each exactly match lowercase `[a-f0-9]{64}`;
- claims contain 1 through 9 entries;
- uncertainty is absent;
- every claim is a valid `ChieClaim`;
- every citation reference has an embedded source-set digest exactly equal to the response source-set digest.

For `unsupported(...)`:

- both digests are valid as above;
- claims are empty;
- uncertainty is the fixed token selected only by operation:
  - `Explain` -> `topic-unsupported`;
  - `Diagnose` -> `diagnostic-not-found`.

Do not accept caller-selected arbitrary uncertainty tokens. If the existing signature includes such an argument, validate that it equals the required token; do not change the public signature in this correction.

For artifact-failure construction, enforce this exact mapping and no other:

| Status | Uncertainty | Digests | Claims |
|---|---|---|---:|
| unavailable | `artifact-unavailable` | null | 0 |
| stale | `artifact-stale` | null | 0 |
| blocked/invalid | `artifact-invalid` | null | 0 |

Preserve existing enums and factory signatures. Throw fixed, non-disclosing `InvalidArgumentException` for invalid direct/factory construction.

### 6.4 `ChieDiagnostic`

The public diagnostic-context factory must accept only a closed, semantic violation token valid for the selected diagnostic. It must reject unknown tokens and mismatched diagnostic/token pairs without echoing the token.

The complete accepted violation-token vocabulary is:

`operation`, `topic-grammar`, `diagnostic-grammar`, `claim-class`, `claim-text`, `claim-citation`, `failure-status`, `invalid-command-usage`, `artifact-path`, `artifact-read`, `framework-root`, `source-unavailable`, `source-state-mismatch`, `artifact-shape`, `artifact-name`, `artifact-metadata`, `topic-catalog`, `topic-unsupported`, `diagnostic-not-found`, `source-missing`, `authority`, `citation`, `heading-missing`, `heading-duplicate`, `section-limit`, `artifact-limit`, `citation-limit`, `response-limit`, `line-limit`, `occurrence-limit`, `unexpected-failure`.

Map each token only to the diagnostic case whose existing meaning and remediation it describes. Do not broaden disclosure classification or add schema fields.

### 6.5 `ChieException`

Enforce these allowed concrete exception-to-diagnostic relationships:

- artifact-unavailable exception -> artifact-unavailable diagnostic;
- artifact-stale exception -> artifact-stale diagnostic;
- invalid-request exception -> invalid-request or response-limit-exceeded diagnostic;
- invalid-artifact exception -> invalid-artifact, invalid-request, citation-resolution-failed, source-section-invalid, or response-limit-exceeded diagnostic.

Reject any mismatch with a fixed, non-disclosing `InvalidArgumentException`. Preserve all existing public signatures, inheritance, diagnostic interfaces, and safe stringification behavior.

### 6.6 `CanonicalJson`

The error encoder accepts only these exact diagnostic/uncertainty pairs:

- invalid-request diagnostic + `invalid-command-usage`;
- unexpected-command-failure diagnostic + `unexpected-failure`.

Reject every other pair with a fixed, non-disclosing `InvalidArgumentException`. Preserve canonical key order, JSON flags, output schema, trailing-newline behavior, and successful response encoding.

## 7. Required test correction

Modify only the two authorized existing test files. Keep their current six test methods each; add assertions/data loops inside existing methods rather than adding test methods. This keeps the focused suite at exactly 36 tests and the complete suite at exactly 633 tests.

Tests must prove:

- every invalid citation component above is rejected;
- rejected absolute/private paths and raw values do not occur in exception messages or `(string) $exception`;
- accepted framework facts require exactly one valid citation;
- observation and uncertainty claims require their exact fixed text and zero citations;
- rejected claim classes and over-bound text fail closed;
- malformed response digests, claim counts, arbitrary uncertainty tokens, and cross-source-set citations fail closed;
- artifact-failure mappings are exact;
- diagnostic context rejects unknown and mismatched tokens without disclosure;
- exception/diagnostic relationships are exact;
- error-envelope pairs are exact;
- every currently valid service/CLI response retains its existing canonical serialized bytes.

Do not weaken, delete, skip, suppress, baseline, or exclude an existing test or analysis rule.

## 8. Validation sequence

### Gate A — authentication and preservation

Authenticate the directive from independently supplied values; authenticate all four earlier caller records, the active zero-byte report, eight starting files, exact Git state, and three manifests. Read all required authorities completely. Stop on any mismatch.

### Gate B — pre-change quality

Run literal `composer quality`. Expected accepted evidence is zero advisories, platform success, 0 of 332 files fixable, zero PHPStan errors, and exactly 633 tests with 14690 assertions.

If and only if the locked audit is blocked solely by managed network/cache access, one identical approved-access retry is permitted. No other retry, configuration change, memory-limit change, or policy weakening is authorized.

### Gate C — bounded implementation

Modify exactly the eight files. Run syntax checks for the six production files, focused PHPStan and style checks for `packages/chie`, and focused PHPUnit for `packages/chie/tests`. Expected focused test count remains exactly 36; assertion count may increase.

Run a direct adversarial probe equivalent to the architect proof. It must demonstrate that malformed citation, claim, response, diagnostic-context, exception-pairing, and error-envelope construction each throws a fixed non-disclosing exception and serializes no attacker-controlled value.

Run valid CLI regressions for answered explain, answered diagnose, unsupported explain, unsupported diagnose, unavailable artifact, stale artifact, invalid artifact, invalid command use, and unexpected command failure. Canonical JSON/state semantics must remain unchanged.

### Gate D — artifact preservation and final quality

The Knowledge artifact is outside this correction boundary and must remain:

- artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
- exactly 31 sources.

Run literal `composer quality` again under the same retry rule. It must pass with zero advisories, 0 of 332 style findings, zero PHPStan errors, and exactly 633 tests. The assertion count must be reported and may exceed 14690.

Reconstruct final manifests. The protected and historical manifests must remain byte-identical to Section 3. The complete manifest may change only through the eight authorized files; report its records, bytes, SHA-256, and aggregate bytes.

Confirm `composer.json`, every package manifest, `composer.lock`, Knowledge artifact, all documentation, all ADRs, all earlier reports/directives, production files outside the six authorized Chie source files, test files outside the two authorized Chie tests, index, refs, history, and Git configuration remain byte-identical.

## 9. Prohibited scope

Do not:

- redesign Chie, Knowledge, Kernel, diagnostics, CLI, or the artifact;
- change API status, package dependencies, Composer files, autoloading, tooling configuration, or PHP constraints;
- add source/test files, diagnostics, context fields, response keys, claim classes, authority classes, commands, topics, or capabilities;
- implement AI/model/network/tool execution, generation, autofix, mutation, training, embeddings, retrieval, search, Hantei, Oshie, agents, telemetry, persistence, routing, DI, ORM, templates, frontend, or later-phase work;
- modify documentation or ADRs;
- stage, commit, tag, create/delete refs, alter history, clean, restore, reset, stash, or repair unrelated state;
- populate an earlier report or rewrite historical evidence;
- place rejected values into error messages, traces created by explicit chaining, diagnostic context, JSON, or reports.

## 10. Stop rules

Stop immediately on the first failed authentication, preservation, syntax, focused analysis, focused style, focused test, direct semantic probe, artifact identity, canonical quality, inventory, or file-boundary gate.

On stop:

- do not retry unless this directive expressly permits the one network/cache retry;
- do not continue to later gates;
- do not stage or commit;
- do not populate the Phase 4D.2 report;
- report expected and observed values;
- preserve the current worktree, index, history, and caller records;
- ask for the smallest architect decision required.

## 11. Completion report

Populate `docs/task_reports/phase_4/Phase_4D_2_correction.md` only after every non-report gate passes. Use the standard 16 sections:

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

It must include:

- independently supplied directive identity;
- all caller and starting-file identities;
- entry and final status counts;
- entry, protected, historical, and final complete manifest evidence;
- exact files changed and confirmation that no file was created;
- each boundary rule implemented;
- focused and canonical command results;
- direct adversarial and valid-regression results;
- artifact identity and source count;
- confirmation of zero staging/commit/history/ref/config/dependency/documentation changes;
- the report's final byte count and uppercase SHA-256, calculated after its content is final.

The final line before the report identity appendix must be exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 12. Acceptance criteria

Phase 4D.2 is complete only when all are true:

1. Independent directive authentication passes.
2. Every required authority is read completely.
3. All caller records and the active report authenticate.
4. Entry Git state and all three manifests match exactly.
5. All eight starting files match their locked identities.
6. Pre-change canonical quality passes with 633 tests and 14690 assertions.
7. Exactly eight authorized files are modified and none created.
8. Citation syntax, path, digest, line, and authority invariants are enforced.
9. Claim text, class, citation-count, and fixed-text invariants are enforced.
10. Response digest, count, source-set, uncertainty, and failure mappings are enforced.
11. Diagnostic context, exception pairing, and error-envelope pairings are closed.
12. Rejected values never appear in exception or serialized output.
13. Existing valid response and CLI semantics remain byte-stable.
14. Syntax, focused PHPStan, focused style, and 36 focused tests pass.
15. Knowledge artifact/source-set identities and 31-source count remain exact.
16. Final canonical quality passes with exactly 633 tests.
17. Protected and historical manifests remain exact.
18. Final complete-manifest delta is confined to the eight files.
19. No prohibited file, dependency, documentation, Git, or history mutation occurs.
20. Earlier reports remain immutable and the active report was populated only after success.
21. The completion report contains all required evidence and exact final sentence.
22. Phase 4E was not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
