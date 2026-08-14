# Kongono API Metadata

`kongono/api-metadata` is the neutral owner of Kongono's compatibility and
supported-use metadata. It requires PHP 8.5 or newer and has no dependency on
another Kongono package or a third-party package.

The package owns exactly two production declarations:

- `Kongono\ApiMetadata\ApiStatus`, a string-backed enum with the ordered cases
  `Internal = internal`, `Experimental = experimental`, and `Public = public`;
- `Kongono\ApiMetadata\Api`, a final readonly non-repeatable attribute targeted
  exactly to class-like declarations and constructed with one public readonly
  promoted `ApiStatus $status` property.

These two bootstrap declarations are Public by definition and are the only
production types exempt from carrying their own `Api` attribute. Every other
production declaration is classified explicitly, with repository-wide
enforcement owned by the root architecture tests.

API status describes compatibility and supported use. It is not authorization,
visibility, a security boundary, discovery metadata, Application Graph data, or
runtime access control. The package provides no service, registry, cache, I/O,
runtime integration, diagnostic context, renderer, or transport.

Phase 0S removed the former `Kongono\Kernel\Api\Api` and
`Kongono\Kernel\Api\ApiStatus` identities atomically. No alias, forwarder, or
other compatibility shim exists.
