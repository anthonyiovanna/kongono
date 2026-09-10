# ADR 0168: Exact-current external-service binding application

- Status: Accepted
- Date: 2026-09-09

## Context

Phases 16B through 16E provide neutral binding and credential contracts,
deterministic secret-free binding artifacts, an explicit bounded system
resolver adapter, and operation-scoped credential lease coordination. Phase 15
already owns governed network-egress policy, runtime, transport adaptation, and
exact-current application wiring. Applications still need a narrow place to
compose those accepted boundaries for one external-service operation without
introducing provider discovery, ambient credentials, or a universal client.

Credential access and network access are independent current permissions.
Binding artifacts, generated PHP, profile identities, credential references,
policy references, receipts, and possession of a collaborator grant neither.

## Decision

Add the optional Experimental `kongono/external-service-application` package.
It validates one exact-current binding artifact and one exact-current Phase 15
application profile, selects exactly one binding, and requires an allowed
origin, operation-scoped credential posture, and capability compatibility.
Its profile identity is a deterministic lowercase SHA-256 of a fixed,
LF-terminated, secret-free identity projection.

One application instance accepts one loaded Phase 15 wiring boundary, one
explicit endpoint resolver and transport, and one explicit credential-source
identity/source pair. It may perform one terminal synchronous operation. A
caller-supplied `ExternalServiceRequestFactory` receives borrowed material only
inside Phase 16E's consumer call. The resulting PSR-7 request must match the
binding origin, contain no URI user information or fragment, and remain at the
binding base path or a slash-delimited descendant.

The coordinator delegates at most one network operation to Phase 15 and lets
Phase 16E perform post-use validation and exactly one retirement attempt for
every acquired lease. Aggregate evidence is produced only when the credential
runtime succeeds and an exact detached egress result exists. Possible-effect
outcomes remain Failed; other represented terminal outcomes are Retired after
successful credential cleanup.

## Security and failure semantics

Package-controlled evidence and presentation omit secret material and all
derivatives, request and response bodies, authorization values, full URIs,
hosts, addresses, provider payloads, filesystem paths, and foreign failure
details. Closed diagnostics expose only stage or reason tokens. Exact foreign
throwable identity remains available only through explicit programmatic
accessors and `getPrevious()` and is not presentation-safe.

The request factory, PSR-7 request, caller code, credential source, and transport
may copy credential material. Ordinary PHP strings provide no reliable
zeroization guarantee. The coordinator provides cooperative lifecycle bounds,
not preemptive cancellation, thread isolation, credential validity, endpoint
availability, or external-effect truth.

## Consequences

Applications gain an inspectable one-operation composition seam while all
provider semantics and effect-capable implementations remain caller-owned.
Applications without external-service access do not install the package.

No provider adapter or SDK, credential source, resolver, transport, registry,
locator, retry, fallback, generated binding loader, command, inspection,
recording, external acceptance, or production-readiness claim is added. Phase
Phase 16A through Phase 16G are Accepted; Phase 16H remains Planned and
separately authorized. See [ADR 0169](0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
for the distinct offline inspection boundary.
