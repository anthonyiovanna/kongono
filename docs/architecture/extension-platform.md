# Extension Platform

## Purpose

Kongono will support plug-and-play extensions without requiring unnecessary source-level coupling or granting third-party code unrestricted application access.

## Planned extension categories

- Core
- Trusted/in-process
- Sandboxed
- External

These categories express intended trust and execution distinctions. Their package formats, admission rules, isolation mechanisms, and APIs are not yet defined.

## Extension Host

The planned Extension Host will govern:

- Permissions and capabilities
- Lifecycle and health
- Storage and network access
- Secret access
- Events
- UI contributions
- Agent capabilities
- Updates and removal

Extensions must contribute through explicit, attributable contracts. Third-party extensions must not automatically receive unrestricted database, filesystem, secret, shell, process, or network access.

## Security boundary

Installation does not imply unlimited trust. Permissions should be least-privilege, inspectable, revocable where applicable, and enforced by deterministic mechanisms. The platform must consider supply-chain integrity, capability escalation, data exposure, lifecycle cleanup, and persistent-runtime isolation in later designs.

## WASM/WASI

WASM/WASI sandboxing is anticipated as a possible future mechanism, but it is not a v1 requirement and is not part of Phase 0A. No sandbox technology or process topology is selected.

## Relationship to the Application Graph

Extension contributions should eventually appear in authoritative application metadata so routes, services, permissions, UI contributions, agent capabilities, and dependencies are not invisible. Contribution schema and validation remain open.

## Stability

The secure, governed Extension Platform direction is **LOCKED**. Host APIs, manifests, packaging, permission vocabulary, isolation, signing, distribution, and update protocols remain **EXPERIMENTAL** or open.

## Related concepts

- [Microkernel](microkernel.md)
- [Application Graph](application-graph.md)
- [Agent model](agent-model.md)
- [ADR 0009](../adr/0009-secure-extension-platform.md)
