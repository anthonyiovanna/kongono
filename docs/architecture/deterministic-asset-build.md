# Deterministic asset build

Phase 19C is Accepted. The optional Experimental `kongono/asset-build` package
sits above `kongono/asset-contract` and below the separately accepted adapter,
resolution, application, inspection, and acceptance layers.

## Boundary

The caller constructs a complete `AssetDeclarationSet` and supplies one
`AssetSource` for each logical declaration. Each source contains a protected
relative `AssetSourcePath` and exact in-memory bytes. The package never opens
that path, scans a directory, parses imports, reads configuration, invokes a
tool, or obtains remote input.

```text
explicit declarations + explicit source bytes
                  |
                  v
complete bounded source set identity
                  |
                  v
reachable acyclic deterministic graph
                  |
                  v
fixed native-copy/1 byte-preserving build
                  |
                  v
canonical artifact + unique content objects
                  |
                  v
contained content-addressed publication
```

The empty declaration/source/dependency/entry-point set is valid and produces
an empty graph, an empty output map, and a deterministic artifact.

## Identities and ordering

Logical identity, protected source path, SHA-256 content identity, complete
source-set identity, graph identity, output path, SHA-384 integrity metadata,
and build-artifact identity are separate evidence. None grants filesystem,
network, URL, HTTP, browser, authorization, deployment, or application-graph
authority.

Declaration, source, dependency, entry-point, graph-node, logical-output, and
unique-object collections use ordinal canonical ordering. Every dependency
kind participates in cycle and reachability validation. Dependencies precede
their dependents in topological order, and the maximum dependency depth is
256.

## Native build and artifact

`native-copy/1` copies exact bytes only. It does not normalize newlines,
minify, transpile, rewrite, concatenate, compress, generate source maps, or
execute content. Each unique SHA-256 content digest has one relative object
path while each logical asset retains its own output record and delivery
metadata.

Artifact JSON is compact, duplicate-safe, bounded to 8 MiB and depth 64,
UTF-8 without BOM or CR/NUL, and has exactly one terminal LF. Its fixed schema
contains builder identity, source-set and graph digests, complete declarations,
dependencies, entry points, and logical output metadata. It contains no source
or output bytes, source paths, absolute paths, URLs, origin/CDN/CSP policy,
credentials, environment, time, PID, or random build value.

Structural validation requires a complete exact map of output path to bytes,
reconstructs the neutral declaration set and graph, checks all content,
integrity, path, count, ordering, and identity relations, and canonicalizes
nothing permissively. Exact-current validation rebuilds from one explicitly
supplied complete current source set. Any declaration, relation, entry point,
source path, source bytes, kind, content type, profile, or canonical ordering
change is stale.

## Publication and lifecycle

The publisher accepts one already-existing absolute canonical ordinary
writable directory. It rejects unsafe roots and ancestors. It writes a complete
operation-owned temporary directory with exclusive binary files, validates the
completed bytes, and atomically renames it to
`kongono-assets-v1-<build-sha256>`. Existing final directories are read only to
prove an exact recursive ordinary inventory and byte identity; they are never
overwritten, merged, repaired, or deleted.

Only the exact owned temporary directory is cleaned after failure. Failures are
deterministic and nondisclosing, retain the exact previous Throwable, and use
diagnostic IDs `KGN-ASSET-BUILD-1911` through `1916`. Raw bytes, source/output
paths, JSON fragments, environment, URLs, credentials, and arbitrary prior
messages do not appear in diagnostic presentation.

The builder and validator have no mutable shared state, destructor, shutdown
hook, watcher, daemon, background worker, registry, or cache. Sequential,
overlapping, reentrant, reordered-input, and fresh-process builds therefore use
the same deterministic contract.

## Explicit exclusions

Phase 19C does not add Vite, Node, npm, pnpm, Yarn, Bun, Deno, an adapter
protocol, a subprocess, network access, source discovery, import parsing,
transformation, runtime building, a current pointer, runtime asset resolution,
URL generation, HTTP delivery, CSP/nonce policy, view helpers, frontend/mobile
integration, a command, deployment, or Phase 19D behavior. Phase 19D was
separately accepted through
[ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
and the [replaceable adapter guide](replaceable-asset-build-adapters.md). Phase
19E is separately accepted through [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
and the [exact-current resolution guide](exact-current-asset-resolution.md);
Phase 19F-G add only the separately accepted application and inspection upper
leaves. Phase 19H accepts the root-owned
[external proof](phase-19-external-asset-acceptance.md) and exact checkpoint;
Phase 19A-H are Accepted and Phase 19 is closed.

## Related material

- [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
- [Asset Platform](asset-platform.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
- [Asset authoring](../engineering/asset-authoring.md)
