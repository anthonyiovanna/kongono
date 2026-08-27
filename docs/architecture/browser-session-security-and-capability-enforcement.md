# Browser-Session Security and Capability Enforcement

## Purpose and context

Phase 9F connects explicit HTTP evidence to the accepted Authentication and Security Runtime boundaries, then places Authorization directly around protected capability execution. It does not merge those responsibilities:

```text
supplied PSR-7 request
  -> browser.session.v1 structural transport validation
  -> exact selected authentication verifier
  -> fresh operation Security Context
  -> exact Authorization request evaluation
  -> protected capability execution only for exact Permit
```

Authentication establishes bounded facts; it does not authorize. Routing and middleware organize delivery; they do not grant access. An authorization receipt records evidence; it cannot execute or replay a capability.

## Selected browser profile

The sole profile is `browser.session.v1`:

- Cookie: `__Host-kongono_session`
- CSRF request header: `X-Kongono-CSRF`
- Authentication mechanism: `browser.session.v1`
- Session and proof syntax: exactly 43 unpadded ASCII base64url characters

The adapter accepts one explicit trusted HTTPS origin. Cookie input is bounded to 4,096 bytes and 64 pairs. Multiple Cookie header lines, malformed pairs, a duplicate reserved cookie, invalid evidence, non-HTTPS transport, or a different origin fails closed. Unknown valid cookies are ignored. Forwarded headers, Host substitutions, request attributes, environment, and ambient request state are not evidence.

Only `GET`, `HEAD`, and `OPTIONS` are safe. Every other method carrying the reserved cookie requires exactly one matching `Origin` and exactly one valid CSRF header. The adapter checks structure and origin; the selected verifier owns live-session lookup and proof binding. A request with no reserved cookie may start one anonymous operation and makes no authentication call.

## Cookie emission boundary

Issuance is exactly:

```text
__Host-kongono_session=<identifier>; Path=/; Secure; HttpOnly; SameSite=Strict
```

Expiration uses an empty value, `Max-Age=0`, `Expires=Thu, 01 Jan 1970 00:00:00 GMT`, and the same ordered host-only attributes. These strings are transport primitives. They generate no entropy, perform no hashing/signing/encryption, know no session truth, write no store, mutate no response, and implement no login/logout flow.

## Protected capability enforcement

A protected capability declares one exact Action and Resource Reference. The stateless enforcer receives the operation Security Context, Authorization engine, Authorization request, and capability. It verifies identity/equality bindings before evaluation, authorizes once, reads the decision once, and executes once only for exact `Permit`. It returns the exact result without retaining it.

Context, action, or resource mismatch stops before evaluation. `Deny` and `Indeterminate` stop before execution. Authorization failure remains an Authorization failure; capability failure after `Permit` propagates unchanged. Neither operation is retried or split into an authorize-now/execute-later handle.

## Lifecycle, concurrency, and disclosure

Gateway and enforcer services retain no call state. Sequential and overlapping/reentrant calls have independent evidence, contexts, decisions, capabilities, and results. Operation close and verifier retire behavior continue to be owned by Security Runtime and Authentication. No destructor cleanup or ambient current-user/session/decision state exists.

Package diagnostics expose only fixed Safe labels. Sensitive identifiers and proofs have deliberately named accessors only for the verifier or cookie-emission boundary and provide no implicit string, JSON, or debug conversion.

## Use and non-use

Use `kongono/security-http` only for the selected same-origin HTTPS browser-session profile. Use `kongono/security-enforcement` at the typed boundary immediately before a protected read or effect. Do not use either package as a session store, provider, policy engine, account product, middleware grant, bearer capability, decision cache, CORS authority, or deployment/TLS configuration.

Phase 9G now composes this unchanged gateway and enforcer through one supported
upper application workflow. The exact current policy artifact supplies identity
evidence only; application-owned evaluator logic still decides, and only exact
Permit here executes. The copy-once skeleton's evaluator and no-session verifier
are examples, not providers. The graph is exactly forty-four acyclic production
packages. Phase 9H externally proves this selected profile, exact-Permit
enforcement, lifecycle isolation, and nondisclosure without adding a second
profile/provider or production authority. See the
[security application workflow](security-application-workflow.md) and
[Phase 9 external acceptance guide](phase-9-external-acceptance.md).
