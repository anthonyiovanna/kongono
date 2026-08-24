# Phase 5 External Application Acceptance

## Purpose and status

Phase 5H implements a root-owned, test-only external harness for the supported pre-1.0 application-composition slice. Run it with:

```text
composer acceptance:phase5
```

Success is one compact canonical JSON object followed by one LF and empty stderr. It reports 28 ordered PASS records, 28 passed, zero failed, and deterministic Composition, generated-wiring, and skeleton identities. Invalid invocation and failure use fixed bounded stderr and nonzero exits.

## External boundary

For each acceptance run, the harness:

1. creates and resolves one fresh GUID-named system-temporary root;
2. copies exactly the 15 skeleton-owned files;
3. mirrors the 15 lock-required Kongono packages and four already locked PSR
   dependencies as ordinary byte-identical files;
4. rewrites only the copied application's path-repository locations;
5. installs the locked application with plugins and network disabled;
6. invokes the copied `app:build`, `app:check`, and `app:run` scripts through Composer;
7. exercises `/missing` through the same generated application and PHP-CGI lifecycle; and
8. removes and proves absence of every harness-owned root in `finally`.

The repository, index, root lock, caller records, and permanent environment remain outside the temporary mutation boundary.

## Evidence matrix

The fixed matrix covers external install; first and repeated build/check/run; exact home and unmatched responses; write-free check/run; missing and invalid source; stale source; missing and tampered artifact; missing and tampered wiring; crossed pairs; renamed output without scan fallback; failed-build atomicity; sequential root isolation; operation retirement; offline success; nondisclosure; and owned cleanup.

Build must publish exactly one complete current JSON/PHP pair. Check and run derive that pair without scanning and write nothing. The home response is exactly `Hello from Kongono.\n`; the unmatched response is status 404 with exactly `Not Found.\n`. Repeated public output is byte-identical.

## Ownership retained

```text
exact build pair -> generated loader -> generated application boundary
                 -> Web Application -> Kernel operation per request
                 -> Web Runtime delivery -> PHP-CGI conversion/emission
                 -> generated reverse cleanup -> process exit
```

The harness observes these boundaries; it does not replace them. Kernel owns operations, Web Application owns application lifecycle, Web Runtime owns delivery, generated boundaries own generated cleanup, and PHP-CGI owns front-controller behavior.

## Failure and disclosure

Missing, stale, invalid, partial, tampered, crossed, renamed, linked, outside-root, or changing state fails closed. The harness compares raw streams and proves rejected path/source/native-error/secret markers and an unrelated offline secret never appear. Native traces are prevented from crossing command boundaries rather than claimed to be sanitized afterward.

## Limits

This is test evidence, not a production package, public API, CLI framework, installer, listener, deployment system, or application dependency. Phase 5 closure does not claim Stable API, 1.0, a full framework, production readiness, deployment certification, automatic upgrades, or Phase 6 authorization.

## Related material

- [ADR 0076](../adr/0076-phase-5-external-application-acceptance.md)
- [Application composition and build](application-composition-and-build.md)
- [Minimal application workflow](minimal-application-workflow.md)
- [Application workflow engineering guide](../engineering/application-workflow.md)
