# Kongono Cache Contract

`kongono/cache-contract` is the optional Experimental Phase 20B package for
bounded immutable derived-data cache values and closed evidence vocabulary.

It defines application-owned namespaces and logical keys, distinct key and
content identities, inert codec identity, bounded opaque bytes, caller-supplied
microsecond time, finite freshness, entry versions, closed operation/outcome
tokens, detached entry evidence, and nondisclosing diagnostics.

The package performs no clock read, serialization, lookup, mutation, adapter,
backend, filesystem, network, runtime, command, or application workflow. Cache
evidence is derived, disposable, non-authoritative, and grants no authority.
