# Kongono External Service Application

`kongono/external-service-application` is the optional Experimental Phase 16F
composition boundary for one exact-current external-service binding, one
exact-current Phase 15 egress profile, one caller-selected credential lease
source, and one terminal synchronous request.

Install it only in applications that call an external service. Applications
without external-service access do not need this package.

## What it composes

`ExternalServiceApplicationProfileFactory` validates the supplied binding
source and canonical artifact through Phase 16C, revalidates the supplied
Phase 15 application profile, selects exactly one binding, and checks origin,
operation-scoped credential mode, and transport-capability relations. The
derived profile identity is secret-free evidence; it grants neither credential
access nor network authority.

`ExternalServiceApplication` accepts one already loaded Phase 15 wiring value,
an explicit endpoint resolver and transport, and one explicit credential
source identity/source pair. One application instance can run once. It acquires
at most one lease through Phase 16E, calls the caller's request factory exactly
once while material is borrowed, validates the resulting origin and base path,
delegates at most one Phase 15 operation, and lets Phase 16E retire every
acquired lease exactly once.

The caller still owns:

- binding and egress-policy source selection and currentness;
- authority-currentness and cancellation collaborators;
- credential-source implementation and credential validity;
- endpoint resolver and transport implementations;
- service method, path, headers, body, authentication syntax, and provider
  semantics inside `ExternalServiceRequestFactory`.

## Request factory

Implement the provider-specific seam in application code:

```php
final class ServiceRequestFactory implements ExternalServiceRequestFactory
{
    public function create(
        ServiceBinding $binding,
        ExternalServiceOperationIdentity $operationIdentity,
        #[\SensitiveParameter] string $credentialMaterial,
    ): RequestInterface {
        // Construct one PSR-7 request. Do not log, persist, or return the
        // borrowed material outside the request needed for this operation.
    }
}
```

The package cannot prevent caller code, a PSR-7 implementation, or a transport
from copying material. PHP strings provide no reliable memory-zeroization
guarantee. Use a fresh application instance for every operation and discard
caller-held request, factory, and secret references promptly.

## Results and failures

A successful credential lifecycle returns one aggregate containing the exact
neutral external-service result, exact Phase 16E lease result, and exact Phase
15 egress result. The six egress outcomes map without widening possible-effect
claims. Egress validation, policy, resolution, cancellation, cleanup, and
transport failure kinds reduce to closed service-level kinds and enum-derived
reason tokens.

Package exceptions preserve exact lower-layer throwable identity through
`getPrevious()` and typed accessors, but their messages, diagnostics, and
stringification expose only closed stage tokens. These programmatic throwable
references are not presentation-safe.

The package supplies no provider SDK, credential source, resolver, transport,
discovery, registry, retry, fallback, command, inspection, background work, or
production-readiness claim.
