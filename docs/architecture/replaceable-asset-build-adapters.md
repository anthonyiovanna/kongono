# Replaceable asset-build adapters

Phase 19D adds an optional tool-neutral adapter protocol and the first separately packaged Vite adapter. Native/no-bundler assets remain first-class. Applications that are API-only, CLI, workers, agents, headless, mobile backends, or have no assets need neither package.

```text
explicit source set -> neutral request -> bounded Vite process -> strict manifest/output validation -> canonical adapter artifact
```

## Installation and fixed profile

Install `kongono/asset-adapter-contract` when application code needs to depend on a replaceable adapter port. Install `kongono/asset-adapter-vite` only when selecting the fixed `vite-client-es2022/1` implementation. Kongono does not install Node or Vite; the application supplies two exact launch files, versions, expected hashes, and a writable workspace parent.

The profile accepts only complete self-contained Phase 19C source graphs. Kongono does not discover PATH, `package.json`, user configuration, plugins, aliases, package scripts, `.env`, proxy or registry settings, bare-package graphs, or ambient output. It makes no latest-version or broad Vite-compatibility claim.

## Toolchain trust and process boundary

Immediately before the build, the adapter proves that both caller-selected launch paths still resolve to the same ordinary non-link files and hashes. This authenticates those launch artifacts; it does not sandbox Node/Vite, attest their full dependency closure, or guarantee descendant termination.

One direct shell-free process receives the fixed build argument vector and only `NODE_ENV=production`, `NO_COLOR=1`, and `FORCE_COLOR=0`. Timeout, stdout, and stderr are bounded. There is no retry, fallback, watcher, daemon, serve, preview, HMR, or request-time build.

## Workspace, output, and failure behavior

For nonempty input the adapter creates one unpredictable operation-owned directory beneath the supplied parent. It writes exact source bytes into `source/`, creates an initially empty `output/`, and writes one deterministic plain-ESM `vite.config.mjs`. The fixed configuration disables source maps, minification, asset inlining, public-directory copying, and the module-preload polyfill, and fixes ES2022, CSS splitting, manifest, and output naming settings.

After a zero exit, the manifest remains untrusted. Duplicate members, unknown fields, unsafe paths, unresolved references, missing or extra files, case ambiguity, links, changed files, excessive counts/bytes/depth, and unsupported output types fail closed. Every retained file is fully read and hashed. The manifest itself is never a browser asset.

Every generated file receives an opaque adapter-output identity, never an application logical identity. Only exact declared entry sources can produce entry bindings. Static imports, dynamic imports, CSS, and other declared resources become closed neutral dependency kinds. Content bytes normalize to `objects/sha256/<prefix>/<digest>` paths; preload remains `none`.

The workspace is removed after success or failure. A cleanup failure is terminal and preserves the primary failure as its exact previous cause while presentation remains nondisclosing.

## Determinism and testing

Canonical repository tests use the neutral fake runner and `PHP_BINARY`; they require no installed Node or Vite. Reordered producer members and semantically unordered lists translate to the same output graph and artifact. Repeated, fresh-process, sequential, and Fiber-overlap probes verify that no mutable adapter state survives an operation.

## Later boundaries

Phase 19A-H are Accepted and Phase 19 is closed. Phase 19E adds only the separate stateless `kongono/asset-resolution` leaf; it revalidates this adapter's complete result and does not change Vite execution. Phase 19F-G add only the accepted application and inspection upper leaves. Phase 19H adds the root-owned [protocol-emulated external proof](phase-19-external-asset-acceptance.md) and checkpoint; it does not certify a live installed toolchain or supported Vite/Node version. Later authority owns deployment, development-server/HMR integration, React/Vue or other frontend integration, performance, accessibility, security certification, and Stable/1.0 readiness.

See [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md), the [Asset Platform guide](asset-platform.md), the [exact-current resolution guide](exact-current-asset-resolution.md), and the [Vite backend integration documentation](https://vite.dev/guide/backend-integration).
