# Kongono Storage Contract

`kongono/storage-contract` is the optional Experimental provider-neutral
contract for bounded storage identities, content evidence, mutation conditions,
requests, outcomes, results, and explicit streaming lifecycle ownership.

The package is inert. It performs no filesystem or network I/O, selects no
provider, reads no credentials, grants no authorization, serializes no values,
and implements no runtime operation. Capability declarations and evidence do
not grant storage authority or promise provider guarantees.

Sources and sinks require explicit close or abort handling. Reads and writes
are illegal after closure, `StorageByteSource::read()` accepts a maximum from 1
through `StorageContractBounds::MAX_CHUNK_BYTES`, and `null` is legal only at
exhaustion. The neutral interfaces do not promise idempotent close or abort;
implementations own timely resource retirement, and destructors are not
lifecycle control.
