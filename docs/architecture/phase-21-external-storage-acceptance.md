# Phase 21 External Storage Acceptance

Phase 21H supplies a root-owned local acceptance harness for the optional
Storage Platform. Its design is recorded in
[ADR 0214](../adr/0214-phase-21-external-storage-acceptance-and-checkpoint.md).
The local result and security audit are distinct from architect acceptance and
the exact Phase 21 checkpoint.

## Running the local proof

From the repository root, run `composer acceptance:phase21` with the supported
PHP `>=8.5.0 <8.6.0` profile and installed locked dependencies. The script
calls `tests/External/Storage/run.php` without a provider or network. The
runner requires its three sibling classes explicitly and uses the installed
root autoloader. On success it emits one bounded JSON object with schema
`kongono.phase-21-storage-acceptance`, version 1, profile
`storage-platform-local/1`, twelve closed check names, and a count of twelve.
The JSON is a completed local proof result, not an authorization token.

The twelve groups are:

| Group | Evidence |
| --- | --- |
| `contract-build` | Repeatable canonical profile bytes, independent identities, exact-current rejection and finite bounds. |
| `publication` | Digest path, idempotence, no-clobber conflicts, containment and outside sentinel. |
| `authorization` | Denial before effects, typed collaborator failure, exact request delegation and later health. |
| `local-operations` | Real contained create/read/replace/delete/list bytes, versions and distinct outcomes. |
| `outcome-truth` | Scripted unavailable and uncertain results, exact primary/cleanup failure identity. |
| `stream-lifecycle` | Bounded chunks, bytes/digest, EOF, early close, invalid streams and source retirement. |
| `runtime-isolation` | Reentry rejection and sequential/interleaved operation health. |
| `application-parity` | Explicit plan/check/apply commands, current artifact, copy-once example and no clobber. |
| `inspection` | Offline revalidation, 17 outcome counts, Safe/Protected projections, role and Graph limits. |
| `nondisclosure` | Five inspection diagnostic families plus representative real entry points for build, local adapter, runtime and application under both trace modes. |
| `process-integrity` | Fresh-process identity/byte equality, bounded raw streams, timeout and failure retention. |
| `cleanup` | Exact owned ledger, complete resource retirement and unchanged protected repository files. |

An isolated `--capture-self-test` mode verifies nine negative and lifecycle
cases for the process/file capture helper before the full acceptance run. Its
test child is bounded and retired before the next case. The complete runner
records failure details in its private temporary root and emits no success
JSON on failure. Raw streams and metadata belong to the invoking validation
environment; preserve them when a gate fails. Successful owned workspaces
are removed only after their exact ledger is acknowledged. Unexpected entries
cause cleanup refusal and remain visible for review.

## Ownership and limits

Application code supplies the authorizer; a strict true decision is required
before the runtime transfers an operation to the adapter. A direct local
adapter write leaves the supplied source with its caller. The guarded runtime
closes an accepted source exactly once on completion, denial after transfer,
or failure. A read closed before verified EOF is cancellation, not completed
content. Cleanup failure cannot erase the primary failure. The local adapter
supplies ordinary contained filesystem evidence; uncertainty tests use a
closed scripted adapter and make no provider claim.

The proof uses one host and does not establish snapshot listing, distributed
coordination, durable storage, live credentials, cloud compatibility,
availability, throughput, or production readiness. A Windows host may deny
symlink creation; that conditional case is recorded as a capability limit,
not a successful link attack. Private throwable causes and raw traces remain
private; public messages, diagnostic context, Safe fields and JSON are checked
for disclosure under effective trace settings 0 and 1.

The broader local gate also runs whole-repository analysis and tests, eight
unchanged earlier acceptance profiles, Composer validation of root/packages/
skeletons, platform requirements, Knowledge checks, style and preservation.
The separately approved network security audit, architect acceptance and
checkpoint remain pending. This guide grants no permission to run network
commands, select a cloud provider, stage or commit changes, or start Phase 22.
