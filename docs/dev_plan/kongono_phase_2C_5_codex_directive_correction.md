# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.5

## HTTP-message test-proof closure and report correction

**Status:** Executor-ready correction directive
**Date:** 2026-08-16
**Repository:** `C:\Users\antho\Dev\kongono`
**Authority:** Architect-issued test-only correction after Phase 2C.4 review

---

## 1. Purpose

Phase 2C.4 successfully corrected all six known production defects. Independent
review reproduced those corrected behaviors and independently passed canonical
quality with zero advisories, zero fixable files, zero PHPStan errors, and 326
tests with 5588 assertions.

Phase 2C.4 is nevertheless **not yet accepted as the final Phase 2C evidence**.
Its completion report overstates closure of the original Phase 2C test matrix.
The remaining defects are in proof coverage, not in demonstrated production
behavior:

1. diagnostic titles, remediation, schema operations, and violated invariants
   are checked only for non-emptiness instead of exact literal values;
2. the raw-disclosure test asserts absence of a value that was never supplied
   to the exception or failing operation;
3. forbidden header controls are sampled rather than exhaustively covering
   every prohibited byte;
4. native stream-operation failure mapping is not directly exercised, and the
   claimed read-filter failure returns an ordinary empty string in this runtime;
5. every PSR copy-on-change mutation on Response and ServerRequest is not
   explicitly proved distinct and original-preserving; and
6. the upload movement test uses more than one chunk but does not observe and
   prove that every requested source read is at most 8192 bytes.

Close only these proof gaps in existing tests. Production source is frozen.
If any new test exposes a production defect, stop and report it; do not repair
production under this directive.

---

## 2. Authentication and authority

### 2.1 Independent directive identity

The caller must supply this directive's byte count and SHA-256 outside this
file. Compare those independently supplied values before quality, test edits,
or any other mutation. The local file may not self-authenticate.

If either value is absent or differs:

```text
STOP
ASK
DO NOT GUESS
DO NOT MUTATE
```

### 2.2 Read completely

Read completely before mutation:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_2C_codex_directive.md`, especially Sections
   8, 9, 10, 11, and 12;
3. `docs/dev_plan/kongono_phase_2C_3_codex_directive_correction.md`, especially
   Sections 5, 6, 7, 8, and 9;
4. `docs/dev_plan/kongono_phase_2C_4_codex_directive_correction.md`;
5. `docs/task reports/Phase_2C_4_correction.md`;
6. every test file authorized in Section 5; and
7. this Phase 2C.5 directive.

Read the four corrected production files and the diagnostic enum read-only to
derive the exact accepted behavior. Do not edit them.

Earlier authority remains in force except where this directive replaces the
report target and narrows the mutable boundary to tests only.

---

## 3. Caller records and resume state

### 3.1 Caller setup

The caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_2C_5_codex_directive_correction.md
```

and create this ordinary zero-byte report placeholder:

```text
docs/task reports/Phase_2C_5_correction.md
```

The report must begin untracked, unstaged, absent from `HEAD`, zero bytes, and
have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until all non-report gates pass. Never modify this directive.

### 3.2 Preserved Phase 2C.4 records

The Phase 2C.4 directive remains:

```text
Bytes: 9428
SHA-256: A301FE034FD045F8C5F17806FE391DC275A902F824EB2F4716E7AFB4D778F431
```

The Phase 2C.4 report is immutable historical evidence:

```text
Path: docs/task reports/Phase_2C_4_correction.md
Bytes: 15451
SHA-256: 9C091DCDE18D2C9BF5E49A3998ACEA74FD6C981FB62FC3B2CAC0D38141CD7485
```

The Phase 2C.3 report remains the immutable zero-byte stopped report. Do not
edit, truncate, normalize, stage, commit, or reuse any earlier directive or
report.

### 3.3 Repository state

Before the caller adds the two Phase 2C.5 records, require:

```text
Branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
Tree: bfa1908c619069b699bc9b08668db17ea2555eb3
Parent: 8fe793b124384744ac43887037360154c149241a
Commit count: 9
Tracked modifications: 18
Untracked paths: 59
Total status paths: 77
Staged paths: 0
```

After caller setup, require:

```text
Tracked modifications: 18
Untracked paths: 61
Total status paths: 79
Staged paths: 0
```

Exactly one local branch, zero remotes, zero tags, and zero unexpected refs
must exist. `refs/codex/turn-diffs/**` remain informational and exempt from the
ordinary-ref count but may not be mutated.

Verify the current production, manifests, lockfile, tooling, documentation,
ADRs, history, caller records, modes, streams, and protected dirty state match
the accepted Phase 2C.4 state. Any unexpected mismatch is a stop condition.

---

## 4. Locked production and baseline quality

No production file may change. Verify these accepted corrected identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/src/Factory/StreamFactory.php` | 3071 | `32908C5AB4F7C691899C6D5C34DB4DC4310099DB9496C3833ABD3D74B9A28B23` |
| `packages/http-message/src/Internal/HeaderCollection.php` | 3674 | `7440CCF9245978E128F82FDB511A9C487A3D4B389B22C1D268F86F00057FACF2` |
| `packages/http-message/src/Message/ServerRequest.php` | 8198 | `74B60C96918275414442BCDC6F338718E399F18EB5A9FF06DFE4C0C9D14D9B18` |
| `packages/http-message/src/Message/Uri.php` | 10022 | `7887F7CE68EB5ABA98D3C4538F8E2A8E9D10F2DA1CE973039C8EA0C61E14EB63` |

The lockfile remains exactly:

```text
Bytes: 187578
SHA-256: 264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F
```

After authentication and state verification, run pre-change:

```powershell
composer quality
```

Require zero advisories, zero of 168 files fixable, zero PHPStan errors, and
326 tests with 5588 assertions. If the audit alone is blocked by managed
network/cache restrictions, retry the identical command once with approved
outside-sandbox access. Do not weaken or skip a stage.

---

## 5. Closed mutable boundary

Only these seven existing test files may change:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php` | 5435 | `B542CC49BE2F148204793D4FD0F9430E78172D35F904F59B52B9DD2C2754745F` |
| `packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php` | 5087 | `822E0143CB6EC0CD62BF6CB62BFCD3D0F02F2722F5D342C74E150ADE2B93E876` |
| `packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php` | 5805 | `B20FA1ADA96A9CE74429973FDE9C1D72E9311DFB0EAB5F28CCA36EFD59347AC0` |
| `packages/http-message/tests/Unit/Message/ResponseTest.php` | 4246 | `5CECBBABECA298F09D06CC15F4D376B807E081459280FC0014764C711F7A3E6B` |
| `packages/http-message/tests/Unit/Message/ServerRequestTest.php` | 11235 | `4576D88BF74FB9F196CBDDB7058A3275224FDA453B718F2EAF7C783F1E33D328` |
| `packages/http-message/tests/Unit/Message/StreamTest.php` | 7941 | `51C9A8D52A60A76E784CC02E492A142BE7EA2403D5DF0D5FAFA28A950CDA2277` |
| `packages/http-message/tests/Unit/Message/UploadedFileTest.php` | 10246 | `B52D7BA5E74BFDA19603B2B815745BF4501C2644C5584289D0752AAA8054B850` |

After all non-report gates pass, populate only:

```text
docs/task reports/Phase_2C_5_correction.md
```

Authorization is a ceiling, not a quota. Do not touch a listed test unless its
change proves a required gap. No file may be created or deleted during
execution; both Phase 2C.5 caller records already exist before execution.

Every other path is read-only. In particular, no production, fixture, Composer,
lock, tooling, architecture-test, documentation, ADR, prior record, Git, mode,
or stream change is authorized.

---

## 6. Exact test-proof requirements

### 6.1 Exact diagnostic contract table

In `HttpMessageDiagnosticTest`, replace non-empty-only proof with one literal
architectural expectation table covering all 28 cases. For every case, assert
exactly:

- case name and identifier;
- title;
- remediation;
- schema operation;
- violated invariant;
- ordered field keys;
- field labels;
- field kinds;
- `Safe` disclosure;
- required status; and
- absence of any extra schema field.

The literal expectations must match the unchanged accepted enum source. Do not
generate expected values by calling the same accessor being tested, parse the
enum source at runtime, or reduce exact strings to non-empty/pattern checks.

Retain uniqueness and identifier-pattern assertions.

### 6.2 Closed Text vocabularies

Add explicit expected sets for all package-owned Text values used by production
diagnostic contexts:

```text
violation:
empty
whitespace_or_control
nul
carriage_return
line_feed
control
del
grammar
structure
host
port
authority
syntax
non_ascii
ip_literal
reg_name
percent_triplet

operation:
open_temp
write_temp
open_file
tell
seek
write
read
get_contents
open_target
read_source
copy_target
close_target

state:
moved
upload_error

component:
scheme
host
userinfo
path
query
fragment

first_unexpected_key_kind:
int
string
```

Prove through representative real production failures that emitted values are
members of these exact sets. Values documented as `get_debug_type()` output
remain type-derived and must be proved bounded to at most 64 bytes; do not
pretend they are members of a smaller package vocabulary.

Perform a direct read-only call-site audit after tests to confirm no production
literal outside these sets enters those fields. Record that inspection in the
report. A brittle runtime source-text assertion inside PHPUnit is not required.

### 6.3 Real raw-disclosure probes

Replace the tautological raw-disclosure assertion with failures generated from
actual bounded raw values supplied to production APIs. Include representative:

- invalid header name and unsafe header value;
- invalid URI text/component;
- invalid request target;
- missing local stream path;
- invalid upload target;
- upload error carrying client filename and media-type metadata; and
- rejected parsed-body/server-related input where only structural kind is
  permitted.

For each resulting package exception, assert the supplied raw value is absent
from the native message, `__toString()`, diagnostic title, remediation, schema
operation, violated invariant, field labels, field keys, and field values.

Use unmistakable bounded literals, never real credentials, paths, user data, or
secrets. Clean every owned temporary resource.

### 6.4 Every forbidden header byte

Exhaustively iterate byte values `0x00` through `0x1F` except horizontal tab
`0x09`, plus `0x7F`. Embed each byte in a bounded header value and prove:

- `InvalidMessageArgumentException`;
- diagnostic ID `KGN-HTTP-1005`;
- deterministic `value_position`;
- exact violation category (`nul`, `carriage_return`, `line_feed`, `control`,
  or `del`); and
- no raw value on any public diagnostic surface.

Separately retain acceptance and exact byte preservation for horizontal tab and
representative obs-text bytes.

### 6.5 Every copy-on-change mutation

Add table-driven or focused behavioral proof that every PSR `with*()` and
`without*()` operation exposed by `Response` and `ServerRequest` returns a
distinct object even when the requested value equals current state, and leaves
the original observable state unchanged.

For Response cover:

```text
withProtocolVersion
withHeader
withAddedHeader
withoutHeader
withBody
withStatus
```

For ServerRequest cover:

```text
withProtocolVersion
withHeader
withAddedHeader
withoutHeader
withBody
withRequestTarget
withMethod
withUri (both preserveHost values where relevant)
withCookieParams
withQueryParams
withUploadedFiles
withParsedBody
withAttribute
withoutAttribute
```

Use a complete pre-mutation snapshot of the original public state and compare
it after each operation. Preserve the existing shared-stream/object caveat; do
not claim deep immutability.

### 6.6 Native stream-operation failures

Use one or more uniquely named test-local stream-wrapper protocols backed by
anonymous classes to deterministically make native read, write, and seek
operations fail. Before registration prove each protocol is unused; unregister
every protocol in `finally`. Never replace a built-in wrapper.

Prove direct `Stream` calls map failures to
`HttpMessageRuntimeException` / `KGN-HTTP-1018` with exact operations `read`,
`write`, and `seek`. Prove `__toString()` remains non-throwing and returns `''`
on the failing readable/seekable wrapper. Replace or correct the existing filter
case so the test does not label an ordinary empty result as a demonstrated
native failure.

Also assert negative read length uses `InvalidMessageArgumentException`, ID
`KGN-HTTP-1019`, and the exact requested-length context. Retain every existing
capability and post-detach proof.

### 6.7 Observable upload chunk bound

Use a test-local anonymous `StreamInterface` decorator around a bounded source
that records every requested `read($length)`. Move content larger than 8192
bytes and prove:

- at least two source reads occurred;
- every requested length is positive and at most 8192;
- the destination bytes are exact;
- the caller-owned source remains open; and
- the upload enters moved state only after full success.

Do not infer the bound solely from source text or destination size.

### 6.8 No production-driven expectation weakening

Tests must fail if the locked behaviors regress. Do not dynamically derive an
expected title from `title()`, catch an unexpected exception and add an
assertion count, mark anything skipped/incomplete/risky, use broad output
suppression, or add a production-only test branch. Test-local wrapper state must
be deterministic and removed in `finally`.

---

## 7. Validation sequence

After test edits, run in order:

1. `php -l` for every changed test file;
2. focused HTTP-message PHPUnit;
3. root architecture PHPUnit;
4. focused PHPStan for the HTTP package using existing configuration;
5. `composer dump-autoload --optimize --strict-psr`;
6. `composer quality`;
7. direct read-only vocabulary/call-site and changed-boundary inspection; and
8. final Git, inventory, lock, file-safety, caller-record, and report gates.

The unchanged approved-access retry rule applies only if the locked audit is
environment-blocked. Report exact final focused and canonical test/assertion
counts; no vanity minimum substitutes for the required scenarios.

Verify:

- 70 production PHP files, 98 test PHP files, 17 HTTP production files, 13 HTTP
  test files, and 32 total HTTP-package files remain exact;
- all four corrected production hashes in Section 4 remain exact;
- `composer.lock` remains exact;
- only the necessary subset of seven authorized tests changed;
- no file was created/deleted during execution;
- zero staged paths and no Git/ref/history mutation exist; and
- final status remains 18 modified, 61 untracked, 79 total, zero staged.

---

## 8. Acceptance criteria

Phase 2C.5 is complete only if every item passes:

1. independent directive identity;
2. locked caller records and repository state;
3. pre-change canonical quality at 326 tests/5588 assertions;
4. exact 28-row literal diagnostic contract proof;
5. exact closed vocabulary proof and direct call-site audit;
6. real raw values exercised and absent from every diagnostic surface;
7. every prohibited header byte tested with exact classification;
8. all listed Response and ServerRequest mutations distinct and original-
   preserving;
9. direct read/write/seek native failures map to `KGN-HTTP-1018`;
10. negative reads map to exact `KGN-HTTP-1019` context;
11. `__toString()` remains non-throwing under a demonstrated failing wrapper;
12. upload read requests are observably bounded to 8192 bytes;
13. syntax, focused tests, architecture tests, PHPStan, strict autoload, and
    canonical quality all pass;
14. zero advisories, zero fixable files, zero PHPStan errors, and no skipped,
    incomplete, risky, warning, deprecation, notice, or output test result;
15. no production, dependency, manifest, lock, tooling, documentation, ADR,
    architecture test, public API, diagnostic, or schema change;
16. only authorized existing tests and the final Phase 2C.5 report changed;
17. exact inventories, protected state, file safety, caller records, and Git
    state;
18. zero staged paths and no commit/ref/history mutation;
19. truthful report with the exact Section 10 structure; and
20. final report byte count and SHA-256 returned outside the report body.

Any failure activates:

```text
STOP
ASK
DO NOT GUESS
DO NOT MODIFY PRODUCTION
```

Do not populate the report unless every non-report criterion passes.

---

## 9. Prohibited scope

Do not modify production code. Do not implement routing, dispatch, middleware,
handlers, adapters, SAPI parsing, emission, clients, authentication,
authorization, sessions, CORS, CSRF, rate limiting, storage, Kernel/DI/Graph
integration, telemetry, CLI, agents, extensions, ORM, frontend, or any Phase 2D
capability.

Do not add files, fixtures, packages, dependencies, diagnostics, schemas,
configuration, suppressions, baselines, exclusions, or public APIs. Do not edit
prior directives/reports, documentation, ADRs, manifests, lockfile, tooling,
architecture tests, file modes, streams, Git config, refs, index, or history.

Do not stage, commit, restore, reset, clean, stash, branch, tag, push, fetch,
merge, rebase, or begin Phase 2D.

---

## 10. Completion report

After every non-report gate passes, populate
`docs/task reports/Phase_2C_5_correction.md` with exactly:

```text
# PHASE 2C.5 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. PHASE 2C.4 REVIEW FINDINGS
## 6. FILES CREATED
## 7. FILES MODIFIED
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. VALIDATION RESULTS
## 14. TEST-PROOF CLOSURE
## 15. PRODUCTION IMMUTABILITY PROOF
## 16. DOCUMENTATION REVIEW
## 17. SECURITY AND FILE-SAFETY REVIEW
## 18. WARNINGS / KNOWN ISSUES
## 19. QUESTIONS / BLOCKERS
## 20. GIT DIFF SUMMARY
## 21. ACCEPTANCE CRITERIA
## 22. NEXT STEP
```

The report must identify every changed test, reconcile each Section 6 proof,
record the direct vocabulary audit, provide exact focused/canonical counts,
record every immutable production/lock/caller identity, and state any approved
network retry. `FILES CREATED` must say none during execution.

When complete, the exact final next step is:

```text
Await architect review before Phase 2D.
```

Validate strict UTF-8 without BOM, LF-only, no NUL, no trailing whitespace,
exact heading order, and exactly one final LF. Compute and return the final
report byte count and SHA-256 outside its body. Do not edit it afterward.

---

## 11. Required outcome

Phase 2C production behavior remains byte-identical to the independently
verified Phase 2C.4 implementation. The test suite, not production, gains exact
and non-tautological evidence for diagnostic contracts, disclosure safety,
header-byte rejection, immutable mutations, native stream failure mapping, and
bounded upload reads. Phase 2D remains blocked until architect acceptance.
