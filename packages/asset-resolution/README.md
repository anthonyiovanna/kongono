# Kongono Asset Resolution

`kongono/asset-resolution` is the optional Experimental exact-current asset
resolution leaf. It normalizes already validated native or adapter build
results into immutable canonical metadata and resolves one caller-selected
logical identity under explicit application-owned URL, cache, integrity, and
preload policy.

The package is stateless and in-memory. It does not build, discover, scan,
publish, read bytes, serve files, emit responses or headers, choose an origin,
create CSP or nonces, select a newest artifact, or fall back to stale evidence.

Applications without browser assets install none of the asset packages.

