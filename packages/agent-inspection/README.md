# Kongono Agent Inspection

This optional Experimental package provides deterministic, offline, read-only,
presentation-neutral inspection of one explicit Phase 13F Agent Application
profile and its exact generated wiring. A completed Phase 13E runtime result is
optional. The result contains only approved identities, bounded aggregate
counts, closed status values, booleans, and explicit limitation tokens.

The inspector revalidates the supplied wiring relative to the supplied profile
without loading it or running an application. It can project one inert neutral
Observation, one inert Security Audit evidence value, and an optional bounded
Phase 11G Application Graph view. It never records, exports, persists, or
establishes a relationship between the inspected principal and the Graph.

Chie, Hantei, and Oshie tokens address explain, verify, and teach views only.
This package does not depend on or execute those roles. Its verifier compares a
supplied view independently and returns fixed ordered evidence; PASS is not
Hantei execution, certification, authorization, or a grant.

Inspection does not observe live application or provider state, current
external authority, revocation, policy, deployment, or external effects. It
omits principal IDs, capability details, constraints, evidence details,
proposals, results, receipt details, generated PHP, paths, credentials, secrets,
and arbitrary caller content. Trusted generated and in-process PHP remains not
sandboxed.

See [ADR 0145](../../docs/adr/0145-visibility-safe-agent-principal-inspection-and-bounded-evidence.md)
and the [architecture guide](../../docs/architecture/agent-principal-inspection-and-evidence.md).
Phase 13H's separate root-owned
[external acceptance harness](../../docs/architecture/phase-13-external-acceptance.md)
exercises this package through production APIs without changing its behavior.
The harness remains test evidence and grants no inspection, Graph, projection,
developer-role, provider, model, or external-effect authority.
