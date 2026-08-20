# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2C

## First-Party PSR-7 HTTP Messages and PSR-17 Server Factories

**Directive type:** bounded implementation, tests, tooling, and documentation

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Phase:** 2C

**Predecessor:** accepted Phase 2B.2

**Next phase:** 2D, only after architect acceptance of this report

---

## 1. Mission

Phase 2C implements Kongono's first-party, bounded server-side HTTP message
foundation against the interoperability contracts accepted in ADR 0044.

This phase must:

1. create the exact `kongono/http-message` monorepo package;
2. install only `psr/http-message:^2.0` and `psr/http-factory:^1.1` as new
   third-party interface dependencies;
3. implement Kongono-owned `ServerRequestInterface`, `ResponseInterface`,
   `UriInterface`, `StreamInterface`, and `UploadedFileInterface` values;
4. implement the five selected server-side PSR-17 factories;
5. enforce PSR-7 copy-on-change behavior while keeping the stateful stream
   caveat explicit;
6. reject structurally invalid headers, URI components, message arguments,
   streams, and upload operations with package-local actionable diagnostics;
7. test the complete selected public surface, important interoperability
   decision tables, shallow immutability, failure categories, and package
   boundaries;
8. expand Composer autoloading, lock data, PHPStan, PHPUnit, PHP CS Fixer, and
   root architecture enforcement for exactly this package; and
9. reconcile documentation from "planned in 2C" to "implemented in 2C."

The intended result is narrow:

> Kongono owns a small inspectable PSR-7/PSR-17 message implementation. PHP-FIG
> owns only the interoperability interfaces. No other framework owns Kongono's
> message model or runtime.

This phase does **not** create a runnable web application. It must not implement
routing, PSR-15, middleware, a composition root, an HTTP adapter, SAPI parsing,
response emission, or Kernel integration.

No broader inference is authorized.

---

## 2. Authority, authentication, and mandatory stop rule

### 2.1 Required reading

Before broad validation or mutation, read completely:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `README.md`;
4. `SECURITY.md`;
5. `CONTRIBUTING.md`;
6. `docs/doctrine.md`;
7. `docs/documentation-standard.md`;
8. `docs/engineering/api-status.md`;
9. `docs/engineering/error-handling.md`;
10. `docs/engineering/package-layout.md`;
11. `docs/architecture/overview.md`;
12. `docs/architecture/runtime-model.md`;
13. `docs/architecture/phase-2-roadmap.md`;
14. `docs/architecture/http-message-model.md`;
15. `docs/adr/0001-record-architecture-decisions.md`;
16. `docs/adr/0003-monorepo-package-boundaries.md`;
17. `docs/adr/0004-microkernel-capability-architecture.md`;
18. `docs/adr/0006-persistent-runtime-first.md`;
19. `docs/adr/0013-error-handling-and-actionable-diagnostics.md`;
20. `docs/adr/0014-api-stability-and-versioning.md`;
21. `docs/adr/0016-package-dependency-direction-and-composition-root.md`;
22. `docs/adr/0026-actionable-diagnostic-runtime-metadata.md`;
23. `docs/adr/0028-presentation-neutral-diagnostic-context.md`;
24. `docs/adr/0031-neutral-api-metadata-package-boundary.md`;
25. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`;
26. `docs/adr/0044-http-message-and-interoperability-architecture.md`;
27. `docs/dev_plan/kongono_phase_2B_codex_directive.md`;
28. `docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md`;
29. `docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md`;
30. `docs/task reports/Phase_2B.md`;
31. `docs/task reports/Phase_2B_1_correction.md`; and
32. `docs/task reports/Phase_2B_2_correction.md`.

Use the installed PSR interfaces after Composer resolution as the exact PHP
signature authority. Use these primary standards as the semantic authority:

- `https://www.php-fig.org/psr/psr-7/`
- `https://www.php-fig.org/psr/psr-17/`
- `https://www.rfc-editor.org/rfc/rfc3986.html`
- `https://www.rfc-editor.org/rfc/rfc9110.html`
- `https://www.rfc-editor.org/rfc/rfc9112.html`

If a statement in this directive appears to conflict with an installed
interface signature or a normative MUST in PSR-7/PSR-17, stop and report the
exact conflict. Do not silently reinterpret either authority.

### 2.2 Independent directive identity

The architect supplies this directive's byte count and uppercase SHA-256
outside this file. Compare those independently supplied values with the local
copy before `composer quality`, mutation, package creation, or dependency
resolution.

The local file must not authenticate itself. If the independently supplied
values are missing or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.3 Caller-managed report placeholder

Before execution, the caller creates exactly:

```text
docs/task reports/Phase_2C.md
```

It must initially be an ordinary, untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Do not populate it until every permitted
non-report gate passes.

### 2.4 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, failed mandatory check,
unapproved dependency or lock delta, protected-file change, new warning
category, or conflict between authorities:

1. stop immediately;
2. preserve the worktree and index exactly as observed;
3. do not repair, normalize, restore, reset, stage, commit, amend, clean, or
   widen scope;
4. leave `Phase_2C.md` zero bytes unless Section 16 authorizes population; and
5. report expected and observed evidence, viable options, and one
   recommendation.

No undocumented architectural choice is authorized. Ordinary private method
decomposition inside an authorized production file is an implementation detail,
not an architectural choice, provided it does not change this directive's
observable contract or add a declaration.

---

## 3. Locked accepted starting state

### 3.1 Git and repository identity before Phase 2C caller setup

| Item | Required value |
|---|---|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `d7846f137a1d53f5306f48d19196b916fde44ac2` |
| `HEAD` tree | `bfa1908c619069b699bc9b08668db17ea2555eb3` |
| `HEAD` parent | `8fe793b124384744ac43887037360154c149241a` |
| Commit count | `9` |
| Remotes | `0` |
| Tags | `0` |
| Staged paths | `0` |
| Modified tracked paths | `11` |
| Untracked paths with `-uall` | `16` |
| Total status paths with `-uall` | `27` |
| Tracked diff | `11 files, 312 insertions, 4 deletions` |
| Binary tracked-diff Git object ID | `b19c5a07271c58a78575894aa569f80ff2db0f46` |
| Package directories | `8` |
| Package manifests | `8` |
| Composer manifests including root | `9` |
| Production PHP files below package `src/` directories | `53` |
| Test/fixture PHP files below package `tests/` plus root `tests/` | `85` |

The only local ref must be `refs/heads/main`. No remote or tag may appear.

After the caller copies this directive and creates the report placeholder, the
expected pre-implementation status is exactly:

```text
11 modified tracked
18 untracked with -uall
29 total status paths
0 staged
```

The two added caller paths are:

```text
docs/dev_plan/kongono_phase_2C_codex_directive.md
docs/task reports/Phase_2C.md
```

### 3.2 Exact accepted pre-Phase-2C status paths

Before the two Phase 2C caller records are added, the 27 accepted paths are:

```text
 M ARCHITECTURE.md
 M README.md
 M SECURITY.md
 M docs/adr/0004-microkernel-capability-architecture.md
 M docs/adr/README.md
 M docs/architecture/overview.md
 M docs/architecture/runtime-model.md
 M docs/doctrine.md
 M docs/engineering/error-handling.md
 M docs/engineering/package-layout.md
 M docs/glossary.md
?? docs/adr/0043-phase-2-runnable-web-framework-milestone.md
?? docs/adr/0044-http-message-and-interoperability-architecture.md
?? docs/architecture/http-message-model.md
?? docs/architecture/phase-2-roadmap.md
?? docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_1I_codex_directive.md
?? docs/dev_plan/kongono_phase_2A_codex_directive.md
?? docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_2B_codex_directive.md
?? docs/task reports/Phase_1I.md
?? docs/task reports/Phase_1I_1_correction.md
?? docs/task reports/Phase_2A.md
?? docs/task reports/Phase_2B.md
?? docs/task reports/Phase_2B_1_correction.md
?? docs/task reports/Phase_2B_2_correction.md
```

Quoted PowerShell/Git display around paths containing spaces is presentation,
not part of the path identity.

### 3.3 Accepted untracked-record identities

Every entry below must remain an ordinary, non-reparse file with the exact
primary-stream identity shown:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0043-phase-2-runnable-web-framework-milestone.md` | 14498 | `3EBA65333547D6265BD95F2FAD5BC1189449D5BF34A29FDFFDFFA3595EB120FF` |
| `docs/adr/0044-http-message-and-interoperability-architecture.md` | 15451 | `F3E2220ACBEFCCB1A53328459748FDFB3F45F3AB5BCD22B6F91763793F91C103` |
| `docs/architecture/http-message-model.md` | 10942 | `874801781BF0429C9012BC049372142344CC39A0DB150EF9F180D0CDCB539DF0` |
| `docs/architecture/phase-2-roadmap.md` | 10427 | `7677DBA6E13D0C1B574B65CA21EFF84581F0E745D124FB81A08E35C44D16C1EE` |
| `docs/dev_plan/kongono_phase_1I_codex_directive.md` | 30470 | `85E3293A9FF52D35FBFF775E45E77C5BEDDD2D6EF125A31DA469EAADDCF6013B` |
| `docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md` | 20148 | `2A1687E99B78B7FFE5E877E26E40B8DA73F69F44DA8714FACC82411121923936` |
| `docs/task reports/Phase_1I.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task reports/Phase_1I_1_correction.md` | 18259 | `B53903B9E8D3808D9F13A100D85598786DADAA13539CE2F1EAC824CCFA2BD6A4` |
| `docs/dev_plan/kongono_phase_2A_codex_directive.md` | 31809 | `6A472F370094BB605F10857A24262E91069BA1898A142FC8D7A545BF41B5A5E1` |
| `docs/task reports/Phase_2A.md` | 16218 | `454396E69FEE973940E781A08A0D14E5C4C7C0EA8635ED965ED2753B4FD6194A` |
| `docs/dev_plan/kongono_phase_2B_codex_directive.md` | 39261 | `0A7E7445E49A17383CAE16D7608C4E48F88E04357EB3791CA091561DE27D1527` |
| `docs/task reports/Phase_2B.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md` | 14406 | `8CED7E155EFF568613DB7244BE9334B4595C00E721B975CF9D8FD5D3E9FD191B` |
| `docs/task reports/Phase_2B_1_correction.md` | 20177 | `62AEC1C828F37A31E35098CC09A5341216EE88F6B0E1DE5DE938A040E4AC8DB7` |
| `docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md` | 9050 | `FAF422328FC398FFF32EF539662A4305C00E7A4910553BB1CE1EE059AD511DDF` |
| `docs/task reports/Phase_2B_2_correction.md` | 8673 | `858583AFC9F109B1495C60EC11BDAC68A3E992060D263CFFFE8056D94279B240` |

The two Phase 2B documentation files are authorized for the exact Phase 2C
reconciliation in Section 12. Their pre-change identities above remain the
required Gate A baseline, not their final identity.

### 3.4 Lockfile and quality baseline

Before Phase 2C:

```text
composer.lock bytes: 182894
composer.lock SHA-256: 804B276750164A634D25ED97A613D9B51FC1E59F9D170A9D345C733299B3498B
```

The accepted canonical quality result is:

```text
security advisories: 0
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

The executor must independently reproduce the pre-change result in Gate B.

### 3.5 Protected history and caller records

Do not edit any earlier directive or task report. Do not stage or commit any
path. Do not modify refs, `.git`, history, author configuration, hooks,
attributes, ignore rules, or accepted file modes. All repository files must
remain ordinary primary streams without alternate data streams or reparse
points.

---

## 4. Exact package and dependency boundary

### 4.1 Package identity

Create exactly:

```text
directory: packages/http-message
Composer package: kongono/http-message
production namespace: Kongono\Http\Message\
test namespace: Kongono\Http\Message\Tests\
```

The package is a library and uses PSR-4 autoloading.

### 4.2 Package manifest

`packages/http-message/composer.json` must contain exactly these requirements:

```json
{
  "kongono/api-metadata": "*",
  "kongono/diagnostics": "*",
  "php": "^8.5",
  "psr/http-factory": "^1.1",
  "psr/http-message": "^2.0"
}
```

It must contain no `require-dev`, plugin, script, repository, conflict,
provide, replace, suggest, or extra policy. The description must accurately say
that this is Kongono's first-party PSR-7 message and selected PSR-17 factory
implementation.

### 4.3 Dependency direction

The only production dependency graph for the package is:

```text
api-metadata -----+
diagnostics ------+
psr/http-message -+--> kongono/http-message --> root framework aggregate
psr/http-factory -+
php 8.5 ----------+
```

No production source in this package may import or reference Kernel,
Dependency Injection, Application Graph, Diagnostic Activation, Diagnostic
Presentation, Diagnostic Handler, PSR-15, a router, an adapter, a runtime, an
emitter, or another PSR-7 implementation.

The root package adds exactly:

```json
"kongono/http-message": "@dev"
```

No existing production package gains a dependency on `kongono/http-message` in
this phase.

### 4.4 Exact new third-party packages

Composer may add exactly:

```text
psr/http-message 2.0
psr/http-factory 1.1.0
```

No third-party implementation package may be added. No existing locked package
version, distribution identity, requirement, or local path reference may
change. A root content-hash change and the new local `kongono/http-message`
path-package record are expected mechanical changes.

If Composer selects different versions permitted by the constraints because
the upstream package state changed, stop and report the exact resolution rather
than silently accepting it.

---

## 5. Exact file boundary

### 5.1 Files to create

Create exactly these 33 implementation records:

```text
docs/adr/0045-first-party-http-message-and-factory-implementation.md
packages/http-message/README.md
packages/http-message/composer.json
packages/http-message/src/Diagnostic/HttpMessageDiagnostic.php
packages/http-message/src/Exception/ExceptionInterface.php
packages/http-message/src/Exception/HttpMessageRuntimeException.php
packages/http-message/src/Exception/InvalidMessageArgumentException.php
packages/http-message/src/Factory/ResponseFactory.php
packages/http-message/src/Factory/ServerRequestFactory.php
packages/http-message/src/Factory/StreamFactory.php
packages/http-message/src/Factory/UploadedFileFactory.php
packages/http-message/src/Factory/UriFactory.php
packages/http-message/src/Internal/HeaderCollection.php
packages/http-message/src/Internal/HttpMessageValidation.php
packages/http-message/src/Message/AbstractMessage.php
packages/http-message/src/Message/Response.php
packages/http-message/src/Message/ServerRequest.php
packages/http-message/src/Message/Stream.php
packages/http-message/src/Message/UploadedFile.php
packages/http-message/src/Message/Uri.php
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

One declaration per production/test PHP file is mandatory. No fixture PHP file,
generated source, eval-defined declaration, anonymous substitute for a named
production type, or hidden bootstrap file is authorized.

### 5.2 Existing files authorized for modification

Modify only these existing paths:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

Ten already-modified tracked documentation files in this list receive only the
Phase 2C reconciliation. The already-modified tracked ADR 0004 is not in this
list and remains byte-identical to the accepted baseline. The other seven
tracked files in this list become newly modified. The three accepted untracked
Phase 2 architecture records remain untracked while receiving the authorized
updates.

No other file may be created, modified, renamed, moved, deleted, truncated,
normalized, staged, or committed.

### 5.3 Expected file counts after implementation

Before report population, exact inventory must be:

```text
package directories: 9
package manifests: 9
Composer manifests including root: 10
production PHP files below package src directories: 70
test/fixture PHP files below package tests plus root tests: 98
modified tracked paths: 18
untracked paths with -uall: 51
total status paths with -uall: 69
staged paths: 0
```

The 69 paths are exactly the union of:

1. the 29 locked post-caller Gate A paths;
2. the 33 created paths in Section 5.1; and
3. the seven newly modified tracked paths:

```text
.php-cs-fixer.dist.php
composer.json
composer.lock
docs/engineering/api-status.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

---

## 6. Exact production declaration inventory and API status

The package must declare exactly these 17 production types:

| Type | Kind | API status |
|---|---|---|
| `Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic` | string-backed enum | Internal |
| `Kongono\Http\Message\Exception\ExceptionInterface` | interface | Public |
| `Kongono\Http\Message\Exception\InvalidMessageArgumentException` | final class | Experimental |
| `Kongono\Http\Message\Exception\HttpMessageRuntimeException` | final class | Experimental |
| `Kongono\Http\Message\Internal\HeaderCollection` | final readonly class | Internal |
| `Kongono\Http\Message\Internal\HttpMessageValidation` | final class | Internal |
| `Kongono\Http\Message\Message\AbstractMessage` | abstract class | Internal |
| `Kongono\Http\Message\Message\Response` | final class | Experimental |
| `Kongono\Http\Message\Message\ServerRequest` | final class | Experimental |
| `Kongono\Http\Message\Message\Stream` | final class | Experimental |
| `Kongono\Http\Message\Message\UploadedFile` | final class | Experimental |
| `Kongono\Http\Message\Message\Uri` | final class | Experimental |
| `Kongono\Http\Message\Factory\ResponseFactory` | final class | Experimental |
| `Kongono\Http\Message\Factory\ServerRequestFactory` | final class | Experimental |
| `Kongono\Http\Message\Factory\StreamFactory` | final class | Experimental |
| `Kongono\Http\Message\Factory\UploadedFileFactory` | final class | Experimental |
| `Kongono\Http\Message\Factory\UriFactory` | final class | Experimental |

Every non-external production declaration must have exactly one `Api`
attribute with the listed status. PHP-FIG interfaces are external and are not
Kongono API-metadata subjects.

No Stable status, alias, facade, service locator, registry, static current
request, or additional public production type is authorized.

---

## 7. Exact public contracts

### 7.1 Response

`Response` implements `Psr\Http\Message\ResponseInterface` and has exactly this
constructor:

```php
public function __construct(
    int $statusCode = 200,
    array $headers = [],
    ?StreamInterface $body = null,
    string $protocolVersion = '1.1',
    string $reasonPhrase = '',
)
```

Its remaining public methods are exactly the inherited PSR-7 response/message
methods. Empty reason phrase remains empty; Kongono does not install an implicit
reason-phrase registry. Valid status codes are integers `100..599`.

### 7.2 Server request

`ServerRequest` implements `Psr\Http\Message\ServerRequestInterface` and has
exactly this constructor:

```php
public function __construct(
    string $method,
    UriInterface|string $uri,
    array $serverParams = [],
    array $headers = [],
    ?StreamInterface $body = null,
    string $protocolVersion = '1.1',
)
```

Its remaining public methods are exactly the inherited server-request,
request, and message methods.

The constructor:

- validates and preserves the case-sensitive HTTP method;
- converts a string URI through Kongono's `Uri` implementation;
- stores server parameters exactly as supplied subject only to shallow
  top-level reference detachment;
- initializes cookies, query parameters, uploaded files, attributes, and
  parsed body to empty/`null` values; and
- creates `Host` from the URI host and non-default port only when no Host
  header was supplied.

The default request target is the URI path or `/` when the path is empty,
followed by `?` and the query when the query is non-empty. The fragment is never
part of the request target.

### 7.3 URI

`Uri` implements `Psr\Http\Message\UriInterface` and has exactly:

```php
public function __construct(string $uri = '')
```

Its remaining public methods are exactly those of `UriInterface`. It supports
absolute URIs and relative references. It does not perform DNS, network,
filesystem, IDNA conversion, base-URI resolution, or scheme-specific lookup.

### 7.4 Stream

`Stream` implements `Psr\Http\Message\StreamInterface` and has exactly:

```php
/** @param resource $resource */
public function __construct(mixed $resource)
```

Its remaining public methods are exactly those of `StreamInterface`. The
constructor accepts only an open PHP stream resource. It may represent a
read-only, write-only, or read/write stream; the PSR-17 resource factory applies
its additional readability requirement.

### 7.5 Uploaded file

`UploadedFile` implements `Psr\Http\Message\UploadedFileInterface` and has
exactly:

```php
public function __construct(
    StreamInterface $stream,
    ?int $size = null,
    int $error = UPLOAD_ERR_OK,
    ?string $clientFilename = null,
    ?string $clientMediaType = null,
)
```

Its remaining public methods are exactly those of `UploadedFileInterface`.
Client filename and media type are stored as untrusted metadata and are not
parsed, normalized, trusted, or exposed in diagnostics.

### 7.6 Factories

The five factories are stateless, declare no properties, and implement exactly
one PHP-FIG factory interface each:

```text
ResponseFactory -> ResponseFactoryInterface
ServerRequestFactory -> ServerRequestFactoryInterface
StreamFactory -> StreamFactoryInterface
UploadedFileFactory -> UploadedFileFactoryInterface
UriFactory -> UriFactoryInterface
```

Their public method signatures must match the installed `psr/http-factory`
interfaces exactly. They declare no public helper or custom constructor.

`ServerRequestFactory` converts string URIs with `Uri`; it does not parse server
parameters to infer method or URI. `UploadedFileFactory` requires a readable
stream and, when size is `null`, uses `getSize()` once. `StreamFactory` uses a
temporary read/write resource for string content, opens caller-requested files
with the supplied valid mode, and wraps supplied readable resources.

### 7.7 Internal declarations

`HeaderCollection`, `HttpMessageValidation`, and `AbstractMessage` are internal
implementation details. They may expose only the package-internal operations
needed by the fixed public surface. They may not acquire ambient state,
extension hooks, inheritance contracts for consumers, I/O beyond explicitly
authorized stream operations, or additional production declarations.

Private methods and private constants within the 17 authorized declarations
are permitted. Public methods beyond constructors, interface methods, the
diagnostic enum methods in Section 10, and package-exception marker methods are
prohibited.

---

## 8. Message, header, and copy-on-change semantics

### 8.1 Copy-on-change boundary

Every PSR `with*()` and `without*()` operation returns a distinct object and
leaves the original message/URI state unchanged, including when the requested
value equals the current value.

This is shallow:

- body streams are deliberately shared until replaced;
- mutable objects stored in attributes or parsed bodies remain consumer-owned;
- nested consumer arrays are not represented as deeply immutable objects; and
- top-level arrays are copied by explicit value enumeration so caller-held
  top-level element references cannot mutate the stored top-level slot.

Tests must demonstrate both the protected top-level boundary and the explicit
shared-object/stream caveat. Do not claim deep immutability.

### 8.2 Protocol version

Accept only:

```text
1.0
1.1
2
2.0
3
3.0
```

Preserve the accepted spelling. Reject empty, whitespace-padded, prefixed,
suffixed, control-containing, or other values. No negotiation occurs.

### 8.3 Header names

Header field names must match the HTTP token grammar:

```regex
^[!#$%&'*+\-.^_`|~0-9A-Za-z]+$
```

Validation occurs before storage. Names are compared with ASCII
case-insensitivity. `getHeaders()` preserves the selected original spelling.

When constructor input contains case variants of the same name, values are
combined in input order and the first spelling is retained. `withHeader()`
replaces all prior values and adopts the newly supplied spelling.
`withAddedHeader()` retains an existing spelling and appends values; for a new
name it uses the supplied spelling. `withoutHeader()` removes every case
variant.

Lookup methods return absence for a syntactically invalid lookup string; they
do not reinterpret or normalize it into a valid field name. Mutation methods
reject invalid names.

### 8.4 Header values

Each mutation accepts only a string or a zero-based consecutive list of
strings. Empty strings are permitted. Preserve each accepted value byte-for-byte
and preserve list order.

Reject any value containing:

- carriage return;
- line feed;
- NUL;
- any other ASCII control byte except horizontal tab; or
- DEL (`0x7F`).

Do not repair folding, trim values, coerce scalars, split comma content, decode,
or normalize whitespace. `getHeaderLine()` joins stored values with exactly
`,` and adds no space.

Raw header names and values must never enter exception messages, diagnostic
context, logs, reports, or documentation examples derived from runtime input.

### 8.5 Body

When a constructor receives no body, create a new empty `php://temp`
resource-backed `Stream`. `withBody()` stores the exact supplied
`StreamInterface`; it does not copy, read, rewind, close, or replace it.

---

## 9. Request and URI semantics

### 9.1 Method and request target

HTTP methods use the same token grammar as header names. They are non-empty,
case-sensitive, and preserved exactly; never uppercase or lowercase them.

An explicitly supplied request target is preserved verbatim after rejecting an
empty target, whitespace, control bytes, and DEL. Origin form, absolute form,
authority form, and `*` are permitted. `withUri()` does not erase an explicitly
set request target.

### 9.2 Complete Host/URI decision table

The constructor sets Host from URI only if Host is absent.

For `withUri($uri, false)`:

- new URI has a host: replace Host with new URI host plus non-default port;
- new URI has no host: carry the existing Host unchanged.

For `withUri($uri, true)`:

- existing Host is present and its first line is non-empty: preserve it;
- Host is absent or its line is empty, and new URI has a host: set it from URI;
- Host is absent or empty, and new URI has no host: preserve absence/emptiness.

URI user information never enters Host. Default ports suppressed by `Uri` do
not enter Host. Tests must cover every row, mixed header casing, explicit empty
Host, URI with user info, default port, non-default port, and hostless URI.

### 9.3 URI parsing and component behavior

Implement deterministic RFC 3986 component behavior without another URI
library. Native parsing helpers are permitted only when their output is
validated against these rules.

Rules:

- scheme is empty or matches `[A-Za-z][A-Za-z0-9+.-]*` and is stored lowercase;
- host is empty, a valid ASCII reg-name, IPv4 literal, or bracketed IP literal,
  and is stored lowercase;
- raw non-ASCII host text is rejected; Phase 2C does not perform IDNA;
- port is `null` or `1..65535`;
- explicit `80` under `http` and `443` under `https` normalize to `null`;
- changing scheme re-evaluates whether the current explicit port is default;
- user info, path, query, and fragment preserve valid percent triplets without
  double encoding and uppercase newly generated hex pairs;
- malformed percent triplets are rejected, not repaired;
- user info permits unreserved, sub-delims, and `:` only in its encoded output;
- path permits unreserved, sub-delims, `:`, `@`, and `/`;
- query and fragment permit the path set plus `?`;
- leading `?` and `#` are component delimiters and are not stored;
- path may be empty, absolute, or rootless; and
- no reserved delimiter is decoded into a different semantic component.

String assembly follows `UriInterface` exactly:

- add `:` after a non-empty scheme;
- add `//` before non-empty authority;
- prefix a rootless path with `/` when authority exists;
- reduce multiple leading path slashes to one when authority is absent;
- add `?` only for non-empty query; and
- add `#` only for non-empty fragment.

`__toString()` must not throw. All invalid state must be rejected before it can
be stored.

### 9.4 Server-request data domains

Keep server parameters, cookies, query parameters, uploaded files, parsed body,
and attributes separate. No setter may update URI, headers, or another domain.

`withUploadedFiles()` accepts only arrays whose leaves are
`UploadedFileInterface`; associative and list nesting are both permitted. It
must validate deterministically without reading upload content. Reject invalid
leaves with depth and value-kind facts only.

`withParsedBody()` accepts only `null`, array, or object. Attributes are exact
string-keyed explicit call-flow values. A missing attribute returns the supplied
default. No attribute is authorization proof or ambient state.

Do not read `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`,
`php://input`, environment variables, or emitted headers.

---

## 10. Stream and uploaded-file semantics

### 10.1 Stream state

The stream stores one PHP stream resource or `null` after close/detach. It
derives readability, writability, and seekability from resource metadata and
the standard PHP mode families.

Required behavior:

- `close()` closes an attached resource once and leaves the object detached;
- `detach()` returns the resource without closing it, then leaves the object
  unusable;
- `getSize()` uses current `fstat()` evidence and returns `null` when unknown;
- `tell()`, `seek()`, `rewind()`, `read()`, `write()`, and `getContents()` fail
  with the package runtime exception when detached or when the required
  capability/operation fails;
- `read(0)` returns `''`; a negative length is an invalid argument;
- `read($length)` never requests more than `$length` bytes;
- `write()` returns the actual byte count;
- `eof()` returns `true` after detach;
- `getMetadata(null)` returns metadata or an empty array after detach;
- `getMetadata($key)` returns the value or `null`; and
- `__toString()` attempts rewind and read-to-end, returns `''` on every failure,
  and never throws.

`getContents()` and `__toString()` are PSR-required caller-invoked whole-stream
operations and may consume substantial memory. No other first-party helper in
this package may use them to parse a request body. Tests use only small bounded
streams.

Narrow suppression of a native warning immediately around `fopen`, `fread`,
`fwrite`, `fseek`, `ftell`, `fstat`, `stream_get_contents`, `fclose`, or stream
metadata inspection is permitted only when the result is checked and mapped to
the required typed failure. Do not install an error handler or mutate global
error state.

### 10.2 Stream factory

`createStream()` uses `php://temp`, writes the exact string, rewinds to zero,
and returns a readable/writable stream. Failure is typed and actionable.

`createStreamFromFile()`:

- rejects an empty or NUL-containing mode;
- permits only modes accepted by PHP's documented `fopen` grammar;
- opens exactly the caller-supplied filename/stream URI with that mode;
- does not canonicalize, search, or include the path in a diagnostic; and
- maps open failure to `HttpMessageRuntimeException`.

`createStreamFromResource()` requires an open stream resource and a readable
mode. It does not duplicate, rewind, or close the resource.

### 10.3 Uploaded file

Valid upload error values are exactly the PHP `UPLOAD_ERR_*` values `0, 1, 2,
3, 4, 6, 7, 8`. Size is `null` or non-negative.

When upload error is not `UPLOAD_ERR_OK`, `getStream()` and `moveTo()` throw a
typed runtime failure. After a successful move, both operations also fail and a
second move is prohibited.

`moveTo($targetPath)`:

- rejects empty or NUL-containing targets as invalid arguments;
- performs only caller-requested local file I/O;
- opens the target for replacement and copies in chunks of at most 8192 bytes;
- rewinds a seekable source before copying and uses the current cursor for a
  non-seekable source;
- closes only the target handle it owns;
- never closes or detaches a caller-owned source stream;
- marks the upload moved only after complete success; and
- maps target open/write/close failure to the package runtime exception without
  disclosing the path.

Exceptions thrown by an arbitrary supplied `StreamInterface` implementation
propagate unchanged. Phase 2C does not invent rollback, atomic replacement,
upload-policy, cleanup, or storage ownership guarantees.

---

## 11. Actionable diagnostic and exception boundary

### 11.1 Exception marker

`ExceptionInterface` extends `Throwable`, is Public, and declares exactly:

```php
public function diagnosticContext(): DiagnosticContext;
public function diagnosticId(): string;
public function diagnosticTitle(): string;
public function diagnosticRemediation(): string;
```

It must remain distinct from every existing package exception marker.

### 11.2 Concrete exception categories

`InvalidMessageArgumentException` extends native `InvalidArgumentException`.
`HttpMessageRuntimeException` extends native `RuntimeException`. Both implement
the package marker and are final Experimental classes.

Each has exactly this constructor shape:

```php
public function __construct(
    HttpMessageDiagnostic $diagnostic,
    array $contextValues = [],
    ?Throwable $previous = null,
)
```

Each stores exactly one private readonly diagnostic and one private readonly
`DiagnosticContext`. The native exception message is exactly the diagnostic
title. Diagnostic accessors are final. Code is `0`.

Use the invalid-argument class only for rejected caller arguments and
constructor state. Use the runtime class only for resource capability/state/I/O
failures. Do not wrap `TypeError`, autoloader failures, or arbitrary exceptions
from consumer-supplied PSR implementations merely to enter this family.

### 11.3 Exact diagnostic catalog

`HttpMessageDiagnostic` is a string-backed Internal enum with exactly these 28
cases and identifiers:

| Case | Identifier |
|---|---|
| `InvalidProtocolVersion` | `KGN-HTTP-1001` |
| `InvalidHeaderName` | `KGN-HTTP-1002` |
| `HeaderValuesNotList` | `KGN-HTTP-1003` |
| `HeaderValueNotString` | `KGN-HTTP-1004` |
| `UnsafeHeaderValue` | `KGN-HTTP-1005` |
| `InvalidMethod` | `KGN-HTTP-1006` |
| `InvalidRequestTarget` | `KGN-HTTP-1007` |
| `InvalidStatusCode` | `KGN-HTTP-1008` |
| `InvalidReasonPhrase` | `KGN-HTTP-1009` |
| `InvalidUriSyntax` | `KGN-HTTP-1010` |
| `InvalidUriComponent` | `KGN-HTTP-1011` |
| `InvalidUriPort` | `KGN-HTTP-1012` |
| `InvalidStreamResource` | `KGN-HTTP-1013` |
| `DetachedStream` | `KGN-HTTP-1014` |
| `StreamNotReadable` | `KGN-HTTP-1015` |
| `StreamNotWritable` | `KGN-HTTP-1016` |
| `StreamNotSeekable` | `KGN-HTTP-1017` |
| `StreamOperationFailed` | `KGN-HTTP-1018` |
| `InvalidStreamReadLength` | `KGN-HTTP-1019` |
| `InvalidStreamMode` | `KGN-HTTP-1020` |
| `InvalidUploadError` | `KGN-HTTP-1021` |
| `InvalidUploadSize` | `KGN-HTTP-1022` |
| `UploadedStreamUnavailable` | `KGN-HTTP-1023` |
| `InvalidUploadTarget` | `KGN-HTTP-1024` |
| `UploadedFileAlreadyMoved` | `KGN-HTTP-1025` |
| `UploadedFileMoveFailed` | `KGN-HTTP-1026` |
| `InvalidUploadedFilesTree` | `KGN-HTTP-1027` |
| `InvalidParsedBody` | `KGN-HTTP-1028` |

The enum declares exactly:

```php
public function contextSchema(): DiagnosticContextSchema;
public function context(array $values): DiagnosticContext;
public function title(): string;
public function remediation(): string;
```

Titles name the precise failed invariant. Remediation tells a developer what
structural correction to make or which resource capability/state to inspect.

### 11.4 Closed context schema

Every field below is `Safe`, required unless marked optional, and limited to
the indicated bounded structural value. No other field is authorized.

| Diagnostic(s) | Fields |
|---|---|
| `InvalidProtocolVersion` | `actual_length: Integer` |
| `InvalidHeaderName` | `name_length: Integer` |
| `HeaderValuesNotList` | `entry_count: Integer`, `first_unexpected_key_kind: Text` |
| `HeaderValueNotString` | `value_position: Integer`, `actual_value_kind: Text` |
| `UnsafeHeaderValue` | `value_position: Integer`, `violation: Text` |
| `InvalidMethod` | `actual_length: Integer` |
| `InvalidRequestTarget` | `actual_length: Integer`, `violation: Text` |
| `InvalidStatusCode` | `status_code: Integer` |
| `InvalidReasonPhrase` | `actual_length: Integer`, `violation: Text` |
| `InvalidUriSyntax` | `actual_length: Integer`, `violation: Text` |
| `InvalidUriComponent` | `component: Text`, `component_length: Integer`, `violation: Text` |
| `InvalidUriPort` | `port: Integer` |
| `InvalidStreamResource` | `actual_value_kind: Text` |
| `DetachedStream`, `StreamNotReadable`, `StreamNotWritable`, `StreamNotSeekable`, `StreamOperationFailed` | `operation: Text` |
| `InvalidStreamReadLength` | `requested_length: Integer` |
| `InvalidStreamMode` | `mode_length: Integer`, `violation: Text` |
| `InvalidUploadError` | `upload_error: Integer` |
| `InvalidUploadSize` | `upload_size: Integer` |
| `UploadedStreamUnavailable` | `state: Text` |
| `InvalidUploadTarget` | `target_length: Integer`, `violation: Text` |
| `UploadedFileAlreadyMoved` | no fields |
| `UploadedFileMoveFailed` | `operation: Text` |
| `InvalidUploadedFilesTree` | `tree_depth: Integer`, `actual_value_kind: Text` |
| `InvalidParsedBody` | `actual_value_kind: Text` |

Every Text value must come from a closed package-owned vocabulary or
`get_debug_type()` and be at most 64 bytes. Lengths are byte lengths, never raw
content. Operation names are closed values such as `read`, `write`, `seek`,
`open_target`, and `copy_target`.

Diagnostic messages and context must never contain raw:

```text
header names or values
cookies or query data
URI text or URI components
request targets
body or upload content
client filenames or media types
local paths or stream URIs
credentials, tokens, authorization values, or secrets
server parameters or attributes
```

The HTTP catalog increases the repository-wide unique diagnostic total from 68
to exactly 96. The new domain count is exactly 28 matching
`^KGN-HTTP-1\d{3}$`.

---

## 12. Tests and architecture enforcement

### 12.1 Test quality rules

Tests must be deterministic, isolated, and meaningful. They may use
`php://temp`, `php://memory`, and temporary files created under the operating
system temporary directory. Every owned temporary file/handle must be removed
or closed in `finally`/tear-down logic. Do not access the network, SAPI globals,
real user files, environment secrets, clocks, randomness, or process-global
error handlers.

No skipped, incomplete, risky, warning-producing, or output-producing test is
accepted. Do not inflate assertions with tautologies or duplicate cases merely
to reach a count. Report exact final test/assertion counts; correctness is
defined by the required scenarios, not a vanity threshold.

### 12.2 Required diagnostic and exception tests

Prove:

- exact 28-case names, IDs, uniqueness, pattern, titles, remediation, schemas,
  fields, kinds, disclosure, and closed vocabularies;
- exact marker methods and marker separation from all five existing package
  markers;
- exact two native exception parent categories;
- exact constructor/property/method shape and API statuses;
- native message equals title, code zero, previous preserved;
- schema-order field output independent of input order; and
- forbidden/raw values never appear in diagnostic context, message, title, or
  remediation for representative invalid inputs.

### 12.3 Required header/message tests

Prove:

- defaults and all interface return types;
- every accepted/rejected protocol version;
- token-boundary header names and methods;
- header case-insensitive lookup and exact case/value-order preservation;
- case-variant constructor merging;
- replace, append, remove, absence, empty value, comma-line behavior;
- every forbidden header byte and valid horizontal tab/obs-text behavior;
- invalid list shapes and non-string entries with deterministic first failure;
- status boundaries `99/100/599/600` and reason-phrase control rejection;
- every mutation returns a distinct value and retains original state;
- body replacement does not read/close either stream;
- shared stream mutation is visible through message clones; and
- top-level array reference mutation is detached while nested objects remain
  explicitly shared.

### 12.4 Required request tests

Prove:

- string and object URI constructor paths;
- default and explicit request targets;
- method case preservation and invalid input;
- all Host/withUri decision-table rows from Section 9.2;
- user info exclusion and port behavior in Host;
- exact isolation of server/cookie/query/upload/parsed-body/attribute domains;
- recursive uploaded-files acceptance and deterministic invalid-leaf failure;
- parsed-body type boundary;
- missing/default/set/replace/remove attribute behavior;
- consumer objects remain shared without ambient retention; and
- no superglobal read or write occurs.

### 12.5 Required URI tests

Use explicit table-driven cases for:

- empty, relative, absolute, authority-only, query-only, and fragment-only
  references;
- scheme/host lowercase normalization;
- valid reg-name, IPv4, and bracketed IP literal hosts;
- rejected raw Unicode/invalid hosts and invalid schemes;
- port boundaries, default-port suppression, and scheme-change behavior;
- user/password assembly and percent encoding;
- path/query/fragment allowed, encoded, malformed-percent, and Unicode-byte
  cases;
- no double encoding of valid percent triplets;
- authority plus rootless path and authority-less multi-slash path;
- complete string assembly; and
- original preservation for every `with*()` method.

No test may perform DNS or network access.

### 12.6 Required stream tests

Cover readable-only, writable-only, read/write, seekable, and non-seekable
resources. Prove exact capability flags, cursor behavior, size, metadata,
bounded reads, writes, rewind, seek modes, EOF, contents, close, detach, repeated
close, and every post-detach failure.

Prove `__toString()` never throws on detached, unreadable, and non-seekable
streams. Prove negative read length is invalid argument and capability/I/O
failures are runtime exceptions with the expected diagnostic.

### 12.7 Required uploaded-file tests

Prove valid/invalid error codes and sizes; metadata preservation; null-size
factory lookup; readable-stream requirement; error-state stream rejection;
successful bounded-chunk move; seekable rewind; non-seekable current-cursor
copy; target replacement; empty/NUL target rejection; target open/write
failure; second move rejection; post-move stream rejection; source stream not
closed; and unchanged propagation from a deliberately failing supplied
`StreamInterface` test double declared inside the matching test file as an
anonymous object.

The anonymous test double is authorized because it is not a named autoloaded
declaration or production substitute.

### 12.8 Factory tests

Each factory test proves exact implemented interface, return implementation,
default values, argument forwarding, failure category, and stateless shape.
`StreamFactoryTest` additionally proves temporary-content rewind, explicit file
mode behavior, invalid mode, open failure without path disclosure, supplied
resource identity, and readability enforcement.

### 12.9 Root architecture test

Update `tests/Architecture/ApiStatusConventionTest.php` to enforce:

- exact 70-type production inventory and listed API statuses;
- exact nine package source directories and nine package manifests;
- exact new package manifest requirements;
- exact root `kongono/http-message:@dev` requirement;
- exact new test namespace and tooling registrations;
- all five PSR-7 and all five PSR-17 implementation relationships;
- exact five stateless factory shapes;
- exact HTTP marker and two concrete exception relationships;
- six pairwise-distinct package exception markers;
- 96 unique diagnostic IDs with the domain split `6/11/25/15/11/28`;
- no forbidden sibling package dependency/import from HTTP production source;
- no `RequestFactoryInterface`, PSR-15, PSR-18, client request class, Symfony,
  Guzzle, Laminas, Nyholm, or Slim production dependency; and
- no missing/extra production declaration.

Architecture enforcement may inspect source/manifests from the root-owned test.
Package unit tests must not import unrelated sibling packages merely to prove
separation.

---

## 13. Composer, lockfile, autoload, and tooling expansion

### 13.1 Root Composer changes

In `composer.json`:

1. add `kongono/http-message:@dev` to root `require` in sorted order;
2. add `Kongono\Http\Message\Tests\` ->
   `packages/http-message/tests/` to root `autoload-dev` in sorted order; and
3. add one package-validation command for `packages/http-message` in sorted
   order.

Do not change scripts, security policy, plugin policy, PHP platform, existing
constraints, or repository configuration otherwise.

### 13.2 Package lock resolution

After both Composer manifests are correct, run exactly:

```powershell
composer update kongono/http-message psr/http-message psr/http-factory --with-dependencies --no-interaction
```

If the managed environment blocks network/cache access, retry that identical
command once with approved outside-sandbox access. Do not change flags, disable
security, edit the lockfile by hand, update all packages, or accept an unrelated
lock delta.

Inspect the semantic lock delta. It must contain only:

- root content-hash change;
- new local path package `kongono/http-message`;
- new `psr/http-message` version `2.0`; and
- new `psr/http-factory` version `1.1.0`.

All existing package names, versions, requirements, references, distributions,
and advisory policy remain unchanged.

### 13.3 Tooling

Add the new source and test directories in the existing package order to:

```text
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
```

PHPStan remains maximum level with strict rules. PHPUnit retains every fail-on
warning/risky/deprecation/notice/incomplete/empty/output control. PHP CS Fixer
retains the existing rules. No exclusion, baseline, suppression file, relaxed
rule, or tool dependency is authorized.

After source creation, run optimized strict autoload generation and require no
PSR-4 warning:

```powershell
composer dump-autoload --optimize --strict-psr
```

---

## 14. Documentation and ADR reconciliation

### 14.1 ADR 0045

Create `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
with:

```text
Status: Accepted
Date: 2026-08-16
```

It must record:

1. the exact package identity and dependency graph;
2. the ten implemented PHP-FIG roles and deliberately absent client/PSR-15
   roles;
3. the exact concrete class names, constructors, defaults, and API statuses;
4. the header grammar, case/value behavior, Host decision table, and injection
   rejection;
5. URI parsing, encoding, default-port, serialization, and no-network rules;
6. shallow message copy-on-change and stateful shared streams;
7. stream resource ownership and explicit file-I/O boundary;
8. uploaded-file one-way movement and lack of rollback/storage policy;
9. separated server-request domains and absence of SAPI parsing;
10. the two native-compatible exception categories, marker, 28-ID catalog, and
    bounded structural context;
11. persistent-runtime non-retention and lack of Kernel/DI integration;
12. Composer/tooling/test consequences;
13. security and performance consequences;
14. rejected alternatives, including another framework's implementation,
    ambient factories, global requests, client HTTP, parsing, routing,
    middleware, and emission; and
15. exact later-phase ownership of remaining work.

Do not claim certified universal PSR conformance. State that the selected
interfaces are implemented and Kongono's required behavioral suite passes.

### 14.2 Existing architecture records

Update ADR 0044 and `docs/architecture/http-message-model.md` from planned to
implemented for the exact Phase 2C boundary. Preserve later deferrals. Remove
only decisions now resolved by Phase 2C from their open-items sections.

Update `docs/architecture/phase-2-roadmap.md` to mark 2C complete and 2D as the
next bounded phase. Do not pre-decide router package, route grammar, conflict
rules, or route-result API.

### 14.3 Repository-wide current-state reconciliation

Update the authorized documents so they consistently say:

- Kongono now has nine first-party packages;
- HTTP message/factory interfaces and Kongono implementations now exist;
- the framework still has no router, middleware pipeline, bootstrap, adapter,
  emitter, runnable web fixture, or production HTTP error page;
- Kernel remains transport-neutral and PSR-free;
- no Symfony/Laravel substrate or third-party PSR implementation was adopted;
- the HTTP diagnostic family has 28 IDs and only bounded structural context;
- message copy-on-change is shallow and streams are stateful;
- explicit factories are not ambient registries or service locators; and
- Phase 2D, not Phase 2C, owns deterministic routing decisions.

Update the ADR index for ADR 0045. Update API-status guidance with the new
Experimental concrete/Public marker/Internal helper examples. Update package
layout and glossary with exact new identities and current/planned distinctions.
Update security/error-handling text without promising adapter validation,
production rendering, response mapping, or cleanup behavior not yet built.

Every local Markdown link in the authorized documentation set must resolve.
Use UTF-8 without BOM, LF endings, no trailing whitespace, exactly one final
LF, and no zero-byte implementation/documentation file.

---

## 15. Prohibited scope

Do not implement, add, select, or modify:

- Dependency Injection definitions, bindings, resolver behavior, or container
  integration for messages/factories;
- Kernel contracts, lifecycle, operation scopes, boot/shutdown behavior, or
  any PSR dependency in Kernel;
- Application Graph nodes, relations, schema, IDs, or serialization;
- PSR-15 packages, request handlers, middleware, dispatchers, or pipelines;
- `RequestInterface` concrete client requests or
  `RequestFactoryInterface` implementations;
- PSR-18 or any outbound/client HTTP behavior;
- router package, route registration, grammar, matching, precedence,
  conflicts, parameters, route results, dispatch, or controller invocation;
- SAPI/global conversion, server/proxy trust, body/cookie/query/upload parsing,
  normalization, limits, or duplicate policy;
- response emission, header sending, streaming delivery, connection handling,
  server integration, or runnable web bootstrap;
- production HTTP error mapping, HTML error page, diagnostic activation,
  presentation, handler delivery, logging, telemetry, tracing, or metrics;
- cleanup coordination, cancellation, simultaneous-failure policy, runtime
  retirement, or exact-once operation closure;
- ORM, database, cache, sessions, authentication, authorization, CSRF,
  validation framework, templating, CLI, agents, extensions, frontend, assets,
  queues, events, scheduling, mail, filesystem abstraction, or configuration
  system;
- Symfony, Laravel, Guzzle, Laminas, Nyholm, Slim, or any other framework/PSR-7
  implementation as production dependency or copied source;
- third-party conformance suites or dev dependencies;
- public serialization schema, array export, JSON export, reflection extension
  point, plugin hook, facade, alias, registry, static global, singleton,
  current-request accessor, thread-local, or fiber-local storage;
- license/version invention, policy weakening, suppressed analyzer errors,
  skipped tests, tool exclusions, generated code, or vendored source; or
- staging, committing, tagging, branching, pushing, remote creation, history
  rewriting, resetting, cleaning, or report inclusion in a commit.

If any required implementation appears to need prohibited scope, stop and ask.

---

## 16. Execution gates

### Gate A - authenticated baseline

Before mutation:

1. authenticate this directive from the independently supplied identity;
2. verify all Section 3 Git identities, counts, refs, modes, and path lists;
3. verify every locked caller/architecture record identity;
4. verify the Phase 2C report is zero-byte, untracked, unstaged, and absent from
   `HEAD`;
5. verify the directive is untracked, unstaged, absent from `HEAD`, and has the
   authenticated identity;
6. verify `composer.lock` identity;
7. verify no `packages/http-message` or ADR 0045 path exists;
8. verify every status path is ordinary and has no alternate stream/reparse
   point; and
9. verify zero staged paths.

Stop at the first mismatch.

### Gate B - pre-change quality

Run:

```powershell
composer quality
```

Require the Section 3.4 baseline. Existing warnings may be limited to accepted
missing-license, local unbounded `@dev`/`*` constraints, missing curl slowdown,
and the known inaccessible user-level global-ignore warning. Any new warning
category is a blocker.

If advisory access alone is sandbox-blocked, retry the unchanged command once
with approved access. Do not skip or weaken `audit:locked`.

### Gate C - manifests, source, and dependency resolution

1. create only the new package manifest/directories/source files;
2. modify root Composer/autoload/tool registration exactly;
3. validate both JSON manifests;
4. run the exact scoped Composer update;
5. inspect the semantic lock delta;
6. generate strict optimized autoload; and
7. run `php -l` on all 17 new production files.

Stop before tests/docs if the dependency or lock boundary differs.

### Gate D - focused implementation tests

Create the 13 exact test files, then run at minimum:

```powershell
vendor\bin\phpunit --configuration phpunit.xml.dist packages/http-message/tests
vendor\bin\phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
vendor\bin\phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/http-message/src packages/http-message/tests tests/Architecture/ApiStatusConventionTest.php
vendor\bin\php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/http-message tests/Architecture/ApiStatusConventionTest.php
```

Do not proceed on a warning, risky/skipped/incomplete test, analyzer error,
style diff, output, or incomplete required scenario.

### Gate E - ADR and documentation

Create ADR 0045 and reconcile only the authorized documentation. Validate:

- ADR status/date/title/required decisions;
- exact package/dependency/current-state wording;
- zero stale claim that 2C is unimplemented;
- zero claim that later web capabilities exist;
- all local links;
- UTF-8/LF/final-LF/trailing-whitespace rules; and
- absence of forbidden raw request examples in diagnostic guidance.

### Gate F - final quality and closed proof

Run:

```powershell
composer validate --no-check-publish
composer validate:packages
composer audit:locked
composer check-platform-reqs
composer dump-autoload --optimize --strict-psr
composer quality
```

Also rerun the focused HTTP and architecture suites. Verify exact package,
declaration, diagnostic, manifest, lock, file, link, encoding, stream, and Git
inventories.

Require:

```text
0 advisories
0 PHPStan errors
0 PHP CS Fixer findings
all PHPUnit tests pass
0 strict-PSR warnings
18 modified tracked paths
51 untracked paths
69 total status paths
0 staged paths
HEAD/tree/refs/history unchanged
```

Do not populate the report until all of Gate F passes.

### Gate G - report population and final integrity

Populate only `docs/task reports/Phase_2C.md` using Section 18. Then verify:

- it is ordinary, untracked, unstaged, absent from `HEAD`, non-empty, strict
  UTF-8/LF, no trailing whitespace, one final LF;
- all other final identities and counts remain unchanged;
- zero staged paths; and
- `HEAD`, tree, refs, and commit count remain locked.

Return the report's final byte count and uppercase SHA-256 outside the report
body. Do not edit it after calculating those values.

---

## 17. Strict acceptance criteria

All criteria are mandatory:

1. directive authentication passed independently;
2. exact Gate A Git/caller/lock baseline passed;
3. pre-change canonical quality reproduced `254 tests / 4279 assertions` with
   zero advisories/style/analyzer failures;
4. exactly one new package exists under the exact identity;
5. package and root manifests have only the authorized requirements/mappings;
6. Composer added exactly the local HTTP package and the two locked PSR
   interface versions;
7. no existing locked dependency/reference changed;
8. exactly 17 new production declarations exist with exact names/kinds/status;
9. the five PSR-7 roles and five PSR-17 factories implement exact installed
   signatures;
10. constructors/defaults/public method inventories match Section 7;
11. message copy-on-change, header behavior, Host table, URI behavior, streams,
   uploads, and server-request domains satisfy Sections 8-10;
12. no SAPI/global/ambient/persistent request state exists;
13. native-compatible invalid-argument/runtime categories and Public marker are
   exact;
14. all 28 HTTP diagnostic IDs and schemas are exact, unique, bounded, and
   secret-free;
15. repository diagnostic total is exactly 96 with split
   `6/11/25/15/11/28`;
16. exactly 13 package test files cover every Section 12 scenario without
   tautological inflation;
17. root architecture enforcement proves all inventories, relationships, and
   dependency bans;
18. tooling covers all new source/tests without suppression or weakening;
19. ADR 0045 is Accepted on 2026-08-16 and contains every required decision;
20. authorized documentation accurately distinguishes implemented 2C from
   deferred 2D-2H behavior;
21. no prohibited scope, extra file, declaration, dependency, API, or warning
   exists;
22. all JSON, PHP syntax, strict autoload, focused suites, PHPStan, PHP CS
   Fixer, package validation, platform, audit, and canonical quality gates pass;
23. all local links and file-safety/encoding checks pass;
24. final file counts and `18/51/69/0` Git counts are exact;
25. `HEAD`, tree, nine-commit history, branch/ref set, remotes, tags, and index
   remain unchanged;
26. no file is staged or committed;
27. only the exact Phase 2C report is populated after all non-report gates;
28. report structure, evidence, final byte identity, and digest are complete;
   and
29. final next step is architect review before Phase 2D.

Any failed, partial, not-run, ambiguous, or assumed criterion means Phase 2C is
not complete.

---

## 18. Standard executor completion report

Populate `docs/task reports/Phase_2C.md` with exactly these headings, in order:

```markdown
# PHASE 2C TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. HTTP MESSAGE AND FACTORY CONTRACTS

## 8. DIAGNOSTIC AND EXCEPTION BOUNDARY

## 9. DEPENDENCIES AND LOCKFILE

## 10. TEST IMPLEMENTATION AND COVERAGE

## 11. ARCHITECTURAL DECISIONS

## 12. DEVIATIONS FROM DIRECTIVE

## 13. ENVIRONMENT

## 14. COMMANDS RUN

## 15. VALIDATION RESULTS

## 16. DOCUMENTATION AND ADR REVIEW

## 17. SECURITY AND FILE-SAFETY REVIEW

## 18. WARNINGS AND KNOWN ISSUES

## 19. QUESTIONS OR BLOCKERS

## 20. GIT DIFF SUMMARY

## 21. ACCEPTANCE CRITERIA

## 22. NEXT STEP
```

The report must include:

- independently supplied and observed directive identity;
- all locked baseline and caller-record results;
- every created/modified path;
- exact public implementations and constructor/default decisions;
- exact 28-case diagnostic and exception proof;
- exact dependency additions and semantic lock delta;
- PHP, Composer, Git, PHPUnit, PHPStan, and PHP CS Fixer versions;
- commands and exact focused/final test/assertion counts;
- advisory/platform/style/static-analysis/autoload results;
- package/type/test/diagnostic/status/link/file-safety inventories;
- documentation current-versus-planned reconciliation;
- explicit prohibited-scope audit;
- exact final Git counts and unchanged history proof;
- PASS/FAIL for every acceptance criterion; and
- any warning, retry, deviation, or blocker without minimizing it.

For success, Section 22 must end exactly:

```text
Await architect review before Phase 2D.
```

If blocked, do not populate a success report. Return the blocker directly and
leave the report zero bytes unless an authenticated correction explicitly says
otherwise.

---

## 19. Executor behavior

Act as executor, not architect. Make only the exact authorized changes. Do not
improve adjacent code, modernize unrelated prose, repair accepted history,
reformat beyond required files, or choose a later-phase abstraction.

Preserve caller-managed dirty state. Never use destructive Git or filesystem
operations. Never adopt locally observed bytes/hashes as architectural
authority when independent confirmation is required.

If an external interface, PHP runtime behavior, Composer resolution, or
existing doctrine makes an exact requirement infeasible, stop with concrete
evidence. A precise blocked report is correct execution; a guessed workaround
is not.

---

## 20. Required final outcome

On successful completion, Kongono has exactly one new bounded first-party HTTP
message package implementing the selected PSR-7 values and PSR-17 server
factories with strict actionable diagnostics, exhaustive production tests,
tooling coverage, and reconciled architecture.

It still has no router, middleware pipeline, PSR-15 implementation, web
bootstrap, SAPI adapter, response emitter, production HTTP error page, or
runnable web fixture. Those remain explicit later phases.

Phase 2D may begin only after architect review and acceptance of the Phase 2C
completion report.
