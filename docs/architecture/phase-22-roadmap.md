# Phase 22 Roadmap: Optional Application Events

## Status and authority

Phase 21A-H are architect-accepted and closed at the exact local checkpoint
whose subject is `Complete Kongono Phase 21 storage platform milestone`.
The underlying local validation, time-bound dependency audit, architect
acceptance, and checkpoint were separate gates with the limits recorded in
the [Phase 21 acceptance guide](phase-21-external-storage-acceptance.md).

Phase 22A accepts [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md),
the [Application Events guide](application-events.md), and the
[event-authoring guide](../engineering/event-authoring.md) as documentation
architecture only. Phase 22B implements the optional Experimental inert
`kongono/event-contract` under
[ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md).
Phases 22A and 22B are architect-accepted. Phase 22C implements the optional
Experimental `kongono/event-runtime` under
[ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md).
Phase 22C is architect-accepted within its recorded local validation and
time-bound audit limits. Phase 22D implements the application-owned
[event-minimal example](../../skeletons/event-minimal/README.md), with focused
local validation complete and architect acceptance granted within recorded
limits. Phase 22E local assembled-path and copied-example validation are
complete and architect-accepted within recorded limits. Phase 22F's root-owned
eight-group offline acceptance and same-checkout, own-autoload copied-consumer
proof are retained for unchanged executable inputs. Full separated style and
analysis and both owner-context full-suite profiles passed within recorded
limits, including 314 suppressed PHP warnings per profile. The September 30,
2026 locked audit passed for the unchanged lock. Documentation and fresh offline
Knowledge reconciliation are recorded in the final Phase 22F report only after
successful validation. Architect milestone review and the exact checkpoint
remain pending; Phase 22 is not closed.
Each row requires its own authenticated architect
directive and independent acceptance. This
roadmap grants no implementation authority and does not mechanically use
eight rows.

## Fixed A-through-F roadmap

| Row | Status | Boundary and dependency | Acceptance intention and exclusions |
| --- | --- | --- | --- |
| **22A - Architecture and Phase 21 reconciliation** | **Architect-accepted** | Accept optional application-event purpose, explicit synchronous direction, ADR 0215, authoring guidance, this six-row roadmap, and truthful Phase 21 status reconciliation. | Documentation consistency and preservation only. No package, PHP API, registration object, runtime, handler effect, command, example, dependency, or checkpoint. |
| **22B - Neutral event contract** | **Architect-accepted** | Inert optional `kongono/event-contract`: native `ApplicationEvent` marker and non-generic `EventHandler`, one already-loaded canonical concrete event class per registration, ordered set of 0-64 registrations, duplicate pair rejection by exact event type and handler object identity, four fixed Safe diagnostics. | Focused type, bound, duplicate, inertness, nondisclosure, tooling and dependency checks pass locally. No dispatcher invocation, auto-discovery, serialization, queue, storage, or authority grant. |
| **22C - Optional synchronous dispatcher** | **Architect-accepted within recorded limits** | Separately optional Experimental `kongono/event-runtime` depends on API Metadata, Diagnostics and the accepted neutral contract; directly invokes matching handlers in exact registration order under ADR 0217. | Focused tests prove complete validation-before-effect, exact match/no-op, original first Throwable and earlier partial effects, same-instance reentrancy rejection, `finally` guard release and operation isolation. No retry, rollback, parallel execution or broker. |
| **22D - Supported caller-owned composition** | **Architect-accepted within recorded local limits** | The eight-file application-owned `event-minimal` copy-once example directly composes two ordered handlers over accepted contracts/runtime. | Focused tests prove event validation, authorization immediately before each effect, original Throwable and partial effects, deliberate lifetime-safe reuse and separate compositions. No ambient service, automatic publisher, compiler, generated wiring or CLI. |
| **22E - Adversarial cross-package and developer-experience acceptance** | **Architect-accepted within recorded local limits** | Root-owned assembled-path proof, corrected demo bootstrap warning boundary and one isolated offline copied-example proof. | Local evidence covers boundary, privacy, partial progress, misuse, isolation and authoring guidance. It does not certify production use or replace 22F's separate consumer evidence. |
| **22F - External acceptance and exact milestone checkpoint** | **Local validation reconciled within recorded limits; architect milestone review and checkpoint pending** | Retained eight-group harness and private own-autoload consumer proof; 129 manifest validations, qualified platform evidence, full separated style and analysis, two owner-context full-suite profiles and time-bound locked audit. Fresh documentation/Knowledge evidence is recorded in the final report after validation. | Separated gates satisfy the authorized offline requirement under F.47; literal Composer quality and Hantei umbrella commands were not run. Diagnostic, residue, runtime and Storage limitations remain in the acceptance guide. No phase closure, publication, Stable/1.0, production, performance or security certification. |

## Dependency and authority order

Architecture precedes a neutral contract. A dispatcher may depend inward on
that contract; a supported application workflow may compose only accepted
lower boundaries. Root-owned acceptance follows the implemented path. No
later row is pulled forward for convenience, and no row self-certifies. The
contract and runtime are separately optional; Kernel, HTTP, DI, database,
Storage and Agent packages receive no mandatory event dependency.

Phase 22A selected no exact PHP signatures or numbers; ADR 0216 now fixes the
Phase 22B inert contract, and ADR 0217 fixes the Phase 22C synchronous runtime.
Event ID, payload schema registry and generated artifact remain unselected.
Future public symbols begin Experimental unless separately
promoted. PHP support remains `>=8.5.0 <8.6.0`; PHP 8.6, PHP 9,
performance superiority, production readiness, and Stable/1.0 remain
unclaimed.

Phase 22D's sample authorizer and in-memory effects are application code, not
framework authorization. The copied destination and path repository require
manual review. Phase 22E's private local copy was distinct from Phase 22F's
[separate-process, own-autoload copied-consumer proof](phase-22-external-acceptance.md).
Both use this source checkout; neither proves a published distribution or a
real owner's destination.

## Deferred capabilities

Operational telemetry and security-audit evidence remain distinct from
application occurrences. No asynchronous queue, worker, scheduler,
transaction/outbox, durable delivery, event store, replay, automatic
publisher, Graph fact, authorization grant, transport, provider, runtime
discovery, wildcard matching, hidden retry, or broad framework integration
is accepted by this roadmap.

## Related material

- [ADR 0215](../adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md)
- [ADR 0216](../adr/0216-neutral-application-event-contract-and-bounded-registration.md)
- [ADR 0217](../adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md)
- [Application Events](application-events.md)
- [Phase 22 local acceptance](phase-22-external-acceptance.md)
- [Event authoring](../engineering/event-authoring.md)
- [Phase 21 roadmap](phase-21-roadmap.md)
- [Phase 21 external acceptance](phase-21-external-storage-acceptance.md)
