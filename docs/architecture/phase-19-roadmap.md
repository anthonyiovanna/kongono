# Phase 19 Roadmap: Optional Deterministic Asset Platform

## Status and entry state

Phase 18A-H are Accepted and Phase 18 is closed. Phase 19A accepts
[ADR 0188](../adr/0188-optional-asset-platform-and-deterministic-build-boundary.md),
[ADR 0189](../adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md),
the [Asset Platform guide](asset-platform.md), and the
[asset-authoring guide](../engineering/asset-authoring.md) as documentation
architecture. Phase 19B accepts [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
and the optional Experimental `kongono/asset-contract` neutral leaf.
Phase 19C accepts [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
and the optional Experimental `kongono/asset-build` deterministic native leaf.
Phase 19D accepts [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md),
the optional Experimental `kongono/asset-adapter-contract` neutral boundary,
and the separately optional Experimental `kongono/asset-adapter-vite`
fixed-profile implementation described by the
[replaceable adapter guide](replaceable-asset-build-adapters.md).
Phase 19E accepts [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md),
the optional Experimental `kongono/asset-resolution` leaf, and the
[exact-current resolution guide](exact-current-asset-resolution.md). Phase 19F
accepts [ADR 0194](../adr/0194-supported-asset-application-workflow-and-explicit-view-integration.md),
the optional Experimental `kongono/asset-application` package, three supported
commands, the copy-once `asset-minimal` slice, and the
[application workflow guide](asset-application-workflow.md). Phase 19G accepts
[ADR 0195](../adr/0195-visibility-safe-asset-inspection-and-developer-intelligence.md),
the optional Experimental `kongono/asset-inspection` package, and the
[asset-inspection guide](asset-inspection-and-intelligence.md). Phase 19H
accepts [ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md),
the root-owned [external acceptance](phase-19-external-asset-acceptance.md), and
the exact local checkpoint.

Phase 19A-H are Accepted and Phase 19 is closed. This closure grants no
authority to begin Phase 20.

## Fixed Phase 19 roadmap

| Row | Required boundary | Explicit exclusions |
| --- | --- | --- |
| **19A - Architecture and fixed roadmap** | Accept ADRs 0188-0189, the Asset Platform guide, asset-authoring guide, security/lifecycle doctrine, package direction, and this fixed roadmap as documentation only. | No package, PHP/JS API, schema, manifest, adapter, build, runtime, command, frontend integration, or checkpoint. |
| **19B - Neutral asset contracts** | Accepted optional Experimental `kongono/asset-contract` values for logical/content identities, closed asset/content kinds, bounded dependency/entry-point declarations, integrity/preload/delivery metadata, deterministic ordering, and package diagnostics. | No filesystem, parser, graph traversal, bundler, subprocess, output publication, URL policy, HTTP, view integration, or runtime resolution. |
| **19C - Deterministic asset graph and build artifact** | Accepted optional Experimental `kongono/asset-build` explicit bounded source set, deterministic complete graph validation, fixed native-copy canonical build artifact, exact currentness, and contained content-addressed no-clobber publication. | No Node/Vite requirement, adapter protocol, remote inputs, runtime building, watcher, dev server, HTTP delivery, view helper, or frontend framework. |
| **19D - Replaceable build adapter and first-party Vite integration** | Accepted optional adapter protocol plus one separately packaged fixed-profile Vite adapter with authenticated complete inputs, contained workspace, bounded no-shell subprocess lifecycle, strict manifest translation, complete output validation, neutral artifact validation, and cleanup. | No core Node dependency, package installation, arbitrary plugin execution, network download, request-time process, watcher/HMR, development server, runtime resolver, URL/HTTP/view integration, or claim that Vite defines the neutral model. |
| **19E - Exact-current asset resolution and browser/HTTP policy boundary** | Accepted immutable supplied-artifact resolution from logical identity to validated bounded delivery metadata under explicit application policy. | No build, scan, mutable global manifest, implicit base URL/CDN, CSP/nonce fabrication, storage server, response emission, file-path disclosure, or permissive stale fallback. |
| **19F - Supported application workflow and explicit integration** | Accepted explicit stable source reading, native plan/build/check/resolve composition, direct neutral-adapter injection, human/machine command parity, copy-once application slice, and application-owned mapping into Phase 18 values. | No hidden build, automatic install/migration, daemon, watcher, HMR, deployment manager, mandatory frontend framework, runtime service locator, tag emission, or lower View dependency. |
| **19G - Visibility-safe inspection and developer intelligence** | Accepted offline/read-only/stateless inspection of one supplied current asset result with bounded Safe/Protected evidence for Graph, Observability, Security Audit, Chie, Hantei, and Oshie. | No source/output disclosure, execution, build, resolution, network, filesystem discovery, repair, apply, recording, role execution, production observation, or certification. |
| **19H - External acceptance and checkpoint** | Accepted root-owned deterministic external proof of native and protocol-emulated Vite-adapter boundaries, currentness, integrity, containment, isolation, nondisclosure, cleanup, regressions, and one exact local Phase 19 checkpoint. | No real Vite/Node compatibility certification, public deployment, browser/accessibility/performance/security certification, CDN certification, push, tag, release, Stable/1.0, mobile adapter, or Phase 20 behavior. |

## Locked direction

Kongono owns a language-neutral Asset Platform abstraction rather than a
bundler. Native or no-bundler assets and no-asset applications remain
first-class. Vite is the first optional replaceable adapter.
Declarations and artifacts are explicit, bounded, deterministic, and
exact-current. Build tools remain untrusted producers until their complete
output is validated.

Logical identity, source path, output path, public URL, content identity, build
identity, and integrity metadata remain distinct. Application and deployment
layers own URL origin, CDN, CSP, headers, cache policy, credentials, and external
effects. Development uses the same contract and receives no stale fallback.

## API and compatibility direction

Phase 19B exposes exactly 19 production declarations: 15 Experimental values,
enums, and the concrete validation exception; one Public exception-family
interface; and three Internal diagnostic/exception/guard declarations. No
declaration is Stable. Later adapters begin Experimental with Internal
machinery kept non-public. Stable, production, or 1.0 status requires later
authority. PHP 8.5 remains the current baseline; future readiness comes from
explicit contracts and replaceable adapters rather than unverified behavior.

Phase 19C adds exactly 30 production declarations and 17 package-test PHP
files. Asset Build has 22 Experimental declarations, one Public package
exception interface, and seven Internal graph/diagnostic/exception/guard
declarations. Phase 19D adds exactly 49 production declarations and 35
package-test PHP files across the two adapter packages: 33 Experimental, two
Public exception-family interfaces, and 14 Internal declarations. The nine new
local production edges point only to API Metadata, Diagnostics, Asset Contract,
Asset Build, and the neutral adapter contract. Phase 19E adds exactly 24
production declarations and 16 package-test PHP files: 20 Experimental, one
Public exception-family interface, and three Internal declarations. Its five
local edges point only to API Metadata, Diagnostics, Asset Contract, Asset
Build, and the neutral adapter contract. At Phase 19E, the graph was 100 packages /
421 local edges / acyclic; PHP inventories are 1,983 / 1,041 / 96, with 100
package-validation entries.

Phase 19F adds 18 production declarations and five package-test PHP files in
Asset Application plus three Console handlers and three Console package tests.
Asset Application has nine Experimental declarations, one Public exception-
family interface, and eight Internal declarations. Its six local edges point
only to API Metadata, Diagnostics, Asset Contract, Asset Build, the neutral
Asset Adapter Contract, and Asset Resolution; Console adds one upper
application edge. The current graph is 101 packages / 428 local edges /
acyclic; PHP inventories are 2,004 / 1,049 / 96, with 101 package-validation
entries and 13 supported `kongo` commands.

Phase 19G adds 22 Asset Inspection production declarations: 19 Experimental,
one Public exception-family interface, and two Internal declarations. Six
package-test PHP files cover reconstruction, disclosure, roles, Graph evidence,
neutral projections, independent verification, diagnostics, isolation, and the
package boundary. The accepted topology is 102 packages and 435 local
production edges, with 2,026 production, 1,055 package-test, and 96 root-test
PHP files and 102 package-validation entries.

Phase 19H adds no production package, production PHP, package-test PHP, local
production edge, skeleton, or command. It adds four root-test PHP files and the
explicit `composer acceptance:phase19` fresh-process proof. Final topology is
102 packages / 435 local production edges / acyclic, with PHP inventories
2,026 production / 1,055 package-test / 100 root-test, 102 package-validation
entries, seven skeleton validations, and 13 supported `kongo` commands. Phase
19 is closed by subject `Complete Kongono Phase 19 asset platform milestone`
with parent `a04b1ae491cb9c52eb5bd32c37f8524fcd683c74`.

## Related material

- [Asset Platform](asset-platform.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Phase 19 external acceptance](phase-19-external-asset-acceptance.md)
- [ADR 0188](../adr/0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](../adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
- [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
- [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
- [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
- [ADR 0194](../adr/0194-supported-asset-application-workflow-and-explicit-view-integration.md)
- [ADR 0195](../adr/0195-visibility-safe-asset-inspection-and-developer-intelligence.md)
- [Asset inspection and developer intelligence](asset-inspection-and-intelligence.md)
- [Replaceable asset-build adapters](replaceable-asset-build-adapters.md)
- [Deterministic asset build](deterministic-asset-build.md)
- [Exact-current asset resolution](exact-current-asset-resolution.md)
- [Asset application workflow](asset-application-workflow.md)
- [Phase 18 roadmap](phase-18-roadmap.md)
- [Server-rendered presentation](server-rendered-presentation.md)
