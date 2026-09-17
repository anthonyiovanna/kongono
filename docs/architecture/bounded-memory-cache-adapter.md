# Bounded Memory Cache Adapter

Phase 20D accepts two separately optional Experimental packages:
`kongono/cache-adapter-contract` and `kongono/cache-adapter-memory`. The first
defines a neutral adapter protocol. The second is one deterministic bounded
process-local implementation of that protocol.

## Protocol boundary

The adapter receives explicit immutable requests for lookup, absent-only store,
version-bound replace, and version-bound remove. Outcomes keep found, miss,
stored, conflict, rejected, and unavailable distinct. A protocol `found`
outcome does not establish a fresh cache hit: freshness remains inert stored
evidence until the separately Accepted Phase 20E runtime evaluates it against
one caller-supplied `now` value.

Entry evidence is detached before it crosses the protocol boundary. Adapter
identity, key identity, content identity, entry version, freshness, operation,
outcome, and diagnostics remain separate domains. Versions are deterministic
conditional-mutation evidence scoped to one adapter instance; they are not
timestamps, durable revision numbers, authority, or cross-provider identities.

## Capacity and eviction

Construction fixes positive finite entry-count and aggregate-value-byte
limits. A value whose bytes alone exceed capacity is rejected without mutation.
At equality the value is admissible. A successful mutation first builds a
complete bounded candidate and evicts the oldest inserted entries until both
bounds hold. Lookup does not refresh insertion order, while successful replace
removes the old position and inserts the replacement as newest.

After complete validation, the adapter swaps the candidate map, aggregate byte
count, and next version sequence into visibility together. Conflict, miss,
rejection, and failure expose no partial mutation and consume no version.

## Supply and portability

Each instance owns isolated state. Callers supply an instance directly; there
is no facade, singleton, static mutable registry, service locator, discovery,
or ambient configuration. The contract is intentionally independent of the
memory implementation so a later application may directly supply another
separately optional adapter after preserving the same bounded protocol.

The memory package does not serialize or hydrate objects, run codecs or
callbacks, read a clock, evaluate expiry, access filesystems or networks, retry,
refresh, repair, enumerate, flush, lock, coordinate, persist, distribute, or
claim production availability. Redis, Memcached, cloud, filesystem, database,
PSR-6, and PSR-16 adapters remain future optional work. Later accepted runtime,
application, inspection, external-acceptance, and checkpoint work does not
change this Phase 20D boundary. See the
[Phase 20 external acceptance guide](phase-20-external-cache-acceptance.md).
