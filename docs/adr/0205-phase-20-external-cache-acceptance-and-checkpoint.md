# ADR 0205: Phase 20 External Cache Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-09-17

## Context

Package tests establish each Phase 20 leaf in isolation, but they do not by
themselves prove that the accepted contract, deterministic profile build,
publication, bounded memory adapter, exact-current runtime, application and
command paths, copy-once slice, and visibility-safe inspection compose in a
fresh process without residue or disclosure. Phase 20 therefore needs one
root-owned local proof above the package suites before the milestone can close.

## Decision

Kongono accepts the root-owned `composer acceptance:phase20` proof. It uses the
real accepted Phase 20 classes and artifacts in fresh PHP processes and creates
only bounded fixtures beneath an operating-system temporary directory. The
repository and explicit fixture bytes are trusted test inputs. Generated
artifacts, adapter results, runtime evidence, command output, and inspection
evidence remain untrusted until the accepted validators revalidate them.

The harness proves the bounded contract and its closed failures; byte-identical
profile build identities and exact-current validation; contained,
content-addressed, ordinary-file, no-clobber publication; bounded in-memory
store, lookup, conditional mutation, eviction, detached evidence, and
independent instances; explicit caller time and all closed runtime outcomes;
direct plan/build/check parity with `cache:plan`, `cache:build`, and
`cache:check`; the copied `cache-minimal` workflow; supplied-evidence
inspection, all three developer roles, `not-established` Graph evidence,
constructed-not-recorded Observability and Security Audit projections, and
independent verification. Tampering, malformed evidence, bounded process
failure, nondisclosure, repository-fingerprint preservation, concurrency, and
complete cleanup are also proved.

Successful direct and Composer execution emits exactly:

```json
{"schema":"kongono.phase-20-cache-acceptance","version":1,"phase":"20","profile":"cache-platform-local/1","status":"accepted","checks":["contract","build-currentness","publication","memory-adapter","runtime-isolation","application-parity","inspection","nondisclosure","cleanup"],"check_count":9}
```

The line is followed by one LF and stderr is empty. Failure is nonzero, emits no
success object, and exposes only one fixed bounded public reason. The proof uses
no public network, ambient DNS, production credential, application data, live
backend, or public listener.

The accepted topology remains 109 packages, 467 local production edges, and
acyclic. Inventories are 2,161 production PHP files, 1,138 package-test PHP
files, and 104 root-test PHP files.

Phase 20A-H are Accepted. Phase 20 is closed by the exact local checkpoint with
subject `Complete Kongono Phase 20 cache platform milestone` and parent
`09da1b58859d394fd85474cde7669c638755cef4`. The post-commit report records the
resulting commit and tree identities.

## Consequences and limits

This acceptance is limited to the bounded process-local memory adapter and the
explicit local stack above it. It does not certify Redis, Memcached, filesystem,
database, cloud, vendor, PSR-6, or PSR-16 compatibility; distributed or
multi-process coordination; performance, throughput, or latency; durability;
production availability; deployment; mobile integration; Stable/1.0; or Phase
21 readiness. Cache evidence remains disposable and non-authoritative.
