# Kongono Extension Host

This Experimental package is the bounded trusted/in-process runtime boundary
for one exact Phase 12E generated extension assembly. It accepts one caller-
selected content-addressed local PHP file only after exact artifact, grant-plan,
path, byte, class, accessor, implementation, and lifecycle-shape validation.
The loader performs one local `require`; it does not scan, select, repair,
retry, change autoloading, or fall back.

Application extensions acquire resources through `start()` in canonical order
and release them through `stop()` in reverse order. Each explicit operation
receives one fresh generated assembly and fresh extension instances, calls
`begin()` in canonical order, and calls `close()` in reverse order. Operation
handles own exact leases, detach all operation-local objects before returning,
and are idempotent. Shutdown is explicit, refuses active leases, changes state
before reverse cleanup, detaches runtime objects, and never retries.

Activation or cleanup uncertainty retires the host for new operations while
preserving explicit shutdown after outstanding operations close. A work failure
whose cleanup succeeds does not retire it. Exact primary and cleanup throwables
are preserved using the documented deterministic composition matrix.

Use this package only when an application already owns the exact current Phase
12E artifact, matching grant plan, generated file path, provider objects, and
trusted implementation classes. Constructors establish object invariants;
hosted resource acquisition belongs in lifecycle methods so successful prefixes
can be accounted for and rolled back.

Trusted/in-process PHP is not sandboxed. Lifecycle interfaces and capability
providers account for Kongono-supplied collaboration edges but cannot prevent
ordinary PHP from using the full technical power of its process. This package
adds no registry, arbitrary dispatch, event bus, authorization, Graph mutation,
general Composition integration, CLI, developer intelligence, external
protocol, marketplace, or distribution behavior.

Stability: Experimental, except the Public package exception marker. Kongono
remains pre-1.0 and not production-ready. Phase 12G inspection is implemented
separately and does not observe this host, load through it, or establish host
state or execution.
