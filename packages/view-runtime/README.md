# Kongono View Runtime

`kongono/view-runtime` is the optional Experimental Phase 18D execution leaf.
It loads one explicit exact-current Phase 18C artifact and renders it
synchronously into the neutral View Contract result types.

The package does not discover artifacts, parse or compile view source, provide
HTTP integration, retain operation state, or expose partial output. Generated
PHP and its deployment filesystem are trusted application inputs; the loader
still authenticates their caller-selected content identity and contained fixed
paths before the one permitted load.

See [Compiled View Runtime](../../docs/architecture/compiled-view-runtime.md)
and [ADR 0183](../../docs/adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md).
