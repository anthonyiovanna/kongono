# Composition Declaration Engineering Guide

## Scope and status

This guide governs review of version-1
`<application-root>/kongono.composition.json` sources. Phase 5C accepts the
contract. Phase 5D implements its strict reader, validator, canonical model,
provenance-bearing artifact, and publisher in `kongono/composition`. Phase 5E
implements corrected typed-wiring generation and isolated lifetime support in
the same package. No schema file, command, production loader, or runtime
integration exists. Current composition remains caller-owned; Phase 5F has not
begun.

## Authoritative source rules

- Accept exactly one application-owned file at the fixed relative path.
- Require the caller to supply the application root explicitly.
- Never infer a working directory, scan, glob, traverse parents, merge,
  overlay, select an environment variant, use a fallback, or fetch remotely.
- Treat bytes as untrusted inert data. Never include, require, evaluate, repair,
  rewrite, or execute them.
- Require an ordinary contained exact-case file: no symlink, junction, reparse
  point, alternate stream, device path, UNC ambiguity, or case collision.
- Prove containment and unchanged identity, size, and bytes across the read.
- Require UTF-8 without BOM, LF-only, exactly one final LF, and no NUL.

## Exact version-1 grammar

The root object has exactly six members: `schema`, `version`, `inputs`,
`services`, `bindings`, and `roots`. Require
`schema: "kongono.composition"` and integer `version: 1`. Reject comments,
trailing commas, duplicate or unknown members, NaN/Infinity, extension syntax,
empty source, and non-object roots.

| Declaration | Exact members |
| --- | --- |
| Input | `contract` |
| Service | `class`, `lifetime`, `arguments`, `cleanup` |
| Binding | `contract`, `service` |
| Root | `kind`, `contract` |
| Argument | `parameter` plus exactly one source form |

Allowed argument source forms are:

```text
service:  one canonical contract symbol
input:    one declared input contract symbol
default:  true
```

No scalar literal, `false` default marker, environment reference, secret,
expression, callback, factory, alias, decorator, tag, condition, profile,
property/setter injection, or extension member is permitted.

Plural collaboration uses an ordinary application-owned typed aggregate
service. Its constructor declares one named `service` edge for every member in
exact parameter order. Reject `services` lists, array/iterable/variadic or
PHPDoc collection injection, automatic multibinding, tags, priorities,
discovery, and framework collection contracts.

## Canonical symbol review

Require exact fully qualified declared PHP names without a leading backslash.
Case and spelling must match the declaration. Reject import/alias syntax,
namespace-relative names, normalization, case repair, nicknames, arbitrary IDs,
reserved words, anonymous classes, traits, enums as services, internal
runtime-only classes, and non-autoloadable symbols.

Service classes must be concrete and instantiable. Input and binding contracts
must be existing classes or interfaces. Inspection may confirm only declared
symbols and constructor/cleanup signatures; it cannot infer or discover
declarations.

## Constructor and argument review

The argument list is authoritative and matches every public constructor
parameter in exact order and by exact name. Version 1 accepts passed-by-value,
non-variadic, named non-nullable class/interface parameters. `default: true`
requires an actual declared default.

Reject untyped, builtin, `mixed`, `object`, nullable, union, intersection,
`self`, `parent`, `static`, reference, variadic, scalar-literal, factory,
closure, property, setter, and magic construction forms. An empty list is valid
only for a supported constructor with no parameters.

## Bindings, roots, and order

Each binding maps one canonical contract directly to one declared concrete
service; a contract has at most one binding. Concrete declared services may be
referenced directly. Interface or abstract contracts require a binding. Reject
chains, aliases, contextual/named bindings, priorities, fallbacks, wildcards,
implicit concrete creation, and automatic multi-binding.

Roots are non-empty and unique. Kind is exactly `application` or `operation`.
The resolved lifetime must match the root kind. Reject transient roots,
arbitrary root names, and service-locator access.

Preserve constructor `arguments` order, including the separate member edges of
an explicit typed aggregate. Review inputs, services, bindings, and roots after ordinal case-sensitive
canonical ordering. Source order for those collections is not semantic and
must not affect output identity or first semantic failure.

## Bounds review

Verify parsed counts before deduplication and fail instead of truncating:

- 1,048,576 source bytes;
- JSON depth 32;
- 256 inputs;
- 4,096 services;
- 4,096 bindings;
- 256 roots;
- 128 arguments per service;
- 32,768 total dependency edges;
- 255 bytes per symbol; and
- 128 bytes per parameter or method name.

## Lifetime edge review

Application services are shared once per composed application and cleaned at
shutdown. Operation services exist at most once per declared service in one
operation and are never shared across operations. Transients are fresh per
explicit construction edge and inherit the requesting application or operation
boundary.

Application reachability may contain application and application-owned
transient services, never operation services. Operation reachability may
contain all three lifetimes. Reject every cycle. Review shared application
objects for concurrency safety and for absence of retained request, response,
principal, tenant, route, diagnostic failure, Kernel scope, operation input, or
other operation-specific state.

## Cleanup review

`cleanup` is `null` or an exact public, non-static, zero-argument instance
method declared `void`. Reject destructors, magic methods, ambiguous inherited
names, arguments, non-void returns, and inferred cleanup.

Every successfully built cleanup owner is registered once and cleaned at most
once in reverse successful-construction order. Construction failure cleans
already-built owned instances. Operation cleanup never releases application
instances. A work/construction/shutdown failure stays primary; cleanup
continues, and cleanup failures remain ordered. Without another primary, the
first cleanup failure becomes primary. Cleanup uncertainty must remain visible
to the external runtime profile; generated wiring does not retry it.

## Diagnostics and non-disclosure

Review deterministic failures in the twelve-category order defined by
[ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md). Within a
category use ordinal canonical declaration order and explicit position. Current
diagnostics identify category, invariant, bounded Safe context, and remediation.
Never expose raw source fragments/values, absolute paths, credentials,
environment values, native parser text, stack arguments, or Protected context.

## Provenance review

Require schema/version, raw-source SHA-256, canonical-model SHA-256, root
`composer.json` and `composer.lock` SHA-256, an ordinal manifest of the exact
source files declaring every admitted service, input, binding, root,
constructor, and cleanup symbol, builder package/version identity, and the
Phase 5D artifact identity. Exclude absolute paths, usernames,
timestamps, randomness, environment values, credentials, request data, and
Protected data. Changed bound state makes output stale.

## Deterministic test expectations

The package tests prove strict JSON and duplicate-member rejection;
all bounds and grammar; ordinal ordering; stable first-failure selection;
symbol/signature matching; binding/root resolution; lifetime reachability and
cycles; cleanup order and combined failure; changed-during-read and filesystem
safety; non-disclosure; byte-identical canonical output; provenance and stale
rejection; and no source or artifact publication after failure. Phase 5C adds no
test or implementation.

## Future schema migration rules

A future schema version requires separately accepted architecture. It must use
an explicit integer version, define supported source and target versions,
preserve authority and bounded provenance, specify deterministic migration and
rejection, and never silently upgrade, downgrade, guess, merge, or reinterpret
version 1. A new version cannot become consumable merely by documentation.

## Review exclusions

Do not add routes, middleware order, HTTP/Kernel configuration, environment
loading, factories, lazy services, proxies, Graph schemas/IDs, runtime
reflection, service location, or developer-agent mutation. Database/ORM,
templates, events, queues, extensions, application agents, telemetry, frontend,
deployment, authentication, authorization, and other capability families are
outside Phase 5C.

## Related material

- [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md)
- [Composition source architecture](../architecture/composition-source.md)
- [Generated wiring](generated-wiring.md)
- [Dependency Injection](dependency-injection.md)
- [Composition build](composition-build.md)
- [ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [ADR 0075](../adr/0075-minimal-application-skeleton-and-workflow.md)
- [Application workflow](application-workflow.md)
