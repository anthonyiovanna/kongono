# Kongono Event Runtime

`kongono/event-runtime` is a separately optional Experimental synchronous dispatcher for the inert [event contract](../event-contract/README.md). It accepts one already validated `EventRegistrationSet` and invokes directly supplied handlers for the event object's exact canonical concrete class, in registration order.

Phase 22C is architect-accepted within its recorded local validation and
time-bound audit limits. See
[ADR 0217](../../docs/adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md),
the [Application Events guide](../../docs/architecture/application-events.md),
and [event authoring](../../docs/engineering/event-authoring.md). The
application-owned [event-minimal example](../../skeletons/event-minimal/README.md)
is architect-accepted within its recorded Phase 22D local validation limits.
Phase 22E assembled-path and copied-consumer validation is locally complete,
and architect-accepted within recorded limits. Phase 22F's root-owned offline
own-autoload consumer and focused current-source validation passed; broad
validation, advisory audit, architect milestone acceptance and exact checkpoint
remain pending.

```php
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;

$dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
    new EventRegistration(OrderPlaced::class, $handler),
]));
$dispatcher->dispatch($orderPlaced);
```

The application must load its event classes before registration, construct and authorize its handlers, and own handler lifetime and effects. Each registration binds one canonical concrete class and one handler object. Up to 64 registrations are accepted by the contract. An empty set, an unmatched class, an anonymous event, an enum marker, or a class name too long to register completes as a no-op when the dispatcher is idle. A derived event does not match its base registration. Alias-created objects use their canonical concrete class. Dispatch calls each selected handler once with the same event object.

`dispatch(ApplicationEvent $event): void` passes that same object to each
matching handler. The first handler `Throwable` escapes unchanged. Earlier handler effects remain; later handlers do not run. The dispatcher supplies no transaction, rollback, retry, durable delivery or authorization. A nested or overlapping call on the same dispatcher rejects before matching, with the fixed Safe diagnostic `KGN-EVENT-RUNTIME-2221` and `violation=reentrant_dispatch`. A handler may catch that rejection and continue its outer dispatch. The guard is released when the outer call unwinds, including after a handler failure. Another dispatcher instance is independent. A suspended Fiber keeps its dispatcher busy until it resumes and unwinds.

The dispatcher retains the caller-supplied registration set and one busy flag. It does not retain completed events, handler failures or progress. Handlers, caught exceptions and suspended Fibers can retain their own references. Caller resources remain caller-owned. Native cloning and serialization are not supported lifecycle/reset APIs. The deliberate busy diagnostic is safe for presentation; native type errors, traces and application exceptions require application-owned safe presentation.

There is no automatic publication, discovery, service location, wildcard or inherited matching, priority, cancellation, queue, transport, worker, telemetry or Graph integration. PHP support is `>=8.5.0 <8.6.0`.
