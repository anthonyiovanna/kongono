# PHP CGI Deployment Contract

## Supported profile

Kongono supports only PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` for this
profile. The consumer must select a literal `CgiOperatorProfile` before calling
`CgiOperatorBootstrap::load()`. Dynamic profile strings and client-controlled
operator policy are unsupported.

This guide is an operational contract, not a runnable server configuration.
Phase 3H's root-owned harness supplies external acceptance evidence for the
selected synchronous PHP-CGI FastCGI RESPONDER profile, but it is not a
production or supported server/client. The authoritative request, response, byte/count,
and unsupported-input contract remains the [PHP CGI FastCGI
profile](../architecture/php-cgi-fastcgi-profile.md). In summary, Kongono
captures only its closed CGI server allowlist, rejects malformed or ambiguous
values, supports bounded origin-form request conversion and a body no larger
than 1,048,576 bytes, rejects PHP-parsed post/upload presence and unsupported
multipart input, and fully stages a response body no larger than 1,048,576
bytes before one emission attempt. Streaming, hop-by-hop fields, ambiguous
headers, proxy trust inference, rollback, and retry are unsupported.

## Composition order

The consumer-owned composition root must use this order:

```text
select literal CgiOperatorProfile
    -> CgiOperatorBootstrap::load(profile)
    -> explicitly construct and boot WebApplication
    -> construct WebRuntimeAdapter(application, bootstrap permission)
    -> CgiFrontController::run(runtime, application shutdown Closure)
```

PHP-shaped composition:

```php
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Entry\CgiFrontController;
use Kongono\Web\Runtime\WebRuntimeAdapter;

$profile = CgiOperatorProfile::Production;
$bootstrapPermission = CgiOperatorBootstrap::load($profile);

// Application-specific composition belongs here: explicitly construct and
// boot $application, including its Kernel, routes, middleware, and handlers.
$runtime = new WebRuntimeAdapter($application, $bootstrapPermission);

CgiFrontController::run($runtime, $application->shutdown(...));
```

The first-class closure must belong to the same already-booted application
wrapped by the runtime. The consumer owns cleanup for any partial construction
or boot failure before `CgiFrontController::run()` is entered.

## Required PHP directives

The current bootstrap validator accepts exactly:

| Directive | Accepted value |
| --- | --- |
| `enable_post_data_reading` | `0` or the empty disabled representation |
| `output_buffering` | exactly `0` |
| `zlib.output_compression` | `0` or empty |
| `display_errors` | `0` or empty |
| `display_startup_errors` | `0` or empty |
| `html_errors` | `0` or empty |
| `expose_php` | `0` or empty |
| `auto_prepend_file` | exactly empty |
| `auto_append_file` | exactly empty |
| `cgi.rfc2616_headers` | `0` or empty |

`display_errors=0` is mandatory protection against an escaped exception being
appended to the response. These values are fixed process prerequisites, not a
general Kongono configuration system.

## Failure and retirement contract

| Entry result | Shutdown result | Observable result |
| --- | --- | --- |
| success | success | return `void` |
| failure `P` | success | throw exact `P` |
| success | failure `S` | throw exact `S` |
| failure `P` | failure `S` | throw `CgiEntryShutdownException(P, S)` |

Entry and shutdown are each attempted once, in that order. There is no second
response, retry, rollback, log, or automatic recovery. A committed or uncertain
emission failure, or any escaped shutdown failure, means state is uncertain;
the external host must retire and replace the PHP process. Kongono does not
kill or restart it.

Serve one request delivery at a time per interpreter invocation. This profile
makes no claim that one runtime can be reused concurrently or after uncertain
emission or shutdown.

## Diagnostic trust and disclosure

`Production` always denies trusted diagnostic presentation. Development trust
requires both the separate literal `LoopbackDevelopment` bootstrap permission
and fresh current-delivery evidence of exact direct canonical loopback
`127.0.0.1` or `::1`, with no `Forwarded`, `X-Forwarded`, or
`X-Forwarded-*` marker. Missing, proxied, ambiguous, failed, or uncertain
evidence denies before protected source access. Client input cannot enable or
widen either permission.

Diagnostic 1019 stores only its fixed Safe violation token. Its two throwable
accessors are developer-inspection surfaces and may contain sensitive detail;
they are not production-safe output.

## Ownership

Kongono owns bounded native capture, conversion, trusted-or-generic response
selection, full response staging and one emission attempt, delivery close, and
the front controller's one application-shutdown attempt.

The application owns Kernel, routes, middleware, handlers, application-specific
construction and boot, cleanup after partial construction or boot failure, and
the explicit shutdown closure supplied after successful boot.

The external host/operator owns socket listening, FastCGI framing, TLS,
proxy policy, filesystem permissions, PHP process lifecycle, timeouts, resource
ceilings outside Kongono's bounds, logs, monitoring, restart/retirement policy,
deployment, secrets, and incident response.

## Unsupported environments and non-guarantees

This contract supplies no Apache, Nginx, Caddy, or IIS snippet; Docker or
service-manager file; `.env`; generated application, bootstrap, or entry script;
health check; listener; process manager; worker supervisor; deployment
automation; secrets mechanism; or production-hardening certification. Other
SAPIs and FastCGI roles are unsupported.

Phase 3H completed real-host CGI/FastCGI acceptance and the Phase 3 checkpoint.
No production package depends on the acceptance code. Production trusted
diagnostics remain denied, and process, proxy, TLS, credentials, logs,
monitoring, incidents, and deployment remain operator-owned. Phase 3 is not the
full framework, Stable API, 1.0, universal SAPI support, deployment automation,
or production-hardening certification. No next phase starts automatically.

## Related material

- [ADR 0057: Bounded PHP CGI Front-Controller Lifecycle and Deployment Constraints](../adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md)
- [PHP CGI FastCGI profile](../architecture/php-cgi-fastcgi-profile.md)
- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)
