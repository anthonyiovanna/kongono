# Kongono Network-Egress Policy Build

`kongono/network-egress-policy-build` is the optional Experimental Phase 15C
package for deterministic network-egress policy evidence. It turns one
complete application-owned typed policy source into a canonical version-1
artifact, validates supplied artifact bytes, compares them with one explicitly
supplied current source, and can publish them to one explicit directory.

The source binds one lowercase policy identity, one to sixty-four exact
canonical origins in canonical order, closed address, transport, redirect,
proxy, cookie, and credential postures, one finite `EgressBudget`, and an exact
set of required transport capabilities. These values are inert policy claims.
They do not authorize or perform a request.

## Boundaries

- Construction and validation are deterministic and offline. They consult no
  environment, filesystem, registry, resolver, endpoint, or live authority.
- Structural validation proves exact framing, schema, member order, typed
  reconstruction, compatibility, canonical bytes, and digests.
- Current validation rebuilds from the one complete source supplied by the
  caller and requires byte-identical evidence.
- Publication accepts one explicit existing canonical ordinary writable
  directory. It is content-addressed, same-directory atomic, idempotent for
  identical bytes, no-clobber for conflicts, and cleans temporary files.
- Artifact bytes are Protected application policy configuration. Diagnostics
  expose only closed Safe labels and bounded numeric context.

The package provides no resolver, DNS lookup, connection, socket, TLS
implementation, credential provider, proxy discovery, cookie jar, transport,
runtime authorization, request execution, network effect, command, loader,
mutable registry, or provider compatibility. Phase 15D is a separate boundary
that now consumes exact-current policy evidence only
through the optional bounded runtime; it does not change this inert build
package. Phase 15E and later rows require separately authenticated authority.

See [the architecture guide](../../docs/architecture/network-egress-policy-artifact.md)
and [ADR 0157](../../docs/adr/0157-deterministic-network-egress-policy-artifact.md).
