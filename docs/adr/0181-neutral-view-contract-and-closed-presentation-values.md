# ADR 0181: Neutral view contract and closed presentation values

- Status: Accepted
- Date: 2026-09-12
- Phase: 18B

## Context

The accepted Phase 18A architecture requires a provider-neutral lower contract
before any syntax, compiler, artifact, loader, renderer, escaper, HTTP adapter,
application workflow, command, or inspection capability can be considered.
That contract must not make server-rendered presentation mandatory or allow a
template to receive ambient application state.

## Decision

Add the optional Experimental `kongono/view-contract` leaf. It depends only on
PHP, `kongono/api-metadata`, and `kongono/diagnostics`; no existing production
package depends on it.

The package distinguishes canonical logical-view and render-operation
identities from a lowercase SHA-256-shaped compiled-view identity. Logical
identities are never paths. Compiled identities are inert evidence and confer
no authority to discover, select, load, or execute an artifact.

Presentation input is a closed recursive domain of string, integer, boolean,
null, list, and record values. Lists, records, root contexts, strings, depth,
and aggregate nodes are bounded. Records and schemas are canonicalized by
ordinal field name. No arbitrary object graph, service, entity, closure,
resource, capability, or ambient application value crosses this boundary.

Recursive schemas declare exact kinds, required and optional record fields,
string byte bounds, and list item/count bounds. A render request validates its
root `ViewContext` against its `ViewContextSchema` during construction. This
typing is structural evidence; it does not authorize values or make them safe
for every output context.

The initial output-context vocabulary contains only HTML text and quoted HTML
attributes. URL, JavaScript, CSS, raw markup, unquoted attributes, and event
handlers remain unsupported. The enum performs no escaping.

Render requests, completed output, derived metadata, results, failures, limits,
and lifecycle states are immutable bounded evidence. Output metadata is derived
from exact caller-supplied bytes. Lifecycle values do not implement a runtime,
lease, state machine, renderer, or cleanup effect. Diagnostics expose only a
closed Safe vocabulary and never rejected names, values, identities, output,
digests, paths, sources, class names, or throwable text.

## Consequences

API-only, CLI, worker, agent, and headless applications remain first-class and
install no view package. Phase 18B supplies the vocabulary future presentation
packages may depend on, while retaining Experimental upgrade freedom.

The package does not establish exact-current artifact selection, live runtime
behavior, delivery, authorization, security policy, compatibility,
certification, performance, production readiness, frontend integration, mobile
integration, or deployment support.

## Rejected alternatives

- Passing associative arrays or arbitrary DTOs to templates was rejected
  because it creates an open authority and type boundary.
- Treating a logical identity as a filesystem path was rejected because view
  selection and loading are separate future responsibilities.
- Treating a compiled digest as loading authority was rejected because a digest
  alone cannot prove containment, exact-current selection, or safe execution.
- Adding URL, JavaScript, CSS, raw-markup, unquoted-attribute, or event-handler
  contexts was rejected until separately specified compiler and escaping work.
- Implementing one generic `escape()` contract was rejected because safety is
  contextual and no escaper exists in this phase.
- Depending on HTTP, routing, DI, kernel, filesystem, or template libraries was
  rejected to preserve the optional neutral leaf.
- Adding parsing, compilation, generation, loading, rendering, caching,
  watching, commands, inspection, or deployment was rejected as future scope.
- Marking the boundary Stable was rejected because Phase 18B intentionally
  retains upgrade freedom.

## Follow-up

Phase 18C-H remain Planned and each requires separately authenticated architect
authority.
