# Authoring Application Events

## Status and choice to adopt

Phase 22A established the architecture. Phase 22B implements the optional
Experimental inert `kongono/event-contract` under
[ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md).
Phases 22A and 22B are architect-accepted. Phase 22C implements the separately
optional Experimental `kongono/event-runtime` under
[ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md).
Phase 22C is architect-accepted within its recorded local validation and
time-bound audit limits. Phase 22D's application-owned
[event-minimal example](../../skeletons/event-minimal/README.md) is architect-
accepted within its recorded local validation limits. Phase 22E assembled-path
and developer-experience validation is architect-accepted within recorded
local limits. Phase 22F's offline own-autoload consumer and focused current-source
validation passed; broad validation, advisory audit, architect milestone
acceptance and exact checkpoint remain pending in the
[Phase 22 roadmap](../architecture/phase-22-roadmap.md).
See [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md)
and the [Application Events guide](../architecture/application-events.md)
for the accepted boundary.

Use a direct call when one use case knows its collaborator and the
relationship is stable. Consider an application event when several
application-owned handlers need the same completed occurrence and the
originating use case should not name each handler. An event says what
occurred; a command asks a recipient to perform work. Neither event
construction nor dispatch grants authorization, proves a transaction
committed, or makes the occurrence durable. Applications may omit this
capability entirely or select another implementation.

## Shape and validation

Name an event for an application occurrence in past-tense business language.
Keep its payload small and application-owned. Validate constructor invariants
and untrusted business input **before** asking a dispatcher to invoke
handlers. Dispatch accepts a typed `ApplicationEvent`, not a loose HTTP or
JSON envelope. Prefer immutable value payloads. A PHP readonly property can
reference a mutable object; it does not deeply freeze that object. The
dispatcher passes the same event to each selected handler, does not clone or
serialize it, and cannot sandbox trusted application code. Handler
mutation is outside the supported immutable-event authoring contract.

Supply registrations and handler collaborators explicitly at the application
composition boundary. A registration pairs one **exact** event type with
one directly supplied handler. Order registrations in the order you intend
effects to run. Several distinct handlers can register for one event.
Duplicate pairs of the same exact event type and the same handler object are
rejected, while distinct handler objects of one class are allowed. The
`EventRegistrationSet` accepts 0-64 ordered registrations and invokes no
handler. Event type names must be canonical ASCII and 1-255 bytes, naming an
already-loaded concrete `ApplicationEvent` class. `SomeEvent::class` supplies
a string but does not load the class. There is no wildcard or inherited-type
matching, priority sorting, automatic subscriber scan or ambient container
lookup.

For example, after the application bootstrap has loaded its `InvoicePaid`
class and supplied a `ReceiptHandler` implementing the native
`EventHandler::handle(ApplicationEvent $event): void` signature, it can build
the inert registration set:

```php
$handler = new ReceiptHandler();
$registrations = new EventRegistrationSet([
    new EventRegistration(InvoicePaid::class, $handler),
]);
```

The application handler must narrow the native marker parameter to
`InvoicePaid` before using event-specific data. This construction does not
dispatch, call the handler, send mail, write a record or infer that an invoice
transaction committed. Effect authorization remains application-owned. After
the application constructs and validates an `InvoicePaid` event, it can use
`new SynchronousEventDispatcher($registrations)` and call
`dispatch($invoicePaid)` explicitly; the dispatcher retains that validated set
and one instance-local busy flag. The application owns the event and handler
lifetimes.

The copy-once example explicitly loads `InvoicePaid` before registration,
passes two distinct handlers directly to an ordered registration set, and
checks the current application authorizer immediately before each receipt and
ledger effect. Its sample policy and in-memory effects grant no real business
permission. Closures may retain captured operation data even when the
dispatcher releases its own per-attempt references; create a fresh composition
for each operation when collaborators capture operation-specific state. Reuse
only with collaborators designed for that lifetime.

## Effects, authorization, and failure

Check authorization at **each business effect boundary**, using the
application's current explicit policy and identity evidence. An event is
neither a Permit nor a capability. The dispatcher does not evaluate arbitrary
business policy. If a handler contacts a provider, writes storage, or emits
telemetry, that effect and its credentials, egress, disclosure and cleanup
belong to the application handler. No framework dispatch path implicitly
performs them.

Handlers run synchronously in registration order. A valid event with no
match, including an empty valid registration set, is a successful no-op.
The first thrown `Throwable` stops later handlers and is returned to the
caller by exact object identity. Earlier handlers may already have made
effects. No rollback or hidden retry occurs. “At most once” describes
invocation of one selected registration **within one attempt**, not
cross-attempt exactly-once delivery. If retry or idempotency is needed for
an application effect, the application must design and authorize it with
the provider's actual guarantees; this guide selects no automatic policy.

Let the outer application error boundary choose safe HTTP/CLI presentation.
Never expose arbitrary handler exception messages, traces, arguments, event
payloads or handler results as public diagnostics. A fixed framework
registration error should identify the rejected invariant and an actionable
repair, such as supplying a distinct binding identity or a valid exact type,
without echoing private payloads. Phase 22B defines four fixed diagnostic IDs
and Phase 22C adds `KGN-EVENT-RUNTIME-2221` for same-instance overlap, each
with a bounded Safe `violation` field. Native PHP errors, handler throwables
and traces are not covered by that deliberate presentation guarantee.

## Lifetime-safe use and review checklist

Treat registrations as explicit retained configuration and event/progress/
failure values as one-dispatch state. Handlers may be application-lifetime
objects only if their own design safely isolates operation data. The
dispatcher rejects overlapping or reentrant calls on the **same instance**
before matching or a nested handler run and clears its guard in `finally`;
another instance is not a global recursion stop. Do not rely on framework dispatch
to close caller-owned handlers or resources. Arbitrary synchronous handler
code can block or allocate beyond framework bookkeeping bounds, so arrange
application-specific limits at its actual effect boundaries.

The Phase 22B contract tests cover bounds, canonical loaded types, duplicate
identity, inertness and nondisclosure. Phase 22C focused tests cover:

1. exact-type selection, explicit order, several distinct handlers, empty
   registration and no-match no-op;
2. duplicate and malformed registration rejection **before** any handler
   effect, with bounded nondisclosing diagnostics;
3. first-handler failure returning the same throwable object, stopping
   later handlers and retaining truthful earlier partial effects;
4. same-instance reentrant/overlapping rejection and later health after
   success and failure;
5. repeated operations without retained event/payload/progress references,
   plus application authorization at each effect boundary; and
6. no automatic payload logging, serialization, telemetry conversion,
   queueing or publication from framework dispatch.

They also cover canonical alias-created objects, idle anonymous/enum/long-name
marker no-ops, original `Exception` and `Error` identity, caught and escaping
nested rejection, suspended-Fiber overlap, later health, and non-retention by
the dispatcher after completion. An idle unregistrable marker object is a
no-op, but its class is not a valid registration input. Handler effects and
authorization remain application-owned. Phase 22C acceptance has its own
recorded gates. Phase 22D's earlier local validation did not exercise a
missing autoloader: Phase 22E exposed its native warning and added a temporary
bootstrap warning handler around the trusted autoloader require. The demo
converts that warning to its fixed failure token and restores the caller's
previous handler in `finally`. This does not sanitize arbitrary native output.
Phase 22E's local copied-example check was distinct from Phase 22F's
[separate-process, own-autoload consumer proof](../architecture/phase-22-external-acceptance.md).
Both used local checkout sources; neither establishes a published distribution,
independent human execution or production suitability.

## Related material

- [Application Events](../architecture/application-events.md)
- [Phase 22 roadmap](../architecture/phase-22-roadmap.md)
- [Event-minimal copy-once example](../../skeletons/event-minimal/README.md)
- [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md)
- [ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md)
- [ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
- [Documentation Standard](../documentation-standard.md)
