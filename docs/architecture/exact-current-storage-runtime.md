# Exact-Current Storage Runtime

Phase 21E accepts the separately optional Experimental
`kongono/storage-runtime` package. It is an upper neutral composition leaf over
the accepted storage contract, profile build, and adapter protocol; it does
not require or select `kongono/storage-adapter-local`.

## Binding proof

A binding receives five explicit inputs: the caller's complete current profile
set, canonical artifact bytes, one selected profile name, one expected adapter
identity, and one directly supplied neutral adapter. The runtime first calls
the structural and exact-current artifact validator. It then proves the
selected profile exists and that the adapter descriptor uses Version 1 and
matches the independently validated source, build, artifact, profile-name,
profile-declaration, and adapter-identity evidence exactly.

Binding performs no storage operation. It does not inspect the adapter's
concrete class, scan packages or directories, discover a provider, rebuild an
artifact, consult a current pointer, select the newest artifact, or fall back.

## Operation scope and validation

Every public method creates one scope containing the exact operation identity
and kind. The instance-local guard permits only one active scope. A completed
nonstreaming operation retires it before return; a completed read transfers it
to the validating source until exhaustion or explicit close. Same-instance
reentrancy is rejected before another adapter call. Separate runtime instances
may overlap, including in persistent workers or Fibers, without shared runtime
state or Fiber-local authority.

Before effect, the runtime checks Contract Version 1, operation/method
agreement, exact namespace, declared capability, conditional-mutation support,
the `if_absent` or exact `if_version` relation, object and list limits, digest
algorithm, metadata capability, and source openness. Possession of a runtime
does not supply application authorization, tenant mapping, credentials, or
egress policy.

## Untrusted results and streams

Returned outcome and evidence are never trusted merely because they use
neutral value objects. The runtime checks legal outcome matrices, exact
success-evidence relations, identity and namespace, sizes, digest algorithm,
metadata, media type, version, list order, page size, completion, and
continuation relations. Exposed evidence is reconstructed as detached
immutable neutral values. Provider objects, handles, paths, locators, native
messages, and mutable collections never cross the boundary.

A completed read contains exactly one validating source. It enforces caller
and profile chunk bounds, exact total bytes, digest, EOF, and exhaustion without
whole-object buffering. Exhaustion closes the adapter source and retires the
scope. Early close explicitly cancels remaining consumption and retires the
same resources without inventing verification success.

Immediately after an adapter returns, its source is runtime-owned pending echo,
result and evidence validation. Rejection or wrapper-construction failure
attempts close once when the source is open, then scope retirement even if
close fails. A throwing closed-state observation is itself a cleanup failure;
it does not establish source retirement. Already-closed sources are rejected
without redundant close. Ownership transfers to the caller only after the
complete runtime read and wrapper are constructed; valid handoff does not close
the source early. An adapter throwing before return remains responsible for
resources it never transferred.

Primary validation errors and native Errors retain their exact identity.
Unexpected collaborator failures use the existing bounded adapter-failure
wrapping. Failure composition happens outside operation catches, preserving
primary and ordered source-close/scope-retirement causes without catching and
recomposing a cleanup failure or repeating close. The same rule applies to
terminal validating-stream paths. No destructor, shutdown hook or retry is
part of source retirement.

For create and replace, ownership of the caller source transfers for the call.
The metered wrapper bounds requests and chunks, counts bytes, computes the
declared digest, and requires exact exhaustion before a Completed result is
accepted. The source is closed exactly once after success, typed non-success,
adapter failure, validation failure, or cleanup failure and is never retained.

## Failure and nondisclosure

The runtime never retries, especially after Uncertain mutation. Adapter
exceptions are causal terminal failures. Primary operation failure, cleanup
failure, and their combination remain separate truths. Combined failures keep
the exact primary and every cleanup cause privately; messages, diagnostics,
stringification, and debug output expose only closed Safe fields such as
operation, outcome, byte count, limit, active state, cleanup count, and whether
a primary failure exists.

Object bytes, raw identities and namespaces, metadata, roots, paths, provider
locators, versions, digests, continuation tokens, credentials, native markers,
previous messages, and traces are never Safe diagnostic data.

## Non-goals

This package supplies no local or cloud adapter, provider discovery, registry,
facade, service locator, dependency-injection binding, ambient configuration,
credential source, authorization system, retry or reconciliation policy,
background refresh, upload controller, HTTP or public-file integration, signed
URL, CDN, media pipeline, malware scan, retention, legal hold, backup,
replication, encryption/KMS, multipart or resumable transfer, watcher, queue,
event, telemetry, scheduler, callback, command, example, application workflow,
inspection surface, external acceptance, checkpoint, Stable/1.0, benchmark,
production-readiness, PHP 8.6, or PHP 9 promise.

See [ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
the [Storage Platform guide](storage-platform.md), the
[contained local adapter guide](contained-local-storage-adapter.md), and the
[fixed Phase 21 roadmap](phase-21-roadmap.md).
