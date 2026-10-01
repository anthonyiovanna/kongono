# Event Contract

`kongono/event-contract` is an optional Experimental, inert contract for
application-owned occurrences and explicit bounded handler registrations.
It depends only on API Metadata and Diagnostics. It does not dispatch, invoke,
serialize, persist, queue, log, authorize, or discover events or handlers.
Applications that do not use events need not install it.

An event class implements `ApplicationEvent`. A handler implements the
non-generic `EventHandler::handle(ApplicationEvent $event): void`; it can
narrow the event explicitly inside its method. A registration proves that
the supplied object implements that broad interface. It cannot prove that
the handler's business logic supports the registered event class. The
application owns that pairing and every later effect's authorization.

Load the event class explicitly during composition before constructing a
registration. `SomeEvent::class` yields a string and does **not** load the
class. `EventRegistration` accepts a canonical 1–255-byte ASCII named class
that is already loaded, concrete, and implements `ApplicationEvent`. It
inspects only that supplied class without autoloading, scanning, constructing
it, or calling user methods. Aliases and wrong-case spellings are rejected.

```php
// Application composition; both classes are already loaded by explicit use.
$handler = new InvoiceReceiptHandler();
$eventType = InvoicePaid::class;
$registration = new EventRegistration($eventType, $handler);
$set = new EventRegistrationSet([$registration]);
```

`EventRegistrationSet` accepts a list of at most 64 registrations. Empty is
valid. It retains insertion order and rejects the same event class plus the
same handler object twice; distinct handler objects for one class and one
handler object for different classes are valid. The array slots are detached,
while handler objects remain caller-owned and are neither cloned nor closed.
The API has no dispatch method. The separately optional Experimental
[`event-runtime`](../event-runtime/README.md) implements invocation and
operation lifecycle under ADR 0217 and is architect-accepted within its
recorded limits. The application-owned
[event-minimal example](../../skeletons/event-minimal/README.md) directly
composes this contract with that runtime; its focused local validation is
architect-accepted within its recorded local validation limits. Phase 22E
assembled-path and copied-consumer validation is locally complete, pending
architect acceptance. This contract remains inert.

Deliberate validation failures use four fixed diagnostic IDs
`KGN-EVENT-CONTRACT-2211` through `2214` and a single closed Safe
`violation` field. Package-owned messages and string presentation omit raw
event names, payloads, handlers, and arbitrary exception text. Native
`TypeError` and programmatic traces remain the outer application's concern.
See [ADR 0216](../../docs/adr/0216-neutral-application-event-contract-and-bounded-registration.md)
and [ADR 0217](../../docs/adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
and the [Application Events guide](../../docs/architecture/application-events.md).
