# Phase 18 Server-Rendered Presentation Acceptance

## Purpose and command

Phase 18H closes the accepted Phase 18 milestone with one root-owned local
external-process harness. Run it explicitly with:

```text
composer acceptance:phase18
```

The command is deliberately outside `composer quality` and the eight canonical
Hantei checks because it launches bounded child processes and creates a copied
test application.

## Trust model

Each success child creates one uniquely named `kongono-phase18-*` workspace
directly beneath the system temporary directory. The harness writes and removes
only that owned tree. Structured source, copied skeleton files, localized
locked packages, deterministic artifacts, and generated PHP are explicit
trusted fixture inputs. They do not grant identity, authentication,
authorization, capability, policy, Graph, or effect authority, and trusted PHP
is not a sandbox.

Composer in the copied application has network access disabled and receives no
proxy variables. The harness uses no public network, ambient DNS, listener,
live server, provider, browser, database, credential source, or external
service.

## Scenarios

The direct production-stack scenario proves repeated equivalent source builds
produce identical canonical metadata and generated PHP; `view:check` is
write-free; malformed, partial, mixed, stale, changed, additional, outside-root,
linked, and no-clobber-conflict evidence fails closed; and generated PHP is not
loaded during planning, building, checking, or structural inspection. Only the
explicit exact-current application factory performs the trusted load.

Rendering uses closed schema-declared presentation values. HTML text and quoted
HTML attributes escape independently. URL, JavaScript, CSS, raw markup, and
event-handler contexts remain unsupported. Missing, extra, duplicate,
wrong-type, oversized, over-depth, over-iteration, over-component, over-step,
and over-output cases fail within accepted bounds without partial success.
Sequential success and failure calls release operation state, and fresh
concurrent children produce byte-identical results.

A completed render becomes a PSR-7 response only through the real Phase 18E
adapter. The caller owns status, reason phrase, headers, and browser-security
policy; the adapter adds exact HTML content type and decimal `Content-Length`
for the completed body. A failed render is returned as failure and creates no
successful response.

The copied `view-minimal` scenario installs only local locked packages, then
runs direct and Composer forms of `view:build`, `view:check`, and `view:run`.
Their outcomes and accepted outputs agree. The CGI run crosses the real routing,
middleware, web-runtime, PHP-CGI, HTTP-message, compiled-view runtime, and
response-adapter path. A separate unmatched request remains a plain 404.

One Phase 18G inspection structurally revalidates exact supplied in-memory
profile evidence without loading generated code or reading rendered values.
Independent Chie-, Hantei-, and Oshie-addressed views verify successfully while
role execution remains `not-performed`, Graph relationship remains
`not-established`, and projections remain `constructed-not-recorded`.

## Output, failure, and cleanup

Success emits exactly one compact LF-terminated line:

```text
{"status":"ok","profile":"kongono-phase18-server-rendered-presentation","case_count":N,"cleanup":"complete"}
```

`N` is a positive observed assertion count and is not fixed in documentation.
Successful stderr is empty. Any failure emits only:

```text
Kongono Phase 18 external acceptance failed.
```

The harness caps stdout and stderr at 1,048,576 bytes and imposes finite child
durations. Accepted package APIs bound source sizes, file counts, source depth,
views, values, iteration, render steps, and output. Adversarial literals,
application and repository paths, source and rendered values, headers, bodies,
environment values, identities, temporary names, exception messages, and
traces never enter public output.

The parent requires clean retirement after ordinary failure, malformed output,
stream overflow, and timeout; then it requires a healthy later run. It compares
repository status and temporary-residue fingerprints before and after repeated
sequential and concurrent children. A cleanup failure is terminal and visible
only through the fixed public failure contract.

## Regression and checkpoint meaning

The command is the root-owned composition regression above package unit and
integration tests. [ADR 0187](../adr/0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md)
accepts it. Phase 18A-H are Accepted and Phase 18 is closed by the checkpoint
subject `Complete Kongono Phase 18 server-rendered presentation milestone` with
parent `e746a368ee884fd7475e1d74893d06903f6311a2`. The correction report records
the resulting commit and tree identities.

Topology remains 95 packages / 402 local production edges / acyclic, with PHP
inventories 1,861 production / 962 package-test / 96 root-test.

## Non-claims

Passing does not establish public deployment, browser compatibility,
accessibility, performance, security certification, third-party engine
compatibility, production readiness, Stable API, 1.0 compatibility, release
readiness, or authority to begin Phase 19.

## Related material

- [Server-rendered presentation](server-rendered-presentation.md)
- [Compiled-view build](compiled-view-build.md)
- [Compiled-view runtime](compiled-view-runtime.md)
- [Compiled-view HTTP](compiled-view-http.md)
- [Compiled-view application workflow](compiled-view-application-workflow.md)
- [Compiled-view inspection and intelligence](compiled-view-inspection-and-intelligence.md)
- [Phase 18 roadmap](phase-18-roadmap.md)
