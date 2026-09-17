# ADR 0202: Exact-Current Cache Runtime and Operation Lifecycle

## Status

Accepted for Phase 20E.

## Context

The accepted cache contract, deterministic profile artifact, neutral adapter
protocol, and bounded memory adapter do not compose cache operations. A runtime
boundary must prove that one caller-supplied artifact is exact-current for one
caller-supplied profile set, select one profile explicitly, bind one directly
supplied matching adapter, and treat every adapter result as untrusted evidence.

A provider reporting that it found an entry does not establish a fresh runtime
hit. The returned key, contract, codec, bytes, content identity, freshness
window, lifetime, and version relations still require validation, and freshness
depends on the explicit caller-supplied instant. At the exact expiry instant the
entry is expired and its value bytes must not escape as runtime evidence.

## Decision

Phase 20E accepts the separately optional Experimental `kongono/cache-runtime`
package. `CacheRuntimeBinding` validates canonical artifact bytes with
`CacheProfileArtifactValidator::validateCurrent()`, selects exactly one named
profile, and validates a directly supplied Version 1 adapter identity and
limits. It retains neither raw artifact bytes nor ambient configuration.

`ExactCurrentCacheRuntime` exposes exactly lookup, store, replace, and remove.
Every request carries an explicit immutable `CacheInstant`. Store and replace
derive the key and complete entry candidate from the selected profile, enforce
the selected value bound, and reject expiry arithmetic overflow before adapter
invocation. Replace and remove pass caller-supplied entry-version evidence
unchanged.

Every adapter result is validated for the requested key, operation-specific
outcome, evidence presence, contract, codec, value bound, content identity,
freshness lifetime, and candidate equality. Lookup evaluates validated found
evidence at the supplied instant. Mutation evidence is returned only for a
validated stored result. Returned evidence is detached and immutable.

One instance-local guard rejects same-instance reentrancy immediately before a
second adapter invocation and retires in `finally` on success, typed failure,
adapter failure, and PHP `Error`. Adapter throwables are translated once with
their prior cause; PHP `Error` is not mislabeled as an operational failure.
Diagnostics expose only closed Safe labels, booleans, counts, and limits.

## Consequences

- Exact-current binding fails before operations on stale, malformed, missing,
  mismatched, or under-capacity inputs.
- Miss, expired, rejected, unavailable, conflict, and not-found remain distinct
  typed outcomes rather than fallback signals.
- No retry, refresh, stale fallback, read-through callback, serializer
  discovery, hidden clock, registry, service locator, lock, lease, or
  coordination behavior is introduced.
- The runtime consumes the neutral adapter port and has no production dependency
  on the bounded memory implementation.
- Application workflow, commands, inspection, external acceptance, provider
  compatibility, and production availability remain planned for later phases.

## Alternatives rejected

Trusting a provider `found` status was rejected because it would let malformed
or stale provider evidence become a fresh application result. Ambient clocks
and default profiles were rejected because they make results dependent on
hidden process state. Retry, refresh, stale fallback, and coordination were
rejected because they add policy, concurrency, and uncertain-effect semantics
beyond this exact synchronous boundary.
