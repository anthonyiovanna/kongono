# Kongono Extension Build

`kongono/extension-build` is the Experimental Phase 12C build-time package for
deterministic extension declaration artifacts. It accepts only an explicit,
already canonical list of Phase 12B `ExtensionDeclaration` values. It does not
discover, select, admit, grant, load, host, or execute extensions.

## Contract

`ExtensionDeclarationArtifactBuilder` accepts 0-256 declarations in strict
ascending binary extension-identity order. Wrong types, non-lists, duplicate
identities, bad order, and excess input fail rather than being normalized. The
builder preserves every neutral declaration field, produces compact UTF-8 JSON
with one terminal LF, and derives separate lowercase SHA-256 identities for the
declaration set and complete artifact.

`ExtensionDeclarationArtifactValidator` reconstructs every neutral contract
value and rebuilds exact canonical bytes. `validateCurrent()` compares only
with the declaration list supplied by its caller; it reads no filesystem,
environment, Composer metadata, registry, or Application Graph state.

`ExtensionDeclarationArtifactPublisher` structurally revalidates before I/O
and publishes into one explicit canonical existing ordinary directory. The
target is content-addressed, publication uses a same-directory temporary file
and atomic no-overwrite hard link, identical existing content is accepted, and
conflicting content is never replaced.

## Boundaries

The package depends only on API Metadata, Diagnostics, Extension Contract, and
PHP. The artifact is declaration evidence, not admission, trust, a capability
grant, an Application Graph fact, loaded code, Composition wiring, or execution
authority. There is no public loader, scanner, registry, resolver, command,
host, callback, or executable surface.

The package is Experimental, pre-1.0, and not production-ready.
