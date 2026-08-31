# Extension Contract Foundation

Phase 12H externally proves the neutral declaration vocabulary without changing
it. A declaration, category, provenance value, capability request, contribution,
visibility, or lifecycle value grants no admission, trust, authorization, or
execution authority. See [Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and current boundary

Phase 12B implements the Experimental `kongono/extension-contract` package as
the neutral declaration vocabulary for Kongono extensions. The package depends
only on API Metadata, Diagnostics, and PHP. It is intentionally independent of
Application Graph, Composition, security, hosting, discovery, and I/O.

Construction proves that supplied values satisfy their local grammar and
bounds and that aggregate lists have exact types, limits, uniqueness, and
strict binary canonical order. Construction does **not** prove admission,
currentness, trust, integrity, authorization, selection, loading, or execution.

## Values

`ContractVersion::Version1` identifies the sole accepted contract vocabulary.
`ExtensionCategory` contains Core, Trusted/in-process, Sandboxed, and External.
These are architectural descriptions. They are not trust, installation,
authorization, isolation, or execution decisions.

`ExtensionIdentity` is an exact lowercase ASCII `vendor/name`; each side is
between 1 and 64 bytes. `ExtensionVersion` is one opaque lowercase ASCII token
between 1 and 64 bytes. The version is not interpreted as SemVer or a Composer
constraint. For example, `acme/search` and `v1_2` are locally valid synthetic
values, but they say nothing about whether an application admits that
extension.

`ExtensionSourceIdentity` and provenance digests are exactly 64 lowercase
hexadecimal bytes. `ExtensionProvenance` contains only the source identity and
source digest. It contains no path, URL, publisher, signature, credential,
license, or executable material and reads no external state.

## Capability requests

A `CapabilityRequest` combines a versioned `CapabilityIdentity`, a bounded
`CapabilityPurpose`, and Required or Optional intent. A request such as
`public.assets` version `1` for `render_catalog` is inert declaration evidence.
It is not a grant, capability port, authorization result, Principal, provider,
resolver, or action. Later application-owned policy must decide any grant.

## Contributions

A `ContributionDeclaration` combines one identity, one versioned schema,
Public/Application/Framework-internal visibility, and one exact source digest.
It carries no payload, PHP symbol, callable, route, service, policy, UI fragment,
agent behavior, path, or execution instruction.

Visibility is eligibility metadata rather than authorization or
declassification. A contribution declaration is not an authoritative
Application Graph fact. Only a separately accepted deterministic admission and
Graph-construction boundary may establish Graph truth.

## Lifecycle intent

`ExtensionLifecycle` declares Application or Operation ownership intent. It
defines no host, callback, start/stop hook, cleanup implementation, retry,
concurrency behavior, process lifetime, or execution authority.

## Aggregate declaration

`ExtensionDeclaration` combines version, extension identity/version, category,
provenance, lifecycle, capability requests, and contributions. Capability
requests are limited to 64 entries and use a private NUL-framed binary key.
Contributions are limited to 128 entries, require unique contribution
identities, and use a private NUL-framed binary key. Both lists must arrive in
strict ascending binary order.

The constructor rejects non-lists, wrong types, duplicates, noncanonical order,
and over-limit input. It never silently reorders or deduplicates. Validation
finishes before properties are assigned; lists are detached; accessors return
fresh lists containing the identical objects in the identical order.

## Failures and disclosure

The package owns seven diagnostics from `KGN-EXTENSION-1201` through
`KGN-EXTENSION-1207`. Context contains only a fixed Safe violation token and,
where relevant, nonnegative entry count/position. Rejected identifiers,
versions, digests, paths, credentials, Unicode, oversized values, payloads,
classes, source text, environment values, and stacks are never copied into the
message, context, or deterministic string form. Native throwable traces are not
claimed to be sanitized.

## Use and do not use

Use this package when code needs to construct or consume exact neutral Phase
12B declaration values. Phase 12C's separate Extension Build package may
serialize and publish those values as deterministic evidence. Extension
Contract itself still performs no I/O and must not discover extensions, decide
admission, grant capabilities, project Graph facts, wire Composition, host
code, or execute behavior.

Phase 12B is the historical/current neutral foundation. Phase 12C brought the
topology to 59 packages and 206 acyclic local production edges by adding
deterministic declaration artifacts, strict reconstruction,
caller-relative currentness, and atomic no-clobber publication. No public
loader exists. Phase 12D consumes these values for deterministic admission;
Phase 12E consumes the resulting exact admission plus these declarations for
explicit capability decisions and inert generated wiring. Neither consumer
changes the neutral contract. The current topology is 61 packages / 217
acyclic local production edges. Kongono remains Experimental, pre-1.0, and not
production-ready.

## Related decisions

- [ADR 0131](../adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
- [ADR 0132](../adr/0132-neutral-versioned-extension-contract-foundation.md)
- [ADR 0133](../adr/0133-deterministic-extension-declaration-artifact.md)
- [ADR 0135](../adr/0135-explicit-extension-capability-grants-and-generated-wiring.md)
- [Extension Declaration Artifact](extension-declaration-artifact.md)
- [Extension Capability Grants and Generated Wiring](extension-capability-grants-and-generated-wiring.md)
- [Extension Platform](extension-platform.md)
- [Phase 12 roadmap](phase-12-roadmap.md)

## Phase 12D consumer

`kongono/extension-admission` now consumes these exact inert declarations. It
rebuilds their complete declaration-set identity, matches selected identities
and versions exactly, validates declared Graph bundles, and requires explicit
conflict winners. This does not change Extension Contract construction or turn
a declaration, request, visibility, lifecycle, or category into authority.

## Phase 12E consumer

`kongono/extension-composition` requires an explicit decision for every exact
capability request and binds its plan to Phase 12D admission. The package uses
the neutral identities and requirements as evidence; it does not widen them
into authorization, discovery, trust, sandboxing, hosting, or runtime service
location. Denied optional dependencies are omitted from generated named
constructor calls, while required denial fails closed.
