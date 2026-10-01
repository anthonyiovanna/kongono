# ADR 0217: Optional synchronous application-event dispatcher and instance lifecycle

## Status

Architect-accepted for Phase 22C within recorded local validation and
time-bound audit limits. The separately optional Experimental runtime remains
distinct from Phase 22D's application-owned
[event-minimal example](../../skeletons/event-minimal/README.md), which is
architect-accepted within its recorded Phase 22D local limits. Phase 22E
local assembled-path validation is architect-accepted within recorded limits;
Phase 22F's local own-autoload consumer and current-source focused validation
have passed; broad validation, advisory audit, architect milestone acceptance
and exact checkpoint remain pending.

## Context

[ADR 0215](0215-optional-application-events-and-explicit-synchronous-dispatch.md) separates application-owned occurrences from telemetry and audit. [ADR 0216](0216-neutral-application-event-contract-and-bounded-registration.md) fixes an inert, validated and bounded registration set. Phase 22C needs one direct invocation path without introducing ambient publication, runtime discovery or an effect authority.

## Decision

`kongono/event-runtime` is a separately optional Experimental leaf depending only on `kongono/api-metadata`, `kongono/diagnostics` and `kongono/event-contract`. It has exactly four production declarations: Experimental final `SynchronousEventDispatcher`, Experimental `ExceptionInterface`, Experimental final `ReentrantDispatchException`, and Internal `EventRuntimeDiagnostic` with sole code `KGN-EVENT-RUNTIME-2221`.

The dispatcher accepts one final validated `EventRegistrationSet` at construction. It retains that readonly reference and one instance-local boolean busy guard. `dispatch(ApplicationEvent $event): void` admits every native marker object. An anonymous, enum-marker or over-255-byte concrete-class event cannot match a valid registration and is an idle no-op. Wrong native types receive native type errors. This policy does not make unregistrable events valid registration inputs.

An active instance rejects a second call before matching or invoking a nested handler. The rejected call does not clear the active guard. Otherwise dispatch acquires the guard and places all operation work under `try/finally`. It obtains `get_class($event)`, compares it by exact string equality to each registration's canonical event type in existing order, and invokes each matching handler once with the same event object. No inherited, wildcard or priority matching is supplied. Empty and unmatched dispatch succeed without a result.

The first handler `Throwable` escapes as the original object. Earlier effects remain and later handlers are not invoked. A handler may catch a nested rejection and continue while its outer attempt remains guarded. If it lets the rejection escape, the outer `finally` clears the guard. Separate instances are independent. A Fiber-suspended handler keeps its dispatcher busy until it resumes and unwinds; overlapping calls reject rather than wait. The runtime has no retry, rollback, transaction, durability, delivery or authorization claim.

The rejection creates a fresh `ReentrantDispatchException` with a fixed message, one required Safe Text `violation=reentrant_dispatch` field, fixed title, operation, invariant and remediation, and a fixed string representation without trace or caller input. The application owns safe presentation of native errors and handler throwables. No event class, payload, handler or position enters the deliberate diagnostic.

Completed operation references remain stack-local. Caller-owned handlers, retained exceptions or traces, and suspended Fibers can retain events independently. The dispatcher does not close caller resources. Native cloning and serialization are not supported lifecycle or reset APIs. No autoload, Reflection, event metadata invocation, property traversal, deep freeze, service lookup, publication, logging, telemetry, Graph, AI role, queue, worker, transport, generated wiring, automatic registry or new third-party dependency is introduced.

## Consequences

Applications explicitly load event classes, build registrations, authorize effects and choose a dispatcher lifetime that suits their handlers. The optional runtime can be omitted entirely. Phase 22D's caller-owned example is separately implemented; this decision grants no later-row acceptance or checkpoint claim. PHP support remains `>=8.5.0 <8.6.0`.
