# Kongono Extension Composition

This Experimental build-only package binds one exact Phase 12D admission result
to application-owned trusted implementation symbols, explicit capability grant
or denial decisions, provider lifetimes, and deterministic ordinary PHP wiring.

Use it when an application has a complete canonical declaration list, one exact
`AdmittedExtensionGraphInput`, and one `ExtensionGrantPlan`. The generator
rebuilds the declaration-set identity, checks the admission and plan identities,
requires one trusted implementation and one decision per admitted request, and
validates exact constructors and provider contracts through build-time
Reflection. No scan, alias repair, autowiring, fallback, or provider discovery
is performed.

A grant permits only one exact application-supplied provider object to cross
one generated named constructor edge. Required requests cannot be denied.
Optional denials omit only a matching defaulted parameter. Application
extensions may receive application providers only; operation extensions may
also receive operation providers. Generated application state retains only the
application providers needed later by operation wiring.

Generated PHP contains exactly `ExtensionApplicationAssembly` and
`ExtensionOperationAssembly` under a content-bound namespace. Both implement
the package-owned lower hosted boundary interfaces while preserving their
existing typed wiring methods. Hosted methods validate exact ordered provider
lists, delegate directly to those typed methods, and expose ordered lifecycle
objects. This package still only validates and may publish the bytes; loading
and lifecycle belong exclusively to `kongono/extension-host`. Publication is
content-addressed, atomic, idempotent for identical bytes, and no-clobber for
conflicts.

Trusted in-process PHP is not sandboxed. Capability ports constrain only the
objects an application passes through these generated edges; they cannot limit
other PHP-process access. Phase 12F now implements a separate bounded host
lifecycle without giving this build package loading or execution authority.
Neither package adds a Security Permit, Principal, Graph authority, CLI,
sandbox, external execution, or developer-intelligence integration.

Stability: Experimental, except the Public package exception marker. Kongono
remains pre-1.0 and not production-ready.

Phase 12G inspection validates generated wiring only as inert bytes through
this package's structural validator. It never calls currentness validation and
never loads, reflects, or executes the generated PHP.
