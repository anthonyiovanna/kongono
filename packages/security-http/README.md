# Kongono Security HTTP

`kongono/security-http` is the Experimental outer adapter for exactly one
same-origin HTTPS browser profile: `browser.session.v1`.

The fixed transport names are `__Host-kongono_session` and
`X-Kongono-CSRF`. Session identifiers and CSRF proofs are exact 43-character
ASCII base64url values without padding. They are opaque sensitive transport
syntax, not entropy, cryptography, signatures, storage, rotation, expiry,
invalidation, login, logout, or authorization.

Cookie issuance emits exactly:

```text
__Host-kongono_session=<opaque>; Path=/; Secure; HttpOnly; SameSite=Strict
```

Expiration uses an empty value followed by `Max-Age=0`, the fixed past
`Expires=Thu, 01 Jan 1970 00:00:00 GMT`, then the same `Path`, `Secure`,
`HttpOnly`, and `SameSite` order. No `Domain` or widening attribute exists.

The gateway reads only an explicit PSR server request. Cookie input is bounded
to one 4,096-byte line and 64 pairs. Malformed, duplicate reserved, non-HTTPS,
or wrong-origin cookie evidence fails closed and never becomes anonymous.
Exactly GET, HEAD, and OPTIONS are safe methods. Every other cookie-authenticated
method requires one exact trusted `Origin` and one structurally valid CSRF proof.
The selected verifier remains responsible for live-session truth and proof
binding.

No cookie begins one anonymous generated Security Runtime operation without an
authentication call. A presented cookie begins an authenticated operation only
after exact Authenticated verification. Every non-success outcome stops without
anonymous downgrade. The returned owner delegates root/context access and
exactly-once close while retaining no request state on the reusable gateway.

This package does not authorize, enforce capabilities, select routes or status
codes, mutate messages, use request attributes, perform store/network/clock/
randomness work, or add a second profile. Phase 9G behavior is absent.
