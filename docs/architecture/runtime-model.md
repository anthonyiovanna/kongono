# Runtime Model

## Purpose

Kongono is persistent-runtime-first. Its core lifecycle must be safe when a process handles multiple requests, jobs, commands, messages, workflow steps, or other operations.

## Lifecycle principle

Framework design may not rely on process teardown to clear mutable state or release operation-specific resources. The Phase 0C Kernel makes operation lifetime explicit but deliberately carries no identity, authorization, tenant, request, error, telemetry, or other scoped data. Future state must have explicit ownership and cleanup.

This principle matters even when an application runs under PHP-FPM, because runtime-independent lifecycle contracts prevent assumptions that block long-running adapters later.

The Phase 0F resolver may be retained across operations because it stores only immutable definitions and constructs a new service object on every root and dependency edge. It contains no current-operation state and has no Kernel lifecycle relationship. Consumer constructors remain responsible for their own side effects, cleanup, and concurrency safety; shared and scoped service lifetimes remain open.

## Runtime adapters

Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime is a Kernel dependency, and Phase 0C selects or implements no adapter.

## Concurrency and isolation

The implemented Kernel permits overlapping operations and owns each opaque scope by object identity in an instance-local collection. Ending one scope cannot affect another. There is no global, static, thread-local, fiber-local, singleton, or other ambient current-operation state. This establishes lifecycle accounting without selecting concurrency primitives, reset hooks, propagation, cancellation, or adapter contracts.

## Failure

Invalid lifecycle actions throw a deliberate Kernel exception before state
mutation. Shutdown fails while active work remains, leaving the Kernel booted
and counts intact.

Every future operation boundary must guarantee scope and resource cleanup when
application work throws. Successful cleanup must not erase or replace the
primary failure, and cleanup failure must not be silently discarded. If cleanup
leaves runtime state uncertain, that worker or runtime instance must not quietly
accept further work. These are locked principles, not an implemented mechanism.

The exact cleanup mechanism, runtime adapter, cancellation and control-signal
types, recovery API, telemetry behavior, and representation of simultaneous
primary and cleanup failures remain open. No cleanup orchestration exists in the
current Kernel or `OperationScope` implementation.

## Performance

Persistent processes may reduce repeated boot cost, but performance cannot justify unsafe shared state or runtime coupling. Measurement and adapter-specific optimization belong to later implementation phases.

## Stability

Persistent-runtime-first design, runtime-independent Kernel boundaries, and the
failure-cleanup principles above are **LOCKED**. The minimal
boot/operation/shutdown lifecycle is implemented but **Experimental**. Scoped
data, cleanup APIs and orchestration, propagation, concurrency models beyond
overlapping identity ownership, cancellation, reset behavior, recovery, and
runtime adapters remain open.

## Related concepts

- [Microkernel](microkernel.md)
- [Architecture overview](overview.md)
- [ADR 0006](../adr/0006-persistent-runtime-first.md)
- [Error handling and actionable diagnostics](../engineering/error-handling.md)
