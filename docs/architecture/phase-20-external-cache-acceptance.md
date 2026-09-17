# Phase 20 External Cache Acceptance

Phase 20H adds one root-owned, fresh-process proof for the complete accepted
Derived-Data Cache Platform. Run it directly with
`php tests/External/Cache/run.php` or through
`composer acceptance:phase20`. Both forms emit the same fixed one-line JSON on
success and nothing on stderr. Failure is nonzero and exposes only a fixed,
bounded reason.

## Purpose and trust model

The package suites prove individual leaves. This harness proves that their real
public classes, canonical artifacts, application commands, copied minimal
slice, and inspection projections compose coherently in a new PHP process.
Repository source and explicit bounded fixture bytes are trusted test inputs.
Artifacts, adapter results, runtime evidence, command output, and projections
are validated before they count as evidence.

All test-owned mutable work is created beneath the operating-system temporary
root. The harness reads only its fixtures, its generated artifacts, the fixed
copy-once slice, and the repository fingerprint needed for regression proof. It
uses no public network, DNS, credential, live backend, public listener, or
application data.

## Covered scenarios

The proof covers valid and invalid contract values; deterministic build,
currentness, tampering, and content-addressed no-clobber publication; bounded
memory store, lookup, conditional replace/remove, capacity, deterministic
eviction, detached results, and isolation; exact-current runtime binding,
caller-supplied time, fresh hit, miss, expiry, conflict, rejection,
unavailability, failure retirement, and independent instances; direct and
command plan/build/check parity; and the copied `cache-minimal` workflow.

Inspection reconstructs one supplied current build and optional detached
aggregate runtime evidence. Graph stays `not-established`; Observability and
Security Audit remain constructed-not-recorded; Chie explains, Hantei verifies,
and Oshie teaches without role execution or new authority. Tampered evidence is
rejected.

The parent proof repeats and concurrently runs successful children, checks
adversarial failure, malformed output, output bounds, timeout retirement,
nondisclosure, exact repository-fingerprint preservation, and temporary-residue
cleanup. Raw keys, values, profile/codec/provider identifiers, private paths,
credentials, tokens, backend addresses, environment data, arbitrary provider
messages, temporary paths, and stack traces never enter public output.

## Checkpoint meaning and non-claims

Passing the harness and the canonical quality, Hantei, and adjacent acceptance
gates closes Phase 20 at the checkpoint subject
`Complete Kongono Phase 20 cache platform milestone` with parent
`09da1b58859d394fd85474cde7669c638755cef4`. The accepted graph remains 109
packages and 467 local production edges, acyclic, with PHP inventories
2,161 / 1,138 / 104.

This proves only the bounded local process-memory profile. It makes no Redis,
Memcached, filesystem, database, cloud, vendor, PSR-6, PSR-16, distributed,
multi-process, performance, durability, production-availability, deployment,
mobile, Stable/1.0, certification, or Phase 21 claim.

See [ADR 0205](../adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md),
the [Cache Platform guide](cache-platform.md), and the
[Phase 20 roadmap](phase-20-roadmap.md).
