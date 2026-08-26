# Persistence Performance Baseline

## Purpose

The Phase 7 external acceptance command records local observational timing for
four named persistence operations while it verifies their functional result.
This is test evidence, not production instrumentation, telemetry, an SLA, or a
time budget.

## Fixed protocol

```text
schema:              kongono.phase7.persistence-performance-baseline
version:             1
timer:               hrtime
unit:                nanoseconds
warmup iterations:   10 per scenario
measured samples:    50 per scenario
percentile method:   deterministic nearest-rank
```

The scenarios are emitted in this fixed order:

1. `identity_map_reuse`
2. `generated_repository_find`
3. `aggregate_repository_load`
4. `explicit_unit_of_work_flush`

Each summary contains exactly `samples`, `min_ns`, `p50_ns`, `p95_ns`, and
`max_ns`. All values are positive integers, `samples` is fifty, and the ordered
invariant is `min_ns <= p50_ns <= p95_ns <= max_ns`. Raw samples are never
serialized and no ceiling is imposed.

## Running and reading the evidence

Run the complete PostgreSQL 18.6 acceptance profile:

```text
composer acceptance:phase7 -- "C:\laragon\bin\postgresql\postgresql-18.6-1-windows-x64-binaries\bin"
```

Read the summaries as observations of that local run. They help identify the
shape and relative cost of the four accepted operations, but they do not prove
another machine, provider version, PHP build, operating system, configuration,
application model, or workload will behave identically.

## Responsible re-baselining

Re-run the complete acceptance profile after a material hardware, operating
system, PHP, PostgreSQL, configuration, framework, generated-evidence, domain
model, or workload change. Record the environment and summaries in the
checkpoint report. Do not compare raw-sample equality, silently adjust the
protocol, exclude a slow sample, weaken functional verification, or introduce a
threshold merely to obtain a pass.

Performance changes require investigation in context. This evidence makes no
zero-overhead, fastest-ORM, production-readiness, universal-portability, or
competitor-superiority claim.
