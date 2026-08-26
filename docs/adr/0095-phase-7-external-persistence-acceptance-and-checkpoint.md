# ADR 0095: Phase 7 External Persistence Acceptance and Checkpoint

Status: Accepted

## Context

Phases 7A through 7G established deterministic persistence contracts, build
evidence, generated state, explicit operation contexts, bounded aggregate
repositories, and exact-current application integration. Closing the milestone
requires one external proof that these boundaries work together against a real
provider without granting the acceptance harness production authority.

PostgreSQL 18 is the primary current Phase 7 provider major. PostgreSQL 18.6 is
the exact minor accepted at this checkpoint. PostgreSQL 17.2 remains immutable
historical Phase 6 regression evidence and is not the current recommended
deployment target. Within an accepted provider major, Kongono maintains the
latest stable minor only after the complete acceptance profile passes again.
Another minor or major has no current acceptance claim until fresh evidence is
recorded. Exact checkpoint evidence is deliberately narrower than universal
PostgreSQL support.

## Decision

Kongono adds the root-owned `acceptance:phase7` Composer command. It requires one
explicit PostgreSQL binary directory and starts a fresh PostgreSQL 18.6 cluster
under a uniquely owned system-temporary root using loopback networking and
bounded test-owned database state. It does not discover ambient credentials,
reuse a developer database, or contact a remote provider.

The harness copies the persistence-minimal application and its locked local
packages into temporary storage without changing authoritative application
bytes. It builds and non-mutatingly checks the exact mapping, generated data
access, persistence artifact, generated state, aggregate repositories,
Composition artifact, and generated wiring before executable loading. Missing,
stale, crossed, renamed, malformed, tampered, or changed-during-read evidence
fails closed.

Real execution proves operation-local identity reuse, cross-operation isolation,
ordered bounded aggregate loading, reviewed one-level cascades, explicit commit
and rollback visibility, optimistic conflict without automatic retry, terminal
failure retirement, peer safety, reverse once-only cleanup, direct Phase 6 data
access, and nondisclosure. The provider process, sessions, roles, generated
outputs, and owned temporary paths are removed before one canonical success
object is emitted.

The test-only performance profile uses `hrtime`, ten warmups, fifty measured
samples, and deterministic nearest-rank summaries for identity-map reuse,
generated repository find, aggregate repository load, and explicit unit-of-work
flush. It records local observations without a timing ceiling, production
telemetry, SLA, portability promise, or competitor comparison.

Phase 7 closes with thirty production packages and one exact 226-path checkpoint
only after focused proof, Phase 7 on PostgreSQL 18.6, Phase 6 regression on
PostgreSQL 17.2, Hantei, Phase 5, Phase 4, canonical quality, documentation,
file-safety, staging, commit, and post-commit gates all pass.

## Rejected alternatives

- Treating PostgreSQL 17.2 historical evidence as the current Phase 7 profile.
- Claiming all PostgreSQL minors or majors from one accepted checkpoint.
- Reusing an ambient service, DSN, credential, port, role, or database.
- Loading generated output before exact-current non-executing validation.
- Hiding transactions, retries, lazy loads, recursive cascades, or schema work
  behind a persistence context or generated repository.
- Adding production instrumentation or timing thresholds to obtain performance
  evidence.

## Consequences

Phase 7 has reproducible whole-platform evidence for one exact PHP, operating
system, and PostgreSQL profile. Direct SQL and generated Data Mapper paths remain
supported. Generated output remains derived exact-current evidence rather than
runtime authority. APIs retain their existing pre-1.0 and Experimental status;
the checkpoint is not a Stable API, production-readiness, deployment, or
universal-portability certification.
