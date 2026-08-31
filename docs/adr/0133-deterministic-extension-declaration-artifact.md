# ADR 0133: Deterministic Extension Declaration Artifact

## Status

Accepted and implemented by Phase 12C.

## Context

Phase 12B supplies inert, locally valid `ExtensionDeclaration` values. A later
phase needs deterministic evidence for one application-selected declaration
set without allowing installed packages, filesystem order, Composer metadata,
or extension self-claims to become selection, admission, trust, authorization,
or execution authority.

## Decision

Kongono implements the Experimental `kongono/extension-build` package above
Extension Contract. A caller supplies one list of 0-256 declarations. The list
must already be a PHP list, contain only exact declaration values, use unique
extension identities, and be in strict ascending binary identity order.
Invalid input is rejected rather than reordered or deduplicated.

The package projects exactly the accepted Phase 12B fields into compact UTF-8
JSON with fixed key order, unescaped slashes and Unicode, and one terminal LF.
The lowercase SHA-256 of the canonical declarations-only object is both the
declaration-set digest and `DeclarationSetIdentity`. A separate lowercase
SHA-256 identifies the complete artifact bytes and is not embedded recursively.

Structural validation admits only exact canonical version-1 bytes. It
reconstructs every Phase 12B value through its public constructor, rebuilds the
artifact, and requires byte equality. Exact-current validation is relative only
to a declaration list explicitly supplied by the caller; no ambient source of
currentness exists.

Publication accepts one canonical absolute existing ordinary directory,
revalidates before I/O, writes and synchronizes a same-directory temporary
ordinary file, and uses an atomic no-overwrite hard-link pattern. The exact
target name is content-addressed. An existing target is accepted only when it
is safe, byte-identical, stable during the bounded read, and structurally valid.
Conflicting targets are never truncated, replaced, repaired, or deleted.

## Consequences

The artifact is deterministic declaration evidence. It is not extension
selection or admission, a capability grant, trust or signature evidence, an
Application Graph fact, Composition wiring, loaded code, a host contract, or
execution authority. No public artifact loader exists. Phase 12D-H remain
planned and do not begin automatically.

The production graph becomes 59 acyclic packages with 206 local production
dependency edges. Extension Build depends only on API Metadata, Diagnostics,
Extension Contract, and PHP. Kongono remains Experimental, pre-1.0, and not
production-ready.
