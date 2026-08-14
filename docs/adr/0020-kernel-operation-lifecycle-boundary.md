# ADR 0020: Kernel and Operation Lifecycle Boundary

## Status

Accepted

## Date

2026-08-12

## Context

The accepted microkernel and persistent-runtime-first direction requires a first executable lifecycle boundary. It must account for multiple operations in one process without selecting a transport, runtime, dependency container, Application Graph mechanism, or future capability.

The package also needs one catchable distinction between failures deliberately raised by Kongono Kernel behavior and native engine errors, without prematurely stabilizing concrete exception policy.

## Decision

Create `kongono/kernel` as the first physical package. Its Phase 0C responsibilities are limited to Kernel boot and terminal shutdown state, operation-scope creation and ownership, active-operation accounting, lifecycle misuse detection, the Kernel exception hierarchy, and the then-Kernel-owned API-status metadata.

A Kernel has exactly `Unbooted`, `Booted`, and `Shutdown` states. It boots only from `Unbooted`, begins and ends operations only while `Booted`, refuses shutdown while any operation remains active, and cannot leave `Shutdown`. Invalid actions throw before valid state changes.

Each operation scope is a new opaque object owned by its creating Kernel through object identity. Multiple operations may overlap. No global, static, singleton, thread-local, fiber-local, or other ambient current-operation pointer exists. A scope has no identifier, data, callback, self-ending behavior, or serialization contract.

Every failure deliberately thrown by this package implements the Public `ExceptionInterface` marker. `KernelException` is the Internal abstract `RuntimeException` base and `LifecycleException` is the Experimental final lifecycle failure. Native engine errors are not wrapped solely to enter this hierarchy.

## Rationale

An instance-owned, identity-based lifecycle is deterministic and allows persistent processes to account for concurrent or interleaved work without leaking an assumed current operation. Refusing shutdown with active work exposes incomplete ownership rather than relying on process teardown. Keeping scopes opaque establishes the transition invariant without guessing the future contents or propagation mechanism.

A marker interface gives consumers one deliberate package-failure catch boundary while concrete lifecycle behavior remains Experimental.

## Consequences

Runtime adapters can eventually map their operations onto explicit scopes without becoming Kernel dependencies. Callers must retain and return the exact scope to its owning Kernel. Ending one scope leaves others active, foreign and already-ended scopes fail, and shutdown is terminal.

The Kernel is not a container or service locator and performs no dependency resolution, object construction, request handling, I/O, global-state access, logging, telemetry, time, randomness, environment access, or cleanup orchestration. Lifecycle contracts and concrete behavior are Experimental; only the deliberate exception marker is a Public catch boundary.

Phase 0F later places explicit typed dependency resolution in the separate `kongono/dependency-injection` component. That resolver has no Kongono-supplied, special, or preconfigured Kernel binding or lifecycle behavior. Phase 0S moves `Api` and `ApiStatus` to `kongono/api-metadata`, removes the old Kernel FQCNs, and makes Kernel and DI depend directly on the neutral owner. Kernel no longer owns API metadata; lifecycle and resolver behavior are unchanged. A consumer-selected Kernel interface may be handled as an ordinary explicit contract without creating a framework-owned integration.

Phase 0T adds exact eager bounded context to the six existing lifecycle failures
through the Kernel marker. It observes the same pre-mutation validation points
and changes no state transition, scope identity, validation priority, message,
or recovery boundary. Kernel requires Diagnostics only for presentation-neutral
values and gains no service or ambient lifecycle integration.

## Rejected alternatives

- A single root `src/` tree without package ownership is rejected because the authorized Kernel boundary must be independently owned in the monorepo.
- Making the Kernel a container, service locator, registry, or object factory is rejected because dependency and construction contracts are outside this phase.
- Request-per-process lifecycle assumptions are rejected because they rely on teardown and conflict with persistent-runtime-first architecture.
- One global or ambient current-operation pointer is rejected because overlapping work would make ownership ambiguous and risk state leakage.
- Forbidding overlapping operations is rejected because runtime-neutral lifecycle accounting must not assume serialized work.
- Operation IDs, hashes, counters exposed as IDs, and serialization are rejected because object identity is sufficient and avoids inventing a transport or persistence contract.
- Self-ending scopes and cleanup callbacks are rejected because lifecycle ownership and validation belong in one Kernel transition point.

## What remains open

Kernel/resolver composition, service scopes, scope contents, context propagation, cleanup after application failure, cancellation, reset hooks, concurrency primitives beyond overlapping identity ownership, runtime adapters, telemetry, logging, Application Graph integration, lifecycle extension points, and compatibility promotion remain open. Phase 0F's separate transient resolver does not settle these questions. No later capability is implied by this decision.
