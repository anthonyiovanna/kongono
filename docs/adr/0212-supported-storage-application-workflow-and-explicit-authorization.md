# ADR 0212: Supported Storage Application Workflow and Explicit Authorization

- Status: Accepted
- Date: 2026-09-23

## Context

The accepted storage contract, deterministic profile build, adapter protocol,
contained local adapter, and exact-current runtime leave profile source
loading, command composition, and business authorization with applications.
Applications need a supported workflow without giving paths, artifacts, or
adapter possession implicit operation authority.

## Decision

Phase 21F adds the separately optional Experimental
`kongono/storage-application` package. A strict, bounded Version 1 source
loader accepts one explicit complete profile source. The application workflow
offers `plan`, `build`, and `check` for one supplied application root, source
file, and existing output directory. Plan and check do not publish. Build
publishes only the exact content-addressed artifact through the accepted
no-clobber publisher. Existing bytes must validate as exact-current; corrupt
or stale output is never repaired implicitly. The command summary exposes only
schema, version, profile count, currentness, publication requirement, and
optional execution intent.

The existing Console application adds `storage:plan`, `storage:build`, and
`storage:check` with required ordinary path inputs and no prompts or defaults.
Only `storage:build --apply` may publish; `--plan` is read-only. Commands do not
construct an adapter, authorize an object operation, or consume object bytes.

For object operations, `StorageApplicationRuntimeFactory` revalidates current
evidence and binds one named profile, expected adapter identity, directly
supplied neutral adapter, and application-supplied
`StorageOperationAuthorizer`. Its wrapper checks the exact unchanged request
once before each read, create, replace, delete, or list delegation. Denial and
authorizer failure have distinct typed outcomes; strict `true` delegates
exactly once. The wrapper holds no decision cache and does not expose the
unguarded runtime. Lower runtime outcome, read ownership, write retirement,
uncertain mutation, and cleanup truth remain unchanged. Authorization is an
application business rule; direct use of the lower runtime remains possible
outside this wrapper.

The copy-once `storage-minimal` example demonstrates explicit roots, a narrow
example authorizer, create-if-absent, bounded read with explicit close, and
version-matched replace/delete. It does not authenticate users or define a
production authorization policy.

## Consequences

All components remain optional and install-none by default. No cloud adapter,
credential discovery, provider registry, upload controller, public serving,
background work, hidden retry, or automatic reconciliation is introduced.
Command-line arguments may be visible to the host process environment; they
are ordinary path selectors and must not carry secrets. Structured and human
results omit source text, paths, object identities, namespaces, digests, and
adapter details. Phase 21G inspection and Phase 21H external acceptance and
checkpoint remain Planned. This decision makes no production-readiness claim.

## Related material

- [Storage application workflow](../architecture/storage-application-workflow.md)
- [Storage Platform](../architecture/storage-platform.md)
- [Phase 21 roadmap](../architecture/phase-21-roadmap.md)
- [ADR 0211](0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md)
