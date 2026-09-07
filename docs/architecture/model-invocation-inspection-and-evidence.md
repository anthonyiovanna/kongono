# Model Invocation Inspection and Bounded Evidence

## Purpose and context

Phase 14G implements the optional Experimental
`kongono/model-invocation-inspection` upper evidence leaf. It makes one exact
supplied Phase 14F profile/wiring pair inspectable and may summarize one already
completed detached Phase 14D result. It does not invoke a model, provider,
adapter, runtime, application, bridge, Agent port, Graph operation, recorder,
exporter, or developer role.

Use it for deterministic aggregate inspection after application-owned evidence
already exists. Do not use it to discover profiles, select providers, obtain
credentials, load wiring, run or replay an operation, observe live state,
authorize work, monitor a service, record telemetry, certify correctness, or
repair anything.

## Input and exact supplied proof

`ModelInvocationInspectionRequest` accepts only a typed
`ModelInvocationApplicationProfile`, matching
`GeneratedModelInvocationWiring`, and optional `InvocationRuntimeResult`.
Paths, directories, streams, callables, loaded handles, adapters, runtimes,
providers, ports, recorders, registries, containers, environment values, and
currentness sources cannot enter the request.

`ModelInvocationInspector` calls Phase 14F structural and exact-current
validation on the supplied canonical bytes relative to the supplied profile.
It then compares every public binding needed to reject substitution, including
profile/source/artifact/request and generated implementation evidence. It does
not require or evaluate generated PHP.

Optional runtime evidence must be retired, cleanup-complete, terminal, exact-
invocation/profile attributable, and receipt/result consistent. Event
invocation relationships are checked through detached public values only. No
payload is replayed or probed and no external truth is inferred.

## Output and disclosure

`ModelInvocationInspection` uses schema
`kongono.model-invocation-inspection`, version 1. Its canonical JSON has fixed
key order, UTF-8 without BOM, LF framing, and one final LF. Its identity is the
lowercase SHA-256 of the complete canonical value with only the identity field
omitted.

The output includes only fixed contract/profile/wiring versions, Protected
technical digests required for exact comparison, aggregate counts, streaming
and presence booleans, optional closed terminal tokens, nonnegative counts
already present in lower evidence, and these limitation meanings:

- scope: `model-invocation-v1`;
- disclosure: `safe-aggregate-only`;
- supplied profile/wiring: `exact-supplied-profile`;
- external profile/provider currentness: `not-established`;
- application state: `not-observed`;
- provider/account/network state: `not-observed`;
- external effect: `not-established`;
- authority: `evidence-only`;
- Graph relationship: `not-established`; and
- trusted generated/in-process PHP: `not-sandboxed`.

Raw prompts/messages/responses/chunks/actions/arguments, provider/model/adapter
labels, arbitrary identities, usage metric names, currencies, receipt details,
credentials, secrets, endpoints, paths, environment values, database data,
generated PHP, foreign exception messages, traces, and hidden reasoning are
omitted from every serialization, diagnostic, exception, and projection.

## Graph and neutral projections

`ModelInvocationGraphEvidenceProjector` accepts an explicit role and optional
already validated Phase 11G `ApplicationGraphIntelligenceView`. A supplied view
must address the same role. Only its already permitted Safe identities,
contract version, aggregate counts, and fixed limitations are copied. No Graph
is read and no model-to-Graph presence, relationship, currentness,
authorization, or executability is inferred.

The Observability and Security Audit projectors construct neutral values with
fixed Safe aggregate attributes. They do not record, export, buffer, flush,
sample, time, retain, store, or deliver them. Projection success means only
that a neutral value was constructed; it is not a security, compliance,
durability, provider-success, or external-effect claim.

## Developer roles and independent verification

`ModelInspectionDeveloperRole` maps Chie to explain, Hantei to verify, and
Oshie to teach. `ModelInvocationIntelligenceDeriver` creates a
presentation-neutral view containing the complete authorized inspection,
optional bounded Graph evidence, and fixed no-execution semantics. It does not
depend on or invoke any role, Knowledge, model, prompt, retrieval system, tool,
or auto-fix mechanism.

`ModelInvocationIntelligenceVerifier` independently compares the caller-
supplied candidate across ordered role, identity/schema, Protected binding,
aggregate, runtime, Graph, limitation, no-execution, and serialization groups.
It never calls the deriver, repairs or replaces the candidate, or executes a
role. PASS is bounded comparison evidence, not Hantei execution,
certification, authorization, approval, provider truth, or proof of effect.

## Lifecycle, concurrency, failure, and performance

All production objects are immutable and the services retain no cross-call
state. Equal explicit inputs produce equal bytes under repeated, nested, and
interleaved use. Work is bounded by the already finite lower-layer collections;
there is no I/O, background work, or unbounded accumulation.

Typed operational failures use diagnostics `KGN-MODEL-INSPECTION-1437` through
`1441` and one closed Safe component value. Foreign messages and arbitrary
caller data are not copied. Prior throwable identity is retained when a lower
failure is translated.

The package is an optional leaf. No existing production package depends on it,
and Kongono remains operable without the package, AI, a provider, an account,
credentials, or a network. Phase 14H remains planned.

## Related material

- [ADR 0153](../adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md)
- [Model Invocation Application Workflow](model-invocation-application-workflow.md)
- [Application Graph Developer Intelligence](application-graph-developer-intelligence.md)
- [Observability and Operational Evidence](observability-and-operational-evidence.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Phase 14 Roadmap](phase-14-roadmap.md)
- [Phase 14 External Acceptance](phase-14-external-acceptance.md)

Phase 14H externally proves that inspection remains offline, read-only,
stateless, non-loading, and presentation-neutral. Its Graph, Observability,
Security Audit, and Chie/Hantei/Oshie-addressed values remain inert evidence,
not recording, delivery, role execution, authority, certification, or repair.
