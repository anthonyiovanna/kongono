# Storage Application

This optional Experimental package composes one explicit storage profile source with the accepted deterministic profile builder, contained publisher, and exact-current runtime. The application supplies the profile source, output root, selected profile, adapter and authorizer directly. No provider or authorization policy is installed by default.

`plan` reads and validates the exact source and target without writing. `build` publishes the content-addressed artifact explicitly and validates its current bytes. `check` requires an existing exact-current artifact. The authorized wrapper calls the supplied application authorizer once per operation before delegating to the lower runtime. It retains no decision or request across calls. Caller-owned write sources remain untouched until delegation; completed read sources must be exhausted or explicitly closed by the caller.

The lower runtime remains directly available outside this wrapper. This package grants no object, filesystem root, credential, tenant, network, or production authorization policy by itself.
