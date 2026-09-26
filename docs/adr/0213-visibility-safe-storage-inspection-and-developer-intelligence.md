# ADR 0213: Visibility-safe storage inspection and developer intelligence

- Status: Accepted for Phase 21G
- Date: 2026-09-24
- Phase: 21G

## Context

The accepted Storage Platform has bounded neutral contracts, deterministic
profiles, a contained local adapter, exact-current operations, and an explicit
application workflow. Developer tools need to inspect supplied profile evidence
without opening objects, listing a provider, executing authorization, or
turning detached aggregates into an operational record. Storage names,
namespaces, object identities, contents, metadata, versions, paths, locators,
credentials, and arbitrary errors can be sensitive.

## Decision

Add the separately optional Experimental `kongono/storage-inspection` upper
leaf. Its request holds one already loaded `CurrentStorageApplicationBuild` and
optional caller-supplied `DetachedStorageRuntimeEvidence`. The inspector copies
every public declaration field into a new `StorageProfileSet`, independently
validates the exact artifact bytes, and compares complete profiles, order,
count, source identity, build identity, and artifact identity. The conclusion
is limited to coherence of the supplied in-memory evidence. It does not prove
present-time filesystem or provider currentness.

Detached evidence has exactly three lowercase SHA-256 bindings, 17 closed
outcome counts, an explicit total, fixed omission and provenance flags, and a
canonical identity. Each count and the total is at most 1,000,000. The distinct
`mutation_uncertain` count is never reclassified as success or failure. Absent
evidence is distinguishable from a present all-zero aggregate. These counts
are caller assertions; they do not establish that operations occurred.

Version 1 inspection bytes are compact UTF-8 JSON with exactly one terminal
LF. Safe fields contain closed tokens, bounded aggregate counts, fixed flags,
limitations, role purposes, and bounded same-role Graph aggregates. SHA-256
source, build, artifact, inspection, detached aggregate, and serialization
identities are Protected. The request may retain its explicit source; returned
inspection and intelligence evidence contains no source object or raw artifact.

Chie, Hantei, and Oshie map only to `explain`, `verify`, and `teach`. Optional
Application Graph intelligence contributes same-role bounded aggregates, with
the storage-to-Graph relationship always `not-established`. Pure Observation
and Security Audit projectors require an explicit context and construct values
marked `constructed-not-recorded`. Their bounded Observation attributes carry
profile count, total outcomes, uncertain mutations, closed flags, and fixed
tokens. The verifier independently reconstructs expected inspection, role,
Graph, serialization, and both projections from the original request; it
returns only closed pass/fail checks and never repairs a candidate.

Diagnostics reserve `KGN-STORAGE-INSPECTION-2151` through `2155` for invalid
inspection, artifact binding, invalid intelligence evidence, projection, and
verification failures. Their only variable Safe context is a validated closed
component token. Foreign exception messages and captured arguments are never
rendered into package diagnostics.

## Consequences

The leaf performs no filesystem, provider, adapter, credential, authorization,
network, runtime, clock, storage operation, mutation, role execution, repair,
recording, or export. It does not certify storage correctness, durability,
security, availability, production readiness, provider compatibility, or
Stable/1.0 status. Phase 21H separately supplies root-owned local acceptance;
see [ADR 0214](0214-phase-21-external-storage-acceptance-and-checkpoint.md).
