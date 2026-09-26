# ADR 0210: Neutral Storage Adapter and Contained Local-Filesystem Implementation

- Status: Accepted
- Date: 2026-09-19

## Context

Phases 21B-C define bounded neutral storage requests and deterministic profile
artifacts, but intentionally perform no object operation and supply no adapter.
A replaceable protocol is required before an exact-current runtime can compose
one explicitly selected implementation. Local development also needs one
bounded implementation without creating a cloud, durability, or production
promise.

## Decision

Phase 21D adds the separately optional Experimental
`kongono/storage-adapter-contract` package. Its Version 1 protocol binds one
adapter descriptor to one already validated storage-profile artifact and one
exact profile. Read, create-only write, version-matched replace,
version-matched delete, and bounded list operations use the accepted Phase 21B
requests and results. Guarantee and limitation sets are closed, detached, and
honest. They grant no authorization, provider discovery, credential, retry, or
runtime authority.

Phase 21D also adds the separately optional Experimental
`kongono/storage-adapter-local` package. `ContainedLocalStorageAdapter` accepts
only one explicit `LocalStorageRoot`, one validated artifact, and one exact
profile name. It maps raw namespace and logical-object identities to
domain-separated lowercase SHA-256 path components beneath
`.kongono-storage-local-v1`; raw identities never become path components.
Every open, publication, replacement, deletion, listing, temporary cleanup,
and lock path is revalidated for final containment.

One object is one framed ordinary record containing a bounded canonical JSON
header and exact raw bytes. Create never overwrites. Replace and delete require
the exact accepted object version. Writes consume a caller-owned source once in
bounded forward-only chunks, verify size and digest before publication, and
never close or retain that source. A completed read returns detached evidence
and one open caller-owned source that must be explicitly closed. Expected
absence, conflict, rejection, unsupported capability, unavailability, and
uncertain mutation remain typed outcomes rather than exceptions or retry
permission.

Listing is namespace-scoped, bounded, ordinal, paginated, and explicitly not a
snapshot. Continuation evidence is opaque and bound to the adapter, artifact,
profile, namespace, and last logical identity. Operation-owned handles and
temporary files are retired on every path without destructor or shutdown-hook
dependence.

## Consequences

The local adapter is intended for deterministic development, tests, and an
explicitly selected single-host use. It declares all supported guarantees and
the limitations `local_filesystem_only`, `single_host_only`, filesystem-
dependent atomicity and durability, no snapshot listing, public URL,
replication, encryption guarantee, cross-process transaction, or protection
from arbitrary out-of-band writers.

Diagnostics, exception strings, and representations do not disclose object
bytes, raw identities, metadata, roots, paths, filenames, handles, versions,
tokens, supplied digests, credentials, provider responses, or native error
messages. The implementation adds no cloud/provider adapter, ambient root,
environment lookup, registry, facade, service locator, runtime discovery,
authorization mechanism, exact-current runtime, command, application workflow,
inspection surface, public delivery, or production-availability claim.

Phase 21E remains separately Planned and must compose only one directly
supplied compatible adapter with exact-current profile evidence under separate
authority.
