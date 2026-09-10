# ADR 0167: Operation-scoped credential lease runtime

- Status: Accepted
- Date: 2026-09-09

## Context

Phase 16B established neutral credential-reference, lease-request, source, lease, consumer, and state contracts. Those values deliberately do not select a source or coordinate acquisition, borrowed use, lifetime checks, and retirement. Applications need a small explicit boundary that can perform that coordination without bundling a credential store, ambient environment or vault lookup, service location, provider SDK, or network behavior.

Credential access and network access remain independent current permissions. A source identity, reference, binding artifact, generated PHP file, digest, lease, or runtime result is evidence only and cannot grant either permission. Ordinary PHP strings also provide no reliable memory-zeroization guarantee.

## Decision

Add the optional Experimental `kongono/external-service-credential-runtime` package. One `CredentialLeaseRuntimeRequest` explicitly binds an existing secret-free lease request, the claimed identity of the caller-selected source, that source object, one consumer, and one monotonic clock. Construction requires the claimed source identity to equal the identity in the request's credential reference. This is a caller-supplied relation, not proof that a source object is honest.

One runtime instance permits one active synchronous operation and is reusable sequentially. Same-instance reentrancy fails before a nested collaborator call; separate instances share no mutable state. Each operation owns only local state and retains no request, source, lease, consumer, clock, result, or foreign failure after terminal completion.

The runtime observes nonnegative monotonic milliseconds at four points on success: start, post-acquisition before material use, post-use before retirement, and post-retirement. Elapsed time equal to the explicit maximum is accepted; plus one fails. This is cooperative checking only. The runtime cannot preempt or interrupt a blocking source, consumer, or retirement call and makes no deadline, cancellation, async-safety, thread-safety, process-isolation, or physical-erasure claim. If clock evidence becomes invalid before retirement, cleanup proceeds without consulting the clock again.

Before borrowed use, the runtime requires the returned lease's accessors to satisfy their declared types, its reference and purpose to equal the exact request, and its state to be `acquired`. It delegates directly to `$lease->withMaterial($consumer)` at most once. It never accepts or returns material and never calls the consumer directly. After a successful borrowed-use return, the lease must again report `acquired`. A failure after access begins means material use may already have occurred.

Every acquired lease receives exactly one retirement attempt on every later path. After `retire()` returns, the state must be `retired`; a throw or any other state makes cleanup uncertain. Retirement is never retried, and the runtime does not reacquire, refresh, renew, or use a fallback source.

The terminal failure matrix is exact:

| Primary failure | Retirement failure | Terminal behavior |
| --- | --- | --- |
| absent | absent | Return successful retired evidence. |
| present | absent | Throw an operation exception with the exact primary as previous and cleanup-complete retired evidence. |
| absent | present | Throw a cleanup exception with one exact retirement failure and uncertain evidence. |
| present | present | Throw a combined exception preserving the exact primary and exact one-item retirement-failure list with uncertain evidence. |

Package exception messages, diagnostic contexts, and deterministic stringification contain only closed package tokens and bounded counts. The exact foreign primary and cleanup objects remain available through deliberate programmatic accessors, but those foreign objects, their messages, traces, codes, classes, paths, and arguments are not developer-presentation-safe.

## Consequences

Applications explicitly implement or select a `CredentialLeaseSource`, create a secret-free request, supply a consumer and monotonic clock, run one lease operation, and handle typed terminal evidence. Applications without external credentials do not install the package.

The package includes no environment, dotenv, file, process, keychain, vault, KMS, cloud, database, or remote source; no provider or service adapter; no service locator or registry; no refresh, renewal, revocation service, rotation watcher, pool, retained session, or cache; no retry or fallback; no networking or network authority; and no background work. It does not load generated bindings or implement application orchestration. Phase 16A through Phase 16G are Accepted; Phase 16H remains separately Planned.
