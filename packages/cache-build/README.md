# Kongono Cache Build

`kongono/cache-build` is the optional Experimental deterministic cache-profile
artifact boundary. It accepts one explicit complete set of bounded Phase 20B
cache declarations, produces canonical inert JSON, validates exact currentness,
and publishes one content-addressed artifact without clobbering existing data.

The package is build-time evidence only. It performs no cache operation,
serialization, codec execution, backend discovery, provider connection,
runtime lookup, clock read, retry, command, or application workflow.

See [ADR 0200](../../docs/adr/0200-deterministic-cache-profile-artifact-and-publication.md)
and the [deterministic cache-profile build guide](../../docs/architecture/deterministic-cache-profile-build.md).
