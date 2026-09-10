# ADR 0169: Visibility-Safe External-Service Inspection and Bounded Evidence

- Status: Accepted
- Date: 2026-09-09
- Stability: Experimental

## Context

Phase 16F composes one exact-current external-service binding with one already
loaded exact-current Phase 15 application for a terminal operation. Applications
also need an offline way to compare that profile and, optionally, summarize one
detached completed result without repeating credential, resolver, transport, or
application work. Inspection must not turn evidence into authority, live truth,
physical-erasure proof, or permission to retry a possible effect.

## Decision

Accept the optional Experimental `kongono/external-service-inspection` upper
evidence leaf. Its request carries exactly one
`ExternalServiceApplicationProfile` and an optional detached
`ExternalServiceApplicationResult`; it accepts no collaborator, material,
generated PHP, operation request, or live application.

The inspector reconstructs the profile through the accepted Phase 16F public
factory using only the explicit binding source, canonical artifact bytes,
selected binding identity, and nested Phase 15 profile. It then independently
compares the application-profile identity, complete ordered binding source,
artifact identities and bytes, selected binding, service profile, endpoint,
credential source/reference, required capabilities, nested network profile,
policy identities and digests, and all closed policy postures. Endpoint and
credential-reference comparisons use package-owned equality without rendering
their raw values. Their protected projections are domain-separated bindings to
the selected content-addressed artifact.

When a completed result is present, the inspector independently checks selected
binding and operation/receipt identity relations; the exact external-service to
Phase 15 outcome, lifecycle, and failure-kind mapping; credential success,
Retired state, absent failure, one acquisition, one material-use attempt, one
retirement attempt, and completed cleanup; response/failure shape; terminal
receipt relations; protocol/capability coherence; and every accepted Phase 15
runtime ceiling. It never reads a request, response, body, header, URI, endpoint,
credential reference or material, failure message, or foreign presentation.

Possible-effect and uncertain-effect outcomes remain uncertain failed evidence.
Credential retirement and cleanup do not prove physical memory erasure, provider
outcome, network effect, or safe replay.

The immutable version-1 inspection uses schema
`kongono.external-service-inspection`. Canonical UTF-8 JSON has fixed ordering,
LF framing with exactly one final LF, and a lowercase SHA-256 identity computed
over the payload excluding only that identity. It exposes only Protected
lowercase digests, closed vocabulary, Safe bounded counts, booleans, and fixed
limitations. Digests are comparison evidence, not authentication, authority,
non-repudiation, external truth, erasure proof, or effect proof.

Optional Graph projection copies only explicit matching-role Safe aggregates and
always reports the external-service-to-Graph relationship as not established.
Observability and Security Audit projection only construct one neutral event and
one Safe audit value; neither is recorded or exported. Chie, Hantei, and Oshie
are closed evidence-purpose labels. No role package is imported or executed. An
independent verifier reconstructs the permitted shape without calling the
deriver, normalizing or repairing a candidate, accessing hidden data, or
certifying itself.

Diagnostics `KGN-EXTERNAL-SERVICE-INSPECTION-1628` through `1632` cover invalid
inspection, binding mismatch, completed-result mismatch, intelligence evidence,
and projection failure. Context is one closed Safe component token. Messages and
deterministic strings omit raw and foreign content while preserving a translated
prior throwable identity.

## Consequences

The final production graph contains 87 packages and 355 local edges, remains
acyclic, and adds exactly 13 edges from the new leaf. The package is stateless,
offline, read-only, synchronous, and safe for independent concurrent calls. It
provides no credential source/store, provider adapter or SDK, resolver, transport,
request factory, authority decision, generated-code loader, recorder, exporter,
command, cache, retry, replay, repair, live probe, checkpoint, compatibility
certification, or Stable claim.

Phase 16A through Phase 16G are Accepted. Phase 16H remains Planned and requires
separate architect authority. Phase 17 does not begin automatically.

## Related decisions

- [ADR 0163](0163-explicit-external-service-binding-and-secret-lease-boundary.md)
- [ADR 0164](0164-neutral-external-service-binding-and-credential-lease-contracts.md)
- [ADR 0165](0165-deterministic-external-service-binding-artifact-and-generated-php.md)
- [ADR 0166](0166-explicit-bounded-system-resolver-adapter.md)
- [ADR 0167](0167-operation-scoped-credential-lease-runtime.md)
- [ADR 0168](0168-exact-current-external-service-binding-application.md)
- [External-service inspection guide](../architecture/external-service-inspection-and-evidence.md)
