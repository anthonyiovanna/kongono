# Model-Invocation Plan Artifact

> **Phase 14G current boundary:** the separate bounded
> [`kongono/model-invocation-runtime`](model-invocation-runtime.md) validates one
> exact source/artifact pair before one directly supplied adapter attempt. The
> Phase 14F [application workflow](model-invocation-application-workflow.md)
> delegates currentness to this package before binding one direct adapter. The
> Phase 14G [inspection leaf](model-invocation-inspection-and-evidence.md)
> reuses exact-current validation over explicit supplied evidence without
> loading generated PHP. The current graph is 75 packages / 289 local edges /
> acyclic, with 1,358 / 686 / 76 production/package-test/root-test PHP files.

## Purpose and context

Phase 14C implements the optional Experimental
`kongono/model-invocation-build` package above the inert Phase 14B neutral
contract. It creates deterministic coordination evidence for a future model
invocation without invoking a provider or granting authority.

Use it when an application already owns one exact version-1
`InvocationRequest`, has explicitly selected an adapter identity label, and
wants to bind those values to finite execution-budget evidence. Do not use it
as a provider client, credential store, adapter registry, authorization token,
runtime receipt, Agent proposal, pricing source, or external-currentness proof.

## Source and budget

`InvocationPlanSource` binds exactly a plan identity, adapter identity,
invocation request, and execution budget. The two new identities use the same
1-128-byte grammar as the neutral contract. The budget fixes one attempt,
1-300,000 milliseconds, 1-1,048,576 retained-evidence bytes, zero to sixteen
strictly sorted unique usage ceilings in the JSON safe-integer domain, and an
optional exact currency/decimal cost ceiling.

These are policy claims only. No timer, cancellation source, meter, tokenizer,
pricing lookup, quota, billing, retry, or fallback is created.

## Canonical evidence

The builder transiently encodes the complete request in fixed insertion order
and hashes those bytes, including one terminal LF. Raw message content is used
only for that hash and is absent from the published artifact, retained state,
diagnostics, and public return surface. A second complete source projection is
hashed separately.

The compact artifact has fixed schema, version, builder, plan, and source digest
fields. It exposes canonical bytes plus artifact, source, and request hashes and
the three coordination identities. All JSON is compact UTF-8, LF-terminated,
float-free, and at most 65,536 bytes.

## Validation and currentness

Structural validation rejects malformed framing, unknown or reordered keys,
wrong types, floats, mixed versions, invalid identities or digests,
noncanonical capability/budget order, incompatible request modes/streaming, and
noncanonical bytes. Because messages are omitted, structural validation proves
only the stored plan evidence.

Current validation uses no filesystem or ambient state. It validates structure,
rebuilds from the one complete source supplied in that call, and requires
byte-identical artifacts and identities plus exact request and source digests.

## Publication, security, and lifecycle

Publication revalidates before I/O. The application supplies one existing
absolute canonical ordinary writable directory whose ancestors contain no
symlink, junction, or reparse transition. The publisher uses
`kongono-model-invocation-plan-v1-<artifact-sha256>.json`, exclusive temporary
creation in the same directory, checked flush/sync, hard-link no-overwrite
publication, identical-byte idempotence, conflict detection, and full temporary
cleanup.

Diagnostics contain only closed Safe violation/field labels and bounded numeric
context. Raw messages, identity labels, provider/model/adapter values, digests,
currency/amount text, paths, credentials, secrets, and throwable messages are
not presented. Values are immutable and arrays detached; no request/source is
retained across operations.

## Non-capabilities and stability

Phase 14C adds no provider or model call, SDK, transport, credential supply,
adapter port or execution, retry/fallback, stream coordinator, runtime,
application workflow, Agent bridge, registry, discovery, command, background
work, Graph fact, recording, or developer-role execution. Phase 14D and Phase
14F are implemented only in their separate packages; Phase 14G is implemented
only in its separate inspection package and Phase 14H remains planned.

The current production graph is 75 packages and 289 local edges, acyclic. PHP
inventory is 1,358 production files, 686 package-test files, and 76 root-test
files.

Phase 14H externally proves deterministic construction, structural validation,
exact caller-relative currentness, contained publication, idempotence, and
no-clobber behavior in an isolated temporary workspace. Plan evidence remains
neither provider availability nor deployment truth. See [Phase 14 external
acceptance](phase-14-external-acceptance.md).
