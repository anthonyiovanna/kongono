# ADR 0164: Neutral External-Service Binding and Credential-Lease Contracts

- Status: Accepted
- Date: 2026-09-08
- Scope: Phase 16B only

## Context

ADR 0163 fixes an optional external-service binding layer above the accepted
Phase 15 governed network-egress platform and below future binding artifacts,
resolver and credential-source implementations, runtime orchestration, and
service/provider adapters. Phase 16B needs a neutral PHP contract foundation
without collapsing binding evidence, credential access, network authority,
resolution evidence, or service semantics into one capability.

## Decision

Add the optional Experimental `kongono/external-service-contract` package. It
contains 27 production declarations and 15 package-test files. Its only local
production dependencies are API Metadata, Diagnostics, Network Egress
Contract, and Network Egress Runtime; PHP is its only platform dependency.
No existing production package depends on it.

The package reuses Phase 15 `CanonicalOrigin`, `TransportCapabilitySet`,
`EgressResolver`, and `EgressResolutionResult`. `EndpointResolver` extends the
existing resolver port without adding a method. The package does not duplicate
origin, address, address-family, address-category, resolution-list,
transport-capability, or selected-address semantics.

Service bindings are immutable, secret-free inert evidence combining one
binding identity, service-profile identity, endpoint, credential reference,
and required transport-capability set. Construction proves local grammar,
bounds, shape, canonical ordering, and internal coherence only. It does not
prove service existence, reachability, DNS truth, credential existence,
account ownership, authentication, authorization, policy currentness,
provider compatibility, cleanup, effect, or production readiness.

Credential leases are represented only by ports. The sole secret-bearing
signature is `CredentialMaterialConsumer::consume(#[SensitiveParameter]
string $material): void`; `CredentialLease::withMaterial()` lends material to
one explicit caller-owned consumer. There is no material getter or returned
secret string. A consumer can technically copy material, and ordinary PHP
strings cannot support a reliable physical-memory-zeroization claim. The
package supplies no lease, source, consumer, or resolver implementation.

Credential access and network access remain independent current permissions.
Bindings, references, leases, resolver compatibility, resolution results,
capability declarations, lifecycle states, results, diagnostics, and tests
grant neither permission.

Lifecycle, outcome, and failure values are closed bounded evidence. The
diagnostic family exposes eight actionable identifiers with a closed Safe
context schema. Package exceptions preserve the exact previous throwable
identity while omitting its text, stack, raw rejected input, and other
sensitive or foreign content from visible surfaces.

`ExceptionInterface` is Public only as the package exception-family marker.
The diagnostic enum, abstract package exception, and internal guard are
Internal. Every other production declaration is Experimental. No declaration
is Stable or Deprecated.

## Security and disclosure consequences

The package stores no credential material and defines no serialization,
stringification, debug, clone, cache, registry, environment, filesystem,
process, DNS, socket, HTTP-client, provider, or network-effect surface.
Credential references are opaque identifiers, not secrets or authority.
Diagnostics accept only allowlisted tokens and bounded nonnegative counts.

## Rejected alternatives

- Duplicating Phase 15 origin, capability, address, or resolution contracts was
  rejected because it would permit semantic drift and ambiguous enforcement.
- Returning credential strings or adding a material value object was rejected
  because it would enlarge disclosure and lifetime surfaces.
- Bundling a resolver, credential source, environment reader, provider SDK, or
  service client was rejected because those are separate future boundaries.
- Treating a binding, reference, lease, or test result as authorization was
  rejected because evidence and current permission are distinct.
- Claiming PHP memory zeroization was rejected because ordinary strings cannot
  provide that guarantee.

## Prohibited scope

Phase 16B adds no deterministic binding source, schema, artifact, generated
PHP, publisher, loader, concrete resolver, DNS cache, address selection,
credential source, secret store, token refresh, runtime orchestration,
application wiring, command, inspection, provider adapter, network behavior,
production-readiness claim, Stable/1.0 claim, Phase 16C behavior, or Phase 17
behavior. Later rows require separate authenticated authority.

## Consequences

Applications may type-check secret-free binding evidence and explicit future
ports without acquiring credentials or opening the network. Future phases can
depend on this boundary only after separate acceptance and must preserve the
independence of credential permission, network permission, resolution
evidence, and service-specific behavior.
