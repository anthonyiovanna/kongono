# Kongono Asset Build

`kongono/asset-build` is the optional Experimental deterministic native asset
graph, artifact, exact-currentness, and contained publication leaf.

It accepts one complete explicit application-owned source set and copies bytes
exactly. It does not discover files, parse imports, transform content, execute a
bundler, select URLs, perform HTTP work, or provide runtime resolution.

See [Deterministic asset build](../../docs/architecture/deterministic-asset-build.md)
and [ADR 0191](../../docs/adr/0191-deterministic-native-asset-graph-and-build-artifact.md).
