# ADR 0216: Neutral application-event contract and bounded registration

- Status: Architect-accepted for Phase 22B; inert contract remains separate from the architect-accepted Phase 22C runtime and the Phase 22D application-owned example
- Date: 2026-09-26
- Phase: 22B

## Context

[ADR 0215](0215-optional-application-events-and-explicit-synchronous-dispatch.md)
accepts optional application-owned events and future explicit synchronous
dispatch. It deliberately left exact PHP types, registration identity,
limits and diagnostics to repository inspection. The accepted 22B discovery
and 22B.1 correction supply that evidence. This decision creates only the
optional inert `kongono/event-contract` leaf. A dispatcher is a separate
Phase 22C decision and implementation.

## Decision

### Exact native contract

`ApplicationEvent` is an empty marker. `EventHandler` has exactly
`handle(ApplicationEvent $event): void` and is **not generic**. PHP native
parameter variance does not let an implementation narrow that parameter to
one concrete event while satisfying the interface. A handler may explicitly
narrow inside its body. A registration validates the handler interface and
event type; it cannot prove semantic compatibility of arbitrary application
business code. No generic annotation asserts an unproved pairing. Typed
closures/adapters, signature reflection, and handler invocation are absent.

`EventRegistration` stores one supplied canonical event class string and the
same supplied handler object. `EventRegistrationSet` stores an ordered list
of zero through 64 validated registrations, detached from caller array
reference slots. It does not clone or own the handler objects. Neither class
calls an event constructor, handler method or user metadata method, and
neither retains event instances or per-dispatch state.

### Already-loaded exact class identity

The type name has 1–255 ASCII bytes, no leading backslash, and one or more
PHP-style identifier segments matching `[A-Za-z_][A-Za-z0-9_]*` separated
by a single backslash. The constructor checks `class_exists($name, false)`
so registration never triggers autoload. It then inspects only that
already-loaded supplied class with one local `ReflectionClass`: reject
anonymous, abstract, enum, interface, trait and non-marker types, and require
`getName()` to equal the supplied bytes. This rejects aliases and wrong-case
spelling. An inherited marker is valid. A private constructor is valid
because registration never constructs the event. This targeted inspection
is not a Reflection/attribute scan or classpath discovery. No class list,
filesystem scan, attribute instantiation, method/property inspection, or
cached reflection object is used. `SomeEvent::class` alone does not load a
class; the caller's explicit composition must ensure it is loaded first.

Matching by exact concrete class, no-match no-op, order, original Throwable,
partial effects and same-instance reentrancy belong to the later dispatcher.
Registration grants no authority to dispatch or perform a business effect.

### Duplicates and finite bounds

The duplicate identity is the exact canonical class string plus identical
handler object (`===`). Repeating that pair is rejected, not replaced.
Distinct handler objects for one class and the same handler object for two
different classes are valid. Insertion order is preserved, without priority
or sort. The collection validates count first (maximum 64), then list shape,
each element, then duplicates. Pairwise comparison is finite at 2,016 pairs.
The type name's 255-byte ceiling and collection's 64-entry ceiling bound
framework bookkeeping, not arbitrary application payloads or handler work.
Payload shape, mutation, privacy and business authorization remain
application-owned.

### Deliberate failures and disclosure

The Internal diagnostic enum contains exactly `KGN-EVENT-CONTRACT-2211`
invalid event type, `2212` invalid registration, `2213` duplicate binding,
and `2214` contract bound exceeded. Its sole context field is required Safe
Text `violation`, chosen from the closed factory-specific token lists;
unsupported caller tokens become `unsupported_violation`. Fixed titles,
operations, invariants, remediation, exception messages and `__toString()`
contain no raw event name, payload, handler, path, count, position or
arbitrary Throwable text. The Experimental package exception marker and
concrete invalid-input exception use an Internal abstract presentation base.
Native PHP signature errors and programmatic traces are not sanitized by
this package; outer application boundaries own safe presentation. No handler
Throwable is caught or relabeled because no handler runs in this package.

### Dependency and lifecycle boundary

The optional package requires only PHP `>=8.5.0 <8.6.0`, API Metadata and
Diagnostics. Its eight production declarations are six Experimental and two
Internal, with none Public or Stable. No lower package gains a mandatory
event dependency. No dispatcher, queue, persistence, transaction hook,
automatic publisher, service locator, generated wiring, CLI, inspector,
provider, test harness or network action is created here.

## Alternatives

An envelope, occurrence ID, schema registry or serializer would add meaning
and lifecycle authority not needed for a synchronous typed occurrence.
Generic handler pair annotations would suggest a runtime guarantee PHP cannot
enforce here. A typed closure adapter would need a separately specified
signature-validation and failure boundary. Global IDs or object hashes would
add identity machinery where bounded direct object comparison suffices.
Autoloading or scanning for event classes would make registration depend on
ambient filesystem and class loading effects. These alternatives are not
selected for Phase 22B.

## Consequences and status

Applications can explicitly build and inspect a finite inert registration
list while retaining direct-call and install-none choices. Phase 22B is
architect-accepted. The separately optional Phase 22C runtime is
architect-accepted under ADR 0217 within its recorded limits; this contract
still invokes no handler. Phase 22D's
[event-minimal example](../../skeletons/event-minimal/README.md) directly
composes caller-owned handlers and is architect-accepted within its recorded
Phase 22D local limits. Phase 22E local validation is architect-accepted within
its recorded limits. Phase 22F local consumer acceptance and current-source
focused validation have passed, with broad validation, advisory audit, architect
milestone acceptance and exact checkpoint pending. No
production-readiness claim or Phase 21 closure change follows.

## Related material

- [ADR 0215](0215-optional-application-events-and-explicit-synchronous-dispatch.md)
- [ADR 0217](0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
- [Application Events](../architecture/application-events.md)
- [Event authoring](../engineering/event-authoring.md)
- [Phase 22 roadmap](../architecture/phase-22-roadmap.md)
