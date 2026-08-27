# Composition Source Architecture

## Status and purpose

- **Current:** application construction is caller-owned. The Experimental
  `kongono/composition` build package consumes and validates
  `kongono.composition.json`; the runtime resolver and structural Application
  Graph are unchanged and unintegrated.
- **Accepted in Phase 5C:** one application-owned, inert version-1 source and
  its declaration, lifecycle, validation, safety, and provenance contract.
- **Implemented in Phase 5D:** strict source/symbol validation, one detached
  canonical model, one provenance-bearing inspectable artifact, exact-current
  validation, and content-addressed no-clobber publication.
- **Implemented in Phase 5E:** the corrected three-form argument grammar,
  canonical typed wiring, isolated application/operation lifecycle support,
  exact validation, and explicit publication.
- **Implemented in Phase 5F:** fail-closed exact-current loading and the
  existing web lifecycle integration.
- **Implemented in Phase 5G:** one reusable build/check workflow and copy-once
  minimal application skeleton.
- **Implemented in Phase 9E:** source version 2 requires explicit application
  or operation lifetime on every input while version 1 remains compatible.

This guide explains the conceptual contract selected by [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
and [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md). It is not a
schema, parser specification, command, or executable example.

## Singular authority

The accepted source location is exactly:

```text
<application-root>/kongono.composition.json
```

A build/check caller supplies the application root explicitly. Kongono
does not infer it from the working directory, traverse parents, scan or glob,
discover it through Composer, merge sources, apply overlays or environment
variants, fall back to another name, or fetch a remote source.

```text
Authoritative application state                 Derived state
┌──────────────────────────────────────┐         ┌──────────────────────────┐
│ <application-root>/                  │ Phase   │ canonical semantic model │
│   kongono.composition.json           │ 5D      │ + bounded provenance     │
│   composer.json / composer.lock      ├────────>│ + generated artifact     │
│   exact declaring PHP source files   │ 5E      │ + typed wiring           │
└──────────────────────────────────────┘         └──────────────────────────┘
          authority                                      evidence

The Phase 5D artifact and Phase 5E generated wiring are implemented build
evidence. They are not source authority or production runtime integration.
```

The source is application code expressed as data. Derived output cannot become
authority or repair, overwrite, or reinterpret it.

## Complete conceptual JSON shape

The root object has exactly `schema`, `version`, `inputs`, `services`,
`bindings`, and `roots`. This small example is non-secret documentation using
invented `Example\...` symbols; only real autoloadable symbols and a complete
valid application project are consumable by the explicit build API.

```json
{
  "schema": "kongono.composition",
  "version": 1,
  "inputs": [
    {
      "contract": "Example\\Config\\DatabaseSettings"
    }
  ],
  "services": [
    {
      "class": "Example\\Database\\Connection",
      "lifetime": "application",
      "arguments": [
        {
          "parameter": "settings",
          "input": "Example\\Config\\DatabaseSettings"
        }
      ],
      "cleanup": "close"
    },
    {
      "class": "Example\\Application\\ReportService",
      "lifetime": "operation",
      "arguments": [
        {
          "parameter": "connection",
          "service": "Example\\Database\\ConnectionContract"
        },
        {
          "parameter": "listeners",
          "services": [
            "Example\\Audit\\ReportListener",
            "Example\\Metrics\\ReportListener"
          ]
        },
        {
          "parameter": "formatter",
          "default": true
        }
      ],
      "cleanup": null
    },
    {
      "class": "Example\\Audit\\ReportListener",
      "lifetime": "transient",
      "arguments": [],
      "cleanup": null
    },
    {
      "class": "Example\\Metrics\\ReportListener",
      "lifetime": "transient",
      "arguments": [],
      "cleanup": null
    }
  ],
  "bindings": [
    {
      "contract": "Example\\Database\\ConnectionContract",
      "service": "Example\\Database\\Connection"
    }
  ],
  "roots": [
    {
      "kind": "operation",
      "contract": "Example\\Application\\ReportService"
    }
  ]
}
```

In version 1, an input has exactly `contract` and remains application-lifetime.
In version 2, an input has exactly `contract` and mandatory `lifetime`, whose
value is `application` or `operation`. A service has exactly `class`, `lifetime`,
`arguments`, and `cleanup`. Each ordered argument has exactly `parameter` and
one of `service`, `input`, or `default: true`. A binding has exactly
`contract` and `service`. Each root has exactly `kind` and `contract`; roots are
non-empty.

## Symbols, constructors, bindings, and roots

Symbols are exact fully qualified declared PHP names without a leading
backslash. Their case and bytes must match the declaration. Services are
declared concrete instantiable classes. Contracts are existing classes or
interfaces. No alias, import, nickname, normalization, inference, scan, or case
repair is accepted.

Constructors are public, passed by value, non-variadic, and use named
non-nullable class/interface parameters. Arguments cover the signature in exact
order and match exact parameter names. Unsupported v1 forms include untyped,
builtin, `mixed`, `object`, nullable, union, intersection, `self`, `parent`,
`static`, reference, variadic, scalar literal, factory, closure, property,
setter, and magic construction.

Bindings are single-hop and unique by contract. Declared concrete services can
be referenced directly; abstract/interface contracts need one exact binding.
Roots are unique typed entry points. Application roots resolve to application
services and operation roots to operation services. Transient roots and general
runtime lookup do not exist.

## Validation pipeline

```text
explicit application root
  -> fixed path and filesystem safety
  -> bytes, UTF-8/LF, strict JSON, duplicates, stable read
  -> schema/version and exact members
  -> bounds and shapes
  -> canonical symbols and declared existence
  -> duplicates/collisions
  -> bindings and inputs
  -> constructors and ordered arguments
  -> roots
  -> lifetime reachability and cycles
  -> cleanup signatures
  -> provenance and source-set stability
  -> detached canonical model (Phase 5D implemented)
  -> inspectable artifact (Phase 5D implemented)
```

This order selects the first failure category. Within a category, validation
uses ordinal canonical declaration order and then explicit argument/list
position. Reordering a non-semantic collection cannot change the selected
semantic failure.

## Bounds

| Measure | Maximum |
| --- | ---: |
| Source bytes | 1,048,576 |
| JSON depth | 32 |
| Inputs | 256 |
| Services | 4,096 |
| Bindings | 4,096 |
| Roots | 256 |
| Arguments per service | 128 |
| Total dependency edges | 32,768 |
| Symbol | 255 bytes |
| Parameter or cleanup method name | 128 bytes |

Counts precede deduplication. Empty, duplicate, unknown, or over-bound state
fails closed; it is never truncated or guessed.

## Lifetime reachability

| Owning root/boundary | Application dependency | Operation dependency | Transient dependency |
| --- | --- | --- | --- |
| Application | allowed and shared | prohibited | allowed; owned by application |
| Operation | allowed and shared | allowed once per declared service per operation | allowed; owned by operation |

A transient inherits the requesting boundary for reachability and cleanup.
Cycles always fail. Application services must remain concurrency-safe and must
not retain operation-specific request, identity, route, failure, input, or scope
state. In version 2, an application service or root must not directly or
transitively reach an operation input. Operation inputs are supplied only when
the generated operation begins and are never retained by generated application
state.

## Cleanup and failure flow

```text
construct successfully -> register cleanup owner once
failure or boundary end -> visit all owned instances in reverse construction order
                       -> call each cleanup at most once
                       -> retain the original work/construction/shutdown failure
                       -> retain cleanup failures in cleanup order
                       -> if no earlier primary, first cleanup failure is primary
                       -> expose cleanup uncertainty to the runtime profile
```

A cleanup declaration names an exact public, non-static, zero-argument instance
method returning `void`. Operation cleanup never releases application objects.
No destructor, magic or inferred cleanup participates.

## Source safety and disclosure

The implemented build boundary proves exact root containment before and after reading and
rejects wrong-case paths, links, junctions, reparse points, alternate streams,
devices, UNC ambiguity, case collisions, non-ordinary files, and source that
changes identity, size, or bytes. The file is UTF-8 without BOM, LF-only, has
one final LF and no NUL, comments, trailing commas, duplicate members, unknown
members, NaN/Infinity, or executable configuration.

Package-owned diagnostics contain a category, invariant, bounded Safe
context, and remediation. They exclude raw source fragments/values, absolute
paths, credentials, environment values, native parser text, stack arguments,
and Protected context.

## Provenance set and invalidation

The implemented artifact binds schema/version, raw source SHA-256, canonical-model
SHA-256, root Composer manifest/lock SHA-256 values, and the ordinal exact-
source manifest declaring every admitted service, input, binding, root,
constructor, and cleanup symbol, plus builder package/version and the artifact
identity selected by Phase 5D. Absolute paths, usernames, timestamps,
randomness, environment values, credentials, request data, and Protected data
are excluded.

Any change to source, lock metadata, declared symbol bytes, builder identity,
schema version, or canonical model makes earlier output stale.

## Build and runtime separation

Phase 5C accepts the source contract. Phase 5D validates it and produces one
inspectable artifact. Phase 5E generates typed constructor wiring and owns its
isolated lifetime support. Phase 5F owns production loading and integration
with runtime boundaries. Runtime
requests do not discover, parse, reflect, build, repair, or locate services.

Phase 9E adds only the version-2 input-lifetime evolution and explicit bounded
operation-input runtime path. Version-1 source and generated semantics remain
compatible. See [ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md).

The current Dependency Injection resolver retains its existing explicit
runtime-definition behavior. The Application Graph, Kernel, routes, middleware,
Web Application, Web Runtime, and PHP-CGI behavior remain unchanged and
caller-owned where composition is concerned. Kernel stays lifecycle-neutral.

## Explicit exclusions

Version 1 defines no routes, middleware order, HTTP/Kernel configuration,
scalar literals, environment loading, secrets, factories, aliases, decorators,
tags, conditions, profiles, plugins, extensions, lazy services, proxies,
property hooks, service location, runtime reflection, Graph schema/IDs, or
developer-agent mutation. Database/ORM, templates, events, queues, application
agents, telemetry, frontend, deployment, authentication, authorization, and
other later capability families remain outside Phase 5C.

Version 1 also defines no service lists, array/iterable/variadic or PHPDoc
collection injection, automatic multibinding, tags, priorities, or discovery.
Applications declare an ordinary concrete typed aggregate and give each member
its own `service` constructor edge in exact parameter order.

## Related material

- [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md)
- [Composition declaration engineering guide](../engineering/composition-declarations.md)
- [Application composition and build](application-composition-and-build.md)
- [Generated wiring](../engineering/generated-wiring.md)
- [Phase 5 roadmap](phase-5-roadmap.md)
- [Composition build artifact](composition-build-artifact.md)
- [ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md)
- [ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [Generated composition wiring](generated-composition-wiring.md)
