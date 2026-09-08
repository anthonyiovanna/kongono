# Kongono Network Egress cURL

`kongono/network-egress-curl` is an optional Experimental HTTP/1.1 transport
adapter for the Phase 15D network-egress runtime. It binds one canonical origin
to one caller-selected literal address through cURL while preserving the origin
hostname for HTTP authority, SNI, and certificate verification.

## Boundary

Use this package only when an application has already produced exact-current
Phase 15C policy evidence and the Phase 15D runtime has selected one admitted
address. The adapter performs one synchronous native transfer and returns one
terminal neutral emission. It does not resolve names, decide authority or
policy, discover credentials, follow redirects, use proxies or cookies, retry,
pool handles, or implement PSR-18.

The caller supplies a bounded in-memory CA bundle to `CurlTlsTrust`. Peer and
hostname verification cannot be disabled, and the bytes are passed only through
`CURLOPT_CAINFO_BLOB`. Plain HTTP is rejected by default and requires the
separate `CurlTransportProfile` opt-in in addition to accepted application
policy.

```php
use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressCurl\Transport\CurlEgressTransport;

$trust = new CurlTlsTrust($applicationOwnedCaBundlePem);
$transport = new CurlEgressTransport(new CurlTransportProfile($trust));
```

Construction validates local cURL capabilities but performs no network effect.
Opening a session performs local request admission and creates one fresh easy
handle. The first `next()` call performs the only transfer; later calls return
`null`. `close()` is explicit and idempotent.

## Security and limitations

Only `http` and `https` are admitted and HTTP/1.1 is forced. Proxy use,
redirects, netrc, automatic authentication, content decoding, connection reuse,
and caller-supplied raw cURL options are disabled. Request bodies are consumed
once from their current cursor. Responses are transfer-decoded for HTTP framing
only and buffered in `php://memory` within the operation budget.

Tests are deterministic and offline; they do not call a public or local
endpoint. Phase 15H remains responsible for supported local transport
conformance and any broader compatibility statement.
