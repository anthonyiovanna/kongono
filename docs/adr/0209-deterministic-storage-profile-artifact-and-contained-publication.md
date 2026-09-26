# ADR 0209: Deterministic Storage Profile Artifact and Contained Publication

- Status: Accepted
- Date: 2026-09-18

## Context

Phase 21B defines neutral storage identities, capabilities, requests, results,
evidence, and lifecycle interfaces. It intentionally supplies no profile,
artifact, filesystem publication, adapter, provider, runtime, credential, or
operation behavior. Applications need deterministic build-time evidence for
the bounded storage policy they explicitly declare without turning that
evidence into storage authority.

## Decision

Phase 21C adds the separately optional Experimental `kongono/storage-build`
package. One explicit complete set of 1 through 256 Contract Version 1 profile
declarations is detached, validated, checked for unique profile names and
namespaces, and ordered by ordinal profile-name bytes. Source identity is
domain-separated by `kongono/storage-profile-source/1`.

The fixed `kongono/storage-profile-artifact/1` schema produces compact
canonical UTF-8 JSON with exactly one terminal LF. Source, build, and exact-byte
artifact identities remain distinct. Structural validation is duplicate-aware,
closed-schema, order-sensitive, bounded to 1,048,576 bytes and depth 16, and
reconstructs every typed declaration. Exact-current validation rebuilds from
one explicitly supplied current set and requires exact artifact-byte equality.

Publication accepts one already validated artifact and one exact
caller-supplied absolute root. It writes only
`objects/sha256/<prefix>/<artifact-sha256>/storage-profile.json` through an
exclusive temporary sibling and same-directory atomic rename. It never
overwrites, truncates, repairs, or replaces an existing destination.
Byte-identical ordinary existing content is accepted only as idempotent
evidence. No mutable `current`, `latest`, alias, catalog, or publication index
is created.

Diagnostics disclose only closed bounded evidence. They do not expose raw
profile names, namespaces, artifact bytes, filesystem roots or paths, supplied
digests, arbitrary exception or operating-system messages, credentials, or
provider data.

## Consequences

The package is inert build-time evidence. A declaration, identity, artifact,
or publication result grants no filesystem, provider, credential,
authorization, network, runtime, or storage-operation authority. The package
performs no object read, create, replace, delete, list, copy, move, or stream
operation and supplies no adapter or provider integration.

Supported profile commands and application composition remain deferred to
Phase 21F. The neutral adapter protocol and contained local adapter remain
Planned for Phase 21D; runtime, application, inspection, and external
acceptance remain separately Planned.
