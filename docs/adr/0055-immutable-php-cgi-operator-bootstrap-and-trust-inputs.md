# ADR 0055: Immutable PHP CGI Operator Bootstrap and Trust Inputs

- Status: Accepted
- Date: 2026-08-21

## Context

Phase 3C added bounded conversion from explicit caller-owned CGI inputs, and
Phase 3D added bounded response staging and emission. Neither phase selected an
operator profile, validated the native process before serving, or derived the
independent current-delivery permission required by trusted diagnostic
presentation.

The existing Diagnostic Activation package already owns two independent typed
permissions. PHP CGI needs to supply those inputs without turning client data,
ambient configuration, or loopback alone into authorization. This phase does
not add a concrete delivery, Web Runtime composition, production entrypoint, or
external acceptance harness.

## Decision

### Typed operator selection

PHP CGI owns the Experimental unbacked `CgiOperatorProfile` enum. Trusted
application bootstrap code must select one literal case:

| Profile | `TrustedDeveloperBootstrapPermission` |
| --- | --- |
| `Production` | `Denied` |
| `LoopbackDevelopment` | `Allowed` |

There is no string parser, default, alias, environment lookup, file lookup,
general configuration system, reload, or watcher. Client and request values
cannot select or alter the profile. `Production` describes the trust decision,
not broad production readiness. Public and production guidance always uses its
denied bootstrap permission.

The Experimental final readonly `CgiOperatorBootstrap` validates the current
process before creating an instance. It retains only the immutable typed
bootstrap permission. It retains no profile object, SAPI value, INI name or
value, path, environment value, failure, or mutable collection.

### Fixed native prerequisites

Bootstrap accepts only the exact `cgi-fcgi` SAPI. After that check, it captures
exactly one `ini_get()` result for each directive in this order:

| Directive | Accepted exact string result |
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

The detached snapshot must have exactly those ten string keys and string
values. Validation does not trim, normalize, parse boolean-like text, or coerce
types. It checks SAPI first, snapshot structure second, and directives in the
fixed order third.

The Experimental `CgiOperatorBootstrapException` reports only Internal
diagnostic `KGN-PHP-CGI-1017`, `OperatorBootstrapFailed`. Its fixed title is
`Unable to bootstrap PHP CGI operator profile`. Its only Safe context field is
`violation`, drawn from this closed ordered vocabulary:

1. `sapi_mismatch`
2. `ini_snapshot_invalid`
3. `enable_post_data_reading_enabled`
4. `output_buffering_enabled`
5. `zlib_output_compression_enabled`
6. `display_errors_enabled`
7. `display_startup_errors_enabled`
8. `html_errors_enabled`
9. `expose_php_enabled`
10. `auto_prepend_file_configured`
11. `auto_append_file_configured`
12. `cgi_rfc2616_headers_enabled`

The first failure wins. Rejected SAPI and INI values are never included in or
retained by the exception or its diagnostic surfaces.

### Independent current-delivery evidence

The Internal `CgiTrustedPresentationPermissionEvaluator` derives only
`AdapterControlledDeliveryPermission` from one caller-supplied current-delivery
snapshot. It returns `Allowed` only when:

- SAPI is exactly `cgi-fcgi`;
- `GATEWAY_INTERFACE` is exactly `CGI/1.1`;
- `FCGI_ROLE` is exactly `RESPONDER`;
- `REMOTE_ADDR` is exactly `127.0.0.1` or `::1`;
- every scanned key is a string; and
- `HTTP_FORWARDED`, `HTTP_X_FORWARDED`, and every key beginning with
  `HTTP_X_FORWARDED_` are absent.

Every other input returns `Denied`; denial is not exceptional. Forwarding
markers deny regardless of value. The two canonical loopback strings are not
expanded or normalized. The evaluator does not receive the operator profile or
bootstrap permission, does not read global state, and does not construct
combined presentation eligibility.

Loopback alone never authorizes presentation. Later composition must still
combine an affirmative immutable bootstrap permission with one fresh
affirmative delivery permission before protected source access. Missing,
invalid, proxied, forwarded, noncanonical, non-loopback, or uncertain evidence
fails closed.

### Ownership and dependency direction

`kongono/php-cgi` adds one direct dependency on
`kongono/diagnostic-activation`. PHP CGI may produce the two existing typed
permission values. Diagnostic Activation, Web Runtime, and inner packages do
not depend on PHP CGI, so no reverse edge or package cycle is introduced. The
production package count remains exactly fourteen.

### Lifecycle and concurrency

An operator profile is selected once in trusted bootstrap code. Successful
bootstrap produces an immutable permission that may be reused across sequential
deliveries in a persistent process. Changing the profile requires explicitly
constructing a new bootstrap/runtime; there is no mutation or cross-delivery
update.

Current-delivery evidence is evaluated independently for each fresh delivery.
No evidence or result is retained across calls, so one delivery cannot upgrade
or downgrade another. Process-level concurrency remains owned by the external
operator and is not added here.

### API status

`CgiOperatorProfile`, `CgiOperatorBootstrap`, and
`CgiOperatorBootstrapException` are Experimental. Their names and exact
mechanisms may change and are not Stable compatibility commitments.
`CgiOperatorBootstrapValidation` and
`CgiTrustedPresentationPermissionEvaluator` are Internal implementation
boundaries and are not extension points.

## Consequences

- Operator intent is explicit, typed, inspectable, and independent from client
  input.
- Native prerequisites reject before any delivery begins and disclose only a
  closed safe token.
- Trusted presentation still requires two independent affirmative permissions;
  neither a development profile nor loopback evidence is sufficient alone.
- Forwarded, proxied, and uncertain origin evidence fails closed.
- No general configuration, authentication, authorization, proxy-trust system,
  concrete delivery, or production hosting mechanism is introduced.

## Rejected alternatives

- Parsing a profile from strings, environment variables, files, or request
  values would create an ambient configuration and trust surface.
- Treating loopback or a development profile as sufficient would collapse the
  two independent permissions and allow stale or client-influenced evidence to
  authorize presentation.
- Accepting forwarded headers or normalized address families would claim proxy
  and network trust semantics not selected by this phase.
- Storing raw process or delivery evidence would weaken persistent-runtime
  isolation and expand disclosure risk.

## Follow-up

Phase 3F alone may implement the concrete PHP CGI delivery, Web Runtime
composition, and production entry boundary. It must consume these independent
typed inputs without changing their fail-closed semantics. Deployment and
external production-profile acceptance remain later work.

## Related decisions

- [ADR 0038: Fail-Closed Trusted-Developer Diagnostic Activation](0038-fail-closed-trusted-developer-diagnostic-activation.md)
- [ADR 0039: Typed Trusted-Developer Bootstrap Permission Foundation](0039-typed-trusted-developer-bootstrap-permission-foundation.md)
- [ADR 0052: PHP CGI FastCGI Responder Profile](0052-php-cgi-fastcgi-responder-profile.md)
- [ADR 0053: Bounded PHP CGI Inbound Conversion](0053-bounded-php-cgi-inbound-conversion.md)
- [ADR 0054: Bounded PHP CGI Response Emission](0054-bounded-php-cgi-response-emission.md)

## Phase 3F integration status

[ADR 0056](0056-php-cgi-production-delivery-and-entry-composition.md) now binds
these immutable trust inputs to the concrete one-delivery boundary. The
Experimental entry receives an already-composed runtime and does not combine
permissions itself. `Production` remains denied. `LoopbackDevelopment` still
requires exact bootstrap prerequisites plus independent direct-loopback,
non-forwarded current-delivery evidence before lazy request input access.

## Phase 3G reconciliation

ADR 0057 adds no configuration input and does not collapse the two permissions.
The consumer still selects the literal profile before bootstrap, then constructs
and boots its application before supplying the paired shutdown closure to the
front controller. `Production` remains denied. The deployment contract records
fixed prerequisites, external process retirement after uncertain emission or
shutdown, and caller ownership of partial-boot cleanup; it adds no listener,
server configuration, deployable artifact, or external proof. Phase 3H alone is
next.

## Phase 3H outcome

Phase 3H externally proves the immutable profile selection and all ten native
prerequisites one failure at a time. `Production` remains bootstrap-denied;
`LoopbackDevelopment` requires literal direct loopback and independently denies
forwarded or nonloopback deliveries. Request input cannot change the fixture's
literal profile. No general configuration, secret handling, production
listener, deployment artifact, or authorization expansion is introduced.
