# ADR 0211: Exact-Current Storage Runtime and Operation-Scoped Lifecycle

- Status: Accepted
- Date: 2026-09-19

## Context

The accepted neutral storage contract, deterministic profile artifact, and
adapter protocol deliberately stop short of runtime composition. Applications
need one bounded way to bind exact-current profile evidence to a directly
supplied adapter while treating every adapter result and stream as untrusted.
That composition must not invent provider, credential, authorization, retry,
or application-workflow authority.

## Decision

Phase 21E adds the separately optional Experimental `kongono/storage-runtime`
package. `StorageRuntimeBinding` accepts exactly one current
`StorageProfileSet`, canonical artifact bytes, selected profile name, expected
adapter identity, and directly supplied neutral `StorageAdapter`. It validates
artifact currentness independently, then requires Adapter Contract Version 1
and exact descriptor equality for adapter, source, build, artifact, profile
name, and profile declaration evidence. It performs no adapter operation,
discovery, rebuild, fallback, or newest selection while binding.

Each read, create, replace, delete, or list obtains one instance-local
operation scope. The scope binds the caller's operation identity and exact
operation kind and is retired explicitly on every terminal path. One runtime
instance permits only one active operation; independent instances remain
independent even when they share an adapter. Request contract version,
namespace, operation, capability, condition, source state, digest, metadata,
object bound, and list bound are checked before adapter effect.

Adapter evidence is validated and detached before exposure. Legal outcomes
remain distinct, including absence, pre-existence, conflict, rejection,
unsupported capability, unavailability, and uncertain mutation. No outcome is
retried, guessed, or silently converted. Create is `if_absent`; replace and
delete require exact `if_version` evidence.

Completed reads return a runtime-owned validating source wrapper. The caller
must exhaust or close it; early close is explicit cancellation and makes no
content-verification claim. The wrapper enforces chunk, total-byte, digest,
exhaustion, and close relations without whole-object buffering. Ownership of a
write source transfers to the runtime for the call; it is metered and closed
exactly once on every terminal path. Destructors and shutdown hooks are not
lifecycle control.

Cleanup failure is never swallowed. Primary-only, cleanup-only, and combined
primary-plus-cleanup failures have distinct exception types, with exact causes
retained privately and only bounded Safe diagnostic context exposed.

## Consequences

The runtime is provider-neutral and does not depend on the contained local
adapter. It adds no registry, facade, service locator, container binding,
ambient configuration, filesystem or network discovery, credential source,
tenant mapping, business authorization, cloud compatibility, retry policy,
background work, command, application workflow, inspection surface, external
acceptance, or production-readiness claim.

It also adds no upload or HTTP layer, public or signed URL, CDN, media
processing, malware scanning, retention, legal hold, backup, replication,
encryption/KMS, multipart transfer, watcher, queue, event, telemetry, scheduler,
callback, PHP serialization, Stable/1.0, PHP 8.6, or PHP 9 claim. Phase 21F
remains separately Planned and requires separately authenticated authority.
