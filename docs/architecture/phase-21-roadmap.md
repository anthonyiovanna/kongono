# Phase 21 Roadmap: Optional Object Storage Platform

## Status and authority

Phase 20A-H are Accepted and Phase 20 is closed by its exact local checkpoint.
Phase 21A accepts
[ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
the [Storage Platform guide](storage-platform.md), and the
[storage-authoring guide](../engineering/storage-authoring.md) as documentation
architecture.

Phases 21A-G are **Current** and Accepted. Row 21H has an implemented local
acceptance harness; security audit, architect acceptance, and checkpoint remain
pending. This
fixed roadmap records sequence and boundaries; it grants no implementation
authority. Each increment requires a separately authenticated architect
directive.

## Fixed A-through-H roadmap

| Row | Status | Dependencies and deliverables | Explicit exclusions and acceptance intent |
| --- | --- | --- | --- |
| **21A - Architecture and doctrine** | **Accepted** | Accept the capability boundary, explicit authority model, provider separation, integrity and lifecycle doctrine, authoring guidance, ADRs 0206-0207, and this roadmap. | Documentation only. No package, PHP API, schema, profile, artifact, adapter, runtime, command, provider, directory, test, dependency, or checkpoint. Acceptance is document consistency and preservation only. |
| **21B - Neutral storage contract and bounded vocabulary** | **Current / Accepted** | Add the optional Experimental `kongono/storage-contract` leaf depending only on API metadata and diagnostics. Define bounded logical identities, declared capabilities, read/create/replace/delete/list requests and results, exact version conditions, typed outcomes, detached evidence, and stream lifecycle interfaces. | Inert contracts only. No provider adapter, path mapping, application convenience layer, credential source, authorization system, network effect, filesystem operation, retry, or provider guarantee. Acceptance proves 40 declarations, exact bounds and relations, immutability, closed vocabulary, and nondisclosure. |
| **21C - Deterministic storage profile build** | **Current / Accepted** | Add the optional Experimental `kongono/storage-build` leaf above the accepted neutral contract: explicit bounded profiles, schema-versioned canonical storage-profile artifacts, separate source/build/artifact identities, structural and exact-current validation, and explicit contained publication. | Build-time evidence only. No provider contact, object operation, credential access, adapter, runtime mutation, ambient discovery, command, workflow, or current pointer. Acceptance proves deterministic bytes, currentness, containment, no-clobber publication, cleanup, and nondisclosure. |
| **21D - Neutral adapter protocol and contained local adapter** | **Current / Accepted** | Depend on the neutral contract and explicit profile evidence. Add a replaceable adapter protocol and first separately optional local-filesystem adapter under one exact supplied root. | No cloud adapter, ambient root filesystem, directory discovery, silent overwrite, hidden retry, public URL, durability inflation, or provider auto-selection. Acceptance proves containment, streaming, mutation outcomes, declared limitations, and cleanup. |
| **21E - Exact-current operation runtime** | **Current / Accepted** | Consume only one exact current profile and one directly supplied compatible adapter. Create one operation-scoped capability, enforce bounds and conditional semantics, validate adapter evidence, map uncertain outcomes honestly, and retire resources. | No service locator, scan, rebuild, heuristic provider selection, retry policy, background work, retained state, implicit credential, or ambient authorization. Acceptance proves lifecycle and persistent-worker isolation. |
| **21F - Supported application workflow** | **Current / Accepted** | Compose profile build, exact-current runtime, explicit adapter and authorization collaborators, three profile commands, and a copy-once `storage-minimal` example. | No automatic provider installation, generated business authorization, hidden directory, upload controller, public serving, or cloud selection. Plan/build/check parity and explicit composition are required. |
| **21G - Visibility-safe inspection and intelligence** | **Current / Accepted** | Inspect one supplied exact-current profile and optional detached aggregate evidence. Add bounded Safe/Protected projections and Chie/Oshie/Hantei evidence integration. | No object open, provider listing, credential validation, runtime observation, operation, mutation, repair, role execution, recording, export, disclosure, or certification. Acceptance must prove offline stateless nondisclosure. |
| **21H - External acceptance and exact checkpoint** | **Implemented; local validation, audit and checkpoint separate** | Add root-owned deterministic adversarial acceptance under one explicitly bounded temporary root, regression gates, and one exact local checkpoint. | No cloud-provider certification, deployment, performance claim, Stable/1.0, push, tag, release, or later-phase authority. Acceptance must prove complete cleanup, containment, integrity, nondisclosure, and preservation. |

## Dependency order

The accepted neutral contract precedes the accepted profile build and all
adapters. Deterministic profile evidence precedes exact-current runtime
composition. The adapter protocol precedes the accepted runtime and application
workflow. Inspection consumes only accepted supplied evidence. External
acceptance closes the milestone after all earlier boundaries are separately
accepted.

No later row may be pulled forward to make an earlier row convenient.

## Locked cross-cutting requirements

- Storage remains optional and install-none by default.
- Logical identity, provider location, authority, authorization, content
  evidence, concurrency evidence, and operation lifecycle remain distinct.
- Cache and storage semantics never merge.
- Silent overwrite and hidden retries remain prohibited.
- Streaming, cancellation, close, abort, cleanup, and persistent-runtime
  isolation remain explicit and bounded.
- Provider guarantees are declared honestly rather than normalized upward.
- Build artifacts are deterministic evidence and runtime is exact-current.
- Diagnostics and inspection are bounded and nondisclosing.
- Future public symbols begin Experimental; Phase 21A creates none.
- Phase 21B's 40 declarations are 37 Experimental and three Internal; none is
  Public or Stable.
- Phase 21C's 24 declarations are 17 Experimental, six Internal, and one
  Public exception-family interface; none is Stable.
- Phase 21D's 33 declarations across two separately optional packages are 16
  Experimental, 15 Internal, and two Public exception-family interfaces; none
  is Stable.
- Phase 21E's 17 declarations are eight Experimental, eight Internal, and one
  Public exception-family interface; none is Stable.
- Phase 21F's 21 storage-application declarations are 16 Experimental, four
  Internal, and one Public exception-family interface; none is Stable.
- Phase 21G's 23 storage-inspection declarations are 20 Experimental, two
  Internal, and one Public exception-family interface; none is Stable.
- PHP support remains `>=8.5.0 <8.6.0`; PHP 8.6 and PHP 9 are unclaimed.

## Related material

- [Storage Platform](storage-platform.md)
- [Storage authoring](../engineering/storage-authoring.md)
- [ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md)
- [ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md)
- [ADR 0208](../adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md)
- [ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md)
- [ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md)
- [ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md)
- [ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md)
- [ADR 0213](../adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
- [ADR 0214](../adr/0214-phase-21-external-storage-acceptance-and-checkpoint.md)
- [Deterministic storage-profile build](deterministic-storage-profile-build.md)
- [Contained local storage adapter](contained-local-storage-adapter.md)
- [Exact-current storage runtime](exact-current-storage-runtime.md)
- [Storage application workflow](storage-application-workflow.md)
- [Storage inspection and intelligence](storage-inspection-and-intelligence.md)
- [Phase 21 external acceptance](phase-21-external-storage-acceptance.md)
- [Phase 20 roadmap](phase-20-roadmap.md)
