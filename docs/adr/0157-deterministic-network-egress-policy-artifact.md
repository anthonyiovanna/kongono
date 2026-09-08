# ADR 0157: Deterministic Network-Egress Policy Artifact

- Status: Accepted
- Date: 2026-09-07
- Phase: 15C
- Stability: Experimental implementation contract

## Context

The inert Phase 15B contract can describe an operation and its claimed policy
reference, but a later runtime must not infer destinations or postures from
configuration arrays, environment state, or a mutable registry. An application
needs deterministic evidence binding its exact intended origins, closed
security postures, finite budget, and required transport capabilities before
any separately governed runtime may consider execution.

## Decision

Create the optional `kongono/network-egress-policy-build` package. One complete
typed `EgressPolicySource` binds a version-1 lowercase policy identity, one to
sixty-four exact canonical origins, closed address-scope, transport-security,
redirect, proxy, cookie, and credential modes, one finite `EgressBudget`, and
an exact `TransportCapabilitySet`.

Origins are unique and supplied in canonical scheme, host, and port order.
Compatibility is fail-closed: an HTTPS-only posture excludes HTTP origins,
HTTPS requires the TLS capability, same-origin redirects require a positive
redirect budget while denied redirects require zero, and explicit proxy use
requires the proxy capability. These checks validate only the supplied policy;
they do not resolve a host or authorize a request.

The builder emits compact UTF-8 JSON with fixed schema, versions, member order,
values, a lowercase SHA-256 of the canonical source projection, and exactly one
terminal LF. The artifact SHA-256 covers the complete canonical artifact bytes.
The two digests have different domains and neither authenticates the source.
The neutral Phase 15B `EgressPolicyReference` uses the policy identity and an
uppercase rendering of the source digest to preserve its accepted contract.

Structural validation reconstructs the typed policy and rebuilds it exactly.
Caller-relative current validation additionally rebuilds from one explicitly
supplied source and requires byte identity. It performs no discovery or
self-loading. Publication uses the artifact digest in the filename, validates
before I/O, accepts one explicit existing canonical ordinary writable
directory, creates an exclusive same-directory temporary file, flushes and
syncs complete bytes, publishes without overwrite, accepts an identical target
idempotently, rejects conflicts, and cleans the temporary file.

Artifact content is Protected application policy configuration, including
exact origin names. Diagnostics and deliberate exception messages therefore
contain only closed Safe labels and bounded numeric evidence; paths, origins,
identities, digests, credentials, and underlying throwable messages are not
presented.

## Consequences

Applications can build, validate, compare, and publish exact policy evidence
offline. Artifact validity and caller-relative currentness do not establish
current authorization, live DNS or endpoint state, safe resolved addresses,
credential availability, transport compatibility, delivery, or external truth.
They grant no runtime or network authority.

The package adds three local production dependencies and produces the accepted
77-package, 294-edge acyclic production graph. No existing production package
depends on it. PHP inventories are 1,408 production, 714 package-test, and 80
root-test files.

## Rejected alternatives

- Untyped configuration arrays would weaken exact reconstruction and public
  contracts.
- Glob, wildcard, suffix, or path-style destinations would obscure the exact
  origin boundary.
- Environment discovery and mutable registries would make policy selection
  ambient and currentness unverifiable.
- Runtime authorization inside the builder would collapse inert evidence into
  authority.
- Self-loading the newest or apparent-current artifact would replace explicit
  caller-relative currentness with filesystem state.
- Overwrite or update-in-place publication would weaken content-addressed
  evidence and no-clobber behavior.

## Non-capabilities

Phase 15C adds no resolver, DNS, connection, socket, TLS implementation,
credential provider, proxy discovery, cookie jar, transport, runtime
authorization, network effect, command, loader, provider integration, or
compatibility claim. Phase 15D through Phase 15H remain Planned.
