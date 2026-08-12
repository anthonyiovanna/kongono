# ADR 0009: Secure Extension Platform

## Status

Accepted

## Date

2026-08-12

## Context

Third parties need a way to extend Kongono applications without unnecessary source coupling or implicit access to all application resources.

## Decision

Create a future governed Extension Platform with core, trusted/in-process, sandboxed, and external extension categories. An Extension Host will govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health.

## Rationale

Explicit extension governance supports plug-and-play contributions while preserving least privilege, inspectability, lifecycle control, and application ownership.

## Consequences

Third-party extensions must not automatically receive unrestricted database, filesystem, secret, shell, process, or network access. Contributions should eventually be represented in authoritative application metadata.

## Rejected alternatives

Unrestricted third-party in-process access and source-copy extension models are rejected as the default platform architecture.

## What remains open

Manifests, packaging, signing, distribution, permission vocabulary, sandboxing, process topology, APIs, update protocol, storage, and enforcement remain open. WASM/WASI is anticipated but is not a v1 requirement.
