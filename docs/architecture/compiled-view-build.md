# Compiled View Build

## Status and boundary

Phase 18C accepts [ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and implements the optional Experimental `kongono/view-build` package above the
accepted neutral `kongono/view-contract` leaf. Phase 18A-D are Accepted. Phase
18E-H remain Planned and unauthorized. Phase 18D's separately accepted
[compiled-view runtime](compiled-view-runtime.md) consumes one exact published
artifact without calling this package's parser, builder, emitter, publisher, or
artifact validator.

View Build is an explicit deterministic build boundary. It parses caller-
supplied bytes, validates a closed source and schema language, derives a
canonical model and complete source map, emits inert ordinary PHP, validates
structure/currentness without executing that PHP, and optionally publishes an
immutable content-addressed two-file directory. It supplies no loader, renderer,
runtime escaping, HTTP adapter, application workflow, command, cache, watcher,
or discovery mechanism; the accepted runtime operations live only in the upper
optional `kongono/view-runtime` package.

## Package direction

```text
kongono/view-build
  -> kongono/api-metadata
  -> kongono/diagnostics
  -> kongono/view-contract
```

No existing production package depends on View Build. The final Phase 18C graph
contains 91 production packages and 380 local edges and is acyclic. Optionality
is preserved: API-only, CLI, worker, agent, and headless applications install no
view package unless they choose the capability.

## Exact source contract

`ViewSourceParser::parse(string $sourceBytes): ViewSourceSet` accepts one direct
byte sequence. It performs no file, directory, package, environment, process,
network, Graph, registry, or service lookup.

The document is valid UTF-8 JSON with no BOM, CR, NUL, disallowed control byte,
duplicate member, float, comment, or trailing token. It has exactly one terminal
LF, is 1 through 1,048,576 bytes, and has maximum JSON depth 64. The root keys
are exactly `schema`, `version`, and `views`, with schema
`kongono.view-source-set` and version `1`.

Each of 1 through 128 views has exactly `id`, `context`, and `body`. Context is
a closed record schema made recursively from:

- `string` with `max_bytes` 0 through 65,536;
- `integer`, `boolean`, or `null`;
- `list` with `max_items` 0 through 256 and one item schema; or
- `record` with unique canonical ordinal fields containing `name`, `required`,
  and `schema`.

Bodies contain only these nodes:

- `text` with bounded static data;
- `value` with a build-resolved string reference;
- `element` with one fixed lowercase name, canonical fixed-name quoted
  attributes, and child nodes; and
- `each` with one list reference, non-shadowing alias, explicit maximum, and
  body.

Root references use `$.field`; loop references use `@alias` and both may
continue through required record fields. Every value or dynamic attribute must
resolve exactly to a string schema. Every loop must resolve exactly to a list,
and its maximum cannot exceed the declared list maximum. There are no implicit
conversions.

Version 1 admits only the structural elements `article`, `aside`,
`blockquote`, `code`, `dd`, `div`, `dl`, `dt`, `em`, `figcaption`, `figure`,
`footer`, `h1` through `h6`, `header`, `li`, `main`, `nav`, `ol`, `p`, `pre`,
`section`, `small`, `span`, `strong`, and `ul`. Attributes are limited to
`aria-describedby`, `aria-hidden`, `aria-label`, `aria-labelledby`, `class`,
`data-testid`, `dir`, `id`, `lang`, `role`, and `title`.

The build enforces 4,096 total nodes, structural depth 32, loop depth 8, 16
attributes per element, 65,536 bytes per text/literal, 1,048,576 aggregate
static bytes, and 256 bytes/16 segments per pointer or reference. Unsupported
HTML contexts and all expressions, calls, arbitrary PHP, raw markup, dynamic
names, components, inheritance, macros, and includes fail closed.

## Context-aware static encoding

Static text is data. In HTML text, `&`, `<`, and `>` become `&amp;`, `&lt;`, and
`&gt;`. In a quoted attribute, those mappings also encode `"` as `&quot;` and `'`
as `&#039;`. Dynamic strings remain typed values and are sent with exactly
`OutputContext::HtmlText` or `OutputContext::HtmlQuotedAttribute`; Phase 18C
does not escape or render them.

## Canonical model, map, and identities

The semantic model ordinal-sorts views, fields, and attributes and owns a
contiguous zero-based operation sequence per view. Its source map contains no
source value: only logical identity, operation index, canonical JSON pointer,
closed operation kind, and optional output context.

Four lowercase SHA-256 values remain distinct:

1. source identity over exact accepted source bytes;
2. model identity over exact canonical model JSON;
3. generated identity over exact ordinary PHP bytes; and
4. compiled-view identity over exact artifact metadata bytes.

Metadata uses compact canonical UTF-8 JSON and embeds the complete model under
fixed schema, version, builder, identity, generated-PHP, and view keys. Model,
metadata, and generated PHP are each bounded to 2,097,152 bytes.

## Generated protocol remains inert

The emitted class is
`Kongono\Generated\View\CompiledViewSet_<full-model-sha256>`. It defines only
the public static `sourceSha256()`, `modelSha256()`, and `emit()` methods, uses a
closed logical-identity `match`, direct checked value access, bounded direct
loops, and the Internal `RenderSink` operations `step()`, `literal()`, and
`value()`. Each structural node and loop iteration is stepped; all sink calls
carry contiguous operation evidence.

Generated PHP contains no source path, timestamp, random value, ambient state,
dynamic include/callable, closure, reflection, filesystem/network/process call,
output buffer, header, session, or service access. Validation uses
`token_get_all(..., TOKEN_PARSE)` and exact regeneration. Production and tests
do not include, require, autoload, reflect, instantiate, evaluate, or execute the
generated class in Phase 18C. It is trusted future in-process code, not a
sandbox.

## Validation and publication

Structure validation checks exact framing, duplicate-free canonical metadata,
key order, closed versions and vocabulary, model reconstruction, all hashes,
generated class and syntax, forbidden surfaces, and byte-identical regeneration.
Currentness first validates structure and then rebuilds from one explicit
current source; every byte sequence and identity must agree.

Publication revalidates before I/O. The caller supplies one existing absolute
canonical ordinary writable directory. The publisher creates
`kongono-view-v1-<artifact-sha256>` through one exclusive same-parent temporary
directory, complete checked writes and syncs, then one rename. The target has
exactly `artifact.json` and `compiled.php`. Existing output is accepted only
when ordinary, non-linked, stable, exact, and byte-identical. It is never
overwritten, merged, repaired, discovered, or selected by recency.

## Failure and disclosure

Six diagnostics, `KGN-VIEW-BUILD-1810` through `1815`, cover invalid source,
unsupported grammar/context, exceeded bounds, invalid artifact, stale artifact,
and publication failure. Exceptions preserve an exact previous throwable where
authorized but never print its message or trace. Raw source, text, values,
generated/metadata bytes, rendered output, and private paths never enter
diagnostic context or exception stringification.

Artifacts, digests, generated PHP, and successful publication are evidence.
They grant no identity, authorization, capability, credential, network,
filesystem, rendering, HTTP, or application-effect authority and make no
Stable, 1.0, compatibility, performance, certification, or production-readiness
claim.
