# Database Performance Baseline

## Purpose

The Phase 6 external harness records a small local observational baseline for
four bounded Data Platform operations. It helps a developer inspect the current
profile and notice changes worth investigating. It is not an SLA, performance
budget, regression threshold, benchmark ranking, portability claim, or
production-readiness certificate.

## Protocol version 1

| Field | Value |
| --- | --- |
| Schema | `kongono.phase6.performance-baseline` |
| Version | `1` |
| Timer | `hrtime(true)` |
| Unit | Integer nanoseconds |
| Warmup | 10 iterations per scenario |
| Samples | 50 measured iterations per scenario |
| Percentile | Deterministic nearest rank |

The fixed scenario order is:

1. `immutable_plan_compile`
2. `prepared_select_round_trip`
3. `generated_repository_find`
4. `transaction_begin_rollback`

Each measured callback performs the same bounded work on every iteration. Its
functional result is checked outside the timed interval where practical, and
the scenario leaves no growing state. A summary contains only `samples`,
`min_ns`, `p50_ns`, `p95_ns`, and `max_ns`, in that order. All values are
positive integers, `samples` is 50, and the timing values are nondecreasing.
Raw samples are not serialized and no fixed time ceiling is applied.

## Run and read the result

Use the exact acceptance command shown in the
[Phase 6 external guide](../architecture/phase-6-external-acceptance.md). Read
the profile and PostgreSQL major together with the protocol metadata. Compare
only runs whose relevant environment and protocol are compatible. Timing
equality is never expected and is not part of build or checkpoint identity.

Rerun and establish a fresh observation after a material hardware, operating
system, PHP, provider, provider configuration, framework, or workload change.
Treat a shift as a prompt for controlled investigation, not an automatic pass
or failure. Record the actual environment alongside any engineering analysis.

## Disclosure and lifecycle boundary

Measurement output contains no timestamp, machine name, username, filesystem
path, connection material, SQL, parameters, rows, private values, provider
messages, or raw samples. The helpers live under root tests and are not
production-autoloaded APIs. The protocol adds no runtime telemetry,
instrumentation, tracing, profiler, or persistent measurement store.

## Limits

The baseline does not claim zero overhead, universal portability, fastest
framework status, or superiority over Laravel, Symfony, or any competitor. It
does not justify a connection pool, automatic retry, hidden caching, or another
runtime mechanism.

## Related material

- [ADR 0086](../adr/0086-phase-6-external-data-platform-acceptance-and-checkpoint.md)
- [Phase 6 external acceptance](../architecture/phase-6-external-acceptance.md)
- [Database contracts engineering guide](database-contracts.md)
