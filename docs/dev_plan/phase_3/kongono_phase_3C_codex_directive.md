# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3C

## Bounded PHP CGI Inbound Conversion

**Directive date:** 2026-08-21

**Authority:** Architect-issued Phase 3C implementation boundary

**Execution posture:** Executor, not architect

**Later-phase authority:** Not granted

---

## 1. Mission

Implement only the accepted Phase 3C inbound boundary selected by ADR 0052:
one explicit, bounded PHP CGI host snapshot plus one caller-owned input stream
becomes one first-party PSR-7 `ServerRequestInterface`.

Phase 3C must:

1. create the fourteenth monorepo package at `packages/php-cgi`;
2. add one stateless Experimental CGI server-request converter;
3. implement the exact profile admission, closed server snapshot, method,
   protocol, target, scheme, authority, header, query, cookie, body, form, and
   upload rules already selected by Phase 3B;
4. add package-owned actionable diagnostics that never retain rejected raw
   values;
5. add production-grade boundary, disclosure, determinism, resource-ownership,
   and repeated-call tests;
6. expand Composer, autoloading, tooling, architecture enforcement, and current
   documentation only as required for this package; and
7. leave Phase 3D response emission, Phase 3E operator/trust composition,
   Phase 3F production entry, and all later work untouched.

The converter is deliberately explicit and testable. It accepts its SAPI label,
closed server projection, post/upload emptiness arrays, and raw input resource
as method arguments. It does not read `PHP_SAPI`, superglobals, environment,
`php://input`, headers, or output by itself. A later production composition
boundary will collect ambient host input and call this converter.

---

## 2. Mandatory stop rules

If architecture, authority, identity, inventory, or a locked value differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Do not:

- self-authenticate this directive from its local file;
- substitute a similarly named ADR, report, package, class, or command;
- repair, restore, truncate, rename, delete, stage, commit, reset, clean, or
  otherwise normalize caller-managed state;
- add a dependency not explicitly authorized here;
- invent another public API, diagnostic field, input source, protocol mapping,
  parser behavior, status mapping, or resource policy;
- suppress or baseline a quality finding;
- weaken Composer validation, audit, platform, style, PHPStan, or PHPUnit;
- begin Phase 3D or later work.

The inaccessible user-global Git ignore warning is environmental only after
the directive's explicit closed inventories match.

---

## 3. Independent directive authentication

Before broad validation or mutation, compare this file with the byte count and
SHA-256 supplied independently by the architect outside the file.

Required repository path after caller copy:

```text
docs/dev_plan/kongono_phase_3C_codex_directive.md
```

If either independently supplied value is absent or differs, stop before
quality checks, Composer mutation, source edits, or report population.

The local file is not self-authenticating.

---

## 4. Required authorities

After authentication and before mutation, read completely:

- `AGENTS.md`;
- `ARCHITECTURE.md`;
- `README.md`;
- `SECURITY.md`;
- `docs/doctrine.md`;
- `docs/documentation-standard.md`;
- `docs/adr/0001-kongono-identity.md`;
- `docs/adr/0014-php-engineering-toolchain.md`;
- `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
- `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
- `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`;
- `docs/adr/0044-http-message-and-interoperability-architecture.md`;
- `docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md`;
- `docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md`;
- `docs/adr/0051-phase-3-production-http-integration-milestone.md`;
- `docs/adr/0052-php-cgi-fastcgi-responder-profile.md`;
- `docs/architecture/http-message-model.md`;
- `docs/architecture/web-runtime-model.md`;
- `docs/architecture/phase-3-roadmap.md`;
- `docs/architecture/php-cgi-fastcgi-profile.md`;
- `docs/engineering/package-layout.md`;
- `docs/engineering/toolchain.md`;
- `packages/http-message/composer.json`;
- every PHP file under `packages/http-message/src`;
- `packages/web-runtime/src/Contract/WebDeliveryInterface.php`;
- `packages/web-runtime/src/WebRuntimeAdapter.php`;
- `composer.json`;
- `.php-cs-fixer.dist.php`;
- `phpstan.neon.dist`;
- `phpunit.xml.dist`;
- `tests/Architecture/ApiStatusConventionTest.php`;
- `docs/dev_plan/kongono_phase_3B_codex_directive.md`;
- `docs/dev_plan/kongono_phase_3B_1_codex_directive_correction.md`;
- `docs/task reports/Phase_3B.md`;
- `docs/task reports/Phase_3B_1_correction.md`;
- this directive; and
- the zero-byte `docs/task reports/Phase_3C.md` placeholder.

Do not infer an authority path from an ADR number. Stop if an exact required
path is absent.

---

## 5. Locked starting state

### 5.1 Git identity

Require:

```text
branch:          main
HEAD:            647053d4bddc4c76090bca9bf3d693faa5880c52
HEAD tree:       3ad60da1dc535fc0b7a166ac8522f954d4c67fac
HEAD parent:     d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:    10
local branches:  1, exactly main
remotes:         0
tags:            0
tracked files:   536
staged paths:    0
```

Codex-managed `refs/codex/turn-diffs/**` are application-owned and exempt from
the repository ref invariant. No other unexpected ref is allowed. Do not
create, edit, or remove exempt refs.

### 5.2 Status inventory

Before the caller adds Phase 3C records, the accepted Phase 3B.1 state is:

```text
modified tracked paths: 16
untracked paths:         11
total status paths:      27
staged paths:            0
Git-visible paths:       547
```

After the caller copies this directive and creates the Phase 3C report
placeholder, require exactly:

```text
modified tracked paths: 16
untracked paths:         13
total status paths:      29
staged paths:            0
Git-visible paths:       549
tracked files:           536
```

The sixteen modified tracked paths must be exactly:

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

The thirteen untracked paths must be exactly:

```text
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/dev_plan/kongono_phase_3A_codex_directive.md
docs/dev_plan/kongono_phase_3B_codex_directive.md
docs/dev_plan/kongono_phase_3B_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_3C_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
docs/task reports/Phase_3A.md
docs/task reports/Phase_3B.md
docs/task reports/Phase_3B_1_correction.md
docs/task reports/Phase_3C.md
```

### 5.3 Locked caller and authority identities

Require:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_3B_codex_directive.md` | 37,311 | `6D7F597C2EC64CE78DF02E55F893ED084072B914D84E6256F94FC16A57793D43` |
| `docs/dev_plan/kongono_phase_3B_1_codex_directive_correction.md` | 25,177 | `F4B6AE5DCAE502929D1FA765C47A7CCC692186A184F3743F60A695C58C60E829` |
| `docs/task reports/Phase_3B.md` | 19,233 | `D7AB7DC0885D7E32A81B5CC1E51598C895443C3E8AB38B745F448FFD2EF7ACB8` |
| `docs/task reports/Phase_3B_1_correction.md` | 19,285 | `24D9BDC3B4640A254C21FFF8AFC5C679CD3AB9DB9D739A78BEEA007526974CEF` |
| `docs/adr/0052-php-cgi-fastcgi-responder-profile.md` | 13,139 | `DCD53CC972FDE94AFE668254C43CF6A531F6FE6A178EC42D83917E84961E17A5` |
| `docs/architecture/php-cgi-fastcgi-profile.md` | 21,647 | `9872CB45D6F582F748521580FF4DDBCF416D0F951747ECC62F3862B8DB1FB5A5` |
| `docs/architecture/phase-3-roadmap.md` | 12,692 | `8DEC5D69A1DB6E3701B4E85776EE4CC0C95AEEDF9BDF0C2D27C1CBEFD4827AA4` |
| `composer.json` | 4,299 | `99A8B23827C0C5C691EE68A19A9ACD20543ABAF2AE4B7C97DF0FCA0A9B6EC76C` |
| `composer.lock` | 196,324 | `28B03AFDC8FF9C6F2F754E65B97C867E499BAD2D4427E0F1881FBBA88E1E6862` |
| `.php-cs-fixer.dist.php` | 2,111 | `AA401C628389FAE49DBCEED1DDBDA89ED26C543B89C831D1022582D461B811D0` |
| `phpstan.neon.dist` | 1,247 | `DEF20A49D534770DDEF29460B64B41072B611ADEC5119542515358B858D5CEC3` |
| `phpunit.xml.dist` | 2,446 | `00F2CF73A2D78FD3AC6D6532A85399CBCDAFD958A4928666A3763193C36CD858` |
| `tests/Architecture/ApiStatusConventionTest.php` | 69,698 | `A082B6953F66AC78620EDA86497AD6394F6FEAAC7B549476BBABB48B46A75483` |

The Phase 3C report must initially be an ordinary untracked, unstaged,
zero-byte file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Populate it only after every non-report gate passes.

### 5.4 Baseline inventories and quality

Require before implementation:

```text
package directories:      13
package manifests:        13
production PHP files:     100
test PHP files:           130
style-covered PHP files:  230
tracked files:            536
PHP memory_limit:         128M
PHP_INI_SCAN_DIR:         absent
```

Run literal pre-change:

```text
composer quality
```

Expected baseline:

```text
locked audit:          zero advisories
platform requirements: pass
PHP CS Fixer:          0 of 230 files fixable
PHPStan:               zero errors
PHPUnit:               480 tests, 8,575 assertions
```

If and only if the unchanged audit is blocked by managed network/cache access,
one identical approved-access retry is authorized. No other retry, overlay,
configuration change, suppression, or weakened command is authorized.

---

## 6. Exact authorized file boundary

### 6.1 Create exactly these fifteen implementation/documentation paths

```text
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
packages/php-cgi/README.md
packages/php-cgi/composer.json
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Exception/CgiRequestReadException.php
packages/php-cgi/src/Exception/ExceptionInterface.php
packages/php-cgi/src/Exception/InvalidCgiRequestException.php
packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/src/Internal/CgiCookieParser.php
packages/php-cgi/src/Internal/CgiHeaderProjector.php
packages/php-cgi/src/Internal/CgiQueryParser.php
packages/php-cgi/src/Internal/CgiRequestValidation.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
packages/php-cgi/tests/Unit/Inbound/CgiServerRequestConverterTest.php
```

No fixture, golden file, custom stream wrapper, executable entrypoint, config,
front controller, emitter, delivery implementation, or additional source/test
file is authorized.

### 6.2 Modify only these eighteen existing paths

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

### 6.3 Caller-owned report

Populate only after all other gates pass:

```text
docs/task reports/Phase_3C.md
```

### 6.4 Closed prohibited scope

Every other path is read-only. In particular, do not edit:

- ADR 0051 or ADR 0052;
- any Phase 2 or Phase 3A/3B directive/report;
- any existing production package source or package test;
- Web Runtime, Web Application, HTTP Message, Kernel, Routing, HTTP Server,
  diagnostics, presentation, handler, DI, or Graph behavior;
- the Phase 2 fixture or root black-box tests;
- any existing package manifest;
- audit/security policy, Composer plugin policy, PHP platform, version
  constraints, or third-party dependency versions;
- `.env`, PHP INI, web-server, FastCGI, deployment, listener, process-manager,
  container, CI, application-template, or executable files;
- DI/container, routing behavior, ORM/database, agents, extensions, CLI,
  frontend, telemetry, cache, session, authentication, authorization, or other
  later framework capabilities; or
- Git index, history, branches, refs, tags, remotes, hooks, signing, or config.

Do not stage or commit.

---

## 7. Package and Composer contract

### 7.1 Package manifest

Create `packages/php-cgi/composer.json` with:

```json
{
  "name": "kongono/php-cgi",
  "description": "Bounded PHP CGI inbound conversion for Kongono's selected FastCGI Responder profile.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/diagnostics": "*",
    "kongono/http-message": "*",
    "php": "^8.5",
    "psr/http-message": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\PhpCgi\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\PhpCgi\\Tests\\": "tests/"
    }
  }
}
```

This is the exact Phase 3C dependency subset. ADR 0052 permits later direct
Diagnostic Activation and Web Runtime edges only when later authorized source
actually uses them. Do not add unused Phase 3D-3F dependencies now.

No third-party dependency is added. `psr/http-message` is already locked.

Composer warnings remain limited to the accepted categories: missing license,
unbounded local Kongono development constraints (including the new package's
authorized `*` requirements), and the environment's missing-curl performance
notice when emitted. Do not add a license, narrow/invent local versions, install
curl, or change policy in this phase. Report any other warning and stop if it
signals a semantic mismatch.

### 7.2 Root manifest

In root `composer.json`:

- add `"kongono/php-cgi": "@dev"` in sorted root requirements;
- add `Kongono\PhpCgi\Tests\` to root dev autoload;
- add package validation to `validate:packages`; and
- preserve every other byte-semantic setting and policy.

### 7.3 Lockfile generation

After both manifests are correct, run exactly:

```text
composer update kongono/php-cgi --with-dependencies --no-interaction
```

An identical approved-access retry is allowed only for a managed network/cache
failure.

The authorized lock delta is limited to:

- root content-hash regeneration caused by the authorized root manifest;
- one new `kongono/php-cgi` local path-package record;
- its exact local dependencies already present in the lock; and
- Composer-generated path-reference refreshes for existing local Kongono
  packages, if Composer mechanically regenerates them while their manifests,
  versions, dependencies, dist/source metadata other than reference, and
  third-party packages remain unchanged.

No third-party package, version, constraint, plugin, advisory setting, or
platform requirement may change. Report every changed local reference. Stop on
any other lock delta.

### 7.4 Autoload proof

Run:

```text
composer dump-autoload --optimize --strict-psr
```

Require zero PSR-4 noncompliance and autoloadability of all nine package
production declarations and all three test classes.

---

## 8. Exact production declaration inventory and API status

Create exactly nine production declarations:

| Type | Kind | Status |
| --- | --- | --- |
| `Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic` | backed enum | Internal |
| `Kongono\PhpCgi\Exception\ExceptionInterface` | interface | Experimental |
| `Kongono\PhpCgi\Exception\InvalidCgiRequestException` | final class | Experimental |
| `Kongono\PhpCgi\Exception\CgiRequestReadException` | final class | Experimental |
| `Kongono\PhpCgi\Inbound\CgiServerRequestConverter` | final class | Experimental |
| `Kongono\PhpCgi\Internal\CgiRequestValidation` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiHeaderProjector` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiQueryParser` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiCookieParser` | final class | Internal |

Every type, including the marker, has exactly one `#[Api(...)]` attribute. The
marker is Experimental: callers may catch the package family, but Phase 3B
explicitly authorizes no new Public, Stable, or Deprecated API. The converter
and concrete failures are also Experimental.

No trait, alias, additional enum, interface, exception, value object, factory,
delivery, emitter, or helper declaration is authorized.

The four Internal helper classes:

- have no instance properties;
- cannot be instantiated (`private __construct()`);
- expose only minimal package-internal static operations needed by the
  converter;
- retain no cache or cross-call state; and
- are not extension points.

Their static helper method names and parameter decomposition are local
implementation details, not architecture or consumer API. The executor may
choose them within the exact responsibilities in Section 18. Do not document
them as supported calling surfaces or make them more visible than required.

`CgiServerRequestConverter`:

- is final and stateless;
- has no declared property;
- has no constructor;
- has no clone/destructor behavior;
- declares exactly one public method, `convert`; and
- creates no ambient/static/process-wide current request.

---

## 9. Converter calling contract

The exact public method is:

```php
/**
 * @param array<array-key, mixed> $server
 * @param array<array-key, mixed> $post
 * @param array<array-key, mixed> $files
 * @param resource                $input
 *
 * @throws InvalidCgiRequestException
 * @throws CgiRequestReadException
 * @throws \Throwable When external stream behavior or another unexpected
 *                    non-HTTP-Message failure throws.
 */
public function convert(
    string $sapi,
    array $server,
    array $post,
    array $files,
    mixed $input,
): ServerRequestInterface;
```

The implementation must return the first-party
`Kongono\Http\Message\Message\ServerRequest` behind the PSR interface.

The caller owns `$input`. The converter:

- validates that it is an attached readable PHP stream resource even when the
  declared body length is zero;
- reads from its current position;
- never rewinds, closes, detaches, or retains it;
- reads exactly the declared number of bytes and never one byte more;
- leaves it open at the position immediately after the declared bytes; and
- copies accepted body bytes into a fresh first-party `php://temp`-backed
  `Stream` owned by the returned request.

If conversion fails after a first-party body stream is created, close that
created stream before rethrowing. Never close the caller's input resource.

Arguments are detached by value before any accepted mutable array becomes
returned request state. Mutation of `$server`, `$post`, or `$files` after the
call cannot change the request or diagnostic.

Do not assign the caller's `$server` array wholesale. Before any stream
operation or first-party constructor can invoke external behavior, validate raw
key/value scalar shape and rebuild accepted string entries into a fresh array
by value so referenced input elements are not retained. Later mutation of the
caller array, an element held by reference, or an array returned by a request
getter cannot mutate converter or request state.

The converter does not implement `WebDeliveryInterface`. A future delivery's
`request()` method will call it so conversion failure reaches existing Web
Runtime generic-500 behavior. Phase 3C itself does not instantiate or call Web
Runtime or Web Application.

---

## 10. Admission and closed server snapshot

### 10.1 Exact admission

Require all three:

```text
$sapi === "cgi-fcgi"
GATEWAY_INTERFACE === "CGI/1.1"
FCGI_ROLE === "RESPONDER"
```

Reject missing, differently cased, padded, alternate, array, object, resource,
or non-string values.

### 10.2 Allowed keys

The converter accepts an already closed projection, not an entire raw
superglobal. Every key must be a string and must be exactly one of:

```text
GATEWAY_INTERFACE
FCGI_ROLE
REQUEST_METHOD
SERVER_PROTOCOL
REQUEST_URI
QUERY_STRING
HTTP_HOST
REMOTE_ADDR
CONTENT_LENGTH
CONTENT_TYPE
HTTPS
REMOTE_PORT
SERVER_ADDR
SERVER_NAME
SERVER_PORT
SERVER_SOFTWARE
SCRIPT_NAME
HTTP_*
```

Every value must be a string. Unknown keys, integer keys, malformed `HTTP_`
keys, and non-string values reject. `HTTP_*` key suffixes must be non-empty
uppercase ASCII letters, digits, or underscores. Reject any raw server key over
128 bytes before normalization.

Required keys are the first eight entries above through `REMOTE_ADDR`.
Optional keys may be absent.

### 10.3 Bounded optional scalar validation

These implementation bounds close Phase 3B's accepted “invalid values reject”
rule without widening an input source:

- `HTTPS` is absent/empty/`off` or exactly `on` as defined below;
- `REMOTE_PORT` and `SERVER_PORT`, when non-empty, are canonical unsigned
  decimal integers from 1 through 65535;
- required `REMOTE_ADDR` and optional `SERVER_ADDR`, when present, contain 1
  through 45 ASCII bytes and validate as an unbracketed textual IPv4 or IPv6
  address through local PHP IP validation; Phase 3C preserves the accepted text
  but does not use either value as trust evidence;
- `SERVER_NAME`, `SERVER_SOFTWARE`, and `SCRIPT_NAME`, when present, contain at
  most 8,192 bytes and no NUL, CR, LF, DEL, or ASCII control byte; and
- header-derived values follow the stricter header bounds in Section 12.

Do not perform DNS, filesystem, network, locale, IDNA, proxy, or environment
lookup. Do not infer absent values.

### 10.4 Canonical server-parameter result

`getServerParams()` contains only the validated closed projection, in this
deterministic order:

1. required keys in the order listed in Section 10.2;
2. present non-`HTTP_*` optional keys in the order listed in Section 10.2; and
3. remaining `HTTP_*` keys in ascending bytewise key order, excluding the
   already emitted required `HTTP_HOST`.

Values are preserved exactly. The snapshot is untrusted and non-authorizing.
Input insertion order cannot change the output order.

`$post` and `$files` must each be exactly empty. Inspect only emptiness; do not
traverse, retain, stringify, hash, count nested values, or interpret entries.
Any entry rejects as a profile violation.

---

## 11. Method, protocol, target, scheme, and authority

### 11.1 Method

`REQUEST_METHOD`:

- contains 1 through 32 bytes;
- is an uppercase ASCII HTTP token using only
  ``!#$%&'*+-.^_`|~0-9A-Z``; and
- is preserved exactly.

Empty, lowercase, non-ASCII, whitespace/control, non-token, or over-limit
methods reject. Do not select an allowed-method vocabulary.

### 11.2 Protocol

Map exactly:

| CGI value | PSR version |
| --- | --- |
| `HTTP/1.0` | `1.0` |
| `HTTP/1.1` | `1.1` |
| `HTTP/2` | `2` |
| `HTTP/2.0` | `2.0` |
| `HTTP/3` | `3` |
| `HTTP/3.0` | `3.0` |

Everything else rejects.

### 11.3 Request target and query agreement

`REQUEST_URI`:

- contains at most 8,192 bytes;
- is origin-form only;
- begins with `/`;
- contains no fragment, whitespace, control byte, DEL, or NUL;
- contains only valid `%HH` triplets where `%` occurs; and
- may contain one path plus an optional query beginning at the first `?`.

Reject absolute-form, authority-form, asterisk-form, scheme, authority,
userinfo, fragment, empty target, and malformed percent encoding.

The bytes after the first `?` must equal `QUERY_STRING` exactly. When no query
delimiter exists, `QUERY_STRING` must be empty. A present empty delimiter is
allowed and the exact raw request target, including a trailing `?`, is preserved
through `withRequestTarget()`.

`QUERY_STRING` contains at most 8,192 bytes.

### 11.4 Scheme

- `HTTPS === "on"` yields `https`.
- absent, empty, or `HTTPS === "off"` yields `http`.
- every other value rejects.

### 11.5 Authority

`HTTP_HOST` is mandatory and the sole authority source. Construct the URI from
the selected scheme, validated host, and origin-form target using the existing
first-party `Uri` implementation. First construct exactly
`$scheme . '://' . $httpHost . '/'`. Require the resulting URI to have the
selected scheme, a non-empty host, empty user information, path exactly `/`,
empty query, and empty fragment. This proves every supplied host byte was
consumed as authority rather than path, query, fragment, or user information.
Then apply the already-split raw path and query through first-party immutable
URI methods. Its RFC 3986 component encoding and default-port normalization are
the accepted URI representation; preserve the exact original raw target
separately with `withRequestTarget()`.

Reject empty authority, user information, fragment, invalid host, invalid port,
or any mismatch in that proof as `authority_invalid`.

Never fall back to `SERVER_NAME`, `SERVER_PORT`, forwarded headers, DNS,
filesystem state, or configuration.

---

## 12. Header projection

### 12.1 Deterministic order

Project request headers in this exact order:

1. `Host` from required `HTTP_HOST`;
2. `Content-Type` when `CONTENT_TYPE` is present and non-empty;
3. `Content-Length` when `CONTENT_LENGTH` is present and non-empty; and
4. remaining `HTTP_*` entries, excluding `HTTP_HOST`, in ascending bytewise
   source-key order.

Each source value becomes exactly one header value. Never comma-split.

### 12.2 Name conversion

For a remaining `HTTP_*` key:

1. remove `HTTP_`;
2. lowercase ASCII;
3. replace `_` with `-`; and
4. uppercase the first ASCII letter of every hyphen-delimited segment.

Validate the resulting field through the first-party HTTP Message contract.
Reject case-insensitive normalized-name duplicates.

### 12.3 Mandatory rejections

- `HTTP_CONTENT_TYPE` and `HTTP_CONTENT_LENGTH` reject as ambiguous.
- `HTTP_PROXY` rejects.
- projected `Transfer-Encoding` rejects.
- any invalid name/value, CR, LF, NUL, DEL, forbidden control, or duplicate
  normalized name rejects.

`Forwarded` and `X-Forwarded-*` remain ordinary untrusted fields. Phase 3C does
not calculate permission or trust from them.

### 12.4 Exact bounds

Before request construction, require:

```text
distinct request headers:              <= 128
one request header value bytes:        <= 8,192
total header name plus value bytes:    <= 65,536
```

Count each distinct projected name once and its single value once. Bounds are
byte counts, not character counts.

Missing/empty `CONTENT_LENGTH` means a zero-byte body and produces no
`Content-Length` header. Canonical present `0` produces `Content-Length: 0`.

---

## 13. Query projection

`CgiQueryParser` implements only:

- empty query -> `[]`;
- otherwise split only on `&`;
- 1 through 128 non-empty fields;
- `name` or `name=value`, with only the first `=` separating;
- `+` -> space;
- valid `%HH` -> decoded byte;
- decoded name non-empty valid UTF-8, at most 128 bytes, with no NUL, DEL,
  control byte, `[` or `]`;
- decoded value valid UTF-8, at most 4,096 bytes, with no NUL, DEL, or control
  byte;
- duplicate decoded names reject; and
- result is a detached declaration-ordered `array<string, string>`.

Reject empty fields, malformed percent triplets, bracket/nesting syntax,
invalid UTF-8, duplicate decoded names, excess pairs, and bound violations.

Do not call `parse_str`, `urldecode`, locale conversion, form parsing, or nested
array logic. A small explicit percent decoder is required. It must not decode
more than one layer.

---

## 14. Cookie projection

`CgiCookieParser` reads only the single projected `Cookie` value:

- absent or empty -> `[]`;
- raw value <= 4,096 bytes;
- split only on `;`;
- remove at most one ASCII space immediately after a separator;
- 1 through 64 non-empty `name=value` pairs;
- only the first `=` separates;
- names are non-empty ASCII HTTP tokens, at most 128 bytes;
- values are at most 1,024 bytes and visible ASCII excluding semicolon, comma,
  backslash, double quote, DEL, and control bytes;
- duplicate names reject; and
- result is a detached declaration-ordered `array<string, string>`.

Do not percent-decode, unquote, unescape, trim arbitrary whitespace, parse
attributes, or use `$_COOKIE`.

---

## 15. Body, forms, uploads, and resource ownership

### 15.1 Content length

Missing or empty `CONTENT_LENGTH` means exactly zero.

A present non-empty value must be canonical unsigned decimal:

- no sign;
- no whitespace;
- no leading zero except the single value `0`;
- no non-digit;
- numeric value from 0 through 1,048,576 inclusive.

Reject before body allocation when invalid or over limit.

### 15.2 Input resource

`$input` must be an attached PHP stream resource with a readable mode. Reject a
closed resource, non-stream resource, object, scalar, array, null, or unreadable
stream through `CgiRequestReadException`.

Classify `get_debug_type($input) === "resource (closed)"` as `input_closed`.
Classify every other non-resource or non-stream resource as `input_not_stream`.
If suppressed `stream_get_meta_data()` does not yield the expected stream mode
metadata, use `input_metadata_failed`; a mode that is not readable uses
`input_unreadable`.

Read in bounded chunks no larger than 8,192 bytes, never requesting beyond the
remaining declared length. A false read, zero-byte read before the declared
length is satisfied, early EOF, or other returned inability to obtain the exact
declared bytes throws `CgiRequestReadException`.

Use error-suppressed package-owned stream operations and inspect their return
values. Do not install, replace, invoke, or retain a process-global error
handler. If an already-installed external handler throws from a stream
operation, propagate that `Throwable` unchanged after closing only any
package-owned temporary body stream; never close the caller input.

Opening the package-owned `php://temp` resource, writing the copied body, and
rewinding it must also be checked. Open failure, false or short write, or rewind
failure throws `CgiRequestReadException` with the exact token in Section 17.1.
No custom stream wrapper or process-global test hook is authorized.

Ownership transfers to the package at the instant the temporary resource opens.
On any later failure, close exactly that raw resource or its first-party `Stream`
wrapper once, whichever currently owns it. On success, the returned request owns
the wrapper. No temporary body resource may leak.

### 15.3 Returned body

Copy the exact accepted bytes into a new first-party readable/seekable body
stream positioned at byte zero. `parsedBody` remains `null` and uploaded files
remain `[]`.

`multipart/form-data`, case-insensitively parsed after trimming only ASCII space
and horizontal tab around the media type before the first `;`, rejects.
`application/x-www-form-urlencoded` remains raw and is not parsed.

Do not parse JSON, forms, MIME, files, content encoding, transfer encoding, or
character sets. Do not decompress or deserialize.

---

## 16. Result construction and determinism

The returned request must have:

- exact method;
- exact raw request target;
- selected PSR protocol version;
- URI scheme and authority from Sections 11.4-11.5;
- URI path/query from the validated target;
- headers in Section 12 order;
- server parameters in Section 10.4 order;
- flat query parameters from Section 13;
- flat cookie parameters from Section 14;
- fresh first-party body stream at position zero;
- `parsedBody === null`;
- uploaded files `[]`; and
- attributes `[]`.

Equivalent snapshots with different input-array insertion order produce
equivalent request values and identical deterministic header/server-param order.

Every `convert()` call returns a fresh request, URI, body stream, and detached
arrays. No request or failure from one call changes another call.

Validation and failure selection are deterministic and follow this order:

1. host-profile admission;
2. server key shape/allowlist, required keys, string values, and optional scalar
   validation;
3. nonempty post data, then nonempty upload data;
4. method;
5. protocol, target/query agreement, scheme, and authority;
6. content length and multipart classification;
7. deterministic header projection;
8. query parsing;
9. cookie parsing;
10. first-party URI construction as final scheme/authority/path/query
    validation;
11. input-resource validation, exact body read, and package-owned body stream;
12. first-party stream wrapper and server-request construction.

Within server-snapshot validation, use the rule order shown in the exact token
vocabulary, required-key order from Section 10.2, optional-key order from
Section 10.2, and bytewise source-key order for remaining `HTTP_*` fields.
Within every later category, use the written rule order and deterministic source
order. Equivalent invalid snapshots with different insertion order must select
the same diagnostic and violation token.

---

## 17. Diagnostics and exceptions

### 17.1 Diagnostic inventory

`PhpCgiDiagnostic` is a string-backed Internal enum with exactly:

```text
InvalidHostProfile          KGN-PHP-CGI-1001
InvalidServerSnapshot       KGN-PHP-CGI-1002
InvalidRequestMethod        KGN-PHP-CGI-1003
InvalidRequestTarget        KGN-PHP-CGI-1004
InvalidRequestHeaders       KGN-PHP-CGI-1005
InvalidQueryParameters      KGN-PHP-CGI-1006
InvalidCookieParameters     KGN-PHP-CGI-1007
InvalidRequestBody          KGN-PHP-CGI-1008
RequestConstructionFailed   KGN-PHP-CGI-1009
InputReadFailed             KGN-PHP-CGI-1010
```

Each case provides the following exact package-owned text:

| Case | Title | Remediation | Operation | Violated invariant |
| --- | --- | --- | --- | --- |
| `InvalidHostProfile` | `Unsupported PHP CGI host profile` | `Run only under PHP cgi-fcgi with CGI/1.1 in the FastCGI RESPONDER role` | `Admit PHP CGI request` | `The host must match the selected cgi-fcgi CGI/1.1 RESPONDER profile` |
| `InvalidServerSnapshot` | `Invalid CGI server snapshot` | `Supply only the required bounded string CGI fields and authorized optional fields` | `Validate CGI server snapshot` | `Server parameters must match the closed bounded profile projection` |
| `InvalidRequestMethod` | `Invalid CGI request method` | `Supply an uppercase ASCII HTTP token no longer than 32 bytes` | `Validate CGI request method` | `The request method must use the bounded uppercase HTTP token grammar` |
| `InvalidRequestTarget` | `Invalid CGI request target` | `Supply a bounded origin-form target, matching query string, supported protocol, and valid host` | `Construct CGI request target` | `Protocol, target, scheme, authority, and query must satisfy the selected profile` |
| `InvalidRequestHeaders` | `Invalid CGI request headers` | `Supply unique bounded CGI header fields without ambiguous, proxy, or transfer-encoding entries` | `Project CGI request headers` | `Projected request headers must be unique, bounded, and safe for the first-party message` |
| `InvalidQueryParameters` | `Invalid CGI query parameters` | `Supply a bounded flat query with valid UTF-8 names, values, and percent encoding` | `Parse CGI query parameters` | `Query parameters must satisfy the bounded flat unambiguous grammar` |
| `InvalidCookieParameters` | `Invalid CGI cookie parameters` | `Supply a bounded flat Cookie field with unique token names and visible values` | `Parse CGI cookie parameters` | `Cookie parameters must satisfy the bounded flat unambiguous grammar` |
| `InvalidRequestBody` | `Invalid CGI request body` | `Supply an allowed media type and canonical body length no greater than 1048576 bytes` | `Validate CGI request body` | `Body metadata, form state, and upload state must satisfy the selected profile` |
| `RequestConstructionFailed` | `Unable to construct CGI server request` | `Correct the validated request data or first-party HTTP Message incompatibility before dispatch` | `Construct first-party server request` | `Validated CGI values must construct one first-party PSR-7 server request` |
| `InputReadFailed` | `Unable to read CGI request input` | `Supply an attached readable input stream containing exactly the declared body length` | `Read CGI request input` | `The caller input must yield exactly the bounded declared body bytes` |

Each context schema has exactly one required field:

```text
key:         violation
label:       Violation
kind:        text
disclosure:  Safe
required:    true
```

`violation` is always one of this exact closed vocabulary:

| Diagnostic | Allowed violation tokens |
| --- | --- |
| `InvalidHostProfile` | `sapi_mismatch`, `gateway_interface_mismatch`, `fastcgi_role_mismatch` |
| `InvalidServerSnapshot` | `server_key_not_string`, `server_key_too_long`, `server_key_not_allowed`, `http_key_suffix_invalid`, `required_server_key_missing`, `server_value_not_string`, `remote_address_invalid`, `remote_port_invalid`, `server_address_invalid`, `server_name_invalid`, `server_port_invalid`, `server_software_invalid`, `script_name_invalid` |
| `InvalidRequestMethod` | `method_empty`, `method_too_long`, `method_not_uppercase_token` |
| `InvalidRequestTarget` | `protocol_unsupported`, `target_empty`, `target_too_long`, `target_not_origin_form`, `target_fragment_forbidden`, `target_unsafe_byte`, `target_percent_invalid`, `query_string_too_long`, `query_string_mismatch`, `https_value_invalid`, `authority_empty`, `authority_invalid` |
| `InvalidRequestHeaders` | `content_type_ambiguous`, `content_length_ambiguous`, `proxy_header_forbidden`, `transfer_encoding_forbidden`, `header_name_invalid`, `header_value_invalid`, `header_name_duplicate`, `header_count_exceeded`, `header_value_too_long`, `header_bytes_exceeded` |
| `InvalidQueryParameters` | `query_field_empty`, `query_pair_count_exceeded`, `query_percent_invalid`, `query_key_empty`, `query_key_too_long`, `query_key_utf8_invalid`, `query_key_unsafe`, `query_nesting_forbidden`, `query_value_too_long`, `query_value_utf8_invalid`, `query_value_unsafe`, `query_key_duplicate` |
| `InvalidCookieParameters` | `raw_cookie_too_long`, `cookie_pair_empty`, `cookie_pair_count_exceeded`, `cookie_pair_missing_equals`, `cookie_name_empty`, `cookie_name_too_long`, `cookie_name_invalid`, `cookie_value_too_long`, `cookie_value_invalid`, `cookie_name_duplicate` |
| `InvalidRequestBody` | `post_data_present`, `upload_data_present`, `content_length_invalid`, `body_too_large`, `multipart_unsupported` |
| `RequestConstructionFailed` | `first_party_message_rejected` |
| `InputReadFailed` | `input_not_stream`, `input_closed`, `input_metadata_failed`, `input_unreadable`, `input_read_failed`, `input_early_eof`, `body_stream_open_failed`, `body_stream_write_failed`, `body_stream_rewind_failed` |

No other token is allowed. The enum declares exactly these public methods:

```php
public function context(string $violation): DiagnosticContext;

/** @return list<DiagnosticFieldSchema> */
public function contextFields(): array;

public function contextOperation(): string;
public function contextViolatedInvariant(): string;
public function title(): string;
public function remediation(): string;

/** @return list<string> */
public function violationTokens(): array;
```

`context()` validates membership against the current case with strict string
comparison before creating a context. An impossible package-programming token
throws native `LogicException` with exactly:

```text
Unsupported PHP CGI diagnostic violation token.
```

The token never contains a raw input, host message, class name, path, method,
target, host, header, query/cookie value, body, environment value, resource ID,
credential, secret, exception message, or trace.

### 17.2 Marker

`ExceptionInterface extends Throwable` and declares exactly:

```php
public function diagnosticContext(): DiagnosticContext;
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

It must remain distinct from every existing package exception marker.

### 17.3 Invalid request exception

`InvalidCgiRequestException`:

- extends native `InvalidArgumentException`;
- implements the package marker;
- is final Experimental;
- stores exactly private readonly properties `$diagnostic` and
  `$diagnosticContext`;
- accepts `(PhpCgiDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)`;
- rejects `InputReadFailed` through native `LogicException` with exactly
  `Unsupported PHP CGI invalid-request diagnostic.`;
- obtains the eager detached context only through the enum's closed-token
  `context()` method;
- sets the native message to the fixed diagnostic title; and
- leaves native code at `0`, preserves the exact previous object, and returns
  exact diagnostic metadata.

Use it for diagnostics 1001 through 1009 only.

### 17.4 Read exception

`CgiRequestReadException`:

- extends native `RuntimeException`;
- implements the package marker;
- is final Experimental;
- stores the same two exact private readonly properties;
- accepts `(string $violation, ?Throwable $previous = null)`;
- hard-codes `InputReadFailed` and validates the token through its `context()`;
- leaves native code at `0`, preserves the exact previous object; and
- never includes a PHP warning or host-provided message in message/context.

Both exceptions declare only their constructor and the four final public marker
methods. They add no getter for the enum or violation token and no mutable
property.

### 17.5 Construction failures

All package-owned grammar validation precedes body reading. Construct the
first-party `Uri` before body reading as the final authority/URI validator. A
first-party HTTP Message package-marker exception from that URI step becomes
`InvalidRequestTarget` with `authority_invalid` and preserves the exact previous
object.

After the exact body is copied, a first-party HTTP Message package-marker
exception from body-stream wrapping, `ServerRequest` construction, or the
authorized immutable `with*` calls becomes `RequestConstructionFailed` with
`first_party_message_rejected`, preserves the exact previous object, and closes
the package-owned body resource/wrapper exactly once. Catch no other arbitrary
`Throwable` merely to change its type. The concrete converter method PHPDoc
must retain the exact generic `@throws \Throwable` line in Section 9 so unchanged
external failures are not hidden from callers or PHPStan.

Do not test raw-value absence against native exception trace stringification;
PHP may capture call arguments. Test package-owned message, diagnostic context,
properties, previous chain, and returned request surfaces.

---

## 18. Internal helper responsibilities

### `CgiRequestValidation`

Own profile admission, closed snapshot detachment/order, optional scalar
validation, method, protocol map, target/query agreement, HTTPS scheme,
authority/URI validation, content-length grammar, content-type classification,
and exact body read.

### `CgiHeaderProjector`

Own deterministic header source ordering, name conversion, ambiguity/proxy/
transfer-encoding rejection, case-insensitive uniqueness, and all header
count/byte limits.

### `CgiQueryParser`

Own only Section 13's flat query grammar and decoder.

### `CgiCookieParser`

Own only Section 14's flat cookie grammar.

Helpers may call one another only where responsibility requires. They expose no
generic parser framework, extension hooks, callbacks, registries, policies,
configuration, or public constants beyond exact private implementation
constants for locked limits.

---

## 19. Test contract

Create exactly the three test classes listed in Section 6.1. Tests use only
ordinary temporary PHP stream resources opened inside test methods/helpers and
closed in `finally`. No global stream wrapper, environment mutation, SAPI
mutation, superglobal mutation, subprocess, network, filesystem fixture, or
production emitter is allowed.

### 19.1 Diagnostic tests

Prove:

- exact ten cases, values, titles, remediations, operations, invariants;
- exactly one required Safe text `violation` field per schema;
- context detachment and canonical field order;
- no raw-value-bearing schema field; and
- Internal status.

### 19.2 Exception tests

Prove:

- native parents and package marker;
- marker separation from every existing package exception marker;
- exact eager same-object context behavior;
- fixed message/title/remediation/ID;
- exact previous preservation where authorized;
- invalid vs read diagnostic category use;
- exact Experimental statuses; and
- raw sentinels absent from package-owned message/context/property surfaces.

### 19.3 Converter success tests

At minimum prove:

- minimal GET;
- all six protocol mappings;
- `http` and exact `https` selection;
- host/port/IPv4/IPv6 authority through first-party URI behavior;
- exact raw target including empty trailing query delimiter;
- flat ordered query decoding and cookie parsing;
- canonical header projection and order;
- canonical server-param order independent of input insertion order;
- `CONTENT_TYPE` and canonical `CONTENT_LENGTH` behavior;
- zero body, boundary body of 1,048,576 bytes, and raw form body;
- readable/seekable first-party body positioned at zero;
- caller input remains open and advances exactly declared bytes;
- extra bytes beyond declared length remain unread;
- caller array mutation cannot change request state;
- mutation of a referenced server element or any getter-returned array cannot
  change request state;
- fresh objects/streams on repeated calls; and
- no retained state across success then failure then success.

### 19.4 Converter rejection tests

Use focused data providers and boundary-minus-one/boundary/boundary-plus-one
where meaningful. Prove every rule in Sections 10-15, including:

- all admission mismatches;
- missing/unknown/integer/non-string/malformed server entries;
- nonempty `$post` and `$files` without traversing their nested sentinels;
- method grammar and 32-byte boundary;
- unsupported protocols;
- target/query length and agreement, form, fragment, control, percent errors;
- HTTPS values and authority rejection;
- header count/value/total limits, unsafe values, ambiguity, proxy, duplicate,
  transfer encoding;
- query pair/key/value bounds, malformed decoding, UTF-8, controls, brackets,
  duplicates, empty fields;
- cookie raw/pair/name/value bounds, syntax, whitespace, forbidden bytes,
  duplicates;
- length grammar and 1,048,576 boundary;
- multipart media type;
- closed, non-resource, unreadable, and early-EOF inputs; and
- exact diagnostic case and closed violation token for every category.

### 19.5 Disclosure proof

Use unique sentinels in rejected method, target, host, header, query, cookie,
body-adjacent input, post, and file values. Prove absence from:

- exception message;
- diagnostic ID/title/remediation;
- diagnostic operation/invariant;
- every diagnostic field key/label/value; and
- every declared exception property reachable through reflection.

Do not stringify the native trace and do not weaken the package disclosure
boundary.

---

## 20. Root architecture enforcement

Update `tests/Architecture/ApiStatusConventionTest.php` so it proves:

- 14 package source directories and 14 package manifests;
- exactly 109 production declarations;
- exact status for all nine new declarations;
- every declaration autoloads and has the authorized status;
- marker separation from all existing markers;
- exact converter and exception reflection shapes plus the helper structural
  constraints from Sections 8 and 18;
- `packages/php-cgi/src` contains exactly nine PHP files;
- package production source contains none of:
  `Kongono\ApplicationGraph`, `Kongono\DependencyInjection`,
  `Kongono\DiagnosticActivation`, `Kongono\DiagnosticHandler`,
  `Kongono\DiagnosticPresentation`, `Kongono\Http\Server`,
  `Kongono\Kernel`, `Kongono\Routing`, `Kongono\Web\Application`,
  `Kongono\Web\Runtime`, or `Psr\Http\Server`;
- package production source does not access `$GLOBALS`, `PHP_SAPI`,
  `php_sapi_name`, `$_SERVER`, `$_ENV`, `$_REQUEST`, `$_GET`, `$_POST`,
  `$_COOKIE`, `$_FILES`, `getenv`, `putenv`, `php://input`, `php://output`,
  `header`, `headers_sent`, `http_response_code`, `exit`, or `die`;
- no `WebDeliveryInterface`, send, emission, listener, FastCGI parser, socket,
  process manager, operator config, trust evaluation, DI, Graph, or Kernel
  integration exists;
- package manifest dependency set is exact;
- root Composer/autoload/validation and all three tooling configs include the
  new package paths exactly once; and
- existing architecture assertions remain intact.

Update count literals only where the exact new inventory requires it. Do not
refactor the architecture test or weaken an existing assertion.

---

## 21. Tooling expansion

Add exactly:

- `packages/php-cgi/src` and `packages/php-cgi/tests` to PHP CS Fixer;
- the same two paths to PHPStan;
- package tests to the PHPUnit suite;
- package source to PHPUnit source coverage; and
- the root dev-autoload mapping and package validator in Composer.

Preserve PHPStan level max, strict rules, the root `analyse` 512M child command,
normal PHP 128M, PHPUnit strictness, style rules, and every existing path.

Expected final inventories before report population:

```text
package directories:      14
package manifests:        14
production PHP files:     109
test PHP files:           133
style-covered PHP files:  242
tracked files:            536
```

The Git tracked-file count remains 536 because staging/commit is prohibited;
the new files are untracked caller-visible worktree paths.

---

## 22. ADR and documentation

### 22.1 ADR 0053

Create `docs/adr/0053-bounded-php-cgi-inbound-conversion.md` with:

```text
Status: Accepted
Date: 2026-08-21
```

Record:

- the exact Phase 3C package and dependency subset;
- converter signature and Experimental status;
- explicit host snapshot rather than ambient/global access;
- all conversion grammar, ordering, bounds, resource ownership, diagnostics,
  and first-party request guarantees in this directive;
- why conversion failure is later deferred through delivery `request()`;
- why raw input stays generic 500 under current Web Runtime;
- performance/memory consequences of bounded full request buffering;
- rejected direct superglobal access, PSR adapter abstraction, form/upload
  parsing, trust evaluation, emission, and later-phase work; and
- Phase 3D as the only next phase.

Do not modify ADR 0052.

### 22.2 Package README

Document purpose, explicit calling contract, exact inputs/outputs, supported and
unsupported syntax, security/disclosure, body resource ownership, failure,
performance, persistent-runtime/repeated-call behavior, status, package
dependencies, and later-phase exclusions. Make clear that no production entry
or Web Runtime delivery exists yet.

### 22.3 Current-document reconciliation

Update only the authorized current documents so they consistently say:

- Phase 3C and ADR 0053 implement the first `kongono/php-cgi` runtime slice;
- the package now exists with bounded explicit inbound conversion only;
- no package code reads SAPI constants, superglobals, environment, or
  `php://input` directly;
- it returns a first-party server request from caller-supplied host values;
- malformed input still becomes generic 500 only when a future delivery calls
  the converter from `request()`;
- no response emission, concrete delivery, operator profile, trust composition,
  production entry, deployment, or external acceptance exists;
- current direct dependencies are API Metadata, Diagnostics, HTTP Message,
  PSR-7, and PHP; Web Runtime/Diagnostic Activation edges remain later;
- Phase 3D is next and only response emission is authorized there; and
- Phase 3 remains neither full framework, Stable API, 1.0, production-ready,
  nor production-hardening certification.

Update the Phase 3 roadmap status:

```text
3C Complete: bounded inbound conversion implemented.
3D Next: bounded response staging/emission only.
```

Preserve the fixed A-H boundary table byte-for-byte. Only the status/current-
versus-planned prose outside that table changes.

No document may claim that Phase 3C reads the real process environment or can
serve a request through PHP CGI in production.

---

## 23. Validation workflow

### Gate A - authority and baseline

1. Authenticate this directive independently.
2. Read every authority completely.
3. Verify exact Git identity, status inventories, refs, caller records, hashes,
   modes, ordinary-file/default-stream state, report placeholder, counts, and
   environment.
4. Run pre-change `composer quality` at exact baseline totals.

Stop on any mismatch.

### Gate B - package/manifests/tooling

1. Create the package manifest and exact file tree.
2. Modify root Composer and tooling only as authorized.
3. Run the exact Composer update command.
4. Inspect the complete semantic lock delta.
5. Run root/package Composer validation and strict optimized autoload.
6. Verify package/test/production/style counts.

Stop on any unauthorized lock or inventory delta.

### Gate C - implementation and focused tests

Run at minimum:

```text
php -l <each of the twelve new PHP files>
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

Require zero syntax, PHPStan, style, PHPUnit warning, risky, deprecation, or
test failure. Composer validation warnings remain limited to the authorized
categories in Section 7.1.

### Gate D - semantic inspection

Prove directly:

- exact declaration/status/file inventory;
- exact manifest dependency graph;
- exact converter signature and reflection shape;
- every Section 10-19 behavior and boundary;
- no raw sentinel in package-owned surfaces;
- no caller input closure or over-read;
- deterministic order and repeated-call isolation;
- no globals/SAPI/environment/emission/delivery/trust/later-phase behavior;
- exact diagnostic IDs and closed schema;
- strict PSR-4 autoload;
- fixed A-H table byte identity;
- current/future documentation consistency;
- local Markdown links;
- UTF-8 without BOM, LF-only, one final LF, no trailing whitespace/NUL;
- ordinary non-reparse files with default streams;
- all tracked modes remain `100644`; and
- `git diff --check` passes.

### Gate E - canonical quality

Run literal:

```text
composer quality
```

Require:

```text
locked audit:          zero advisories
platform requirements: pass
PHP CS Fixer:          0 of 242 files fixable
PHPStan:               zero errors
PHPUnit tests:         greater than 480
PHPUnit assertions:    greater than 8,575
```

Report exact final test and assertion totals. One identical approved-access
retry is allowed only for managed network/cache failure.

### Gate F - final preservation and report

Before report population require exactly:

```text
modified tracked paths: 21
untracked paths:         28
total status paths:      49
staged paths:            0
Git-visible paths:       564
tracked files:           536
```

The twenty-one modified tracked paths must be the exact sixteen entry paths
plus:

```text
.php-cs-fixer.dist.php
composer.lock
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

The twenty-eight untracked paths must be the thirteen entry paths plus the
fifteen created paths in Section 6.1.

Require unchanged HEAD/tree/parent/count, zero staged paths, unchanged index,
one branch, no remotes/tags, no unexpected refs, and no unauthorized path
change.

Only then populate `docs/task reports/Phase_3C.md`. Recheck both report identity
and final repository state.

---

## 24. Completion report contract

Use exactly:

```text
# PHASE 3C TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND API CONTRACT
## 8. INBOUND CONVERSION CONTRACT
## 9. DIAGNOSTICS AND DISCLOSURE
## 10. RESOURCE OWNERSHIP AND ISOLATION
## 11. ARCHITECTURAL DECISIONS
## 12. DEVIATIONS FROM DIRECTIVE
## 13. DEPENDENCIES ADDED/REMOVED
## 14. ENVIRONMENT
## 15. COMMANDS RUN
## 16. VALIDATION RESULTS
## 17. DOCUMENTATION REVIEW
## 18. SECURITY AND FILE-SAFETY REVIEW
## 19. WARNINGS / KNOWN ISSUES
## 20. QUESTIONS / BLOCKERS
## 21. FINAL GIT STATUS
## 22. ACCEPTANCE CRITERIA
## 23. NEXT STEP
```

Report:

- exact directive identity and caller identities;
- exact entry/final Git/path/count evidence;
- every created and modified path;
- exact lock delta including every local reference refresh;
- exact API/status/diagnostic inventory;
- exact focused and canonical test/assertion totals;
- resource ownership, over-read, disclosure, and isolation results;
- every retained focused-search match and why valid;
- report byte count and SHA-256 outside the report body; and
- no staging, commit, Phase 3D, or later work.

The exact final sentence is:

```text
Await architect review before Phase 3D.
```

---

## 25. Strict acceptance criteria

Phase 3C is Complete only when all are true:

1. independent directive identity matches;
2. all authorities are read completely;
3. exact caller records and `16/13/29/0` entry state match;
4. pre-change canonical quality passes at 480 tests / 8,575 assertions;
5. exactly fifteen paths are created and eighteen existing paths authorized;
6. no unauthorized path changes;
7. package manifest, root manifest, lockfile, and dependency graph are exact;
8. no third-party package/version/policy changes;
9. strict optimized PSR-4 autoload passes;
10. exactly nine production declarations and three test classes exist;
11. API statuses and reflection shapes are exact;
12. converter signature and first-party return type are exact;
13. admission and closed snapshot rules are exact;
14. method/protocol/target/scheme/authority behavior is exact;
15. header/query/cookie conversion and bounds are exact;
16. body length/read/form/upload behavior and bounds are exact;
17. caller input remains open and is never over-read;
18. returned body and arrays are fresh, detached, and deterministic;
19. diagnostic IDs, marker, native parents, context, and disclosure are exact;
20. every required rejection maps to the correct package diagnostic;
21. repeated-call isolation passes;
22. root architecture enforcement passes;
23. no ambient input, emission, delivery, trust, Web Runtime call, Kernel scope,
    listener, FastCGI parser, configuration, or later capability exists;
24. ADR 0053 and package/current docs are complete and consistent;
25. fixed A-H roadmap table is byte-identical;
26. links, encoding, whitespace, modes, streams, and file safety pass;
27. focused PHPStan/style/PHPUnit and canonical quality pass;
28. normal PHP remains 128M with no INI overlay;
29. final state is exactly `21/28/49/0`, 564 visible, 536 tracked, unchanged
    history/index; and
30. the report contract and independent digest handoff pass.

Any failed criterion means status cannot be Complete.

---

## 26. Final instruction

Implement Phase 3C only. Do not emit a response, implement a production
delivery, read ambient host state, calculate trust, add operator configuration,
compose Web Runtime, create an entrypoint, or begin Phase 3D.

When complete, return the report plus its byte count and SHA-256 and await
architect review.
