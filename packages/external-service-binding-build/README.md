# Kongono External Service Binding Build

`kongono/external-service-binding-build` is an optional Experimental offline
build-time package. It converts one explicit immutable secret-free
`ExternalServiceBindingSource` into a canonical versioned JSON artifact and
deterministic ordinary PHP.

The artifact, generated PHP, source and implementation digests, credential
references, and publication paths are inert evidence. They do not grant
credential access or network authority, establish DNS or provider truth, or
prove current authorization. Validation is either structural or relative to
one explicit current typed source supplied by the caller.

Both publishers accept one existing canonical absolute writable ordinary
directory. Publication is content-addressed, atomic, no-clobber, and
idempotent only when an existing target is stable, structurally valid, and
byte-identical. Publishers neither scan nor create the directory.

Generated PHP contains only fixed framework value constructors and the
secret-free values already present in the validated artifact. This package
validates generated PHP by reconstructing and re-emitting its bytes; it never
includes, requires, evaluates, reflects, or invokes those bytes.

The package supplies no resolver, credential source or lease implementation,
provider adapter, runtime coordinator, application workflow, command,
registry, secret lookup, DNS, socket, TLS, HTTP, or other network behavior.
Credential access and network access remain independent current permissions.
PHP strings provide no reliable memory-zeroization guarantee, although no
credential material enters this package.

Only `Exception\ExceptionInterface` is Public. All source, artifact,
generator, validator, publisher, version, and concrete failure declarations
are Experimental; implementation helpers are Internal. No Stable, 1.0, or
production-readiness claim is made.
