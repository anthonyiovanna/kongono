# Kongono Cache Runtime

`kongono/cache-runtime` is the optional Experimental exact-current runtime for
bounded derived-data cache operations. A caller supplies one current profile
set, its canonical artifact bytes, one selected profile name, one expected
adapter identity, one adapter, and one explicit instant for every operation.

The runtime performs lookup, store, conditional replace, and conditional remove
without reading a clock or discovering configuration. Adapter results are
untrusted until their complete key, contract, codec, value, content, freshness,
version, and operation relations have been validated. Expired lookup evidence
never returns stale value bytes.

This package provides no serialization, retry, stale fallback, refresh,
coordination, provider integration, persistence, application workflow, command,
inspection, or production-availability claim.
