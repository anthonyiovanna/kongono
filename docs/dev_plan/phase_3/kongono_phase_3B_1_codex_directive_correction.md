# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3B.1

## Current-State Documentation and Completion-Report Reconciliation

**Directive date:** 2026-08-21

**Authority:** Architect-issued correction to Phase 3B only

**Execution posture:** Executor, not architect

**Phase 3C authority:** Not granted

---

## 1. Mission

Correct the accepted Phase 3B documentation boundary and its completion report
without changing the selected PHP CGI/FastCGI architecture.

Phase 3B's architectural artifacts, package reservation, limits, security
policy, dependency direction, roadmap table, and selected-not-implemented
status are otherwise accepted. The correction is required because:

1. seven current documents retain pre-Phase-2 statements that say no outer
   adapter, delivery, response, or activation enforcement exists, even though
   Phase 2G implemented the neutral Web Runtime adapter and delivery port;
2. `docs/glossary.md` says production host integration remains **unselected**,
   even though Phase 3B selected the PHP `cgi-fcgi` FastCGI `RESPONDER`
   architecture;
3. the Phase 3B completion report transcribes six locked caller-record SHA-256
   values incorrectly while marking them PASS; and
4. the report describes `kongono/php-cgi` as depending inward on Web
   Application, while the selected direct package edge is to Web Runtime and
   Web Application is reached only transitively.

Make only the bounded prose and report corrections in this directive. Do not
implement Phase 3C or any runtime behavior.

---

## 2. Mandatory stop rules

Stop, preserve state, and report expected and observed values if any locked
identity, path inventory, status count, or authority differs.

Do not:

- guess a missing value;
- self-authenticate this directive from its local copy;
- restore, truncate, rename, delete, stage, commit, amend, reset, clean, or
  otherwise repair caller-managed state;
- edit an unlisted path;
- change the Phase 3B architectural decision; or
- begin Phase 3C.

---

## 3. Independent directive authentication

Before broad validation or mutation, compare this file with the byte count and
SHA-256 supplied independently by the architect outside the file.

Required path after caller copy:

```text
docs/dev_plan/kongono_phase_3B_1_codex_directive_correction.md
```

If either value is missing or differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

The local artifact is not self-authenticating.

---

## 4. Required authorities

After authentication and before mutation, read completely:

- `AGENTS.md`;
- `ARCHITECTURE.md`;
- `README.md`;
- `SECURITY.md`;
- `docs/doctrine.md`;
- `docs/documentation-standard.md`;
- `docs/architecture/http-message-model.md`;
- `docs/architecture/overview.md`;
- `docs/glossary.md`;
- `docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md`;
- `docs/adr/0051-phase-3-production-http-integration-milestone.md`;
- `docs/adr/0052-php-cgi-fastcgi-responder-profile.md`;
- `docs/architecture/phase-3-roadmap.md`;
- `docs/architecture/php-cgi-fastcgi-profile.md`;
- `packages/web-runtime/src/WebRuntimeAdapter.php`;
- `packages/web-runtime/src/Contract/WebDeliveryInterface.php`;
- `docs/dev_plan/kongono_phase_3B_codex_directive.md`;
- `docs/task reports/Phase_3B.md`;
- this correction directive; and
- the zero-byte `docs/task reports/Phase_3B_1_correction.md` placeholder.

Do not substitute similarly named or similarly numbered files.

---

## 5. Locked starting state

### 5.1 Git identity and status

Require:

```text
branch:          main
HEAD:            647053d4bddc4c76090bca9bf3d693faa5880c52
HEAD tree:       3ad60da1dc535fc0b7a166ac8522f954d4c67fac
HEAD parent:     d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:    10
tracked files:   536
staged paths:    0
```

Before caller copy, the accepted Phase 3B state is exactly:

```text
modified tracked paths: 16
untracked paths:         9
total status paths:      25
staged paths:            0
```

After caller copies this directive and creates the correction-report
placeholder, require exactly:

```text
modified tracked paths: 16
untracked paths:         11
total status paths:      27
staged paths:            0
Git-visible paths:       547
tracked files:           536
```

The eleven untracked paths must be exactly:

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/dev_plan/kongono_phase_3A_codex_directive.md
docs/dev_plan/kongono_phase_3B_codex_directive.md
docs/dev_plan/kongono_phase_3B_1_codex_directive_correction.md
docs/task reports/Phase_2H_final_closure.md
docs/task reports/Phase_3A.md
docs/task reports/Phase_3B.md
docs/task reports/Phase_3B_1_correction.md
```

The sixteen modified tracked paths must remain exactly the accepted Phase 3B
inventory:

```text
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-handler/README.md
packages/http-server/README.md
packages/kernel/README.md
```

### 5.2 Locked artifact identities

Require these exact starting identities:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_3B_codex_directive.md` | 37,311 | `6D7F597C2EC64CE78DF02E55F893ED084072B914D84E6256F94FC16A57793D43` |
| `docs/task reports/Phase_3B.md` | 17,551 | `672727E4E85DF4B8AA0FBACFE03A8C20255DBD41214583CD1637E5FA68224643` |
| `docs/adr/0052-php-cgi-fastcgi-responder-profile.md` | 13,139 | `DCD53CC972FDE94AFE668254C43CF6A531F6FE6A178EC42D83917E84961E17A5` |
| `docs/architecture/php-cgi-fastcgi-profile.md` | 21,647 | `9872CB45D6F582F748521580FF4DDBCF416D0F951747ECC62F3862B8DB1FB5A5` |
| `docs/architecture/phase-3-roadmap.md` | 12,692 | `8DEC5D69A1DB6E3701B4E85776EE4CC0C95AEEDF9BDF0C2D27C1CBEFD4827AA4` |
| `README.md` | 15,009 | `790B0F741734285EFD6729DE47F614D4582AFC2F26D220A09BCDC1A76CADF33A` |
| `ARCHITECTURE.md` | 21,467 | `47B3334265BD350C147EF1DE24394339754C9D3C7FED5492744EC671890EF9D9` |
| `SECURITY.md` | 22,790 | `28CA649C865CEAEB0E8D13AB5C3C2782F1739BFC63908E1E22C07969D5519E3C` |
| `docs/architecture/http-message-model.md` | 14,227 | `EC819D67C0EA825AF28F5D50277F31C217A80531B5102E4DB767BFED89DFDE70` |
| `docs/architecture/overview.md` | 17,531 | `B7FF1830CABE86B2BD5507CC841DB10DF25AB6651596DF44D691C01D6580D4C3` |
| `docs/doctrine.md` | 19,798 | `02E149B5024E206C3FD103C59B89C2A5E8056563658767AA5E14E56933630169` |
| `docs/glossary.md` | 34,199 | `98C133823EDA14C27F07CAA9D1F7D352DA14E65944211B6686718DACC6214622` |
| `composer.json` | 4,299 | `99A8B23827C0C5C691EE68A19A9ACD20543ABAF2AE4B7C97DF0FCA0A9B6EC76C` |
| `composer.lock` | 196,324 | `28B03AFDC8FF9C6F2F754E65B97C867E499BAD2D4427E0F1881FBBA88E1E6862` |

Require the correction-report placeholder to be an ordinary untracked,
unstaged, zero-byte file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not populate it until all non-report gates pass.

### 5.3 Accepted package and quality baseline

Require:

```text
package directories:       13
package manifests:         13
production PHP files:      100
test PHP files:            130
style-covered PHP files:   230
tracked files:             536
PHP memory_limit:          128M
PHP_INI_SCAN_DIR:          absent
```

No package, production PHP, test PHP, manifest, Composer lockfile, tooling,
autoload, quality-policy, or security-policy path may change.

---

## 6. Exact authorized mutation boundary

### 6.1 Existing current documents

Edit only:

```text
README.md
ARCHITECTURE.md
SECURITY.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/doctrine.md
docs/glossary.md
```

### 6.2 Existing active completion report

Edit only:

```text
docs/task reports/Phase_3B.md
```

### 6.3 Caller-owned correction report

Populate only after every non-report gate passes:

```text
docs/task reports/Phase_3B_1_correction.md
```

### 6.4 Prohibited scope

Everything else is read-only. In particular, do not edit:

- ADR 0051 or ADR 0052;
- either Phase 3 architecture profile;
- the A-H roadmap boundary table;
- any package README or other current document;
- either directive;
- any earlier report;
- `composer.json` or `composer.lock`;
- production code, tests, fixtures, manifests, tooling, or configuration; or
- Git index, history, refs, branches, tags, remotes, hooks, or settings.

No file is created by the executor. The correction directive and zero-byte
report placeholder are caller records.

---

## 7. Locked current-state model

All corrected prose must preserve these distinctions.

### 7.1 Implemented now

- Phase 2G implemented `Kongono\Web\Runtime\WebRuntimeAdapter` and the Public
  `Kongono\Web\Runtime\Contract\WebDeliveryInterface` port.
- Web Runtime receives an immutable bootstrap permission at construction.
- For each delivery, Web Runtime obtains the adapter-controlled permission from
  the delivery port when bootstrap permission permits evaluation.
- Web Runtime owns the neutral per-delivery sequence: request acquisition, one
  Web Application call, fixed generic or eligible trusted response selection,
  one send attempt, and one close attempt.
- Denied, failed, or uncertain permission evaluation fails closed to the fixed
  generic response.
- Web Application retains the sole Kernel operation-scope ownership.
- Phase 2H proves this existing path with root-owned fixture code only.

### 7.2 Selected but not implemented

- Phase 3B selected PHP SAPI `cgi-fcgi`, CGI/1.1, and FastCGI role
  `RESPONDER` as the first production HTTP host profile.
- The future outer leaf is `packages/php-cgi`, Composer package
  `kongono/php-cgi`, namespace `Kongono\PhpCgi\`.
- That future leaf depends directly inward on HTTP Message and Web Runtime,
  together with the already selected metadata/diagnostic contracts recorded by
  ADR 0052. It does **not** depend directly on Web Application, HTTP Server,
  Routing, DI, Application Graph, or Kernel.
- Web Application is reached transitively through Web Runtime composition; do
  not describe it as a direct `kongono/php-cgi` dependency.
- Phase 3C will implement inbound CGI-to-PSR-7 conversion only.
- Phase 3D will implement bounded response staging and CGI emission only.
- Phase 3E will implement exact operator-bootstrap syntax and production trust
  composition only.

### 7.3 Still absent

- no production PHP CGI package, manifest, namespace, or runtime symbol;
- no production SAPI/global converter, emitter, listener, process manager,
  deployment recipe, or supported application template;
- no operator configuration reader or production evidence evaluator;
- no claim of Stable API, 1.0, full framework, production readiness, or
  production-hardening certification.

### 7.4 Historical versus current wording

It is valid to say that a named Phase 1 package itself owns no adapter,
transport, delivery, or response. It is not valid to present the entire current
repository as having no adapter, delivery, response, combined gate enforcement,
or selected production host profile.

When retaining chronological Phase 1 prose, qualify repository-wide absence
claims as historical at the end of that phase and immediately reconcile the
current Phase 2G/2H state. Do not rewrite history as though Phase 2G existed in
Phase 1.

---

## 8. Required documentation corrections

### 8.1 `README.md`

Reconcile the Phase 1A-1H chronology so repository-wide claims such as “no
adapter,” “future outer adapter,” “delivery remains unimplemented,” and “no
response exists” are explicitly historical at those phase boundaries.

Add or revise the current-state reconciliation to state that Phase 2G now owns
the neutral Web Runtime adapter/delivery sequence in Section 7.1, Phase 2H proves
it through test-owned infrastructure, and only production host conversion,
emission, operator configuration/evidence evaluation, and deployment remain
unimplemented. Preserve package-local statements about what Diagnostic
Activation and Diagnostic Handler do not own.

### 8.2 `ARCHITECTURE.md`

Replace the stale conceptual composition that begins with “future outer adapter
establishes both typed permissions” with the current implemented composition:

```text
production host adapter remains selected but unimplemented
    -> WebDeliveryInterface supplies request, delivery permission, send, close
        -> WebRuntimeAdapter owns fail-closed response selection and delivery
            -> WebApplication owns one Kernel operation scope
            -> generic failure or eligible trusted presenter output
```

Preserve the distinction that the future production host will construct and
supply the concrete delivery boundary, while Web Runtime already performs the
neutral adapter sequence.

Reconcile the later statements that currently say no adapter, delivery,
response, combined enforcement, or production model exists. The truthful current
boundary is: neutral adapter/delivery/generic/trusted sequencing exists; raw
operator configuration/evidence evaluation and production SAPI hosting do not.

### 8.3 `SECURITY.md`

Reconcile the Phase 1 security chronology that describes a future outer adapter
and says no adapter, response, delivery, or enforcement exists. State that Web
Runtime now:

- holds the bootstrap permission;
- requests current-delivery permission from the delivery port only when allowed;
- fails permission-evaluation uncertainty closed;
- chooses the fixed generic or eligible trusted response;
- attempts send and close; and
- retires on uncertainty according to its implemented contract.

Keep explicit that Web Runtime does not authenticate raw evidence and that the
future production PHP CGI leaf must own validated host inputs and exact
operator-profile composition. Do not weaken any denial or disclosure rule.

### 8.4 `docs/architecture/http-message-model.md`

Replace “One future outer adapter owns each inbound delivery” with current
ownership: Web Runtime owns the neutral per-delivery coordination and cleanup
contract through `WebDeliveryInterface`; the selected but unimplemented PHP CGI
leaf will own SAPI/global conversion and concrete delivery/emission for the
production profile.

Do not change the HTTP Message model or imply that message objects establish
trust.

### 8.5 `docs/architecture/overview.md`

Reconcile the Phase 1H paragraph ending with a repository-wide claim that no
adapter, delivery, response, or production model exists. Preserve the exact
Diagnostic Handler package-local limitation, then state the current Phase 2G
Web Runtime boundary and remaining Phase 3 host gap.

### 8.6 `docs/doctrine.md`

Reconcile repository-wide claims that evaluation, combined enforcement,
adapters, activation enforcement, and transport all remain future. Preserve the
doctrine that typed enum cases alone are not authority. State that Web Runtime
now performs the implemented two-gate fail-closed response-selection sequence
from explicitly supplied decisions, while raw operator configuration, host
evidence evaluation, and production SAPI transport remain unimplemented.

### 8.7 `docs/glossary.md`

Correct all affected entries consistently:

- “production host integration remains unselected” becomes selected by Phase 3B
  but unimplemented;
- “no activation enforcement exists” is narrowed to the absence of raw operator
  configuration/evidence evaluation and production host integration; Web Runtime
  already enforces the supplied two-gate eligibility for response selection;
- “future outer adapter” is reconciled with implemented Web Runtime and the
  future production `kongono/php-cgi` concrete delivery boundary; and
- existing PHP CGI profile/package and Web Runtime entries remain consistent.

Do not broaden the meaning of trust, permission, authorization, or production
readiness.

---

## 9. Required Phase 3B report corrections

Edit only the inaccurate statements; preserve the exact 23-heading structure,
chronology, commands actually run, validation evidence, and final sentence.

### 9.1 Caller-record hashes

In Section 4, replace the six incorrect values with these exact locked values:

| Path | Correct SHA-256 |
| --- | --- |
| `docs/dev_plan/kongono_phase_3A_codex_directive.md` | `FD7CB6F72B8C9DF2FD41174C137EDA876A139118B8299870AD9B65D58A8682EA` |
| `docs/task reports/Phase_2H_final_closure.md` | `F187072B7B69327BB2129152AFF1114B36F6D2611A4E986CB06F95955341C245` |
| `docs/task reports/Phase_3A.md` | `28B462AE7066F8E4C783901FC13879A47925B62B707979E53E84B4AA0F4FDB88` |
| `docs/adr/0051-phase-3-production-http-integration-milestone.md` | `B199B14CF306B5869ADB1AA2A3617FA06D0B22DEF16744F496705FA3732CDD2F` |
| `docs/architecture/phase-3-roadmap.md` at entry | `14DF511EED770DB321D1AE243C6A0388E4271064B06E82669AC9A3405B32A3BD` |
| `composer.json` | `99A8B23827C0C5C691EE68A19A9ACD20543ABAF2AE4B7C97DF0FCA0A9B6EC76C` |

The existing `composer.lock` hash is already correct and must remain unchanged.

### 9.2 Package dependency wording

In Section 10, replace the claim that the future package depends inward on the
HTTP Message and Web Application boundaries. State instead that it is an outer
leaf with direct inward dependencies on HTTP Message and Web Runtime plus the
metadata/diagnostic contracts authorized by ADR 0052. Web Application is
transitive through Web Runtime and is not a direct package edge.

### 9.3 Correction disclosure and acceptance claims

Update Sections 6, 12, 16, 17, 20, and 22 only as needed so the report candidly
records:

- the seven current documents corrected by Phase 3B.1;
- the original Phase 3B review found six hash transcription errors, one package
  dependency wording error, and stale current-state prose;
- Phase 3B required correction before acceptance;
- the corrected documents now distinguish implemented Web Runtime from the
  selected-but-unimplemented PHP CGI host; and
- all acceptance criteria are claimed PASS only after the correction gates pass.

Do not claim the original report was already exact.

Recalculate and return the corrected Phase 3B report identity outside that
report body.

---

## 10. Validation gates

### Gate A - authentication and preservation

1. Authenticate this directive from independently supplied values.
2. Read all authorities completely.
3. Verify Section 5 Git state, caller records, file identities, ordinary-file
   state, modes, streams, zero-byte correction report, package counts, and PHP
   environment.
4. Verify zero staged paths and unchanged history.

Stop on any mismatch.

### Gate B - bounded correction

1. Snapshot byte counts and SHA-256 values for all eight authorized existing
   mutation paths.
2. Apply only Sections 8 and 9.
3. Use `apply_patch` for edits.
4. Confirm no other path changed relative to the authenticated entry snapshot.

### Gate C - focused documentation proof

Prove across the seven corrected current documents:

- `WebRuntimeAdapter` and `WebDeliveryInterface` are described as implemented;
- the neutral adapter owns request acquisition, response selection, send, and
  close, without owning Kernel scope;
- no repository-wide current claim says no adapter, delivery, response, or
  activation enforcement exists;
- package-local “owns no adapter/transport/delivery” statements remain properly
  scoped;
- PHP CGI/FastCGI is selected but unimplemented;
- `kongono/php-cgi` depends directly on Web Runtime, not Web Application;
- Phase 3C is the only next implementation phase; and
- no Phase 3C runtime behavior is claimed implemented.

Search at minimum for:

```text
future outer adapter
no adapter
no activation enforcement
still-unselected
production host integration remains unselected
combined enforcement
delivery remains unimplemented
no response
```

Every remaining match must be reviewed in context and either be explicitly
historical, package-local, or unrelated. Report the path, line, and reason for
every retained match.

Also verify:

- the six corrected caller hashes occur exactly once in the Phase 3B report;
- the six incorrect hashes are absent;
- the direct Web Runtime / transitive Web Application dependency wording is
  exact;
- all local Markdown links resolve;
- strict UTF-8 without BOM, LF-only, exactly one final LF, no trailing
  whitespace, no NUL, ordinary files, and default streams only; and
- `git diff --check` passes.

### Gate D - preservation and canonical quality

Verify:

- ADR 0052, the PHP CGI profile, the Phase 3 roadmap A-H table,
  `composer.json`, `composer.lock`, all PHP, tests, fixtures, manifests, tooling,
  and configuration are byte-identical to entry;
- package counts remain exact;
- status remains `16/11/27/0` before correction-report population;
- history, refs, branches, tags, remotes, index, and modes are unchanged; and
- normal PHP remains `128M` with `PHP_INI_SCAN_DIR` absent.

Run literal:

```text
composer quality
```

Expected:

```text
locked audit:          zero advisories
platform requirements: pass
PHP CS Fixer:          0 of 230 files fixable
PHPStan:               zero errors
PHPUnit:               480 tests, 8,575 assertions
```

If the unchanged audit is blocked only by managed network/cache access, one
identical approved-access retry is authorized. No other retry, memory-limit
overlay, policy weakening, suppression, baseline, or configuration change is
authorized.

### Gate E - correction report

Only after Gates A-D pass, populate:

```text
docs/task reports/Phase_3B_1_correction.md
```

Then verify both the corrected Phase 3B report and the Phase 3B.1 correction
report for exact heading contracts, file quality, untracked/unstaged/
absent-from-HEAD state, byte count, and SHA-256.

Final status remains exactly:

```text
modified tracked paths: 16
untracked paths:         11
total status paths:      27
staged paths:            0
Git-visible paths:       547
tracked files:           536
```

---

## 11. Phase 3B.1 completion report contract

Use exactly these headings:

```text
# PHASE 3B.1 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. CORRECTIONS APPLIED
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. VALIDATION RESULTS
## 14. DOCUMENTATION REVIEW
## 15. SECURITY AND FILE-SAFETY REVIEW
## 16. WARNINGS / KNOWN ISSUES
## 17. QUESTIONS / BLOCKERS
## 18. FINAL GIT STATUS
## 19. ACCEPTANCE CRITERIA
## 20. NEXT STEP
```

`FILES CREATED` must say none by the executor and classify the directive/report
placeholder as caller records.

Report:

- exact expected and observed directive identity;
- exact entry/final Git identities and inventories;
- starting and final identities of all eight edited files;
- the six corrected hashes;
- every retained focused-search match and why it is valid;
- exact quality totals;
- the corrected Phase 3B report byte count and SHA-256; and
- the Phase 3B.1 report byte count and SHA-256 separately outside its body.

The exact final sentence is:

```text
Await architect review before Phase 3C.
```

---

## 12. Acceptance criteria

Phase 3B.1 is Complete only when all are true:

1. independent directive identity matches;
2. exact caller records, artifact identities, and `16/11/27/0` entry state
   match;
3. all authorities are read completely;
4. only the seven named current documents and active Phase 3B report are edited;
5. the six caller-record hashes in the Phase 3B report are exact;
6. the future package direct dependency is Web Runtime, not Web Application;
7. all current documents distinguish implemented Web Runtime from the future
   PHP CGI host;
8. stale repository-wide claims are removed or explicitly historical;
9. package-local limitations remain accurate;
10. Phase 3B remains selected-not-implemented and Phase 3C remains next;
11. ADR 0052, architecture profiles, A-H roadmap table, PHP, tests, manifests,
    tooling, dependencies, and lockfile remain byte-identical;
12. links, encoding, line endings, whitespace, stream, mode, and ordinary-file
    checks pass;
13. canonical quality passes at 480 tests / 8,575 assertions;
14. normal PHP remains `128M` with no INI overlay;
15. history and index remain unchanged with zero staged paths;
16. final state is exactly `16/11/27/0`, 547 visible, 536 tracked; and
17. both reports satisfy their contracts and independent digest handoffs.

Any failed criterion means status cannot be Complete.

---

## 13. Final instruction

Correct only Phase 3B documentation and reporting. Do not begin Phase 3C, do
not alter architecture, and do not touch production or dependency state.

When complete, return the Phase 3B.1 correction report plus its byte count and
SHA-256, then await architect review.
