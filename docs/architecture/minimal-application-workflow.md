# Minimal application workflow

## Purpose and status

Phase 5G implements one supported copy-once minimal application skeleton and one Experimental reusable Composition build/check workflow. It connects the already implemented Phase 5D-5F boundaries; it does not add runtime dependency discovery, a new transport, or deployment behavior.

## Ownership

Inside this repository, Kongono owns every byte under `skeletons/minimal/`. After a developer copies the skeleton, all non-generated files are application-owned. Kongono does not automatically upgrade or merge them.

Two areas are derived:

- `vendor/`, installed by Composer; and
- content-addressed Composition JSON and generated PHP beneath `var/kongono/build/`.

Derived files are replaceable, but rebuild does not delete old identities. Cleanup is an explicit application-owner action.

## Build and check

```text
application source + Composer state + declaring symbols
                |
                v
      ApplicationBuildWorkflow
         |                 |
   canonical artifact   generated PHP
         |                 |
         +-- atomic content-addressed publication --+
```

`build()` requires an existing ordinary application root and an existing ordinary output directory strictly inside it. It builds and generates in memory, publishes through the existing no-clobber publishers, then safely reads and validates the exact published files again.

`check()` performs the same in-memory derivation, calculates both basenames directly, and admits only those exact files. It does not scan, create, write, rename, touch, lock, clean, or delete. Identical application state selects identical content identities. Changed admitted state requires new identities. A partial pair is never accepted.

## Runtime

```text
app:check -> exact generated loader -> generated application root
          -> Web Application -> Web Runtime -> PHP-CGI front controller
          -> one request -> generated shutdown -> process exit
```

The skeleton constructs one explicit route table, one home handler, one unmatched handler, one `RoutingRequestHandler`, and one `MiddlewarePipeline`. These are passed as a typed startup input; they are not discovered or generated.

The generated assembly receives, in canonical order, a fresh Kernel, a production-denied generated web bootstrap, and the explicit request-handler pipeline. Reflection remains bounded startup validation. Web Application owns one Kernel operation per request. Web Runtime owns delivery. PHP-CGI owns request conversion, emission, and front-controller failure composition. Generated shutdown calls Web Application cleanup once.

## One-request development run

`app:run` invokes the installed sibling `php-cgi` executable with a fixed local `GET /` CGI snapshot, validates the bounded response, emits `Hello from Kongono.` with its final LF, and exits. The snapshot carries the inert fixed query marker `kongono_proof=1` only so one-shot Windows PHP-CGI honors the command-line hardening settings; the route path remains `/`, and application behavior does not depend on the marker. It never binds a socket, selects a port, listens, forks, watches, supervises, manages workers, or claims deployment readiness.

## Failure and security

The workflow rejects missing, outside-root, linked/reparsed, aliased, oversized, partial, stale, renamed, tampered, or changing files. Diagnostic 1018 exposes only bounded fixed tokens. Application command boundaries emit deterministic messages without stack traces, absolute paths, native messages, rejected bytes, environment dumps, credentials, or secrets.

## External acceptance

`composer acceptance:phase5` copies the exact skeleton and lock-required local
packages into a fresh external workspace, installs with plugins and network
disabled, and proves the fixed 28-scenario success/failure/isolation/security
matrix. Check and run remain write-free; failures remain bounded and
non-disclosing; every owned temporary root is removed. See the
[Phase 5 external acceptance guide](phase-5-external-acceptance.md).

## Not implemented

There is no container, service locator, mutable current/latest pointer, route schema, request-time reflection, application installer, automatic skeleton upgrade, listener, development server, deployment automation, production-readiness certification, or Phase 6 work.
