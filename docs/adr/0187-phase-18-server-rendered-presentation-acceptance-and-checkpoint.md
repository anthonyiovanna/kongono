# ADR 0187: Phase 18 Server-Rendered Presentation Acceptance and Checkpoint

- Status: Accepted
- Date: 2026-09-13
- Phase: 18H

## Context

Phases 18B through 18G separately established the optional Experimental
neutral view contract, deterministic structured-source compiler, exact-current
runtime, completed-result PSR-7 adapter, supported application workflow, and
visibility-safe inspection leaf. Unit and integration coverage prove those
individual boundaries, but closure requires fresh-process evidence that the
real accepted stack composes without adding ambient authority, permissive
runtime behavior, retained state, or disclosure.

## Decision

Accept the root-test-only harness at `tests/External/View/run.php` and the
explicit `composer acceptance:phase18` command. The harness owns one unique
temporary application workspace and uses the real Phase 18 production packages.
It builds one explicit structured source repeatedly, proves canonical artifact
and generated-PHP identity, checks exact currentness without writes, and admits
only the explicit trusted runtime load. Generated PHP remains inert during
build and structural inspection.

The proof exercises HTML text and quoted-attribute escaping, closed schemas and
values, output and step bounds, iteration and structural bounds, sequential and
overlapping isolation, cleanup-failure evidence, and recovery with a healthy
later render. Stale, malformed, partial, mixed, additional, outside-root,
linked, changed, and no-clobber-conflict evidence fails closed. Unsupported URL,
JavaScript, CSS, raw-markup, and event-handler contexts are rejected rather than
silently generalized.

One completed result passes through the real Phase 18E adapter with exact
caller-owned status, reason phrase, ordered headers, HTML content type, body
bytes, and decimal `Content-Length`. Failed rendering remains a failure and is
never adapted as success.

The harness also copies the `view-minimal` skeleton into its owned workspace,
installs only localized locked packages with Composer networking disabled, and
proves direct and Composer parity for `view:build`, `view:check`, and `view:run`.
The run traverses the real routing, middleware, web-runtime, PHP-CGI,
HTTP-message, view-runtime, and response-adapter slice. Unmatched routing
remains an independent 404 response.

Phase 18G inspection structurally revalidates one exact supplied profile in
memory and is independently verified for the Chie, Hantei, and Oshie evidence
purposes. Graph relationship remains `not-established`, projections remain
`constructed-not-recorded`, and role labels execute no role or effect. Stable
diagnostic identifiers remain visible while rejected literals, paths, source
values, rendered values, headers, bodies, environment values, traces, and
temporary names remain undisclosed.

Public success is exactly one compact LF-terminated JSON line:

```text
{"status":"ok","profile":"kongono-phase18-server-rendered-presentation","case_count":N,"cleanup":"complete"}
```

`N` is the observed positive deterministic assertion count. Failure emits only
`Kongono Phase 18 external acceptance failed.` followed by LF and exits
nonzero. Standard streams are bounded to 1,048,576 bytes, every child duration
is finite, files and source structures are bounded by accepted APIs, and every
owned process and workspace is retired. Repeated sequential children, two
concurrent children, overflow, timeout, malformed-output, failure-then-health,
repository-fingerprint, and temporary-residue checks make cleanup failure
visible.

The proof uses no public network, ambient DNS, live server, provider, browser,
database, credential, or external service. The copied deployment files and
generated PHP are explicit trusted fixture inputs, not sandboxed content.

## Checkpoint

Phase 18A through Phase 18H are Accepted and Phase 18 is closed by one ordinary
non-amending local commit with subject
`Complete Kongono Phase 18 server-rendered presentation milestone` and parent
`e746a368ee884fd7475e1d74893d06903f6311a2`. Resulting commit and tree
identities belong to the post-commit correction report, not architecture.

Topology remains 95 production packages, 402 local production edges, and
acyclic. PHP inventories are 1,861 production, 962 package-test, and 96
root-test files.

## Security and evidence limits

Acceptance is deterministic local evidence for the exact checkpoint. It does
not establish public deployment, browser compatibility, accessibility,
performance, security certification, third-party template-engine
compatibility, production readiness, Stable API, 1.0 compatibility, or Phase
19 authority.

## Consequences

Phase 18 now has one explicit repeatable acceptance command and one exact
checkpoint. API-only, CLI, worker, agent, and headless applications remain
first-class and need not install any view package.

## Related material

- [Phase 18 server-rendered presentation acceptance](../architecture/phase-18-server-rendered-presentation-acceptance.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [Compiled-view application workflow](../architecture/compiled-view-application-workflow.md)
- [Compiled-view inspection and intelligence](../architecture/compiled-view-inspection-and-intelligence.md)
- [Phase 18 roadmap](../architecture/phase-18-roadmap.md)
- [ADR 0179](0179-server-rendered-presentation-and-compiled-view-boundary.md)
- [ADR 0180](0180-context-aware-output-safety-and-presentation-lifecycle.md)
- [ADR 0181](0181-neutral-view-contract-and-closed-presentation-values.md)
- [ADR 0182](0182-deterministic-structured-view-source-and-compiled-artifact.md)
- [ADR 0183](0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
- [ADR 0184](0184-explicit-completed-view-to-psr-7-response-adaptation.md)
- [ADR 0185](0185-supported-compiled-view-application-workflow.md)
- [ADR 0186](0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
