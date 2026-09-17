# Deterministic Cache-Profile Build

Phase 20C accepts the optional Experimental `kongono/cache-build` leaf. It sits
above `kongono/cache-contract` and has exactly three local production
dependencies: API metadata, diagnostics, and the neutral cache contract.

## Inputs

Callers create a complete `CacheProfileSet`. The empty set is valid. Each of at
most 256 declarations binds Version 1, an application-owned profile name, one
unique cache namespace, an inert codec name/version, a finite lifetime, and an
accepted maximum value size. Input arrays are detached and canonicalized by
ordinal profile name. No key, value bytes, current time, provider address,
capacity, eviction policy, credential, callback, class, path, or command is
part of a profile.

## Artifact and currentness

`CacheProfileBuilder` uses only the supplied in-memory set. The fixed build
profile is `kongono/cache-profile-artifact/1`. Source, build, and exact-byte
artifact identities remain separate lowercase SHA-256 domains.

The canonical artifact has fixed member order, compact UTF-8 JSON, one terminal
LF, a 1 MiB byte limit, and depth limit 8. Validation rejects duplicate,
missing, unknown, reordered, malformed, noncanonical, and unsupported evidence.
Exact-current validation performs a fresh deterministic build from the supplied
complete set; no newest selection, fallback, repair, scan, or implicit rebuild
exists.

## Publication

Publication is explicit and contained beneath an existing caller-owned root:

`objects/sha256/<first-two-digest-characters>/<artifact-digest>/cache-profile.json`

The publisher uses exclusive creation, verifies the complete temporary
inventory, atomically renames the completed directory, and never overwrites,
merges, repairs, truncates, follows, or deletes an existing final target.
Byte-identical existing content is idempotent evidence. Cleanup is limited to
the validated operation-owned temporary directory and preserves primary and
cleanup failure truth.

## Non-authority boundary

Profiles and artifacts do not grant cache, filesystem, credential, network,
runtime, provider, synchronization, or application authority. The package
does not encode application values or perform cache operations. Phase 20D
adapter work is separately Accepted and does not change this build boundary.
Later accepted runtime, application, inspection, and
[external acceptance](phase-20-external-cache-acceptance.md) work likewise does
not expand this Phase 20C boundary.
