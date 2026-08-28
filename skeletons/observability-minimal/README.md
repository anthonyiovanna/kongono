# Minimal Observability Application

This directory is a copy-once example of Kongono's supported Phase 10G
observability build, check, and application workflow. Kongono owns these bytes
only while they remain in the framework repository. After copying the
directory, every non-generated file is application-owned; Kongono does not
merge, patch, watch, upgrade, or overwrite the copy.

Run `composer install`, then `composer app:build`, `composer app:check`, and
`composer app:run`. Build publishes one content-addressed observability
configuration artifact into the existing `var/kongono/build` directory. Check
performs no writes and rejects missing, stale, renamed, or tampered evidence.
Run checks that exact evidence, constructs all runtime collaborators explicitly,
records one trace, metric, operational event, and distinct security-audit item,
flushes, and closes the application.

`ExampleTraceClock`, `ExampleOpenTelemetryClock`,
`ExampleTraceIdentifierSource`, and `RecordTraceSampler` are deterministic
examples, not production implementations. Replace them with reviewed
application-owned collaborators. `CapturingOtlpTransport` stores only a small
bounded set of payloads during one process and performs no network access. It
is not an exporter, provider, client, collector, or production no-op.

The configuration deliberately contains no endpoint, URL, credential, header,
token, provider, sampler, or environment lookup. Runtime transport and policy
remain explicit application choices. Generated output and `vendor/` are
derived and ignored. The scripts do not clean or overwrite outputs, create
directories, disclose payloads or identifiers, or print native exception text.
