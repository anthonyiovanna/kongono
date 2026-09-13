# Kongono View Build

`kongono/view-build` is the optional Experimental Phase 18C package for
deterministic compilation of one explicit structured view-source byte sequence.
It validates a closed version-1 grammar, produces a canonical model and source
map, emits syntax-validated ordinary PHP without executing it, and publishes an
immutable two-file content-addressed artifact.

The package performs no source discovery, generated-code loading, rendering,
runtime escaping, HTTP adaptation, command handling, or application workflow.
Generated PHP is trusted in-process code evidence, but Phase 18C never includes,
requires, or executes it.
