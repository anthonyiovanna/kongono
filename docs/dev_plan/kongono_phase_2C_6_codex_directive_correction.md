# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.6

## Native-trace disclosure boundary and test-resume correction

**Status:** Executor-ready correction directive
**Date:** 2026-08-16
**Repository:** `C:\Users\antho\Dev\kongono`
**Authority:** Architect-issued correction to Phase 2C.5

---

## 1. Decision and purpose

Phase 2C.5 stopped correctly after its new test proved that PHP can include raw
method arguments in native exception stack traces when
`zend.exception_ignore_args` is disabled. The same run also exposed one test-
authoring error: PHPUnit 13 treats the third `assertContains()` argument as the
failure message, not as a strict-comparison flag.

The architect decision is:

1. **Do not override exception `__toString()`.**
2. **Do not require `zend.exception_ignore_args=On` as a package precondition.**
3. **Do not claim that a PHP `Throwable` object is sanitized.**
4. Kongono guarantees that raw rejected values do not enter package-owned
   exception messages or structured diagnostic surfaces.
5. Native stack traces, trace arguments, file/line data, and native Throwable
   stringification are protected runtime/debug data outside that package-owned
   diagnostic boundary. They must never be emitted to an untrusted or
   production surface.

This is not a weakening of accepted Kongono doctrine. Existing doctrine already
excludes raw exception messages, traces, source, requests, environment, and
paths from production presentation, and the trusted developer projection and
renderer deliberately do not discover traces. PHP's `getTrace()` and
`getTraceAsString()` are final native methods; overriding only `__toString()`
would hide one representation while leaving the underlying trace arguments
available. Such an override would create a misleading security guarantee and
make HTTP exceptions inconsistent with Kongono's native-exception foundation.

Phase 2C.6 therefore corrects the Phase 2C.5 proof boundary, fixes the PHPUnit
assertion call, and resumes the remaining test-only work. Production remains
frozen.

---

## 2. Authentication and authority

### 2.1 Independent identity

The caller must supply this directive's byte count and SHA-256 outside this
file. Compare them before test edits or validation. The local file may not
self-authenticate.

On absence or mismatch:

```text
STOP
ASK
DO NOT GUESS
DO NOT MUTATE
```

### 2.2 Read completely

Read completely:

1. `AGENTS.md`;
2. `SECURITY.md`, especially the diagnostic-output and production-surface
   rules;
3. `docs/engineering/error-handling.md`, especially current package exception
   boundaries and production presentation;
4. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
5. `docs/dev_plan/kongono_phase_2C_5_codex_directive_correction.md`;
6. `docs/task reports/Phase_2C_5_correction.md`;
7. the seven current authorized tests in Section 5; and
8. this Phase 2C.6 directive.

Read the HTTP exception marker/classes and all HTTP production throw sites
read-only. Do not edit production.

Phase 2C.5 remains authoritative except for the exact disclosure-boundary,
report-target, starting-test-identity, and resume corrections in this document.

---

## 3. Caller records and locked state

### 3.1 Caller setup

The caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_2C_6_codex_directive_correction.md
```

and create:

```text
docs/task reports/Phase_2C_6_correction.md
```

The Phase 2C.6 report must begin as an ordinary, untracked, unstaged, zero-byte
file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every non-report gate passes. Never modify this
directive.

### 3.2 Preserved Phase 2C.5 records

The Phase 2C.5 directive remains immutable:

```text
Bytes: 19356
SHA-256: 224B87D67452245DD915C220E8FA23B031AEFA78CDAA50BD6639015B8113C361
```

The stopped Phase 2C.5 report is now an immutable historical caller record:

```text
Path: docs/task reports/Phase_2C_5_correction.md
Bytes: 3754
SHA-256: 689C4B0B549504E0F2B7CF8CB3B5D46C229CD77A0C4054D640CF444ECB9278F2
State: untracked, unstaged, absent from HEAD
```

Its statement that the report was not populated records executor-time state;
the caller's preserved copy is intentionally populated as historical evidence.
Do not edit, truncate, normalize, stage, commit, or reuse it.

### 3.3 Repository state

Before Phase 2C.6 caller setup, require:

```text
Branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
Tree: bfa1908c619069b699bc9b08668db17ea2555eb3
Parent: 8fe793b124384744ac43887037360154c149241a
Commit count: 9
Tracked modifications: 18
Untracked paths: 61
Total status paths: 79
Staged paths: 0
```

After caller setup, require:

```text
Tracked modifications: 18
Untracked paths: 63
Total status paths: 81
Staged paths: 0
```

Require exactly one local branch, zero remotes, zero tags, and zero unexpected
refs. `refs/codex/turn-diffs/**` remain informational/exempt and must not be
mutated.

Verify production, manifests, lockfile, tooling, documentation, ADRs, history,
prior records, modes, streams, and protected dirty state remain at the accepted
Phase 2C.5 stopped state. Any other mismatch is a stop condition.

---

## 4. Corrected disclosure boundary

### 4.1 Package-owned surfaces that must exclude raw values

For each actual bounded rejected input required by Phase 2C.5 Section 6.3,
assert absence from:

- `Throwable::getMessage()`;
- `ExceptionInterface::diagnosticId()` except for the fixed catalog ID;
- `ExceptionInterface::diagnosticTitle()`;
- `ExceptionInterface::diagnosticRemediation()`;
- diagnostic schema operation;
- diagnostic violated invariant;
- every diagnostic field key;
- every diagnostic field label; and
- every diagnostic field value.

These are the package-owned diagnostic and message surfaces.

### 4.2 Native protected surfaces excluded from this proof

Do **not** assert raw-value absence from:

```text
(string) $exception
$exception->getTrace()
$exception->getTraceAsString()
$exception->getFile()
$exception->getLine()
```

Those surfaces retain native PHP behavior and may contain method arguments,
source locations, and other protected runtime detail. Do not inspect, print,
serialize, include in a test failure message, or copy their raw contents into
the completion report.

The test may assert only the structural fact that the exception remains a
native `Throwable` and that no custom `__toString()` is declared by either HTTP
exception class. Never assert or record the actual native trace arguments.

### 4.3 Presentation consequence

No current production error surface exists. Future production handling must
remain generic and must not stringify or expose a Throwable. The existing
trusted developer projection/renderer remains presentation-neutral and trace-
free. A future trace viewer, logger integration, runtime configuration policy,
or trace sanitization mechanism requires separate architecture and is not
authorized here.

No documentation edit is required because current `SECURITY.md`, ADR 0026, and
the engineering guide already state this boundary.

---

## 5. Current authorized test state

The Phase 2C.5 stopped execution modified exactly these seven tests. Their
current identities are the accepted Phase 2C.6 starting point:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php` | 9388 | `8C9FFAD9B8FB72929E522E13B1EB967FCF1CAE3A941F068AA2B111BDDD2ACC73` |
| `packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php` | 12970 | `5DAD6B799E5452CB0622B73605F863F77B3F30301B43583903F32AFD687DC646` |
| `packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php` | 7302 | `91CBA7CDA54D0D261305051E683AFDBA6AC45DA528D2938A777537A3B799A239` |
| `packages/http-message/tests/Unit/Message/ResponseTest.php` | 6418 | `9DE767694BA54971B683BF66863B801312174C880F38527E796839EED4F0FC42` |
| `packages/http-message/tests/Unit/Message/ServerRequestTest.php` | 15068 | `80B9E2AEC89515446A7109BD1A2DFD22EF6DB93AE7FCB77B593BF9AF939893AE` |
| `packages/http-message/tests/Unit/Message/StreamTest.php` | 11984 | `AE666FFAAD67211D697FB81FC8BBFF3E7694F2177487BCFF133E43F9380342A9` |
| `packages/http-message/tests/Unit/Message/UploadedFileTest.php` | 12420 | `8FDCEECE7EEE96A51C9419127D1B53992D370F216DD60E0A4781E123124D04D3` |

Only these seven existing tests may be modified, and only to:

1. apply the corrected disclosure boundary in Section 4;
2. replace the invalid PHPUnit call at
   `HttpMessageExceptionTest.php` line 196 from the three-argument strict-flag
   form to PHPUnit 13's valid strict membership assertion form; and
3. correct another test-authoring error discovered when resuming the still-
   required Phase 2C.5 tests.

PHPUnit 13's `assertContains()` already performs strict comparison. Use:

```php
self::assertContains($needle, $haystack);
```

or another semantically exact PHPUnit 13 assertion. Do not place a boolean in
the message parameter.

If a corrected test exposes any other production defect, stop. Production is
not authorized for modification.

---

## 6. Frozen production identities

Verify and preserve exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/src/Factory/StreamFactory.php` | 3071 | `32908C5AB4F7C691899C6D5C34DB4DC4310099DB9496C3833ABD3D74B9A28B23` |
| `packages/http-message/src/Internal/HeaderCollection.php` | 3674 | `7440CCF9245978E128F82FDB511A9C487A3D4B389B22C1D268F86F00057FACF2` |
| `packages/http-message/src/Message/ServerRequest.php` | 8198 | `74B60C96918275414442BCDC6F338718E399F18EB5A9FF06DFE4C0C9D14D9B18` |
| `packages/http-message/src/Message/Uri.php` | 10022 | `7887F7CE68EB5ABA98D3C4538F8E2A8E9D10F2DA1CE973039C8EA0C61E14EB63` |
| `packages/http-message/src/Exception/InvalidMessageArgumentException.php` | 1455 | `FFE5266A013397F8E53AD5828E1148AF6671A7ECC2EA58E4E024B5485E648ACB` |
| `packages/http-message/src/Exception/HttpMessageRuntimeException.php` | 1425 | `780DF2E5AEC2689F5D315F01D7E64B3355DADB5377B7BA52F24AA1542D1BFE94` |

Before execution, independently calculate and compare every row. If either
exception identity differs from these architect-observed values, stop rather
than infer. Do not add `__toString()` or any other production method.

The lockfile remains:

```text
Bytes: 187578
SHA-256: 264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F
```

---

## 7. Resume and validation sequence

The failed focused Phase 2C.5 run is accepted historical evidence:

```text
75 tests, 855 assertions, 1 failure, 1 error
```

Do not run canonical quality before correcting the two known test issues. If
the same execution context continues, do not reproduce or print the raw trace.
If context was lost, the populated Phase 2C.5 report is sufficient evidence;
do not intentionally reproduce protected trace arguments.

After the narrow test corrections:

1. run `php -l` on every changed test;
2. run the focused HTTP-message PHPUnit suite;
3. if it fails, classify the failure as a production defect or test-authoring
   error and obey the stop rule;
4. run the root architecture suite;
5. run focused package PHPStan using existing configuration;
6. run `composer dump-autoload --optimize --strict-psr`;
7. run `composer quality`;
8. perform the Phase 2C.5 direct vocabulary/call-site audit;
9. verify all Phase 2C.5 proof requirements under the corrected Section 4
   boundary; and
10. verify final Git, inventory, caller-record, lock, production-identity,
    encoding, mode, stream, and protected-state evidence.

The unchanged single approved-access retry remains allowed only for an
environment-blocked Composer audit. Do not weaken or skip any gate.

Expected inventories remain:

```text
Production PHP files: 70
Test PHP files: 98
HTTP production files: 17
HTTP test files: 13
HTTP package files: 32
```

Final Git status must remain 18 tracked modifications, 63 untracked paths, 81
total status paths, and zero staged paths. No commit/ref/history mutation is
authorized.

---

## 8. Report-target correction

Preserve the populated Phase 2C.5 report unchanged. After every non-report gate
passes, populate only:

```text
docs/task reports/Phase_2C_6_correction.md
```

Use the exact Phase 2C.5 Section 10 heading structure, replacing only the title
with:

```text
# PHASE 2C.6 CORRECTION TASK COMPLETION REPORT
```

Under `PHASE 2C.4 REVIEW FINDINGS`, explain that Phase 2C.5 correctly found
native trace-argument retention, and that Phase 2C.6 distinguishes package-owned
diagnostics from protected native trace data without inspecting or printing the
trace.

Under `PRODUCTION IMMUTABILITY PROOF`, report all six Section 6 identities and
confirm neither exception declares custom `__toString()`.

Under `TEST-PROOF CLOSURE`, reconcile every Phase 2C.5 Section 6 requirement
under the corrected disclosure boundary and record the PHPUnit assertion fix.

When complete, the exact final next step remains:

```text
Await architect review before Phase 2D.
```

Return the report's final byte count and SHA-256 outside its body.

---

## 9. Acceptance criteria

Phase 2C.6 is complete only if:

1. independent Phase 2C.6 identity matches;
2. every prior and current caller record matches;
3. the stopped seven-test state matches Section 5;
4. every Section 6 production/lock identity remains exact;
5. no custom HTTP exception `__toString()` exists;
6. the disclosure test uses actual rejected values and proves absence from every
   package-owned surface in Section 4.1;
7. the test neither inspects nor asserts native trace contents;
8. the PHPUnit 13 membership assertion is valid and strict;
9. all remaining Phase 2C.5 proof requirements pass;
10. syntax, focused tests, architecture tests, PHPStan, strict autoload, and
    canonical quality pass;
11. zero advisories, zero fixable files, zero PHPStan errors, and no skipped,
    incomplete, risky, warning, deprecation, notice, or output result;
12. production, dependencies, manifests, lock, tooling, architecture tests,
    docs, ADRs, diagnostics, schemas, APIs, and prior records remain unchanged;
13. only the seven authorized tests and Phase 2C.6 report change during this
    execution;
14. exact inventories and final 18/63/81/0 Git state;
15. zero stage/commit/ref/history mutation;
16. truthful, exact, strict UTF-8/LF completion report; and
17. final report byte count and SHA-256 returned outside its body.

Any failure activates:

```text
STOP
ASK
DO NOT GUESS
DO NOT MODIFY PRODUCTION
```

---

## 10. Prohibited scope

Do not modify production or add exception stringification. Do not select a
global PHP configuration, logger, trace sanitizer, trace viewer, production
error surface, or trusted trace presentation. Do not inspect or output captured
trace arguments.

All other Phase 2C.5 prohibited scope remains in force: no new files during
execution, fixtures, dependencies, packages, APIs, diagnostics, schemas,
configuration, baselines, suppressions, documentation, ADRs, tooling, routes,
middleware, handlers, adapters, SAPI work, emission, or later capability.

Do not edit prior records, stage, commit, restore, reset, clean, stash, branch,
tag, push, fetch, merge, rebase, or begin Phase 2D.

---

## 11. Required outcome

Phase 2C production remains byte-identical. Tests provide exact proof for every
package-owned diagnostic/message disclosure boundary and all remaining Phase
2C.5 requirements, while native PHP traces remain explicitly classified as
protected, non-presentable runtime data rather than falsely claimed sanitized
exceptions.
