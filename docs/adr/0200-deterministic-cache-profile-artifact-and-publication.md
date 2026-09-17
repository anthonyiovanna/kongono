# ADR 0200: Deterministic Cache Profile Artifact and Publication

- Status: Accepted
- Date: 2026-09-16

## Context

Phase 20B defines neutral cache values but intentionally supplies no profile,
artifact, filesystem, adapter, or runtime behavior. Applications need a
deterministic build-time description of the bounded cache policy they intend to
use without converting that description into cache, backend, or effect
authority.

## Decision

Phase 20C adds the separately optional Experimental `kongono/cache-build`
package. One explicit complete list of Version 1 declarations is detached,
bounded to 256 entries, checked for unique profile names and namespaces, and
ordered by ordinal profile name. The set identity hashes its complete canonical
declaration model.

The fixed `kongono/cache-profile-artifact/1` build produces compact canonical
UTF-8 JSON with one terminal LF. Structural validation is duplicate-aware,
closed-schema, order-sensitive, bounded to 1,048,576 bytes and depth 8, and
reconstructs every neutral value. Exact-current validation rebuilds from one
explicitly supplied current set and requires byte, identity, declaration, and
count equality.

Publication accepts one existing caller-selected root and one already validated
artifact. It writes only
`objects/sha256/<prefix>/<artifact-sha256>/cache-profile.json` through an
operation-owned temporary sibling and atomic rename. Existing destinations are
accepted only when their complete ordinary-file inventory and bytes are exact.

Diagnostics disclose only closed Safe reasons/counts and Protected identities.
They never expose raw rejected profile text, keys, values, artifact JSON,
credentials, provider addresses, private roots, temporary names, or arbitrary
native messages.

## Consequences

The package is inert build-time evidence. It adds no serializer, executable
codec, cache operation, adapter, backend, provider, clock, runtime, command,
workflow, discovery, retry, lock, lease, queue, session, rate limiter, or
production-readiness claim. Cache loss and artifact loss remain non-authoritative.
Phase 20D and later phases remain separately Planned.
