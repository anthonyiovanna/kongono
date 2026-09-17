# Kongono Bounded Memory Cache Adapter

`kongono/cache-adapter-memory` is an optional Experimental process-local
implementation of the neutral cache-adapter protocol. It provides finite entry,
single-value, and aggregate-value-byte capacity with deterministic
oldest-insertion-first eviction.

The adapter is intended for deterministic development and tests. It is not
durable, distributed, shared between processes or hosts, production-available,
or evidence of compatibility with Redis, Memcached, PSR cache interfaces, or a
vendor provider. It stores freshness evidence but does not read a clock or
decide whether an entry is fresh.
