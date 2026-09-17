# ADR 0203: Supported Cache Application Workflow and Explicit Codec/Policy Mapping

Status: Accepted

## Context

Phases 20B-E provide neutral cache values, deterministic profile artifacts, a replaceable adapter contract with one bounded memory implementation, and an exact-current runtime. Applications still need one supported route from an explicit profile source to exact-current runtime composition without making cache mandatory or giving Kongono ownership of application serialization, time, data truth, or provider selection.

## Decision

Accept the optional Experimental `kongono/cache-application` package, three profile-evidence commands, and the copy-once `cache-minimal` application slice.

The package admits one explicit canonical application root, one relative contained source-file path, and one contained publication root. The Version 1 JSON source is bounded UTF-8, duplicate-member rejecting, and closed to `schema` plus a complete list of profile name, namespace, codec name/version, finite lifetime microseconds, and maximum encoded bytes. Stable reads require an ordinary unlinked file and unchanged metadata and byte count.

`plan` constructs expected evidence in memory and is write-free. `build` publishes only through `CacheProfileArtifactPublisher`, then validates the exact expected artifact. `check` reads only that exact content-addressed path and is write-free. There is no scan, newest selection, pointer, stale tolerance, hidden build, or fallback.

`CacheProfileCodecMapping` is inert application evidence binding one accepted profile name to its exact declared codec identity. It contains no callable and performs no encoding or decoding. `CacheApplicationRuntimeFactory` revalidates exact-current evidence and the mapping before constructing the accepted runtime from one directly supplied adapter and identity. Every operation continues to receive explicit caller time and opaque bytes.

The supported noninteractive commands are `cache:plan` (ReadOnly/Observe), `cache:build` (MutationCapable with explicit plan/apply), and `cache:check` (ReadOnly/Observe). They expose the same closed Safe record through human and JSON presentation and never operate on cache entries.

Diagnostics are assigned exactly:

- `KGN-CACHE-APPLICATION-2041`: invalid definition;
- `KGN-CACHE-APPLICATION-2042`: unsafe source read;
- `KGN-CACHE-APPLICATION-2043`: workflow or currentness failure;
- `KGN-CACHE-APPLICATION-2044`: publication failure;
- `KGN-CACHE-APPLICATION-2045`: profile/codec mismatch;
- `KGN-CACHE-APPLICATION-2046`: runtime composition failure;
- `KGN-CACHE-APPLICATION-2047`: operation mismatch;
- `KGN-CACHE-APPLICATION-2048`: internal invariant failure.

The copy-once skeleton owns its codec and value schema, directly constructs the bounded memory adapter, supplies explicit instants, and handles miss, expiry, replacement, removal, and complete adapter loss without changing application truth. Generated artifacts remain ignored beneath its application-owned output directory.

## Rejected alternatives

Rejected: a global registry or facade, service location, magic serialization, PHP object caching, inferred codecs, framework-owned schemas, hidden clocks or default TTLs, read-through callbacks, automatic caching, stale fallback, newest-artifact selection, mutable current pointers, destructive flush commands, provider discovery, background refresh, warming daemons, and framework-owned application policy.

## Consequences

Phase 20F is Accepted. Phases 20G-H remain Planned. Cache remains optional, derived, disposable, explicitly composed, and non-authoritative. This decision adds no inspection, recording, certification, provider integration, network effect, distributed semantics, production-availability claim, checkpoint, or Phase 21 authority.
