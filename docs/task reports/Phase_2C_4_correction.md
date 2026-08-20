# PHASE 2C.4 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

Complete.

## 2. SUMMARY

Completed the composite Phase 2C.3/2C.4 correction. The four authorized
production files now preserve explicitly stored falsey attributes, route
integer header keys through `KGN-HTTP-1002`, accept the exact documented
structural `fopen` mode grammar, use the first Host value for preserve-Host
decisions, retain explicit URI ports across scheme changes, and retain non-null
passwords for empty users. The existing 13 HTTP-message test files now
materially cover the original Phase 2C Sections 12.2 through 12.8 matrix.

No package, production declaration, public API, diagnostic, schema, dependency,
manifest, lockfile, tooling configuration, documentation, ADR, route,
middleware, adapter, SAPI behavior, or later-phase capability changed.

## 3. BASELINE VERIFIED

- Phase 2C.4 supplied and observed identity: 9428 bytes,
  `A301FE034FD045F8C5F17806FE391DC275A902F824EB2F4716E7AFB4D778F431`.
- Starting Git status after caller setup: exactly 18 modified tracked, 59
  untracked, 77 total, and zero staged paths.
- Branch `main`, `HEAD`
  `d7846f137a1d53f5306f48d19196b916fde44ac2`, tree
  `bfa1908c619069b699bc9b08668db17ea2555eb3`, parent
  `8fe793b124384744ac43887037360154c149241a`, and nine commits matched.
- Exactly one branch, zero remotes, zero tags, zero unexpected refs, and 38
  informational `refs/codex/turn-diffs/**` refs existed.
- The authenticated accepted `Uri.php` starting identity matched 10165 bytes
  and full SHA-256
  `BD947D0119D3380EA5D763DA0679328566D86F1093081392BF64C6AA2FB5ED57`.
- Phase 2C.4 superseded the incomplete historical 61-character value for
  evaluation only; that value was not used, padded, repaired, or inferred.
- All other Phase 2C.3 target identities matched. Inventories were 70
  production PHP files, 98 test PHP files, 17 HTTP production files, 13 HTTP
  test files, and 32 total HTTP-package files.
- The lockfile matched 187578 bytes and SHA-256
  `264C1D57B3029A4DE9CB917CE5051819492E642D1BEFE6D787714ADA61A0E99F`.
- All 77 status paths were ordinary default-stream files without reparse
  points or alternate streams.
- Pre-correction canonical quality passed after the authorized identical
  network retry: zero advisories, zero of 168 files fixable, zero PHPStan
  errors, and 292 tests with 5107 assertions.

## 4. CALLER RECORDS

- Phase 2C.3 directive remained 33378 bytes with SHA-256
  `E40B22C646E62460D241A2B174EA110CE3D3A013349224A53E551DFCE5D4CE37`.
- Phase 2C.3 stopped report remained zero bytes with SHA-256
  `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`,
  untracked, unstaged, and absent from `HEAD`.
- Phase 2C.4 directive remained 9428 bytes with SHA-256
  `A301FE034FD045F8C5F17806FE391DC275A902F824EB2F4716E7AFB4D778F431`.
- This Phase 2C.4 report began as an ordinary zero-byte file with the empty-file
  SHA-256, untracked, unstaged, and absent from `HEAD`. It was populated only
  after every non-report gate passed.
- The original Phase 2C and Phase 2C.1 reports remain zero bytes. The accepted
  Phase 2C.2 report remains 16652 bytes with SHA-256
  `F8E5F417DF88513403F30FBF4E281F0D6963E5A1A5B22E3B8397E5C7DD85FBC6`.

## 5. PRE-CORRECTION DEFECT EVIDENCE

All six bounded probes reproduced the accepted defects without exposing a real
path or caller-controlled data:

1. Present `null` attribute returned the supplied fallback.
2. Integer header key produced native `TypeError` rather than the HTTP marker.
3. Native `fopen` accepted `rbe`; `StreamFactory` rejected it with
   `InvalidMessageArgumentException` / `KGN-HTTP-1020`.
4. Preserve-Host retained the original two-value list when its first value was
   empty.
5. Explicit HTTP port 80 became absent after changing the scheme to HTTPS.
6. Empty user plus the bounded literal password produced empty user info.

Post-correction probes returned `null`; package invalid-argument exception with
`KGN-HTTP-1002`; native and factory acceptance of `rbe`; replacement Host
`new.test`; HTTPS port 80 and `:80` serialization; and `:secret` user info with
the corresponding authority.

## 6. FILES CREATED

None. Both Phase 2C.4 caller records existed before execution.

## 7. FILES MODIFIED

Production corrections:

- `packages/http-message/src/Factory/StreamFactory.php` - exact base/modifier
  grammar, unique modifiers, and mutually exclusive `b`/`t`.
- `packages/http-message/src/Internal/HeaderCollection.php` - bounded
  `InvalidMessageArgumentException` for non-string array keys.
- `packages/http-message/src/Message/ServerRequest.php` - attribute key
  presence and first-Host-value behavior.
- `packages/http-message/src/Message/Uri.php` - retained explicit ports and
  exact empty-user/non-null-password assembly.

Existing test files expanded:

- `packages/http-message/tests/Unit/Diagnostic/HttpMessageDiagnosticTest.php`
- `packages/http-message/tests/Unit/Exception/HttpMessageExceptionTest.php`
- `packages/http-message/tests/Unit/Factory/ResponseFactoryTest.php`
- `packages/http-message/tests/Unit/Factory/ServerRequestFactoryTest.php`
- `packages/http-message/tests/Unit/Factory/StreamFactoryTest.php`
- `packages/http-message/tests/Unit/Factory/UploadedFileFactoryTest.php`
- `packages/http-message/tests/Unit/Factory/UriFactoryTest.php`
- `packages/http-message/tests/Unit/Internal/HeaderCollectionTest.php`
- `packages/http-message/tests/Unit/Message/ResponseTest.php`
- `packages/http-message/tests/Unit/Message/ServerRequestTest.php`
- `packages/http-message/tests/Unit/Message/StreamTest.php`
- `packages/http-message/tests/Unit/Message/UploadedFileTest.php`
- `packages/http-message/tests/Unit/Message/UriTest.php`

This report was populated after the non-report proof. No other path changed
during Phase 2C.4 execution.

## 8. ARCHITECTURAL DECISIONS

None. Phase 2C.4 corrected authority only; implementation followed the locked
Phase 2C.3 contracts.

## 9. DEVIATIONS FROM DIRECTIVE

None. One initial focused run exposed an over-broad reflection assertion that
included inherited native exception properties. The test was corrected within
the authorized file boundary, and the ordered validation sequence restarted
from syntax before successful final validation.

## 10. DEPENDENCIES ADDED/REMOVED

None. Composer manifests and `composer.lock` are unchanged.

## 11. ENVIRONMENT

- Windows PowerShell.
- PHP 8.5.9 ZTS x64.
- Composer 2.10.2.
- Git 2.45.1.windows.1.
- PHPUnit 13.3.0.
- PHPStan 2.2.8 at maximum level with strict rules.
- PHP CS Fixer 3.95.18.

## 12. COMMANDS RUN

Commands covered directive/caller hashes and bytes; Git status, branch, tree,
parent, history, staging and ref classification; ordinary-file/reparse/stream
checks; inventory and lockfile proofs; `composer quality` before and after the
correction; six pre- and post-correction `php -r` probes; PHP CS Fixer formatting
limited to the 17 authorized changed PHP files; `php -l` for all 17 changed PHP
files; focused HTTP and architecture PHPUnit commands; focused package PHPStan;
`composer dump-autoload --optimize --strict-psr`; environment version commands;
and final encoding, inventory, caller-record, file-safety, Git and report checks.

Both sandboxed `composer quality` attempts reached only the locked security
audit and failed because Packagist/cache access was unavailable. Each was
retried once with the identical command and approved access, as authorized.

## 13. VALIDATION RESULTS

- Syntax: PASS for all 17 changed PHP files.
- Focused HTTP PHPUnit: PASS, 72 tests and 873 assertions.
- Root architecture PHPUnit: PASS, 6 tests and 1028 assertions.
- Focused package PHPStan: PASS, zero errors.
- Strict optimized autoload: PASS, 3151 classes and zero PSR warnings.
- Canonical `composer quality`: PASS after the identical audit retry; zero
  advisories, zero of 168 files fixable, zero PHPStan errors, and 326 tests
  with 5588 assertions.
- Post-correction six-probe verification: PASS.
- Lock identity and package/file inventories: PASS.
- Changed-PHP strict UTF-8/LF/NUL/trailing-whitespace/final-LF checks: PASS.
- Final file-safety, ref, history, staging, caller-record, and protected-state
  checks: PASS.

## 14. TEST-MATRIX RECONCILIATION

Original Phase 2C Section 12.2, diagnostics/exceptions: exact ordered 28-case
catalog and IDs; exact field names/order/kinds/Safe required disclosure;
catalog/marker/concrete class shapes and API statuses; native parents; code
zero; previous identity; schema-order output; five sibling separations; and raw
input exclusion are explicitly tested.

Section 12.3, headers/messages: all protocol spellings and rejection classes;
token boundaries; mixed-case merging and lookup; spelling/value order;
replace/append/remove/absence; empty/comma lines; every forbidden byte class;
HTAB/obs-text; deterministic invalid lists/entries; status 99/100/599/600;
reason controls; distinct copies; body replacement without read/close; shared
stream state; and detached returned arrays are explicitly tested.

Section 12.4, server requests: string/object URIs; default and explicit request
targets; method case/token behavior; every Host decision row; mixed casing;
empty and multi-value Host first-value cases; preserve true/false; hostless
URIs; user-info/default-port exclusion and non-default inclusion; all six
request domains; recursive list/associative uploads and deterministic invalid
depth/kind; parsed-body boundaries; absent/set/replace/remove/falsey
attributes; shared objects; top-level reference detachment; and unchanged
superglobals are explicitly tested.

Section 12.5, URI: empty, relative, absolute, authority-only, query-only and
fragment-only forms; scheme/host normalization; reg-name, IPv4 and bracketed
IPv6; invalid Unicode/host/scheme; port limits, default suppression and
bidirectional scheme re-evaluation; user/password including empty user;
allowed/generated/malformed percent behavior; Unicode-byte encoding; no double
encoding; authority/rootless and authority-less slash rules; complete assembly;
and distinct original-preserving `with*()` values are explicitly tested.

Section 12.6, streams: read-only, write-only, read/write and non-seekable
resources; flags, cursor, size, complete/missing metadata, bounded/zero/negative
reads, actual writes, rewind, all seek origins, EOF, contents, close/repeated
close, detach/repeated detach, every post-detach operation, capability failures,
native-open failure mapping, and non-throwing string conversion for detached,
unreadable, non-seekable and bounded read-filter failure are explicitly tested.

Section 12.7, uploads: every accepted/rejected error and size boundary;
metadata; null-size single lookup; readable-stream requirement; error-state
stream/move rejection; bounded multi-chunk copy; seekable rewind; non-seekable
current cursor; target replacement; empty/NUL target; open/write failures;
second move and post-move rejection; open caller source; and unchanged
anonymous supplied-stream failure propagation are explicitly tested. The
target-write wrapper used one unique unused test-local protocol and was always
unregistered in `finally`.

Section 12.8, factories: all five exact interfaces, concrete returns, defaults,
argument forwarding, failure categories and stateless property shapes are
tested. Stream factory additionally covers content rewind, all base modes,
resource identity/readability, malformed grammar, native-open failures, and
native-supported `rbe`, `rb+e`, and `r+be` extended modes.

## 15. DOCUMENTATION REVIEW

No documentation or ADR changed. Existing Phase 2C documentation continues to
distinguish implemented message/factory behavior from deferred routing,
middleware, bootstrap, adapters, emission and production HTTP behavior. No
current capability was overstated and no architecture decision was added.

## 16. SECURITY AND FILE-SAFETY REVIEW

Integer/coerced header keys now fail inside the bounded package diagnostic
boundary without raw key/value disclosure. Mode validation rejects malformed
bytes before I/O and does not disclose modes or paths. Host and URI corrections
retain existing user-info/path/credential exclusion from diagnostics. Tests use
bounded literal data, owned temporary resources, no DNS or external network,
and deterministic cleanup.

All 77 status paths remain ordinary default-stream files with zero reparse
points or alternate streams. The 17 changed PHP files are strict UTF-8 without
BOM, LF-only, NUL-free, trailing-whitespace-free, and have one final LF. No
public API, diagnostic schema, dependency, manifest, lock, tooling,
documentation, ADR, mode, caller record, ref, stage, or history mutation exists.

## 17. WARNINGS / KNOWN ISSUES

Accepted Composer warnings remain limited to absent license metadata and local
unbounded `@dev`/`*` constraints. Git continued to report the accepted
inaccessible user-level global-ignore warning. Managed cache/network access
required the two authorized identical `composer quality` retries. No policy or
gate was weakened.

Kongono remains pre-alpha, the HTTP concrete types remain Experimental, and no
certified universal PSR conformance or later web capability is claimed.

## 18. QUESTIONS / BLOCKERS

None.

## 19. GIT DIFF SUMMARY

Phase 2C.4 changed content in exactly four authorized production files and all
13 existing authorized test files, then populated only this report. No path was
created or deleted during execution. Final status remains exactly 18 modified
tracked, 59 untracked, 77 total, and zero staged paths. `main`, `HEAD`, tree,
parent, nine-commit history, one-branch/zero-remote/zero-tag state, 38 exempt
Codex refs, and zero unexpected refs remain unchanged. No commit or ref write
occurred.

## 20. ACCEPTANCE CRITERIA

Phase 2C.3 criteria: 1 PASS all authorities/targets read; 2 PASS independent
identity; 3 PASS corrected locked baseline/caller identities; 4 PASS initial
zero-byte report; 5 PASS pre-quality; 6 PASS six pre-change probes; 7 PASS
falsey attributes; 8 PASS absent-only fallback; 9 PASS integer/coerced keys and
`KGN-HTTP-1002`; 10 PASS exact mode grammar; 11 PASS extended modes including
`rbe`; 12 PASS malformed/runtime failure separation; 13 PASS first-value Host
table; 14 PASS reversible ports and empty-user/password; 15 PASS original test
matrix; 16 PASS deterministic bounded tests; 17 PASS complete validation; 18
PASS advisory/style/static/test signals; 19 PASS no new production/test file;
20 PASS closed production/test file boundary; 21 PASS no API/diagnostic/
dependency/manifest/lock/tooling/docs/ADR change; 22 PASS inventories; 23 PASS
no prohibited capability; 24 PASS zero stage/commit/ref/history mutation; 25
PASS protected state/records; 26 PASS Git/file safety; 27 PASS exact report
headings/proof; 28 PASS report identity procedure.

Phase 2C.4 criteria: 1 PASS independent identity; 2 PASS four 2C.3/2C.4 caller
records; 3 PASS stopped state; 4 PASS full corrected URI identity; 5 PASS all
other 2C.3 gates; 6 PASS all composite implementation/test/validation/security
criteria; 7 PASS incomplete digest rejected; 8 PASS prior records unchanged; 9
PASS closed changed-file/report boundary; 10 PASS Phase 2C.3 report remains
zero; 11 PASS this report populated only after non-report success; 12 PASS zero
stage/commit/ref/history mutation; 13 PASS strict report format validation; 14
PASS final external byte/digest procedure.

## 21. NEXT STEP

Await architect review before Phase 2D.
