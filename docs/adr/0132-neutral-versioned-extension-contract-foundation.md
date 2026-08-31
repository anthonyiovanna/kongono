# ADR 0132: Neutral Versioned Extension Contract Foundation

## Status

Accepted and implemented by Phase 12B.

## Context

Phase 12A established the governed Extension Platform architecture and kept
declaration, deterministic admission, application-owned capability grant, and
execution separate. Phase 12B needs a first production package that can carry
bounded extension declaration evidence without creating any of those later
authorities.

Package installation, self-description, a category label, visibility, a
capability request, or a contribution claim cannot safely establish trust,
authorization, Application Graph truth, hosting, or execution. The foundation
therefore needs exact local grammar and canonical collection rules while
remaining independent of Application Graph and runtime packages.

## Decision

Create the Experimental `kongono/extension-contract` package. It depends only
on API Metadata, Diagnostics, and PHP. The package owns:

- contract version 1 and the exact Core, Trusted/in-process, Sandboxed, and
  External architectural categories;
- exact bounded extension, capability, contribution, schema, and source
  identities plus an opaque extension version;
- exact lowercase SHA-256 provenance;
- inert versioned capability requests with bounded purpose and required or
  optional intent;
- contribution identity, schema, visibility, and source digest without a
  payload or Application Graph fact;
- application- or operation-scoped lifecycle intent without callbacks or host
  behavior; and
- one immutable aggregate declaration with typed lists, fixed limits,
  uniqueness, strict binary canonical ordering, detachment, and preservation of
  object identity.

Caller input is rejected rather than normalized, reordered, or deduplicated.
All aggregate validation completes before state assignment. Canonical keys are
private implementation details.

The package owns diagnostics `KGN-EXTENSION-1201` through
`KGN-EXTENSION-1207`. Their messages and Safe contexts contain only fixed
allowlisted violation tokens and optional nonnegative counts/positions. The
Public package exception marker remains independent of every other package
marker; other consumer declarations are Experimental and infrastructure
declarations are Internal. No declaration is Stable.

## Security and non-authority boundary

Construction proves only local grammar, bounds, types, uniqueness, and order.
It does not prove admission, currentness, trust, package integrity, publisher
identity, authorization, isolation, or executability. A request is not a grant;
a contribution is not an authoritative Graph fact; visibility is not
authorization or declassification; lifecycle is not a host; and category is
not a security decision.

The package contains no path, URL, credential, secret, payload, callable, PHP
symbol, discovery input, or executable instruction. It performs no filesystem,
network, environment, process, reflection-discovery, serialization, registry,
Composition, or Application Graph operation.

## Topology

Phase 12B brings the production graph to exactly 58 packages and 203 acyclic
local production dependency edges. No existing production package depends on
`kongono/extension-contract`; only the root development aggregate requires it.

## Consequences

Applications and future build tooling can exchange one exact neutral
declaration vocabulary without smuggling in admission or execution authority.
The strict bounds and canonical ordering make later deterministic evidence
possible, while rejection instead of normalization keeps caller intent and
failure visible.

The package deliberately cannot answer whether an extension should be
selected, trusted, granted access, projected into the Graph, loaded, or run.

## Rejected alternatives

- Treating installed packages, Composer metadata, scanning, Reflection, newest
  files, or mutable registries as extension declarations.
- Combining a capability request with a grant or authorization decision.
- Treating contribution metadata as an Application Graph record or fact.
- Adding payloads, PHP class names, callables, hooks, lifecycle callbacks, or a
  host interface to the neutral package.
- Adding serialization, artifact publication, exact-current validation,
  Composition wiring, or Application Graph integration in Phase 12B.
- Calling Trusted/in-process a sandbox or treating category/visibility labels
  as security evidence.

## Open work

Phase 12C may add deterministic declaration/artifact build and exact-current
validation only under separate authenticated authority. Phase 12D through 12H
remain planned for admission and Graph projection, capability planning and
generated wiring, trusted/in-process hosting, inspection, and external
acceptance. None has started.

Kongono remains Experimental, pre-1.0, and not production-ready.
