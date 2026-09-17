# Kongono Cache Adapter Contract

`kongono/cache-adapter-contract` is the optional Experimental provider-neutral
port for bounded cache lookup and conditional mutation. It carries immutable
requests and detached result evidence above `kongono/cache-contract`.

The package stores nothing, reads no clock, creates no entry version, discovers
no adapter, and grants no cache, provider, correctness, or authority claim.
`found` means only that adapter evidence exists; Phase 20E remains responsible
for determining freshness from one supplied `now` value.
