# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.3

## HTTP-message semantic corrections and required-test closure

**Status:** Executor-ready correction directive
**Date:** 2026-08-16
**Repository:** `C:\Users\antho\Dev\kongono`
**Authority:** Architect-issued correction to Phase 2C, Phase 2C.1, and Phase 2C.2

---

## 1. Mission

Phase 2C.2 is **not accepted as complete**. Its repository boundary, package
shape, dependency graph, diagnostics catalog, documentation, and tooling work
remain provisionally accepted, but independent review found six observable
semantic defects and a material gap between the tests required by the Phase 2C
directive and the tests actually delivered.

Correct only this bounded HTTP-message implementation:

1. preserve an explicitly stored `null` server-request attribute;
2. map every invalid header-name key, including PHP-coerced integer keys, to the
   existing actionable HTTP-message invalid-header-name exception contract;
3. accept the extended modifier combinations permitted by PHP's documented
   `fopen` mode grammar while still rejecting malformed modes before I/O;
4. make preserve-Host behavior depend on the first Host field value, as locked;
5. preserve an explicit URI port so scheme changes can re-evaluate whether it
   is the default, and preserve an explicitly supplied password when the user
   component is empty; and
6. complete the originally required behavioral test matrix in the existing 13
   HTTP-message unit-test files.

This is a correction and proof phase. It does not reopen Phase 2C architecture,
add another feature, or authorize cleanup elsewhere.

---

## 2. Governing authority and authentication

### 2.1 Read completely before any mutation

Read, in this order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. `docs/engineering/error-handling.md`
6. `docs/engineering/api-status.md`
7. `docs/engineering/testing.md`
8. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
9. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
10. `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
11. `docs/architecture/http-message-model.md`
12. `docs/dev_plan/kongono_phase_2C_codex_directive.md`
13. `docs/dev_plan/kongono_phase_2C_1_codex_directive_correction.md`
14. `docs/dev_plan/kongono_phase_2C_2_codex_directive_correction.md`
15. `docs/task reports/Phase_2C_2_correction.md`
16. this Phase 2C.3 correction directive.

Also read every authorized production and test file listed in Section 6 before
editing it. Earlier directives remain authoritative except where this document
explicitly corrects them.

### 2.2 Independent directive identity

The caller must supply this directive's byte count and SHA-256 **outside this
file**. Compare those independently supplied values with the local artifact
before broad validation or mutation. The local file may not self-authenticate.

If either value is absent or differs:

```text
STOP
ASK
DO NOT GUESS
DO NOT MUTATE
```

### 2.3 Caller-managed records

Before execution, the caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_2C_3_codex_directive_correction.md
```

and create this ordinary zero-byte report placeholder:

```text
docs/task reports/Phase_2C_3_correction.md
```

The report must begin untracked, unstaged, absent from `HEAD`, zero bytes, and
have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The executor must never modify the directive. Keep the report zero-byte until
all non-report acceptance gates pass.

The accepted Phase 2C.2 report is immutable historical evidence:

```text
Path: docs/task reports/Phase_2C_2_correction.md
Bytes: 16652
SHA-256: F8E5F417DF88513403F30FBF4E281F0D6963E5A1A5B22E3B8397E5C7DD85FBC6
```

Do not populate, truncate, rewrite, normalize, stage, or commit any earlier
Phase 2C report.

---

## 3. Locked starting state

Before the caller adds the two Phase 2C.3 records, the accepted observed state
is:

```text
Branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
Tree: bfa1908c619069b699bc9b08668db17ea2555eb3
Parent: 8fe793b124384744ac43887037360154c149241a
Commit count: 9
Local branches: 1 (main)
Remotes: 0
Tags: 0
Tracked modifications: 18
Untracked paths: 55
Total status paths: 73
Staged paths: 0
Package directories: 9
Package manifests: 9
Composer manifests including root: 10
Production PHP files: 70
Test PHP files: 98
HTTP-message production PHP files: 17
HTTP-message test PHP files: 13
```

After the caller adds the directive and zero-byte report, require:

```text
Tracked modifications: 18
Untracked paths: 57
Total status paths: 75
Staged paths: 0
```

Exactly one local branch must exist. Zero remotes and zero tags must exist.
Codex application-managed refs matching only
`refs/codex/turn-diffs/**` are exempt from the ordinary-ref count. Report their
count, but do not create, update, delete, pack, or otherwise mutate them. Any
other unexpected ref is a stop condition.

The accepted lockfile identity is:

```text
Path: composer.lock
Bytes: 187578
SHA-256: 264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F
```

Verify all stated identities, ordinary-file status, stream safety, modes,
inventories, caller records, and zero staged state. If the actual state differs
outside the expected two caller records, stop and report the exact mismatch.
Do not repair, reset, restore, stage, commit, or normalize it.

---

## 4. Mandatory pre-change evidence

### 4.1 Canonical baseline

After every authentication and repository-state gate passes, run:

```powershell
composer quality
```

The accepted Phase 2C.2 baseline is:

```text
Security advisories: 0
PHP CS Fixer: 0 of 168 files fixable
PHPStan: 0 errors
PHPUnit: 292 tests, 5107 assertions
```

If the audit is blocked only by managed-environment network/cache restrictions,
retry the identical command once with approved outside-sandbox access. Do not
weaken or skip any stage.

### 4.2 Record the six failing pre-change probes

Run bounded, read-only PHP probes through the existing Composer autoloader and
record their results. Do not create a script file.

Probe A must demonstrate that:

```php
$request = (new Kongono\HttpMessage\Message\ServerRequest('GET', '/'))
    ->withAttribute('nullable', null);
$request->getAttribute('nullable', 'fallback');
```

currently yields `'fallback'`, even though the correct result is `null`.

Probe B must demonstrate that constructing the existing internal header
collection with an integer array key currently produces a native `TypeError`
instead of the existing package-local invalid-argument exception with
diagnostic ID `KGN-HTTP-1002`.

Probe C must create an owned temporary file, first verify that native `fopen`
accepts the mode `rbe` in this environment, and then demonstrate that the
existing `StreamFactory::createStreamFromFile()` rejects that same mode as an
invalid argument. Clean up the temporary file in `finally`.

Probe D must demonstrate that `withUri($newUri, true)` incorrectly preserves a
Host value list whose first value is empty and a later value is non-empty,
instead of replacing it from the new URI host.

Probe E must demonstrate that an explicit `80` port normalized under `http` is
lost after changing the URI scheme to `https`, instead of becoming the
non-default explicit port `80`.

Probe F must demonstrate that `withUserInfo('', 'secret')` currently discards
the supplied password instead of representing the encoded `:secret` user-info
component. Use this literal bounded test value only; do not use a real secret.

These failures are the authorized correction basis. Do not expose a real path
or caller-controlled raw value in report text.

---

## 5. Exact correction contracts

### 5.1 Explicitly stored null attributes

In `ServerRequest::getAttribute()` distinguish key absence from a present key
whose value is `null`. Use an exact key-presence check; do not use null
coalescing as the presence test.

Required behavior:

- absent key returns the supplied default;
- present `null` returns `null`;
- present `false`, `0`, `''`, array, and object return those exact values;
- replacement and removal retain the existing immutable copy-on-change rules;
- attribute names remain exact, case-sensitive strings; and
- no other server-request domain is read or changed.

This corrects the implementation of the already locked Phase 2C rule that a
**missing** attribute returns the default. It creates no new API.

### 5.2 Integer and PHP-coerced header-name keys

PHP array keys may be integers, including numeric-looking string literals that
PHP coerces to integers before the constructor sees them. Every non-string key
must follow the existing HTTP-message invalid-header-name diagnostic path.

Required behavior:

- throw `Kongono\HttpMessage\Exception\InvalidMessageArgumentException`;
- use existing diagnostic `HttpMessageDiagnostic::InvalidHeaderName`;
- expose diagnostic ID `KGN-HTTP-1002`;
- supply only the diagnostic's already declared bounded structural context;
- never disclose the raw header key or value in message, title, remediation,
  context, or exception string; and
- never leak a native `TypeError` for this invalid caller input.

Do not add or alter a diagnostic, schema, exception class, interface, or public
method. Do not convert arbitrary iterable input; the existing array contract
remains exact.

### 5.3 Documented `fopen` mode grammar

Replace the overly narrow mode validation in `StreamFactory` with a deterministic
structural validator for the documented grammar.

The accepted structural language is:

- exactly one leading base mode from `r`, `w`, `a`, `x`, or `c`;
- after it, zero or more modifier characters drawn only from `+`, `b`, `t`,
  `e`, and `n`;
- every modifier may occur at most once;
- `b` and `t` are mutually exclusive;
- the mode contains no NUL, whitespace, delimiter, second base character, or
  other byte; and
- the entire string must match; no prefix match or normalization is permitted.

This admits documented extended combinations such as `rbe`, `rb+e`, and
`r+be`. It also admits the documented non-blocking `n` modifier structurally.
Whether a particular wrapper/platform supports a structurally valid mode is
decided only by the attempted native `fopen` operation.

Failure categories remain distinct:

- malformed structural mode: existing
  `InvalidMessageArgumentException` / invalid-file-mode diagnostic;
- structurally valid mode whose open fails: existing
  `HttpMessageRuntimeException` / stream-open diagnostic, with no supplied path
  disclosure; and
- successful native open: return the existing `Stream` implementation without
  changing ownership rules.

Do not silently remove, reorder, lowercase, or repair any mode byte. Do not add
a filesystem search, path canonicalization, global error handler, or warning
leak.

### 5.4 First-value Host preservation

For `ServerRequest::withUri($uri, true)`, inspect the ordered Host field values,
not the comma-joined header line. Preserve the existing Host only when its first
value is non-empty. When Host is absent or its first value is empty and the new
URI has a host, replace Host with the new URI host plus its non-default port.
When the new URI is hostless, retain the existing absence or values.

Retain every other row of the original Section 9.2 table, including case-
insensitive Host identity, user-info exclusion, and default-port exclusion.

### 5.5 URI explicit-port and empty-user/password semantics

The URI must retain the caller's validated explicit port internally even when
`getPort()` and authority serialization suppress it because it is the current
scheme's default. Changing the scheme must re-evaluate that same explicit port:

- `http` plus explicit `80` exposes `null` and omits `:80`;
- changing that value to `https` exposes `80` and serializes `:80`;
- `https` plus explicit `443` exposes `null` and omits `:443`;
- changing that value to `http` exposes `443` and serializes `:443`; and
- setting a port or changing a scheme never invents a port that was not
  explicitly present.

Do not add a property. The existing port property may retain the validated
explicit value, while `getPort()` and authority assembly apply default-port
suppression against the current scheme.

For `withUserInfo()`:

- empty user and `null` password produce empty user info;
- a non-null password, including an empty password, is represented after `:`
  even when the user is empty;
- both components retain the existing component encoding rules; and
- authority serialization includes non-empty resulting user info and `@`.

Do not change URI parsing, host validation, percent encoding, or any other
component rule outside the locked contract.

### 5.6 Implementation restraint

Use the smallest clear implementation. A private helper inside
`StreamFactory` is permitted only if needed for readable validation. No new
production declaration, interface, trait, enum, package, dependency, schema, or
public/protected API is authorized.

---

## 6. Closed file boundary

### 6.1 Production files authorized for modification

Exactly these four production files may change:

```text
packages/http-message/src/Factory/StreamFactory.php
packages/http-message/src/Internal/HeaderCollection.php
packages/http-message/src/Message/ServerRequest.php
packages/http-message/src/Message/Uri.php
```

Their accepted starting identities are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/src/Factory/StreamFactory.php` | 2689 | `206F7F409A106F279EB9869453C72C23C45331A35D7116DEA57D4AE793AD22D1` |
| `packages/http-message/src/Internal/HeaderCollection.php` | 3533 | `63408E9B60BE64FC33BD7CA9D862E5311186AA13E2FA3BDF456141C26DBBFE92` |
| `packages/http-message/src/Message/ServerRequest.php` | 8135 | `D1A5919541F8646794A0FB4FC457809A815EA0744BAEFF4385F33212CF9A3E2C` |
| `packages/http-message/src/Message/Uri.php` | 10165 | `BD947D0119D3380EA5D763DA0679328566F1093081392BF64C6AA2FB5ED57` |

### 6.2 Existing tests authorized for modification

Exactly these 13 existing test files may change:

```text
packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php
packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php
packages/http-message/tests/Unit/Factory/ResponseFactoryTest.php
packages/http-message/tests/Unit/Factory/ServerRequestFactoryTest.php
packages/http-message/tests/Unit/Factory/StreamFactoryTest.php
packages/http-message/tests/Unit/Factory/UploadedFileFactoryTest.php
packages/http-message/tests/Unit/Factory/UriFactoryTest.php
packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php
packages/http-message/tests/Unit/Message/ResponseTest.php
packages/http-message/tests/Unit/Message/ServerRequestTest.php
packages/http-message/tests/Unit/Message/StreamTest.php
packages/http-message/tests/Unit/Message/UploadedFileTest.php
packages/http-message/tests/Unit/Message/UriTest.php
```

Their accepted starting identities are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php` | 2434 | `EB70DFB8394FE7EE1CC15D51563B123550B2480D35CBF663BDA0DD946307826A` |
| `packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php` | 2081 | `B278F651669FA18A9AEC982818F5D5CD6CC4ACCDF8D54099C26D386942BA232F` |
| `packages/http-message/tests/Unit/Factory/ResponseFactoryTest.php` | 840 | `538D9DA4687EE7B1C6E280E219D06DFD58F9EC26FA9B92A39BF82AEC2963D437` |
| `packages/http-message/tests/Unit/Factory/ServerRequestFactoryTest.php` | 958 | `38D1E3F873C8E5DA363A9A2C75D9A0F37E81386B774A085DE83939C1BCBDAC04` |
| `packages/http-message/tests/Unit/Factory/StreamFactoryTest.php` | 2844 | `CB683C8A343EF1B4FC740520881269C14229E26A491D515554A85D3D20BA5D2F` |
| `packages/http-message/tests/Unit/Factory/UploadedFileFactoryTest.php` | 1182 | `3A55636391880560E8843E55C09C0BEB23BA6F09F8040DBD21AEFBBB235BBBD5` |
| `packages/http-message/tests/Unit/Factory/UriFactoryTest.php` | 733 | `D61F4C78519C4E14C0E7E00CAFE76B0673925BD3212109A817BC4A20738B3A1F` |
| `packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php` | 2602 | `27FDB0AB7E426FAF8D6E30A44C8DE5D61CBF10511F8C92C9BC158416237E6177` |
| `packages/http-message/tests/Unit/Message/ResponseTest.php` | 2414 | `EB130C20883EEA89B24AEDF40700A7B56553F5D9FF45B35164B5EF85CA3DBA6A` |
| `packages/http-message/tests/Unit/Message/ServerRequestTest.php` | 3774 | `A68A48B4D49052FCDDDED359283C0720563907D1A9A6D4D13EA9D47D97265D46` |
| `packages/http-message/tests/Unit/Message/StreamTest.php` | 3241 | `C980FF8B820FEE0D5F19BC4465C0C1BA09D61DA04E5B8DBE6EFB38230C283239` |
| `packages/http-message/tests/Unit/Message/UploadedFileTest.php` | 3674 | `BDAB35E8310A80F59D4AADC27B2274E69A15294FA922D8D0011CC5CDF7DB55F5` |
| `packages/http-message/tests/Unit/Message/UriTest.php` | 2902 | `B68934356C365B9AABB2D52ED5D614EB730E2C60B03CFB714EA122C9169D5C52` |

Not every authorized test file must change. Modify only those needed to prove
the matrix. Authorization is a ceiling, not a quota.

### 6.3 Report file

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2C_3_correction.md
```

### 6.4 Everything else is prohibited

No other path may be created, deleted, renamed, moved, modified, normalized,
staged, committed, restored, or repaired. In particular, do not modify:

- any Composer manifest or `composer.lock`;
- any root tooling configuration;
- any documentation or ADR;
- any other production or test file;
- any previous directive or report;
- `.gitignore`, attributes, file modes, streams, refs, hooks, or Git config; or
- the existing caller-managed dirty work outside this closed boundary.

No test fixture file may be added. A test-local anonymous class is permitted
only where Phase 2C already authorized it and must not become a named autoloaded
declaration.

---

## 7. Required test closure

### 7.1 Test quality

Tests must be behavioral, deterministic, bounded, and inspectable. They must
fail on the uncorrected behavior where applicable. No tautological reflection
check may substitute for an observable contract. No test may use the network,
DNS, SAPI globals, real user files, environment secrets, time, randomness,
process-global error handlers, or another framework's implementation.

Use only owned temporary files/resources and clean them in `finally` or
tear-down logic. Do not suppress PHPUnit warnings, mark tests incomplete or
risky, loosen configuration, or inflate assertion counts.

To exercise target-write failure portably, a test may register one uniquely
named, test-local stream-wrapper protocol backed by an anonymous class, provided
the test first proves the protocol is unused, registers it only inside the test,
and always unregisters it in `finally`. It must not replace a built-in wrapper,
escape the test process, emit output, or become production behavior. No other
process-global mutation is authorized.

### 7.2 Six regression proofs

Add explicit tests proving:

1. an absent attribute returns its supplied default, while present `null`,
   `false`, `0`, `''`, array, and object values return exactly themselves;
2. integer key `0` and a numeric-looking string literal that PHP coerces to an
   integer both throw the existing invalid-message-argument exception with ID
   `KGN-HTTP-1002`, correct marker, bounded context, and no raw key/value; and
3. native-supported extended modes including `rbe`, `rb+e`, and `r+be` are
   accepted by the factory; structurally valid native-open failures remain
   runtime failures; malformed modes remain invalid arguments;
4. preserve-Host true replaces a Host list whose first value is empty when the
   new URI has a host, while retaining a Host whose first value is non-empty;
5. explicit default ports are suppressed under their matching scheme and become
   visible after a scheme change makes them non-default, in both `80` and `443`
   directions; and
6. empty-user user-info retains every non-null password, including empty and
   percent-encoded password cases, while empty user plus null password remains
   empty.

For mode cases whose native support can vary by platform or wrapper, first
establish support with an owned native handle and assert factory equivalence
only when supported. The fixed environment must directly exercise `rbe`; it
may not be skipped because the pre-change probe already established support.

Malformed-mode coverage must include empty, NUL-containing, whitespace,
unknown byte, repeated modifier, both `b` and `t`, repeated `+`, and second-base
cases. Verify no supplied path or raw invalid mode appears in diagnostics.

### 7.3 Close the original Phase 2C matrix

The executor must reconcile the actual test suite against Sections 12.2 through
12.8 of the original Phase 2C directive. Every listed scenario remains
mandatory. At minimum, add focused coverage for all currently missing or
under-proved categories below.

#### Diagnostics and exceptions

- exact schema field names, order, kinds, disclosure levels, and closed
  vocabularies for all 28 HTTP diagnostics;
- exact marker and concrete-exception method/property shapes and API status;
- all five sibling-marker separations, native parent categories, code zero,
  previous preservation, input-order-independent schema output; and
- representative forbidden/raw input absence from every public diagnostic
  surface.

#### Header and message behavior

- every accepted/rejected protocol version and method/header token boundary;
- mixed-case merge/lookup, spelling retention, replace/append/remove/absence,
  empty line, comma-line order, forbidden bytes, HTAB, and obs-text;
- invalid list shapes and non-string entries with deterministic first failure;
- status `99/100/599/600`, reason controls, immutable distinct copies;
- body replacement without read/close, shared stream mutation across clones;
  and
- detached top-level arrays with explicitly shared nested objects.

#### Server request and Host

- string and object URI construction and default/explicit request targets;
- every Section 9.2 Host table row, mixed Host casing, explicit empty Host,
  multi-value Host with empty and non-empty first values, preserve-host
  true/false, hostless URI, URI user-info exclusion, default-port exclusion, and
  non-default-port inclusion;
- exact isolation of all six request data domains;
- recursive uploaded-file list and associative trees plus deterministic invalid
  leaf depth/kind;
- parsed-body `null`/array/object acceptance and scalar rejection;
- attribute absence/default/set/replace/remove plus the explicit-null regression;
  and
- shared consumer objects with no superglobal interaction.

#### URI

Use table-driven cases for every item in original Section 12.5, including:

- empty, relative, absolute, authority-only, query-only, fragment-only;
- normalized scheme/host; reg-name, IPv4, bracketed IPv6;
- raw Unicode and invalid host/scheme rejection;
- port boundaries, default-port suppression, retained explicit port, and
  bidirectional scheme-change re-evaluation;
- user/password assembly, including empty-user with non-null password, and
  percent encoding;
- path/query/fragment allowed bytes, generated Unicode-byte encoding,
  malformed triplets, and no double encoding;
- authority with rootless path, authority-less multiple leading slashes, and
  complete string assembly; and
- original-state preservation plus distinct return values for every `with*()`.

#### Stream

Cover readable-only, writable-only, read/write, seekable, and non-seekable
resources. Prove capability flags, cursor, size, complete metadata, missing
metadata key, bounded reads, actual write count, rewind, all supported seek
origins, EOF, contents, close, repeated close, detach, every post-detach failure,
negative and zero read length, capability failures, and mapped native operation
failures.

Prove `__toString()` returns `''` and never throws for detached, unreadable,
non-seekable-rewind failure, and read failure. Tests remain small and bounded.

#### Uploaded file

Cover every original Section 12.7 item: all accepted/rejected upload errors and
sizes, metadata, null-size factory lookup, readable-stream requirement,
error-state `getStream()` and `moveTo()`, bounded-chunk movement, seekable rewind,
non-seekable current-cursor copy, target replacement, empty/NUL target,
target-open and target-write failure, second move, post-move stream rejection,
caller source remaining open, and unchanged propagation from an intentionally
failing supplied `StreamInterface` anonymous object.

#### Factories

For each of the five factories prove the exact implemented PSR interface,
returned concrete implementation, defaults, argument forwarding, failure
category, and stateless shape. Retain the full stream-factory requirements from
original Section 12.8, extended by Section 7.2 above.

### 7.4 No implementation gaming

Do not weaken an assertion to match current output. Do not catch an unexpected
native failure and relabel the test successful. Do not copy another framework's
tests wholesale. Do not add production-only branches for the test environment.
Do not claim a scenario is covered merely because a neighboring assertion
passes.

---

## 8. Validation sequence

After implementation, run in this order:

1. syntax checks for every changed PHP file;
2. focused HTTP-message PHPUnit suite;
3. root architecture PHPUnit suite;
4. focused PHPStan for the package if supported by existing configuration;
5. optimized strict autoload generation:

```powershell
composer dump-autoload --optimize --strict-psr
```

6. canonical validation:

```powershell
composer quality
```

If the final security audit alone is environment-blocked, use the same single
unchanged approved-access retry rule as Section 4.1.

Then perform direct source/test inspection and prove:

- only authorized implementation/test paths changed;
- no path was created or deleted;
- the six defects are corrected;
- the test matrix is materially present rather than merely reported;
- no dependency, manifest, lock, tooling, documentation, ADR, diagnostic
  catalog, or public API changed;
- `composer.lock` remains exactly 187578 bytes with SHA-256
  `264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F`;
- counts remain 70 production PHP files, 98 test PHP files, 17 HTTP production
  PHP files, and 13 HTTP test PHP files;
- the 32-file HTTP package inventory is unchanged;
- zero staged paths exist; and
- history, branch, refs, remotes, tags, caller records, file modes, alternate
  streams, and protected dirty state remain unchanged except for the two new
  Phase 2C.3 caller records and authorized content changes.

Final test/assertion counts may increase and must be reported exactly. No exact
vanity minimum replaces required scenario coverage.

---

## 9. Strict acceptance criteria

Phase 2C.3 is complete only if every item passes:

1. all authority and target files were read completely;
2. the independently supplied directive identity matched;
3. every locked baseline and caller-record identity matched;
4. the report began as an ordinary zero-byte untracked/unstaged file;
5. pre-change `composer quality` passed at the accepted baseline;
6. all six pre-change defects were reproduced and recorded safely;
7. explicitly stored `null` and other falsey attributes are preserved;
8. absent attributes alone use the supplied default;
9. integer/coerced header keys use `KGN-HTTP-1002` and never leak `TypeError` or
   raw input;
10. the stream-factory validator implements the exact Section 5.3 structural
    grammar;
11. native-supported extended modes, including `rbe`, pass through the factory;
12. malformed-mode and native-open failure categories remain distinct and
    actionable;
13. preserve-Host behavior uses the first Host value and satisfies every locked
    decision-table row;
14. URI default-port suppression remains reversible across scheme changes and
    empty-user/password assembly is exact;
15. every originally required Phase 2C unit scenario is now explicitly tested;
16. tests are deterministic, bounded, warning-free, and meaningful;
17. syntax, focused tests, architecture tests, PHPStan, strict PSR autoload, and
    canonical quality all pass;
18. advisories are zero, PHP CS Fixer reports zero fixable files, PHPStan reports
    zero errors, and PHPUnit reports no warning/risky/incomplete/skipped/output
    condition;
19. no new production or test file exists;
20. only the authorized four production files and necessary subset of the 13
    authorized tests changed;
21. no public API, diagnostic catalog/schema, dependency, manifest, lockfile,
    tooling file, documentation, or ADR changed;
22. all file/package/type counts remain exact;
23. no prohibited capability or neighboring package work was introduced;
24. zero staged paths exist and no commit/ref/history mutation occurred;
25. caller-managed dirty state and prior records are preserved byte-for-byte;
26. final Git and file-safety evidence is exact;
27. the completion report contains every Section 11 heading and truthful proof;
    and
28. the executor returns the report's final byte count and SHA-256 outside the
    report body after it is fully written.

Any failure activates the stop rule. Do not populate the report unless every
non-report criterion has passed.

---

## 10. Prohibited scope

Do not implement or alter:

- routing, route grammar, route matching, dispatch, controllers, middleware,
  handlers, PSR-15, HTTP clients, PSR-18, request emission, response emission,
  SAPI parsing, body parsing, multipart parsing, content negotiation, sessions,
  cookies policy, CORS, CSRF, authentication, authorization, rate limiting,
  uploads storage policy, or server adapters;
- Kernel lifecycle integration, DI registration, Application Graph integration,
  diagnostics presentation, trusted-developer activation, telemetry, CLI,
  agents, extensions, ORM, frontend, or persistence;
- universal PSR-conformance claims or compatibility shims;
- new packages, dependencies, abstractions, helpers, fixtures, schemas, IDs,
  configuration, baselines, suppressions, or exclusions;
- staging, commit, amend, reset, restore, checkout, clean, rebase, merge, tag,
  branch, remote, stash, ref mutation, or history rewriting; or
- opportunistic cleanup, formatting, spelling, documentation, or historical
  record changes.

Preserve all locked Kongono doctrine, strict no-assumption rules, disclosure
boundaries, and persistent-runtime non-retention rules.

---

## 11. Standard executor completion report

After all non-report gates pass, populate
`docs/task reports/Phase_2C_3_correction.md` with exactly these top-level
headings in this order:

```text
# PHASE 2C.3 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. PRE-CORRECTION DEFECT EVIDENCE
## 6. FILES CREATED
## 7. FILES MODIFIED
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. VALIDATION RESULTS
## 14. TEST-MATRIX RECONCILIATION
## 15. DOCUMENTATION REVIEW
## 16. SECURITY AND FILE-SAFETY REVIEW
## 17. WARNINGS / KNOWN ISSUES
## 18. QUESTIONS / BLOCKERS
## 19. GIT DIFF SUMMARY
## 20. ACCEPTANCE CRITERIA
## 21. NEXT STEP
```

The report must state:

- complete or blocked truthfully;
- every starting and final repository identity/count;
- the independently supplied directive identity;
- the safe observed result of all six pre-change probes;
- exact created files (`None` is expected);
- exact modified files;
- the implementation of each correction without raw caller data;
- a scenario-by-scenario reconciliation of original Sections 12.2-12.8;
- exact focused and canonical test/assertion counts;
- advisory, style, PHPStan, syntax, autoload, architecture, inventory, lock,
  link, encoding, stream, mode, ref, and Git results;
- confirmation that no documentation/dependency/public API/prohibited
  capability changed;
- any environment-only retry;
- all acceptance items as PASS/FAIL/NOT RUN; and
- `Await architect review before Phase 2D.` as the exact final next step when
  complete.

After writing the report, validate strict UTF-8 without BOM, LF endings, no NUL,
no trailing whitespace, no extra top-level heading, exactly one final LF, and
zero staged paths. Compute and return the report's final byte count and SHA-256
outside the report body. Do not edit it afterward.

---

## 12. Required outcome

The required result is a corrected, fully proved Phase 2C HTTP-message boundary:

- falsey request attributes retain exact presence semantics;
- invalid header keys always enter Kongono's actionable diagnostic boundary;
- documented extended PHP file modes are structurally accepted and native
  support remains the final open decision;
- preserve-Host behavior uses the first Host value exactly;
- explicit URI ports survive default suppression for later scheme changes, and
  empty-user password assembly is preserved;
- the original required test matrix is present and passing;
- package shape, API, diagnostics, dependencies, documentation, lockfile,
  history, and all later capabilities remain unchanged; and
- Phase 2D does not begin until independent architect review accepts this
  correction.
