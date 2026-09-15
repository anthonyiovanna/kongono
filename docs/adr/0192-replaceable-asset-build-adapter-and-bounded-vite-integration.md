# ADR 0192: Replaceable asset-build adapter and bounded Vite integration

Status: Accepted

## Context

Phase 19B defines neutral asset declarations and Phase 19C defines explicit source sets, deterministic graphs, native-copy output, and content-addressed artifacts. Applications may also need an external build tool, but that tool must not become Kongono's logical-identity authority, dependency manager, runtime service, or source of trusted metadata.

## Decision

Kongono provides two optional Experimental packages. `kongono/asset-adapter-contract` defines the tool-neutral request, process port, generated-output graph, and exact-current canonical artifact. `kongono/asset-adapter-vite` implements one fixed `vite-client-es2022/1` profile behind that port. Separating them keeps alternative adapters independent of Vite and keeps every existing production package independent of both packages.

The application supplies exact absolute Node and Vite launch paths, semantic versions, expected launch-file hashes, a writable workspace parent, and a process runner. The adapter re-authenticates both ordinary non-link launch files immediately before invocation. These launch artifacts are trusted external application inputs. This is not an operating-system sandbox, complete installation attestation, or universal descendant-process-control claim.

Vite output is untrusted. The adapter executes one shell-free argument vector with a finite deadline and bounded stdout and stderr, then strictly duplicate-safely decodes the closed manifest vocabulary and validates the complete output inventory. Exit code zero alone is never success. Every ordinary contained output file is completely read, bounded, hashed, and normalized to a content-addressed object path.

Generated outputs receive opaque Kongono `AdapterOutputIdentity` values derived from the fixed profile and private producer keys. Producer keys and filenames cannot create, rename, or replace application-owned `LogicalAssetIdentity` values. Only exact declared entry points are bound back to logical identities.

The adapter creates one unpredictable operation-owned workspace, writes exact supplied source bytes and a fixed plain-ESM configuration, and removes the workspace after success or failure. Cleanup failure preserves the original failure as `previous`; neither raw failure message is presented.

The canonical `kongono.asset-adapter-build-artifact` is duplicate-safe compact UTF-8 JSON with one terminal LF. It binds adapter/profile, toolchain, source-set, Phase 19C graph, entry bindings, generated dependencies, and output content evidence. It omits source and output bytes, private paths and producer keys, stdout/stderr, environment, URLs, time, process identifiers, and random values. Currentness is evaluated only against one explicitly supplied complete source set, profile, toolchain identity, artifact, and complete object-byte map.

Kongono installs no Node or Vite dependency. It performs no PATH lookup, configuration/plugin/package discovery, package installation, registry or network access, environment loading, watcher, dev server, preview, HMR, retry, fallback, or runtime/request-time build. The V1 profile supports only self-contained caller-supplied graphs and makes no claim about a latest Vite version, every Vite release, bare-package graph, plugin, or frontend framework.

## Consequences

Native/no-bundler assets remain first-class. API-only, CLI, worker, agent, headless, mobile-backend, and no-asset applications install neither adapter package. Applications opting into Vite own installation and toolchain selection and must treat browser-executable output as trusted application code after validation.

Broader npm graphs, supported-version certification, development-server integration, asset resolution and delivery, application workflow, inspection, external acceptance, deployment, and frontend/mobile integration remain Phase 19E-H or later decisions.

## References

- [Vite backend integration](https://vite.dev/guide/backend-integration)
- [Vite build options](https://vite.dev/config/build-options)
- [Vite CLI](https://vite.dev/guide/cli)
- [Asset Platform](../architecture/asset-platform.md)
- [Deterministic asset build](../architecture/deterministic-asset-build.md)
- [Replaceable asset-build adapters](../architecture/replaceable-asset-build-adapters.md)
