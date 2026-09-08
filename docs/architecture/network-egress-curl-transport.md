# Network egress cURL transport

## Purpose and status

`kongono/network-egress-curl` is the optional Experimental first-party adapter
between the accepted Phase 15D transport port and PHP `ext-curl`. Install it only
for applications that explicitly select this profile. It is neither a global
HTTP client nor the network-egress control plane.

Phase 15D still owns current authority and policy evidence, one resolver call,
candidate admission, exact address selection, budgets, cancellation checkpoints,
uncertainty, and cleanup coordination. The cURL adapter admits one already-built
operation, binds its selected address, performs at most one synchronous transfer,
and returns one terminal emission.

## Collaborators and flow

1. The application constructs `CurlTlsTrust` from application-owned CA PEM
   bytes and a `CurlTransportProfile`.
2. `CurlEgressTransport` validates the required native cURL capabilities once
   and exposes the fixed HTTP/1.1, TLS, and request-streaming capability set.
3. Phase 15D supplies an `EgressTransportRequest` containing the exact operation,
   attempt identity, and selected literal address.
4. `open()` validates the request locally and allocates one fresh easy handle.
5. The first `next()` performs one transfer and produces one terminal emission.
   Later pulls return `null`.
6. Phase 15D calls idempotent `close()` exactly once under its existing cleanup
   ownership rules.

No step performs discovery, name resolution, authorization, retry, replay,
fallback, redirect handling, or background work.

## Profile and TLS trust

The trust constructor accepts 1 to 1,048,576 bytes, rejects NUL and incomplete
certificate markers, copies the bytes, and exposes them only for native
`CURLOPT_CAINFO_BLOB` use. It performs no path, environment, certificate-store,
credential, secret, or network lookup. CA certificates are caller-owned trust
material; they are not credentials and do not grant network authority.

The profile contains only that trust value and `allowPlainHttp`, which defaults
to `false`. HTTPS verification cannot be disabled. Plain HTTP proceeds only when
both the accepted application policy allows explicit HTTP and the adapter profile
opts in; Phase 15D remains responsible for the policy half of that conjunction.

```php
$trust = new CurlTlsTrust($syntheticApplicationCaPem);
$profile = new CurlTransportProfile($trust);
$transport = new CurlEgressTransport($profile);
```

The example constructs values only and performs no request.

## Fixed cURL safety posture

| Concern | Fixed option or behavior |
| --- | --- |
| URL identity | Canonical origin hostname remains in the URL |
| Address | One `CURLOPT_RESOLVE` host/port/literal mapping |
| Protocol | `CURLOPT_HTTP_VERSION = CURL_HTTP_VERSION_1_1` |
| Schemes | `CURLOPT_PROTOCOLS_STR = http,https` |
| Redirects | Follow disabled, maximum zero, redirect protocols limited to HTTP and HTTPS |
| Proxy | Empty proxy and `CURLOPT_NOPROXY = *` |
| Netrc | `CURL_NETRC_IGNORED` |
| TLS | Peer true, verify-host 2, explicit CA-info blob |
| Decoding | Content decoding false; HTTP transfer decoding true |
| Errors | HTTP 4xx/5xx remain responses |
| Lifetime | Fresh connection and reuse forbidden |
| Time | Finite connect and total millisecond budgets |
| Callbacks | Explicit request-read, header, body, and progress callbacks |

No raw option injection is accepted, so a caller cannot override this table.
There is no share handle, multi handle, global initialization state, cookie
engine, proxy tunnel, resolver callback, or authentication callback.

## Request mapping

The operation origin must equal the PSR-7 URI origin. Schemes are lowercase
`http` or `https`; the method is a token other than `CONNECT` or `TRACE`; and the
target is origin-form with no authority, scheme, fragment, whitespace, or
control byte. Host appears exactly once and matches the canonical hostname plus
the explicit non-default port representation.

Header names and values are validated. `Connection`, `Proxy-Connection`,
`Keep-Alive`, `Transfer-Encoding`, `Upgrade`, `Trailer`, `Expect`, and
`Proxy-Authorization` are prohibited. A supplied Content-Length is one canonical
decimal equal to the remaining known body bytes. Header count, header bytes,
complete mapped request bytes, response bytes, and buffering must all fit the
operation budget before handle allocation.

The readable body is consumed once from its current cursor. The cursor is not
rewound or restored, and the body is not cloned, stringified, hashed, or replayed.

## Response mapping

cURL removes HTTP transfer framing but does not decode content encodings. Header
callbacks enforce count, aggregate-header, retained-evidence, response, and
buffer limits. Informational 1xx blocks may precede the final block; exactly one
complete non-1xx HTTP/1.1 block becomes the response. Malformed, duplicated,
control-bearing, incomplete, or oversized evidence fails closed.

The content-encoded body is written only to `php://memory`. A successful
first-party `Response` owns the readable, seekable stream at position zero.
Redirect statuses are returned normally and are never followed.

## Transfer and failure evidence

| Observation | Transfer state | Typical neutral failure |
| --- | --- | --- |
| No request or response byte | `None` | connection or validation |
| Request bytes, no response | `RequestPartiallySent` | connection, timeout, or uncertain effect |
| Any response byte, incomplete | `ResponsePartiallyReceived` | response, protocol, budget, or uncertain effect |
| Complete valid response | `ResponseReceived` | none |

Numeric cURL codes distinguish connection, TLS, timeout, protocol, response,
budget, and uncertain-effect cases. Localized error text is never retained.
Transfer evidence never claims that the remote peer processed the request.

## Lifecycle and cleanup

| State | `next()` | `close()` |
| --- | --- | --- |
| Fresh | Executes once and returns terminal evidence | Retires without network I/O |
| Running | Re-entry fails closed | Coordinated after the pull returns |
| Terminal | Returns `null` | Retires native and memory resources |
| Closed | Returns `null` | No-op |

Cleanup failures preserve the original throwable as `getPrevious()` while the
package message and string form remain bounded and nondisclosing. Destructors are
not part of the calling contract.

## Capability and interoperability boundary

| Capability | Advertised |
| --- | --- |
| HTTP/1.1 | Yes |
| TLS | Yes |
| Request streaming | Yes, synchronous pull from current cursor |
| HTTP/2, HTTP/3 | No |
| Response streaming | No |
| Mid-transfer cancellation | No |
| Reuse, proxy, mutual TLS, trailers | No |

PSR-7 supplies request and response message semantics. PSR-18 may be considered
by an outer application adapter, but it is not the Phase 15D control plane and
cannot replace exact selected-address binding, authority rechecks, budgets,
transfer evidence, cleanup, or uncertainty. This package does not depend on or
implement `psr/http-client`.

## Failure, performance, and limitations

Memory use is bounded by the response buffer and retained header budgets. One
session owns one easy handle and performs one blocking transfer. Large request
bodies are read incrementally, while responses are deliberately buffered because
the profile does not advertise response streaming.

No compatibility claim follows merely from loading `ext-curl`. Offline units
prove the fixed local mapping only. Phase 15F adds the separate exact-current
single-profile application workflow without changing this adapter. Phase 15G
adds only the separate offline visibility-safe inspection leaf under ADR 0161.
Phase 15H is Accepted and Phase 15 is closed; there is no first-party resolver, credential
provider, live inspection probe, inspection command, provider adapter, public
compatibility certification, Stable/1.0 claim, production-readiness claim, or
public Internet or production-readiness certification. The current topology is
81 packages / 317 local edges / acyclic, with PHP inventories 1,514 / 768 / 84.
