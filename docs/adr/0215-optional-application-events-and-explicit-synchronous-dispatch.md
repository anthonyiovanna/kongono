# ADR 0215: Optional application events and explicit synchronous dispatch

- Status: Phases 22A-E architect-accepted within recorded limits; Phase 22F local acceptance harness and current-source focused validation complete, with broad validation, advisory audit, architect milestone acceptance and exact checkpoint pending
- Date: 2026-09-26
- Phase: 22A

## Context

Kongono's small Kernel and explicit application composition leave room for
independently installed capabilities. An application sometimes needs to make
an occurrence known to several application-owned collaborators without making
the originating use case call each collaborator directly. Direct calls remain
the simpler choice when the relationship is fixed and clear.

An application occurrence has different meaning from Kongono's
`operational-events` telemetry signal or `security-audit` evidence. Those
signals describe observations for operators and security review; neither is a
business-event dispatcher. An Application Graph fact describes an accepted
application state, while a command requests work. A queue or durable message
would introduce transport, persistence, recovery, and delivery guarantees
that synchronous in-process calls cannot prove.

The [Phase 22 roadmap](../architecture/phase-22-roadmap.md) selects only this
bounded direction. This ADR accepted architecture and created no PHP
declaration or package in Phase 22A. The separately governed
[ADR 0216](0216-neutral-application-event-contract-and-bounded-registration.md)
defines the Phase 22B inert contract; it adds no dispatcher or automatic
publisher. The separate
[ADR 0217](0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
defines the implemented Phase 22C dispatcher.
The application-owned [event-minimal example](../../skeletons/event-minimal/README.md)
demonstrates the Phase 22D direct composition without changing this ADR's
framework boundary.

## Decision

### Purpose, adoption, and ownership

Application events express an application-defined occurrence to explicitly
selected application handlers. Applications may omit this capability, use
ordinary direct calls, or choose another event implementation. Events are not
a universal framework integration mechanism. Kernel, HTTP, DI, database/ORM,
Storage, and Agent packages acquire no event dependency or automatic
publication.

The first implementation target is a neutral optional
`kongono/event-contract` package and a separately optional
`kongono/event-runtime` synchronous dispatcher. These names reserve
dependency direction. The contract package exists under ADR 0216 and the
runtime under ADR 0217. The contract remains acyclic and
neutral; runtime depends on it, not on Kernel or a transport. Existing API
Metadata and Diagnostics conventions may be used where needed. No third-party
dependency or build artifact is selected. Future public symbols start
Experimental unless separately promoted.

An application occurrence, its type, payload, selected registration, dispatch
attempt, handler effect, and result evidence remain distinct. Event
construction does not authenticate a caller, authorize an effect, prove a
database commit, create a globally unique ID, or establish delivery or
durability. This phase selects no clock, global occurrence ID, serialization
format, event store, or schema registry.

### Explicit registration and validation

The caller supplies a bounded collection pairing exact event types with
directly supplied handler collaborators. Registration order is execution
order. Validate the entire collection, finite limits, exact type relationships,
and duplicates before a dispatch can invoke a handler. Multiple distinct
handlers may match one event; duplicate binding identities are rejected
rather than silently collapsed or replaced. The precise identity
representation, PHP signatures, diagnostics, and numeric bounds require
Phase 22B repository inspection and a separate directive, now recorded in
ADR 0216 for the inert contract.

Matching uses the exact declared event type. There is no wildcard,
inheritance-based, priority, or implicit subscriber match. An empty valid
registration set, or a valid event with no match, completes as a no-op.
Malformed input fails before handler effects. Dispatch receives an explicit
typed application event, never an arbitrary HTTP or JSON envelope inferred
at runtime.

No ambient bus, service locator, automatic boot registration, Reflection or
attribute scan, property-hook resolution, runtime container lookup, or
subscription mutation during dispatch is selected.

### Payload, invocation, and failure

Applications own payload types, business meaning, constructor validation, and
business-input validation. Prefer immutable value payloads. PHP `readonly`
does not make referenced objects recursively immutable, and the dispatcher
does not clone, serialize, deeply traverse, redact, retain, or sandbox a
payload. Each selected handler receives the same supplied event synchronously.
Mutating a payload is outside the supported immutable-event authoring
contract; arbitrary trusted PHP cannot be prevented from doing so by this
architecture.

Matching handlers run sequentially in registration order. Each selected
registration is invoked at most once **in one dispatch attempt**, which is
not cross-attempt or distributed exactly-once delivery. The first handler
`Throwable` stops later handlers. Earlier effects may have occurred and are
not rolled back. The original handler throwable object reaches the caller
unchanged; it is not wrapped as a framework validation failure, swallowed, or
retried. The outer application boundary owns safe presentation. No claim is
made that native throwable messages, traces, or arguments are sanitized.

Fixed framework diagnostics follow the existing bounded nondisclosure
conventions. They must not include raw payloads, handler results, arbitrary
exception messages, or previous throwable stringification.

### Lifecycle, authority, and privacy

Registrations are explicit retained configuration. Per-dispatch event,
progress, and failure references must leave no state in a later operation.
Overlapping or reentrant dispatch on the **same dispatcher instance** is
rejected before any nested handler call; a guard is released in `finally`
on success and failure. Separate instances have no global recursion guard,
and no parallelism or thread safety is promised. Handlers and their resources
remain caller-owned; dispatch does not close them.

Finite registration/input bounds limit framework bookkeeping. Arbitrary
synchronous application code can block or allocate; no hard wall-clock
deadline or total application-memory guarantee is implied. Later composition
must document lifetime-safe handlers and isolation after both success and
failure.

Publishing an event grants no business permission. Each effectful handler
checks application-owned authorization at its effect boundary. The
dispatcher cannot certify arbitrary business policy. Framework dispatch
performs no hidden network, filesystem, credential, export, telemetry,
developer-role, AI, or background action. Application handler effects are
caller-owned and must be disclosed by their authors. No payload is
automatically rendered, logged, or included in Chie, Oshie, or Hantei
evidence. Application handlers are trusted PHP, not isolated code.

## Alternatives and tradeoffs

- **Direct calls:** preferred for one clear, fixed relationship; they avoid
  registration and dispatch complexity but couple the originating use case
  to each named collaborator.
- **Explicit synchronous dispatch:** selected as an optional way to hand one
  occurrence to several explicitly registered handlers while keeping call
  order, failure, and ownership visible. It does not add persistence.
- **Asynchronous or durable alternatives:** deferred. Queues, workers,
  acknowledgements, retries, recovery, and cross-process delivery require
  distinct authority and proof; they cannot be inferred from in-process calls.

## Consequences and non-goals

Applications can choose a small explicit event boundary while preserving
direct calls and install-none defaults. Handler failures expose partial
progress rather than a fabricated atomic outcome. Phase 22A implemented no
runtime; Phase 22C implements the separately optional direct dispatcher under
ADR 0217, pending its own audit and architect acceptance.

There is no asynchronous dispatch, queue, worker, retry, scheduler, outbox,
transaction integration, after-commit hook, rollback, event sourcing, replay,
persistence, transport, distributed ordering, deduplication, cancellation,
stop-propagation or veto, return-value aggregation, listener scan, ORM hook,
automatic model event, Graph schema/ID, new AI role, CLI command, UI, cloud
provider, benchmark, release, production-readiness, or Stable claim. Later
work should use supported PHP features where they improve correctness and
measure performance before claiming a benefit. A compiler or generated
wiring requires separate demonstrated need and authority.

## Related material

- [Application Events architecture](../architecture/application-events.md)
- [ADR 0216 neutral application-event contract](0216-neutral-application-event-contract-and-bounded-registration.md)
- [ADR 0217 synchronous dispatcher](0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
- [Event authoring](../engineering/event-authoring.md)
- [Phase 22 roadmap](../architecture/phase-22-roadmap.md)
- [Microkernel and Capabilities](../architecture/microkernel.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [ADR 0119: operational events and security audit](0119-structured-operational-events-and-bounded-security-audit-evidence.md)
