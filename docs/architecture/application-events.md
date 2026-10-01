# Application Events

## Status and purpose

Phase 22A accepts architecture for optional, explicit, synchronous application
events under [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md).
Phase 22B implements the optional Experimental inert neutral contract under
[ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md).
Phases 22A and 22B are architect-accepted. Phase 22C implements the separately
optional Experimental synchronous dispatcher under
[ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md).
Phase 22C is architect-accepted within its recorded local validation and
time-bound audit limits. Phase 22D implements the application-owned
[event-minimal copy-once example](../../skeletons/event-minimal/README.md)
and is architect-accepted within its recorded local limits. Phase 22E has
completed local assembled-path and copied-example validation and is
architect-accepted within recorded limits. Phase 22F's local own-autoload
consumer and focused current-source validation passed; broad validation,
advisory audit, architect milestone acceptance and exact checkpoint remain
pending under the
[Phase 22 roadmap](phase-22-roadmap.md).

An application event represents an application-defined occurrence that its
author chooses to share with explicitly selected application handlers. A
fixed relationship is often clearer as an ordinary direct call. Applications
may omit events or choose another implementation. No core Kernel, HTTP, DI,
database/ORM, Storage or Agent dependency on events is implied.

## Concepts and ownership

The application owns the occurrence meaning, payload type, constructor and
business-input validation, handler business effects, and authorization at
those effects. The neutral `kongono/event-contract` implements only a marker,
a native non-generic handler interface, and bounded registration vocabulary.
A separately optional `kongono/event-runtime` owns one synchronous dispatch
attempt. The runtime depends on the neutral contract, never on Kernel or
a transport. Applications supply both registrations and handler collaborators
directly through explicit composition.

An event is not an operational-events telemetry signal, security-audit
evidence, diagnostic, Application Graph fact, command, queue entry, or durable
message. There is no implicit conversion among them. Event dispatch cannot
prove that the occurrence was committed, durable, globally unique,
authorized, or delivered across processes. This architecture selects no
clock, global occurrence ID, serialization format, store, or schema registry.
An event value and its payload are not credentials or grants.

### Implemented neutral contract

`ApplicationEvent` is an empty marker. `EventHandler::handle(ApplicationEvent
$event): void` makes no generic claim that a handler supports every registered
business event. Application handlers narrow and validate the event type in
their own code. `EventRegistration` takes one raw class-name string and one
caller-owned handler. It requires a 1-255 byte canonical ASCII name of an
already-loaded concrete `ApplicationEvent` class; a targeted local
`ReflectionClass` checks that class without scanning or constructing it.
An alias or wrong-case spelling is rejected. `EventRegistrationSet` accepts
a list of at most 64 registrations, preserves order, and rejects a duplicate
only when both exact event type and handler object identity match. Construction
and accessors invoke no event or handler method. The set grants no dispatch
or authorization authority. The four fixed diagnostics expose only bounded
Safe violation tokens. Native PHP type errors and programmatic traces are
outside the fixed presentation guarantee.

## Explicit dispatch flow

The implemented contract and runtime follow this sequence:

1. The application constructs a typed event and validates its own business
   input. It directly supplies a finite ordered registration collection and
   handler collaborators.
2. The Phase 22B contract validates the *entire* caller-supplied collection
   before the dispatcher can use it: 0-64 ordered entries, canonical
   already-loaded event types and duplicate exact type/handler-object pairs.
   Distinct handlers may register for one type. Construction invokes none.
3. The dispatcher accepts a native `ApplicationEvent`, rejects an active
   same-instance call before matching, and compares the event object's
   canonical concrete class with each registration's exact declared type.
   Matching handlers run sequentially in registration order with that same
   object. An empty collection or no match is a successful no-op; an idle
   anonymous, enum-marker or over-255-byte concrete-class event cannot match
   a valid registration and is also a no-op. Wrong native types raise native
   type errors.
4. The first handler `Throwable` stops later invocations and reaches the
   caller as the **same throwable object**. Earlier effects may have happened
   and are not rolled back. No hidden retry turns uncertainty into success.
5. The dispatch guard is released in `finally` after the outer call unwinds,
   including failure or no-match. A nested rejection may be caught by a
   handler without releasing that outer guard. A separate dispatcher instance
   is independent. The caller's outer application boundary decides how to
   present a safe failure and whether business policy permits any later
   separate attempt.

Each selected registration runs at most once **within that attempt**. This
is not a cross-attempt or distributed exactly-once guarantee. There is no
wildcard, inherited-type, priority, ambient or runtime-container matching.
No automatic subscriber discovery, Reflection/attribute scan, property-hook
resolution, boot registration or subscription mutation during dispatch is
selected. Malformed registrations or inputs fail before handler effects;
an arbitrary HTTP/JSON envelope is not a typed application event.

## Payload and privacy boundary

Prefer small immutable application value payloads. PHP `readonly` is
shallow: an object held in a readonly property can still have mutable
internals. Dispatch passes the **same supplied event** to selected
handlers. It does not clone, serialize, deeply traverse, redact, spill to
disk, retain, or sandbox an arbitrary object graph. Payload mutation is
outside the supported immutable-event authoring contract, while arbitrary
trusted PHP code remains technically capable of it.

Fixed framework diagnostics are bounded, actionable, and
nondisclosing. They identify the registration or reentrancy invariant and remediation
without raw payloads, handler results, arbitrary exception messages or
throwable stringification. The original handler throwable remains available
programmatically to its caller; native exception strings, traces and
arguments are not promised safe for public output. No payload is
automatically rendered, logged, exported, or supplied to Chie, Oshie or
Hantei.

Phase 22E found that a missing autoloader emitted a native warning before the
example's fixed failure token. The example now installs a temporary E_WARNING
handler only around its trusted autoloader `require`, converts that warning to
a fixed exception, and restores the previous handler in `finally`. The local
missing-autoloader and success proofs passed. This narrow boundary does not
sanitize arbitrary native warnings, malformed PHP, handler throwables or traces.

## Persistent runtime and effect boundary

Registrations are retained explicit configuration. Per-dispatch event,
progress and failure references are released before the next operation,
on success and failure. Overlapping or reentrant dispatch on the **same
dispatcher instance** is rejected before a nested handler invocation, with
the guard released in `finally`. Different instances are not governed by a
global recursion detector. No parallelism or thread safety is inferred.
Caller-owned handlers and resources are never closed behind the caller's back.
Lifetime-safe application composition must keep handler state from leaking
between operations.

Finite registration bounds limit framework bookkeeping. An
application handler is ordinary synchronous PHP and can block, allocate,
contact a service, or mutate state; the framework cannot promise a hard
deadline, total application-memory ceiling, sandbox or automatic cancellation.
Publishing grants no business permission. Each effectful handler must check
application-owned authorization at its own effect boundary. The dispatcher
neither evaluates nor certifies arbitrary business policy. Framework
dispatch performs no hidden network, filesystem, credential, telemetry, AI,
role, export or background action. Any such effect in a handler belongs to
the application and must be documented by its author.

## Accepted boundary and remaining work

| State | Boundary |
| --- | --- |
| **Architect-accepted (22A)** | This optionality, ownership, synchronous order, failure, privacy and lifecycle direction; ADR 0215 and the fixed A-F roadmap. |
| **Architect-accepted (22B)** | Optional inert `kongono/event-contract`, exact marker/handler/registration/set API, finite bounds and four Safe diagnostics under ADR 0216. It remains inert. |
| **Architect-accepted within recorded limits (22C)** | Separately optional `kongono/event-runtime`, four declarations, exact synchronous dispatch, one fixed Safe reentrancy diagnostic and adversarial operation tests under ADR 0217. |
| **Architect-accepted within recorded local limits (22D)** | Eight-file application-owned `event-minimal` example: explicitly loaded event, two direct handlers, authorization before each receipt/ledger effect, truthful partial failure and caller-owned closure lifetime. No framework package or API. |
| **Architect-accepted within recorded local limits (22E)** | Root-owned assembled-path proof, narrow demo bootstrap warning correction and one isolated offline copied-example proof. No new framework API or package. |
| **Local acceptance and focused current-source validation complete (22F)** | Root-owned eight-group acceptance runner, separate own-autoload private consumer, accepted per-file style and analysis, focused PHPUnit and offline root validation. Broad validation, advisory audit, architect milestone acceptance and exact checkpoint remain pending. |

No asynchronous dispatch, queue, worker, retry, scheduler, outbox,
transaction hook, rollback, event sourcing, replay, persistence, transport,
distributed ordering, deduplication, cancellation, veto/stop propagation,
return aggregation, ORM/model hook, Graph schema/ID, new AI role, CLI, UI,
cloud provider, benchmark, release or Stable claim is selected. Generated
event wiring or a build artifact requires separate demonstrated need and
architect authority; explicit composition remains the current direction.

## Related material

- [Event authoring](../engineering/event-authoring.md)
- [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md)
- [ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md)
- [ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
- [Phase 22 roadmap](phase-22-roadmap.md)
- [Event-minimal copy-once example](../../skeletons/event-minimal/README.md)
- [Application composition and build](application-composition-and-build.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
