# ADR 0149: Deterministic Model-Invocation Plan Artifact

- Status: Accepted
- Date: 2026-09-01
- Stability: Experimental implementation contract

## Context

The neutral Phase 14B request vocabulary can describe one bounded model
invocation without choosing or contacting an adapter. A later runtime needs
deterministic coordination evidence that binds an exact request, an
application-selected adapter label, and finite execution limits without
turning message content into a broadly disclosed build artifact or implying
provider authority.

## Decision

Create the optional `kongono/model-invocation-build` package. One complete
typed `InvocationPlanSource` binds an exact version-1 `InvocationRequest`, one
canonical plan identity, one canonical application-owned adapter identity, and
one finite `InvocationExecutionBudget` whose attempt ceiling is exactly one.

The builder hashes a canonical full request projection, including raw messages
and its terminal LF, but does not retain or publish that projection. It hashes a
second canonical source projection and publishes a compact canonical artifact
containing only the plan/profile/request metadata, budget, and request/source
digests. The complete artifact hash is computed over the exact artifact bytes
and is used in the publication filename.

Structural validation is strict about framing, shape, order, versions, values,
capability compatibility, budgets, and canonical re-encoding. It does not claim
to reconstruct omitted messages. Current validation first validates structure,
then rebuilds from one explicit current source and requires byte-identical
artifact and digest evidence.

Publication revalidates before I/O and accepts one explicit existing canonical
ordinary writable directory. It uses same-directory exclusive temporary
creation, complete checked writes and sync, hard-link publication without
overwrite, idempotent identical-target acceptance, conflict rejection, and
complete temporary cleanup.

## Consequences

The artifact is deterministic and privacy-preserving relative to raw message
storage, but its identities, budgets, and hashes remain Protected evidence.
Hashes do not authenticate content or make it Safe. Exact-current validation is
relative only to the supplied source and says nothing about live provider,
model, account, adapter, credential, price, or capability state.

The package adds three local production dependencies and produces the accepted
71-package, 265-edge acyclic graph. No existing production package depends on
it.

## Rejected alternatives

- Publishing raw messages would unnecessarily widen disclosure.
- Ambient discovery or newest-file selection would make currentness and
  adapter choice non-deterministic.
- Provider calls, credentials, retries, runtime receipts, and Agent bridging
  belong to separately authorized later phases.
- Overwrite or update-in-place publication would weaken content-addressed
  evidence and no-clobber behavior.
