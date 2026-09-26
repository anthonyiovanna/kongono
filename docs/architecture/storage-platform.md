# Object Storage Platform

## Status

Phases 21A-F are **Current** and Accepted through
[ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
this guide, the [storage-authoring guide](../engineering/storage-authoring.md),
the fixed [Phase 21 roadmap](phase-21-roadmap.md), and
[ADR 0208](../adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
[ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
[ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
[ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
[ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md),
the [deterministic storage-profile build guide](deterministic-storage-profile-build.md),
the [contained local adapter guide](contained-local-storage-adapter.md), and
the [exact-current storage runtime guide](exact-current-storage-runtime.md), and
the [storage application workflow guide](storage-application-workflow.md).
Phase 21G implements the optional Experimental storage-inspection leaf under
[ADR 0213](../adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](storage-inspection-and-intelligence.md).
Phase 21G is Current and Accepted. Phase 21H adds the
[root-owned local acceptance harness](phase-21-external-storage-acceptance.md)
under [ADR 0214](../adr/0214-phase-21-external-storage-acceptance-and-checkpoint.md).

Phase 21B adds only the optional inert Experimental
`kongono/storage-contract` leaf. Phase 21C adds only the optional Experimental
`kongono/storage-build` deterministic profile/artifact/currentness/publication
leaf. Phase 21D adds the optional Experimental
`kongono/storage-adapter-contract` protocol and separately optional
Experimental `kongono/storage-adapter-local` implementation. Phase 21D adds no
exact-current runtime, command, cloud/provider integration, credential or
authorization mechanism, application workflow, public delivery, or executable
example. Phase 21E adds the separately optional Experimental
`kongono/storage-runtime` exact-current operation leaf over one directly
supplied compatible neutral adapter; it does not require the local adapter.
Phase 21F adds the separately optional Experimental
`kongono/storage-application` workflow, three explicit profile commands, and
the copy-once `storage-minimal` example. Phase 21G adds only offline
inspection of supplied exact-current evidence and optional detached bounded
aggregate counts. Phase 21H implementation has separate local validation,
security audit, architect acceptance, and checkpoint gates.

## Goals

The Storage Platform provides an optional provider-neutral boundary for
application-owned objects and binary content whose persistence may be
authoritative. It is designed to preserve:

- install-none default behavior;
- explicit capability and authorization ownership;
- distinct logical and provider identity domains;
- honest integrity, mutation, and uncertain-outcome evidence;
- bounded streaming and deterministic cleanup;
- replaceable provider adapters;
- deterministic build artifacts and exact-current runtime consumption; and
- bounded, actionable, nondisclosing diagnostics.

## Non-goals

Storage is not cache, database persistence, an asset builder, upload
controller, media processor, public-file server, CDN, backup system, cloud SDK
abstraction, queue, lock service, or deployment manager. Phase 21 selects no
cloud provider and creates no automatic public URL, encryption/KMS, retention,
replication, malware scanning, multipart protocol, watcher, or background job.

## Component and dependency direction

```text
application policy and composition root
        |
        +--> Current application workflow (21F)
        |          |
        |          +--> Current exact-current runtime (21E)
        |                     |
        |                     +--> Current neutral adapter protocol (21D)
        |                                  |
        |                                  +--> Current contained local adapter
        |
        +--> Current deterministic profile build (21C)
        |          |
        |          +--> Current neutral storage contract (21B)
        |
        +--> Current visibility-safe inspection (21G)

lower Kernel, HTTP, database, security, Graph, asset, cache, and agent packages
do not depend outward on Storage
```

Every package is independently optional. Provider adapters depend on
the neutral contract rather than redefining it. The first accepted concrete
adapter is local filesystem under one exact supplied root; cloud adapters are
outside Phase 21.

## Logical identity and explicit authority

A logical object identity identifies an application concept. It is not a path,
URL, filename, provider key, bucket name, digest, credential, capability, or
authorization decision. A logical namespace groups identities for one bounded
application-owned purpose without becoming a provider locator.

The following domains remain distinct:

| Domain | Meaning | Does not grant |
| --- | --- | --- |
| Logical object identity | Opaque application selection | Provider location or operation authority |
| Logical namespace | Bounded application grouping | Bucket, directory, tenant, or credential selection |
| Provider locator | Adapter-owned root, bucket, container, endpoint, or key mapping | Authorization or logical identity |
| Content digest | Evidence over exact bytes under a named algorithm | Durability, origin, authorization, or currentness |
| Metadata | Bounded caller-owned descriptive evidence | Trusted provider truth or executable policy |
| Version/concurrency token | Provider-scoped mutation evidence | Global ordering, lock, lease, or authority |
| Operation identity | One bounded lifecycle correlation | Permission or proof of external effect |
| Authorization | Application-owned decision for an exact operation | Provider credential or successful mutation |

Storage authority is supplied through an explicit capability at the
composition root. Provider credentials, tenant mapping, authorization policy,
and network-egress policy stay independently owned. A string or configuration
record is never promoted into authority.

## Contract, build, adapter, runtime, application, and inspection separation

- The **Current neutral contract** defines bounded identities, requests,
  results, outcomes, concurrency evidence, streaming ownership, and lifecycle
  vocabulary without a provider or convenience layer.
- The **Current deterministic build** validates explicit application-owned
  declarations and emits canonical versioned storage-profile evidence without
  contacting a provider.
- The **Current adapter protocol** translates neutral operations and reports
  only guarantees it can support. The accepted local adapter is separate.
- The **Current runtime** consumes one exact current profile, creates one
  operation-scoped capability, validates adapter evidence, applies bounds, and
  guarantees cleanup.
- The **Current application workflow** provides explicit plan, build, check,
  composition, and copy-once example paths without discovering configuration.
- The **Planned inspection leaf** reconstructs supplied evidence offline and
  emits only permitted Safe or Protected projections.

The contract uses Contract Version 1 and exact finite maximums: namespace 64,
logical identity 255, operation identity 64, version 256, continuation 512,
media type 127, metadata 32 entries with 64-byte names and 1,024-byte values,
chunk 8,388,608, object 1,099,511,627,776 bytes, and list page 1,000 entries.
Create requires `if_absent`; replace and delete require `if_version`.
Completed results require their typed evidence while uncertain and failed
results may not overclaim resulting evidence.

No layer silently acquires authority from another. An artifact is not a
credential, an adapter is not authorization, a provider acknowledgement is not
universal durability, and inspection is not a storage operation.

## Integrity, concurrency, and uncertain outcomes

The accepted mutation vocabulary distinguishes create-only, conditional replacement,
version-matched mutation, and deletion. It also distinguishes absent,
already-existing, conflict, known completion, known failure, rejected,
unavailable, and uncertain outcomes. Silent overwrite is forbidden.

Content lengths, checksums, algorithms, versions, conditional tokens, and
provider acknowledgements carry explicit provenance. They are evidence only.
The neutral layer does not claim stronger atomicity, durability, consistency,
or ordering than the selected adapter publishes.

An uncertain mutation means an effect may have occurred without conclusive
acknowledgement. It is not safe success, ordinary failure, or permission for a
hidden retry. Application policy owns any later reconciliation. Storage
conditional tokens do not create locks, leases, transactions, queues,
counters, or exactly-once behavior.

## Streaming and persistent-runtime lifecycle

The accepted contract supports bounded streaming without requiring complete
object buffering. Before an effect, callers and adapters establish finite
chunk, total-byte, metadata, duration, operation-count, and result bounds.
Backpressure, cancellation, close, abort, ownership transfer, and cleanup are
explicit.

Every operation owns its mutable state. File handles, sockets, temporary files,
multipart state, buffers, locks, callbacks, credentials, and object metadata
are retired on success, expected outcome, cancellation, primary failure, and
cleanup failure. No operation state leaks into a later request in a persistent
worker. Destructors, shutdown hooks, and garbage collection are not the primary
cleanup protocol.

## Contained local adapter

The accepted local adapter receives one exact caller-supplied root and no other
filesystem authority. It must reject:

- traversal and absolute-path escape;
- ambiguous separators and platform device paths;
- reserved names and alternate data streams;
- symlink or reparse-point escape;
- case-folding collisions or ambiguity it cannot prove safe;
- current-working-directory, home, or temporary-directory defaults; and
- directory scanning as object discovery authority.

Containment is proven at the final read, mutation, and cleanup boundaries. The
architecture does not promise identical path, case, link, metadata, atomicity,
or durability behavior across operating systems or providers.

## Provider capabilities and limitations

Each adapter declares supported operation kinds and its actual
atomicity, durability, consistency, conditional mutation, metadata, listing,
ordering, cancellation, and cleanup guarantees. Unsupported behavior fails
explicitly. Kongono does not guess, invisibly emulate, or inflate guarantees.

Listing is optional. A supported listing is bounded, paginated,
deterministically ordered under a documented adapter contract, and continued
with opaque evidence. Empty, truncated, stale, and unsupported results remain
distinct. A list result is not authorization, configuration, or provider
discovery.

## Accepted artifact and runtime flow

```text
explicit declarations
    -> structural validation
    -> canonical versioned profile artifact
    -> explicit contained copy-once publication
    -> exact-current validation against explicit current declarations
    -> explicit adapter and authorization composition
    -> one bounded operation
    -> typed result plus deterministic cleanup
```

The profile build package itself exposes no command or runtime. The accepted
runtime never scans declarations, rebuilds, selects the newest artifact,
reconciles stale input, discovers providers or credentials, or falls back to a
convenient profile. Phase 21F commands are explicit application workflows.

## Diagnostics, disclosure, and inspection

Diagnostics identify a closed operation category, violated invariant, bounded
fact, and actionable remediation. Allowed evidence may include declared
capabilities, byte/count limits, algorithm identifiers, generic outcome
categories, and provenance state. It omits object contents, raw identities,
paths, provider locators, URLs, credentials, signing material, customer
metadata, tenant identifiers, arbitrary provider messages, and response
bodies.

Planned inspection is offline, read-only, stateless, bounded, and
authority-preserving. It does not open objects, list provider contents, contact
a provider, validate credentials, execute an operation, mutate runtime state,
rebuild artifacts, or reveal Protected or Forbidden values.

## Developer workflow

The accepted application path is explicit:

1. install only separately authorized packages;
2. declare one bounded storage profile;
3. plan, publish, and check the exact deterministic profile artifact explicitly;
4. supply an exact current build, compatible adapter, and application authorizer;
5. invoke the guarded runtime for each authorized operation; and
6. handle typed results, uncertain outcomes, cancellation, and cleanup.

Good contracts, deterministic tools, safe defaults, generated examples, and
inspection provide convenience. Global facades, runtime scanning, hidden
retries, implicit directories, environment discovery, and silent provider
fallbacks do not.

## Phase status

| Phase | Status | Boundary |
| --- | --- | --- |
| 21A | **Accepted** | Documentation architecture, authority and integrity doctrine, authoring guidance, and fixed roadmap only. |
| 21B | **Current / Accepted** | Optional inert Experimental neutral storage contract and bounded vocabulary. |
| 21C | **Current / Accepted** | Optional Experimental deterministic storage-profile artifact, exact-current validation, and contained publication. |
| 21D | **Current / Accepted** | Neutral adapter protocol and contained local adapter. |
| 21E | **Current / Accepted** | Exact-current operation runtime. |
| 21F | **Current / Accepted** | Supported profile workflow, explicit guarded composition, three commands, and copy-once example. |
| 21G | **Current / Accepted** | Visibility-safe inspection and developer intelligence. |
| 21H | **Implemented; local validation, audit and checkpoint separate** | External acceptance and exact checkpoint. |

The roadmap is not implementation authority.

## Prohibited scope

Phases 21B-C add no config key, environment variable, provider selection,
credential source, URL scheme, adapter, runtime, object operation, command,
executable example, upload flow, public delivery, encryption/KMS,
retention, legal hold, replication, backup, malware scanning, media
transformation, multipart transfer, watcher, queue, event, telemetry, mail,
notification, realtime, scheduler, workflow engine, or AI behavior.

## Related material

- [ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md)
- [ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md)
- [ADR 0208](../adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md)
- [ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md)
- [ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md)
- [ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md)
- [ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md)
- [Deterministic storage-profile build](deterministic-storage-profile-build.md)
- [Contained local storage adapter](contained-local-storage-adapter.md)
- [Exact-current storage runtime](exact-current-storage-runtime.md)
- [Storage application workflow](storage-application-workflow.md)
- [Phase 21 roadmap](phase-21-roadmap.md)
- [Storage authoring](../engineering/storage-authoring.md)
- [Cache Platform](cache-platform.md)
- [Progressive architecture](progressive-architecture.md)
- [Security policy](../../SECURITY.md)
