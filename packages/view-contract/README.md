# Kongono View Contract

`kongono/view-contract` is an optional Experimental leaf containing neutral,
immutable contracts for a future compiled server-rendered presentation layer.
Applications that do not render views do not need this package.

The package defines logical, compiled, and operation identities; a closed
recursive presentation-value domain; closed schemas; two initial HTML output
contexts; explicit render limits; and bounded request, completed-result, and
failure evidence. A `ViewContextSchema` validates a `ViewContext` when a
`RenderRequest` is constructed.

It does not parse or compile templates, generate or load PHP, render or escape
output, inspect the filesystem, perform I/O, select routes, adapt HTTP
responses, or grant execution or security authority. A compiled-view identity
is evidence only and a logical-view identity is never treated as a path.

All consumer-facing declarations are Experimental. The package exception
marker is Public only so callers can catch the family without coupling to a
concrete subtype. Upgrade freedom is retained until the boundary is accepted
as stable in a separately authorized phase.
