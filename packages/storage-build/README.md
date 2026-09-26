# Kongono Storage Build

`kongono/storage-build` is the optional Experimental deterministic
storage-profile artifact boundary. It accepts one explicit complete set of
bounded Phase 21B storage declarations, produces canonical inert JSON,
validates exact currentness, and publishes one content-addressed artifact
without clobbering existing data.

The package is build-time evidence only. It performs no storage operation,
adapter selection, provider discovery or contact, credential access, runtime
lookup, retry, command, or application workflow.

See [ADR 0209](../../docs/adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md)
and the [deterministic storage-profile build guide](../../docs/architecture/deterministic-storage-profile-build.md).
