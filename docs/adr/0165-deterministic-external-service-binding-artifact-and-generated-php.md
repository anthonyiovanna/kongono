# ADR 0165: Deterministic External-Service Binding Artifact and Generated PHP

- Status: Accepted
- Date: 2026-09-08
- Decision scope: Phase 16C only

## Context

Phase 16B established neutral secret-free service-binding and credential-lease
contracts. Applications need deterministic build evidence without turning a
credential reference, endpoint declaration, generated file, or digest into
credential or network authority.

## Decision

Accept the optional Experimental `kongono/external-service-binding-build`
package. One explicit immutable typed `ExternalServiceBindingSource` produces
one canonical version-1 JSON artifact and one deterministic ordinary PHP file.
The provenance chain binds the source identity and canonical source digest to
artifact bytes, then binds the artifact digest and one fixed generator
implementation digest to the generated PHP bytes.

Structural validation reconstructs all lower typed values, re-encodes the
artifact or re-emits the generated PHP, and requires exact byte equality.
Currentness is caller-relative and is checked only against one explicit typed
current source supplied in the same call. Validators discover no ambient or
live state.

Generated PHP contains only fixed framework value constructors, secret-free
binding values, credential references, and provenance. Phase 16C treats it as
inert bytes and never includes, requires, evaluates, reflects, or invokes it.

Publication accepts one explicit existing canonical absolute writable ordinary
directory. The JSON and PHP publishers are independent, path-safe,
content-addressed, atomic, idempotent for identical stable valid bytes, and
no-clobber. They do not scan, create, clean, or manage the output directory.

`Exception\ExceptionInterface` is the package's only Public declaration.
Source, artifact, generator, validator, publisher, version, and concrete
exceptions are Experimental. Helpers and the abstract exception are Internal.

## Security boundary

No credential material, material-derived hash or length, environment value,
provider response, authorization result, account fact, session, cookie,
authentication header, or query string enters source, artifact, generated PHP,
diagnostics, tests, documentation, or developer output. A credential reference
is not credential material. PHP strings provide no reliable memory-zeroization
guarantee, although no secret enters this phase.

Credential access and network access remain independent current permissions.
Artifacts, generated PHP, digests, provenance, endpoints, and credential
references are evidence only and grant neither permission.

## Consequences

Phase 16C adds no resolver adapter, credential source or lease implementation,
provider adapter, exact-current application, command, inspection surface,
runtime coordinator, generated-code loader, DNS, HTTP client, socket, TLS,
retry, redirect, registry, background work, or live network behavior. It makes
no Stable, 1.0, production-readiness, Phase 16D-H, or Phase 17 claim.

Phase 16A through Phase 16G are Accepted. Phase 16H remains Planned and requires
separate authenticated authority.

## Related decisions

- [ADR 0163](0163-explicit-external-service-binding-and-secret-lease-boundary.md)
- [ADR 0164](0164-neutral-external-service-binding-and-credential-lease-contracts.md)
- [External-service binding and secret leases](../architecture/external-service-binding-and-secret-leases.md)
- [Phase 16 roadmap](../architecture/phase-16-roadmap.md)
