# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3E

## Immutable PHP CGI Operator Bootstrap and Fail-Closed Trust Inputs

**Repository:** `C:\Users\antho\Dev\kongono`
**Role:** executor, not architect
**Required branch:** `main`
**Date:** 2026-08-21
**Phase:** 3E only

---

## 1. Mission

Implement only the accepted Phase 3E operator-bootstrap and trust-input
boundary inside the existing `kongono/php-cgi` outer leaf.

Phase 3E must:

1. represent the operator's bootstrap selection with one closed, unbacked,
   immutable PHP enum rather than strings, `.env`, broad configuration arrays,
   or runtime heuristics;
2. provide exactly two explicit profiles: public/production denial and direct
   loopback development eligibility;
3. load one immutable bootstrap result before serving only after the selected
   PHP CGI SAPI and ten fixed PHP INI prerequisites pass;
4. fail before serving with one typed, actionable, non-disclosing package
   exception when a prerequisite is absent or wrong;
5. derive the existing `TrustedDeveloperBootstrapPermission` only from the
   explicit operator profile;
6. calculate a fresh existing `AdapterControlledDeliveryPermission` only from
   the current delivery's exact host-controlled SAPI, CGI role, direct remote
   address, and absence of forwarded/proxy evidence;
7. permit current-delivery `Allowed` only for canonical `127.0.0.1` or `::1`
   under the exact `cgi-fcgi` / CGI/1.1 / FastCGI `RESPONDER` profile;
8. make missing, invalid, ambiguous, non-loopback, forwarded, failed, or
   uncertain delivery evidence return `Denied` without throwing;
9. keep bootstrap and current-delivery decisions independent and typed for
   later Phase 3F composition;
10. add one closed diagnostic, production tests, ADR 0055, and current-document
    reconciliation; and
11. leave concrete delivery, ambient request capture, Web Runtime composition,
    front-controller entry, deployment, hardening, and external acceptance to
    Phases 3F through 3H.

The operator syntax selected here is typed PHP code at the future operator-owned
composition boundary:

```php
CgiOperatorProfile::Production
CgiOperatorProfile::LoopbackDevelopment
```

There is no raw configuration parser. Phase 3F will place exactly one enum
literal in its operator-owned composition and call the Phase 3E loading API
before constructing a runtime that can serve work.

Phase 3E is not a general configuration system, environment loader, secrets
manager, authentication system, authorization system, proxy-trust framework,
delivery adapter, production entrypoint, listener, or deployment profile.

---

## 2. Mandatory stop rules

Stop immediately, preserve the exact reached state, report expected and
observed values, and request the smallest authenticated correction if any
identity, inventory, dependency, API contract, diagnostic, test result,
documentation boundary, or file-safety predicate differs.

Do not:

- guess, infer, or silently adopt a locally observed mismatch;
- replace, truncate, normalize, restore, or repair a caller record;
- use culture-based sorting where ordinal sorting is required;
- stage, commit, amend, reset, restore, clean, switch branches, create/delete
  refs, rewrite history, or change the semantic index;
- add a package, third-party dependency, plugin, baseline, suppression, ignore,
  exclusion, reduced analysis level, or weakened security policy;
- read `.env`, `$_ENV`, `$_SERVER`, `$_REQUEST`, `getenv()`, arbitrary files,
  arbitrary environment values, headers, cookies, query, routes, sessions,
  databases, providers, or previous operations as bootstrap authority;
- parse a string, integer, boolean, JSON, INI file, YAML, XML, PHP array, CLI
  flag, request value, or truthy scalar into an operator profile;
- treat loopback alone as sufficient trusted-presentation eligibility;
- trust `Forwarded`, any `X-Forwarded-*`, proxy inference, `SERVER_ADDR`,
  `SERVER_NAME`, `HTTP_HOST`, DNS, TLS state, a token, or a secret;
- combine the two permissions, call presentation code, access protected source
  data, or implement Web Runtime behavior in this phase;
- implement `WebDeliveryInterface`, capture `php://input`, read response output,
  emit a response, close delivery resources, or add a front controller;
- broaden Phase 3B's selected host profile or change Phase 3C/3D request or
  response behavior; or
- begin Phase 3F or later work.

One unchanged retry of a Composer command is permitted only when its sole
failure is managed network/cache denial. Record both attempts. No non-network
retry is authorized merely to obtain a pass.

On any stop:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS, CLEAN UP, OR EXPAND SCOPE
```

---

## 3. Independent directive authentication

The architect must provide this directive's expected byte count and SHA-256
outside the file body:

```text
docs/dev_plan/kongono_phase_3E_codex_directive.md
```

Before reading beyond authentication or running quality, editing, staging, or
committing, calculate the local identity and compare it exactly. The local file
is not self-authenticating.

The caller must also create:

```text
docs/task reports/Phase_3E.md
```

It must initially be an ordinary, untracked, unstaged, `HEAD`-absent, zero-byte
file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero bytes until every non-report gate passes.

---

## 4. Required authorities

After authentication and before mutation, read completely:

```text
AGENTS.md
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/web-runtime-model.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/dev_plan/kongono_phase_3D_codex_directive.md
docs/dev_plan/kongono_phase_3D_1_codex_directive_correction.md
docs/task reports/Phase_3D.md
docs/task reports/Phase_3D_1_correction.md
packages/diagnostic-activation/README.md
packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php
packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php
packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php
packages/php-cgi/README.md
packages/php-cgi/composer.json
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/src/Internal/CgiRequestValidation.php
packages/web-runtime/README.md
packages/web-runtime/src/Contract/WebDeliveryInterface.php
packages/web-runtime/src/WebRuntimeAdapter.php
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
.php-cs-fixer.dist.php
tests/Architecture/ApiStatusConventionTest.php
```

Historical ADRs, directives, reports, and checkpoint commits remain immutable.
Current documentation may be reconciled only where Section 6 authorizes it.

---

## 5. Locked starting state

These values apply after the caller copies this directive and creates the
zero-byte Phase 3E report placeholder.

### 5.1 Git and inventory identity

```text
branch:                  main
local branches:          1
HEAD:                    647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                    3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                  d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:            10
remotes:                 0
tags:                    0
modified tracked paths:  21
untracked paths:         44
total status paths:      65
staged paths:             0
Git-visible files:      580
tracked files:          536
package directories:     14
package manifests:       14
production PHP files:   116
test PHP files:         135
style-covered PHP files: 251
```

Exactly one local branch ref, `refs/heads/main`, is permitted. Existing
application-managed `refs/codex/**` refs are exempt from the branch count but
must not be created, deleted, or changed by the executor. No other unexpected
ref is permitted.

The 21 modified and 42 pre-Phase-3E untracked paths must match the accepted
Phase 3D.1 state exactly. The only two additional untracked paths are:

```text
docs/dev_plan/kongono_phase_3E_codex_directive.md
docs/task reports/Phase_3E.md
```

Use `git status --short --untracked-files=all` for exact path counts.

### 5.2 Locked accepted records

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_3D_codex_directive.md` | 39,679 | `33EA26695BAB62B1B1C6B9513FBAADD0E9333BBC1238A2F0FA335FE72EFC5647` |
| `docs/task reports/Phase_3D.md` | 16,434 | `D786136B0BCE72994FFDEC3D1AD02DB9BB64B704ED4A220BFC648F22D2D90285` |
| `docs/dev_plan/kongono_phase_3D_1_codex_directive_correction.md` | 26,465 | `DE91986A9AAF5CF4A9A7B49370C9D799D9CBF62B71DB864DD6B4D0520F2CB53E` |
| `docs/task reports/Phase_3D_1_correction.md` | 16,984 | `33B32B8C95676156B5A818C652FE41EB4256DEB4992CE44AFDAF76EBB6A3AE3F` |
| `docs/adr/0052-php-cgi-fastcgi-responder-profile.md` | 13,139 | `DCD53CC972FDE94AFE668254C43CF6A531F6FE6A178EC42D83917E84961E17A5` |
| `docs/adr/0054-bounded-php-cgi-response-emission.md` | 5,399 | `A3A485D0654FD579317A3156E21991B7B2D37E37C30D9186BDAFA83E06CD087B` |

The Phase 3A-H fixed roadmap table remains exactly 1,329 LF bytes with SHA-256:

```text
653BE9BE8A64487C275FCEC935C724F1201D2BCCC398B6CAC4DFCCE0F3ED9B04
```

### 5.3 Starting identities for every authorized existing path

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `ARCHITECTURE.md` | 22,963 | `053E6BB9BC023AB1439AC8100BE54EE25DD68AD9D6356DC42E74573A683B96AE` |
| `README.md` | 15,898 | `B865105F19B9577966332F279D798118486AB47A0CC82C04BF350F8F16647D03` |
| `SECURITY.md` | 24,376 | `1D5E7FF030492D73161DCEBC05BE16EB2EB45B044D89049616BCDA710CEA9FF8` |
| `composer.lock` | 197,391 | `2C44558DB971885324A00761F4099AE901A6AAADDB4D243221E9BCEDFE7C2A75` |
| `docs/adr/README.md` | 10,997 | `CBBE8F0AE9A395F11693B7537FB3047EEA05898FD858A4AA4B0BC85F271E5C1C` |
| `docs/architecture/overview.md` | 18,434 | `AD40697B496BA8334E23024CAE5706DB5B96D685A2CF5D15014E93BF24397051` |
| `docs/architecture/phase-3-roadmap.md` | 13,194 | `01E8300EEE2CF74F076B77EF589A3A3806DEBB215F94CDB155A58DF4B2F65F20` |
| `docs/architecture/php-cgi-fastcgi-profile.md` | 21,726 | `3A241E056E9018B12BE58A27064263D132EBD6AA130AB93E67C859797CDB34BB` |
| `docs/architecture/runtime-model.md` | 15,289 | `97A55FAD12331A6BFC2D1C37A6A37CCCCE8844C9D7B83C5D3B0C446B5BD0DEFB` |
| `docs/architecture/web-runtime-model.md` | 5,795 | `862B5BB268C4244F0F8A56E38AFE3F54E6C6008F3C850F479F0C32B688177A4C` |
| `docs/doctrine.md` | 20,147 | `9487B87CC09350496994506B30CD5F347934D3BC03953F4EC533A18FC930B0FB` |
| `docs/engineering/error-handling.md` | 78,338 | `C87556DB19593DED1A415146FBBBECB7A23C093E2B5F8194CA52EC8D6B264E58` |
| `docs/engineering/package-layout.md` | 20,530 | `C7C4EA0A72157C792ABDFE99039CBE003473BBB2EB63F7237292AAA3E1ADF22D` |
| `docs/engineering/toolchain.md` | 5,869 | `7628D56900A4A1762C6D23397C957FC430D8F061B9B4463B1D8050EE9FD638EA` |
| `docs/glossary.md` | 35,025 | `8E9F9555B56F350E5645AF66A273DD9032CAC29A581C579BD9592CDF461729DE` |
| `packages/diagnostic-activation/README.md` | 6,453 | `7F8924D73CEF972474F9EF97C1147CCAEDC6E4EF47AE96ECCF109D25094BCD62` |
| `packages/diagnostic-handler/README.md` | 6,299 | `7FCC9C3603C91164888E97A3CEFFA487C473D20E056C99850C4D02B61885BF85` |
| `packages/diagnostic-presentation/README.md` | 9,459 | `39EF2DCF3E25680E48F6762FAD201DFAAB5A99D45838C27FB87D5A6AF77F9E05` |
| `packages/php-cgi/README.md` | 5,313 | `ABDF9E78B0D893586F5529C41D264EB6E559EA164BA12D59AFA5E0C2E75DAA65` |
| `packages/php-cgi/composer.json` | 491 | `EAAD0110ADD2AF5C9C35F237177687DB5A1C34B998AB385FCF70FB7A641CF685` |
| `packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php` | 12,966 | `2B9516904F499338865A314F19FF980EC644D620FBF58973AE8B22C79FE230B5` |
| `packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php` | 11,577 | `2BBFF41D213F2DCD410BFB5A4ED4BE6ED304F10D7F0B47E126B9231EFEA4986F` |
| `packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php` | 12,868 | `9090E68CB279DFCA5252FD516843C482835043250FEC888929378D1D2A036E03` |
| `packages/web-runtime/README.md` | 3,136 | `F40C9904174750219920BE4A99E897DAF2CB3E7841474838C2EF84EC4C299EDC` |
| `tests/Architecture/ApiStatusConventionTest.php` | 77,840 | `817A21E0A170DDC539706970B4E86E48614D696853A6619BA28B93B3A5C9AF21` |

If any starting identity differs, stop. Do not repair or overwrite it.

### 5.4 Closed protected-state manifest

Build a UTF-8-without-BOM manifest from:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Normalize paths to `/`. Exclude exactly the 25 authorized existing paths in
Section 6.2 plus:

```text
docs/dev_plan/kongono_phase_3E_codex_directive.md
docs/task reports/Phase_3E.md
```

The eight Section 6.1 created paths do not yet exist at Gate A; exclude them
when reconstructing the same manifest after implementation.

Sort with:

```powershell
$paths.Sort([System.StringComparer]::Ordinal)
```

Do not use `Sort-Object`, CurrentCulture, InvariantCulture, or ignore-case
ordering. Serialize each record exactly as:

```text
path<TAB>byte-count<TAB>uppercase-SHA-256<LF>
```

Encode as UTF-8 without BOM. The exact result is:

```text
records:               553
manifest bytes:       68496
manifest SHA-256:     A70AF99AF2BCCA36D1D68F9678B167B6EA96BF9A7019E0D208ADECABF4EE0A00
aggregate file bytes: 5663837
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

Reconstruct it after implementation and after report population. Every value
must remain exact.

---

## 6. Exact authorized file boundary

### 6.1 Create exactly eight paths

```text
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
packages/php-cgi/src/Bootstrap/CgiOperatorBootstrap.php
packages/php-cgi/src/Bootstrap/CgiOperatorProfile.php
packages/php-cgi/src/Exception/CgiOperatorBootstrapException.php
packages/php-cgi/src/Internal/CgiOperatorBootstrapValidation.php
packages/php-cgi/src/Internal/CgiTrustedPresentationPermissionEvaluator.php
packages/php-cgi/tests/Unit/Bootstrap/CgiOperatorBootstrapTest.php
packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
```

### 6.2 Modify exactly these 25 existing paths

```text
ARCHITECTURE.md
README.md
SECURITY.md
composer.lock
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/architecture/runtime-model.md
docs/architecture/web-runtime-model.md
docs/doctrine.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/diagnostic-presentation/README.md
packages/php-cgi/README.md
packages/php-cgi/composer.json
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
packages/web-runtime/README.md
tests/Architecture/ApiStatusConventionTest.php
```

### 6.3 Caller report

Populate only after all non-report gates pass:

```text
docs/task reports/Phase_3E.md
```

### 6.4 Closed scope

No other path may be created, modified, deleted, moved, renamed, formatted,
staged, committed, regenerated, or normalized.

In particular, do not modify any existing production class outside the exact
diagnostic enum, any existing test outside the three authorized tests, root
`composer.json`, any package other than the PHP-CGI manifest, any tooling
configuration, any historical ADR/directive/report, or any Phase 2 fixture.

---

## 7. Package and Composer contract

`kongono/php-cgi` remains the existing fourteenth package and outer leaf. Add
exactly one direct package dependency:

```json
"kongono/diagnostic-activation": "*"
```

The complete package manifest must be semantically exactly:

```json
{
  "name": "kongono/php-cgi",
  "description": "Bounded PHP CGI conversion, operator trust inputs, and response emission for Kongono's selected FastCGI Responder profile.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/diagnostic-activation": "*",
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

Root `composer.json` remains byte-identical. The root dependency on both local
packages already exists.

After changing only the package manifest, run:

```powershell
composer update kongono/php-cgi --with-dependencies --no-interaction
```

The root lock `content-hash` must remain:

```text
cd2451460b76f0afd26d8936c011994f
```

The semantic lock delta is limited to:

- adding `kongono/diagnostic-activation: *` to the locked PHP-CGI package's
  `require` map;
- replacing its description with the exact text above; and
- Composer-generated mechanical local path-package references for
  `kongono/php-cgi` and, only if Composer regenerates it,
  `kongono/diagnostic-activation`.

No package version, third-party dependency, source/dist type, policy, plugin,
platform requirement, autoload mapping, or other semantic lock field may
change. If Composer changes anything else, stop with the generated lock
preserved for inspection.

---

## 8. Exact declaration and API inventory

Create exactly five production declarations:

| FQCN | Shape | API status |
| --- | --- | --- |
| `Kongono\PhpCgi\Bootstrap\CgiOperatorProfile` | unbacked enum | Experimental |
| `Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap` | final readonly class | Experimental |
| `Kongono\PhpCgi\Exception\CgiOperatorBootstrapException` | final class | Experimental |
| `Kongono\PhpCgi\Internal\CgiOperatorBootstrapValidation` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiTrustedPresentationPermissionEvaluator` | final class | Internal |

After Phase 3E:

```text
package directories:     14
package manifests:       14
production PHP files:   121
test PHP files:         137
style-covered PHP files: 258
packages/php-cgi/src PHP files:   21
packages/php-cgi/tests PHP files:  7
```

No new Public, Stable, Deprecated, interface, trait, abstract class, backed
enum, global function, constant, attribute, service registration, DI
definition, Application Graph node, or serialization surface is authorized.

---

## 9. Operator-profile contract

`CgiOperatorProfile` is an unbacked enum with cases in exactly this order:

```php
case Production;
case LoopbackDevelopment;
```

It declares exactly:

```php
public function bootstrapPermission(): TrustedDeveloperBootstrapPermission;
```

The mapping is exact:

| Profile | Bootstrap permission |
| --- | --- |
| `Production` | `Denied` |
| `LoopbackDevelopment` | `Allowed` |

The enum has no scalar backing, parser, `from`, `tryFrom`, alias, default,
mutable state, environment lookup, file lookup, request lookup, magic method,
serialization contract, or other declared method.

`Production` means trusted developer presentation is denied even on loopback.
It does not mean the application is broadly production-ready.

`LoopbackDevelopment` means only that the independent bootstrap input may be
`Allowed` after host prerequisites pass. It does not make the current delivery
eligible, authenticate an audience, prove direct origin, authorize protected
source access, or permit public/proxied use.

The operator selects one literal enum case in trusted application bootstrap
code. Kongono does not turn raw strings or ambient values into this enum.
Changing the profile requires explicit construction of a new bootstrap/runtime;
there is no reload, watcher, mutation, or cross-delivery update.

---

## 10. Immutable bootstrap-loading contract

`CgiOperatorBootstrap` is final and readonly. It declares exactly one private
instance property:

```php
TrustedDeveloperBootstrapPermission $bootstrapPermission
```

It has a private constructor and exactly these public methods:

```php
public static function load(CgiOperatorProfile $profile): self;

public function bootstrapPermission(): TrustedDeveloperBootstrapPermission;
```

`load()` must:

1. validate the real current process through
   `CgiOperatorBootstrapValidation::captureAndValidate()`;
2. create the object only after validation succeeds;
3. derive the stored enum only through `$profile->bootstrapPermission()`;
4. retain no profile object, SAPI, INI key/value, path, environment value,
   failure, or mutable collection; and
5. perform no other I/O, registration, composition, delivery, or global write.

The result may be reused by later Phase 3F composition across sequential
deliveries because it contains only the immutable operator decision. It does
not contain current-delivery permission.

Absence of a profile means there is no valid call to `load()` and serving must
not begin. No default profile is selected by the framework.

---

## 11. Exact process-prerequisite validation

`CgiOperatorBootstrapValidation` is final, Internal, property-free, and has a
private constructor. It declares exactly:

```php
public static function captureAndValidate(): void;

/** @param array<array-key, mixed> $directives */
public static function validate(string $sapi, array $directives): void;
```

Private helpers and one private array constant containing the exact directive
names are permitted. No public method beyond the two above is permitted.

### 11.1 Native capture

`captureAndValidate()` is the only new production method allowed to read:

```text
PHP_SAPI
ini_get()
```

It must:

1. reject non-`cgi-fcgi` `PHP_SAPI` as `sapi_mismatch` before any `ini_get()`
   call;
2. call `ini_get()` exactly once for each of the ten names below, in the fixed
   order shown;
3. capture no other INI setting or ambient value; and
4. delegate to `validate()` with that detached exact snapshot.

It may not use `php_sapi_name()`, `ini_get_all()`, `getenv()`, `$_ENV`,
`$_SERVER`, a file, shell command, process scan, or reflection.

### 11.2 Closed prerequisite snapshot

The snapshot contains exactly these ten string keys, with no missing or extra
key:

```text
enable_post_data_reading
output_buffering
zlib.output_compression
display_errors
display_startup_errors
html_errors
expose_php
auto_prepend_file
auto_append_file
cgi.rfc2616_headers
```

Every value must be a string. `false`, missing, non-string, non-string key,
extra key, or duplicate-after-PHP-array-normalization state rejects as
`ini_snapshot_invalid`.

The accepted values are exactly:

| Directive | Accepted `ini_get()` result |
| --- | --- |
| `enable_post_data_reading` | `''` or `'0'` |
| `output_buffering` | `'0'` |
| `zlib.output_compression` | `''` or `'0'` |
| `display_errors` | `''` or `'0'` |
| `display_startup_errors` | `''` or `'0'` |
| `html_errors` | `''` or `'0'` |
| `expose_php` | `''` or `'0'` |
| `auto_prepend_file` | `''` |
| `auto_append_file` | `''` |
| `cgi.rfc2616_headers` | `''` or `'0'` |

Do not trim, lowercase, parse booleans, accept `off`, `false`, `no`, whitespace,
numeric types, or any other coercion.

Validation order is:

1. SAPI;
2. snapshot keys and value types;
3. the ten directives in the fixed table order.

On the first failure, throw one `CgiOperatorBootstrapException` with the exact
closed token. Never include or retain the rejected value.

---

## 12. Current-delivery permission evaluator

`CgiTrustedPresentationPermissionEvaluator` is final, Internal, property-free,
has a private constructor, and declares exactly:

```php
/** @param array<array-key, mixed> $server */
public static function evaluate(
    string $sapi,
    array $server,
): AdapterControlledDeliveryPermission;
```

The method is total for every PHP string/array input. It must not throw,
allocate or retain a request, call the converter, read a stream, touch global
state, or report a diagnostic.

Return `Allowed` only when all are true:

1. `$sapi === 'cgi-fcgi'`;
2. `GATEWAY_INTERFACE` exists and is the string `'CGI/1.1'`;
3. `FCGI_ROLE` exists and is the string `'RESPONDER'`;
4. `REMOTE_ADDR` exists and is exactly the string `'127.0.0.1'` or `'::1'`;
5. every array key inspected during the evidence scan is a string;
6. `HTTP_FORWARDED` is absent;
7. `HTTP_X_FORWARDED` is absent; and
8. no string key starts with `HTTP_X_FORWARDED_`.

Otherwise return `Denied`.

The canonical loopback set is intentionally two exact strings. Do not accept
expanded IPv6 loopback, IPv4-mapped IPv6, integer forms, hostnames, whitespace,
zones, CIDRs, private/link-local ranges, `SERVER_ADDR`, configured allowlists,
DNS, or normalization.

Forwarded/proxy keys deny regardless of their value, including empty or
non-string values. Other request fields and values are not permission evidence
and must not be read as such.

The evaluator receives no bootstrap/profile input. The profile and delivery
decisions remain independent. It returns only the existing typed delivery enum;
it does not construct `TrustedDeveloperPresentationEligibility` or access
presentation/source behavior.

Phase 3F will create one fresh delivery object, capture its host snapshot, call
this evaluator for that delivery only, and expose the result through the
existing `WebDeliveryInterface`. That behavior is not implemented here.

---

## 13. Diagnostic and exception contract

Add exactly one Internal `PhpCgiDiagnostic` case after 1016:

```text
OperatorBootstrapFailed = KGN-PHP-CGI-1017
```

Its metadata is exactly:

| Field | Value |
| --- | --- |
| Title | `Unable to bootstrap PHP CGI operator profile` |
| Remediation | `Run the selected cgi-fcgi profile with every required PHP setting disabled before serving` |
| Operation | `Bootstrap PHP CGI operator profile` |
| Violated invariant | `The selected cgi-fcgi process must satisfy every fixed pre-delivery prerequisite` |

It retains the existing one-field Safe `violation` schema and accepts exactly
these tokens in order:

```text
sapi_mismatch
ini_snapshot_invalid
enable_post_data_reading_enabled
output_buffering_enabled
zlib_output_compression_enabled
display_errors_enabled
display_startup_errors_enabled
html_errors_enabled
expose_php_enabled
auto_prepend_file_configured
auto_append_file_configured
cgi_rfc2616_headers_enabled
```

`CgiOperatorBootstrapException`:

- is final;
- extends native `RuntimeException`;
- implements only the existing PHP-CGI `ExceptionInterface` marker;
- is Experimental;
- mirrors `CgiRequestReadException`'s exact two private readonly properties and
  five public methods;
- has constructor:

```php
public function __construct(
    string $violation,
    ?Throwable $previous = null,
);
```

- always uses `PhpCgiDiagnostic::OperatorBootstrapFailed`;
- eagerly constructs the diagnostic context;
- uses the fixed diagnostic title as its native message and code `0`;
- preserves an explicitly supplied previous throwable by identity; and
- retains no SAPI, INI name/value, profile string, path, environment value,
  credentials, token, raw host message, file, or line.

Do not add a delivery-denial diagnostic. Denial is an expected fail-closed
permission result, not an exception.

---

## 14. Required production tests

### 14.1 Bootstrap tests

`CgiOperatorBootstrapTest` must prove at minimum:

- the profile is an unbacked Experimental enum with exactly the two ordered
  cases and one declared public method;
- exact profile-to-bootstrap-permission mapping;
- no scalar parsing, mutable property, constructor, alias, or magic method;
- bootstrap class final/readonly status, exact one-property shape, private
  constructor, and exact public method inventory;
- Internal validation class shape and static method inventory;
- one exact synthetic valid snapshot succeeds for each accepted empty/zero
  representation where applicable;
- input array order does not change validation;
- non-CGI SAPI rejects before directive validation;
- missing, extra, non-string key, false, and non-string value snapshots reject
  as `ini_snapshot_invalid`;
- every directive's first invalid value produces its exact token;
- values such as `off`, `false`, whitespace, and integer `0` are not coerced;
- a real `CgiOperatorBootstrap::load()` under the test CLI fails with
  `sapi_mismatch` and creates no result;
- exception shape, marker separation, fixed message, diagnostic identity,
  context, previous identity, and raw-sentinel non-retention; and
- repeated validation and profile mapping retain no cross-call state.

Do not change PHP INI settings or use an INI overlay to test successful native
loading. Successful validation is tested through the explicit Internal
validator with a synthetic closed snapshot; Phase 3H owns external cgi-fcgi
acceptance.

### 14.2 Delivery-permission tests

`CgiTrustedPresentationPermissionEvaluatorTest` must prove at minimum:

- exact Internal final/property-free/private-constructor/static-method shape;
- `Allowed` for exact IPv4 and IPv6 canonical loopback under exact SAPI,
  gateway, and responder role;
- denial for every missing, non-string, or wrong host evidence component;
- denial for public, private, link-local, expanded IPv6 loopback, mapped,
  whitespace, hostname, and other noncanonical addresses;
- denial for `HTTP_FORWARDED`, `HTTP_X_FORWARDED`, and at least three distinct
  `HTTP_X_FORWARDED_*` keys regardless of empty/string/non-string values;
- denial when any scanned key is not a string;
- unrelated client fields/values do not upgrade or downgrade otherwise exact
  direct-loopback evidence and are never retained;
- the evaluator never reads bootstrap/profile input and never constructs
  combined eligibility;
- it never throws for heterogeneous arrays; and
- success/denial/success plus reordered equivalent evidence proves isolation
  and order independence.

### 14.3 Existing tests and architecture enforcement

Update the existing diagnostic and exception tests for the exact 1017 metadata,
tokens, shape, and disclosure behavior.

Update `ApiStatusConventionTest` to enforce:

- 121 exact production declarations and all five new API statuses;
- 21 PHP-CGI source files and the exact expanded dependency map;
- Diagnostic Activation imports only in the exact new profile, bootstrap, and
  evaluator files that require them;
- `PHP_SAPI` and `ini_get()` only in
  `CgiOperatorBootstrapValidation.php`;
- no `ini_get_all`, `php_sapi_name`, `$_SERVER`, `$_ENV`, `getenv`, `putenv`,
  `.env`, general configuration, Web Runtime, delivery, presentation,
  listener, socket, or process-manager access in PHP-CGI production source;
- no reverse dependency from Diagnostic Activation, Web Runtime, or any inner
  package to PHP-CGI;
- the existing native response-emission calls remain confined to
  `CgiNativeEmissionTarget`;
- existing converter/emitter declarations and tests remain unchanged; and
- no package count or tooling-path drift.

Do not use mocks to weaken exact value behavior, source-text assertions in
package unit tests, eval, generated declarations, baselines, suppressions, or
filesystem mutation.

---

## 15. ADR and documentation reconciliation

Create `docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md`
with:

```text
Status: Accepted
Date: 2026-08-21
```

It must record:

- the typed code-literal profile decision and why no string/file/environment
  parser is added;
- exact profile cases and permission mapping;
- exact SAPI and ten INI prerequisites;
- fixed validation, failure, diagnostic, and non-disclosure behavior;
- independent per-delivery evidence and exact canonical loopback allow set;
- forwarded/proxy denial rules;
- package dependency direction;
- persistent-runtime immutability and per-delivery isolation;
- why loopback alone does not authorize presentation;
- why production/public guidance remains bootstrap-denied;
- API statuses and consequences; and
- Phase 3F delivery/composition/entry as future work.

Reconcile only the 19 authorized current documentation paths so they agree:

- Phase 3E is implemented and ADR 0055 is Accepted;
- exactly fourteen packages remain;
- PHP-CGI now owns typed operator bootstrap and current-delivery permission
  sources in addition to conversion/emission;
- no general configuration or raw scalar parser exists;
- no concrete delivery, ambient capture, Web Runtime composition, production
  entry, deployment, or external acceptance exists;
- operator selection is explicit trusted PHP code, not client input;
- public/production always maps bootstrap to Denied;
- loopback development still requires independent fresh delivery permission;
- forwarded/proxied/uncertain delivery evidence denies;
- Phase 3F alone is next.

The fixed Phase 3A-H roadmap table must remain byte-identical. Update current
status prose and current/future tables outside that fixed table.

Do not rewrite historical ADRs, directives, reports, or checkpoint claims. Do
not claim Stable API, authentication, authorization, public safety, proxy
support, production hardening, or a runnable production entry.

---

## 16. Prohibited scope

Phase 3E must not implement or select:

- `WebDeliveryInterface` implementation or changes;
- ambient capture of `PHP_SAPI`, superglobals, post/files, or `php://input`
  outside the exact bootstrap prerequisite method;
- server request conversion changes;
- response staging/emission changes;
- response send/close resource ownership;
- Web Runtime, Web Application, Kernel, routing, or middleware behavior;
- a front controller, application bootstrap file, package template, framework
  CLI, listener, FastCGI protocol parser/client, socket, process manager, web
  server, deployment file, container image, service unit, or acceptance harness;
- `.env`, environment variables, JSON/YAML/XML/INI/PHP config files, a config
  repository, config cache, reload/watch, secret, token, callback, provider,
  extension, agent, DI binding, service locator, or Application Graph config;
- proxy allowlists, trusted proxy headers, TLS/client certificate trust,
  authentication, authorization, sessions, CSRF, rate limiting, incidents,
  logging, telemetry, retries, or automatic recovery;
- database, ORM, migrations, models, templates, frontend, cache, queue, events,
  mail, scheduler, realtime, agents, extensions, MCP, A2A, or CLI capability;
- PHP 8.6/PHP 9 speculative APIs or compatibility shims; or
- Stable, Deprecated, 1.0, full-framework, feature-parity, production-ready, or
  production-hardening claims.

No Symfony, Laravel, or other framework component is introduced.

---

## 17. Validation workflow

### Gate A - authentication and baseline

1. Authenticate this directive externally.
2. Read every authority completely.
3. Verify the zero-byte report placeholder.
4. Verify Git/history/index/ref/status counts and exact path unions.
5. Verify every locked caller and starting identity.
6. Reconstruct the protected-state manifest with literal ordinal sorting.
7. Verify file safety, modes, normal PHP `memory_limit=128M`, and absent
   `PHP_INI_SCAN_DIR`.
8. Run literal:

```powershell
composer quality
```

Expected accepted pre-change evidence is zero advisories, platform pass, zero
style findings, zero PHPStan errors, and 521 tests with 10,949 assertions.
Record the actual style-covered-file count.

### Gate B - package dependency and autoload

1. Modify only `packages/php-cgi/composer.json` to the exact Section 7 content.
2. Run the exact Composer update command.
3. Inspect semantic lock JSON before any production implementation.
4. Stop if the lock delta exceeds Section 7.
5. Run:

```powershell
composer dump-autoload --optimize --strict-psr
```

### Gate C - exact implementation boundary

Create exactly the five production and two test PHP files. Modify only the
diagnostic enum and authorized tests. Verify exact declarations, statuses,
methods, properties, constants, exception parent/marker, and source confinement
before documentation work.

Run syntax on every new PHP file and the four modified PHP files.

### Gate D - focused validation

Run exactly:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

The 512 MiB limit is process-local and consistent with the accepted root
analysis policy. `--debug` selects one deterministic serial focused run. Do not
run a 128 MiB focused attempt or warm/clear the PHPStan cache.

Require zero errors, fixable files, failures, warnings, risky tests, skipped
tests, incomplete tests, notices, or deprecations. Record actual test and
assertion counts.

### Gate E - documentation and semantic inspection

Create ADR 0055 and reconcile exactly the 19 authorized current documentation
paths. Prove:

- all local Markdown links resolve;
- current and future claims agree;
- the fixed A-H table remains exact;
- no prohibited capability or symbol exists;
- diagnostic metadata/tokens and permission truth tables are exact;
- operator profile and delivery evidence remain independent;
- no raw or sentinel value appears on package-owned diagnostic surfaces;
- no environment/file/request parser exists;
- strict UTF-8 without BOM, LF-only, one final LF, no trailing whitespace, no
  NUL, ordinary files, default streams, and unchanged tracked modes pass; and
- `git diff --check` passes.

### Gate F - canonical quality

Run literal:

```powershell
composer quality
```

If and only if it fails solely because the managed environment cannot reach
the locked advisory service or normal cache, repeat the identical command once
with approved network/cache access.

Require zero advisories, platform pass, zero style findings, zero PHPStan
errors, and a completely passing PHPUnit suite. Record exact final counts.

### Gate G - final preservation and report

Before report population, the exact expected state is:

```text
modified tracked paths:  21
untracked paths:         52
total status paths:      73
staged paths:             0
Git-visible files:      588
tracked files:          536
package directories:     14
package manifests:       14
production PHP files:   121
test PHP files:         137
style-covered PHP files: 258
```

Verify the exact created and modified path inventories, source/test counts,
semantic lock delta, accepted caller identities, protected-state manifest,
normal 128M PHP, absent scan overlay, branch, refs, history, semantic index,
modes, streams, encoding, links, and no staging.

Only then populate `docs/task reports/Phase_3E.md`.

After report population, rerun report integrity, exact inventories, protected
manifest, Git identity/status, no-staging, and no-history-change proofs.

No staging or commit is authorized.

---

## 18. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND DEPENDENCY CONTRACT
## 8. OPERATOR PROFILE AND BOOTSTRAP
## 9. CURRENT-DELIVERY PERMISSION
## 10. DIAGNOSTICS AND DISCLOSURE
## 11. PERSISTENT-RUNTIME ISOLATION
## 12. ARCHITECTURAL DECISIONS
## 13. DEVIATIONS FROM DIRECTIVE
## 14. DEPENDENCIES ADDED/REMOVED
## 15. ENVIRONMENT
## 16. COMMANDS RUN
## 17. VALIDATION RESULTS
## 18. DOCUMENTATION REVIEW
## 19. SECURITY AND FILE-SAFETY REVIEW
## 20. WARNINGS / KNOWN ISSUES
## 21. QUESTIONS / BLOCKERS
## 22. FINAL GIT STATUS
## 23. ACCEPTANCE CRITERIA
## 24. NEXT STEP
```

The final next step must be exactly:

```text
Await architect review before Phase 3F.
```

After completion, return the report byte count and SHA-256 outside the report
body. Do not put self-referential identity values inside the report.

---

## 19. Strict acceptance criteria

All must pass:

1. Independent directive authentication matched.
2. Every authority was read completely.
3. Git identity, semantic index, exact `21/44/65/0` entry state, caller records,
   starting identities, and 553-record protected manifest matched.
4. Pre-change canonical quality passed.
5. Exactly eight paths were created and 25 existing paths modified.
6. No unauthorized path changed.
7. Exactly one direct package dependency was added; package count remained 14.
8. Package manifest and semantic lock delta are exact; root manifest and policy
   remain unchanged.
9. Strict optimized PSR-4 autoload passed.
10. Exactly five production declarations and two test files were added.
11. API statuses, shapes, cases, properties, and method inventories are exact.
12. Operator profile cases and permission mapping are exact and scalar-free.
13. Bootstrap loading validates the real SAPI and exact ten INI prerequisites
    before constructing a result.
14. Validation is closed, deterministic, non-coercing, and fail-before-serving.
15. Bootstrap failures use only exact diagnostic 1017 metadata and tokens and
    retain no raw value.
16. Current-delivery permission is Allowed only for the exact selected host,
    canonical loopback, and no forwarded/proxy evidence.
17. Every missing, invalid, heterogeneous, forwarded, proxied, nonloopback, or
    uncertain evidence path returns Denied without throwing.
18. Bootstrap and delivery permission sources remain independent.
19. No client-controlled value, prior operation, provider, token, secret,
    profile parser, or ambient configuration can upgrade trust.
20. Repeated and reordered calls prove no retained delivery state.
21. Existing converter, emitter, Web Runtime, and permission enum bytes remain
    unchanged.
22. Root architecture enforcement passes with exact confinement.
23. ADR 0055 and current documentation are consistent; fixed A-H table remains
    byte-identical; Phase 3F alone is next.
24. No delivery, entry, ambient request capture, general configuration, proxy
    trust, deployment, hardening, external acceptance, or later capability was
    implemented.
25. Focused syntax, 512 MiB serial PHPStan, style, package tests, and
    architecture tests pass.
26. Canonical quality passes with zero advisories, platform pass, zero style
    findings, zero PHPStan errors, and complete PHPUnit success.
27. Links, UTF-8, LF, final LF, whitespace, NUL, ordinary-file, stream, mode,
    and `git diff --check` proofs pass.
28. Normal PHP remains 128M and `PHP_INI_SCAN_DIR` remains absent.
29. Final state is exactly `21/52/73/0`, 588 visible, 536 tracked, 121
    production PHP files, 137 test PHP files, 258 style-covered files, with
    unchanged branch, refs, history, and semantic index.
30. Nothing was staged or committed.
31. Report structure, truthful evidence, final next step, and external report
    identity handoff pass.

Any failure means Phase 3E is not complete. Preserve the exact reached state,
leave the report zero bytes unless all non-report gates passed, and request the
smallest authenticated correction.

---

## 20. Final instruction

Implement Phase 3E only.

Use modern typed PHP boundaries: one unbacked enum, immutable bootstrap result,
closed native prerequisite validation, and one stateless typed permission
evaluator. Do not add dynamic configuration or retrofit a container/config
system around this narrow decision.

Authenticate first. Preserve caller-managed dirty state. Stop on every
unapproved mismatch. Do not stage, commit, or begin Phase 3F.

Stop after returning the completed report's independently computed byte count
and SHA-256.
