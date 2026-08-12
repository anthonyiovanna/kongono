# Kongono Documentation Standard

## Purpose

Kongono documentation must let a developer understand a component without reverse-engineering it. Brevity does not justify vagueness. Documentation should state present behavior precisely and label planned behavior, experimental mechanisms, and open decisions honestly.

## Context model

Use the relevant subset of these concerns for a concept, component, or public symbol:

| Concern | Question to answer |
| --- | --- |
| Purpose | Why does this exist? |
| Context | Where does it fit in Kongono? |
| Use when | What scenarios justify it? |
| Do not use when | What scenarios need something else? |
| Input | What is semantically expected? |
| Output | What is guaranteed? |
| Side effects | What database, filesystem, network, event, or state changes occur? |
| Security | What permissions, trust boundaries, sensitive information, or attack implications exist? |
| Lifecycle | What scope and persistent-runtime rules apply? |
| Concurrency | What happens during concurrent execution? |
| Failure | What errors and failure states are possible? |
| Performance | What costs or constraints matter? |
| Calling contract | What must be true before use? |
| Extension contract | May extensions replace, use, or implement it? |
| Agent contract | May agents interact with it, and under what authority? |
| Stability | Is it Internal, Experimental, Public, Stable, or Deprecated? |
| Related concepts | What other framework concepts or documents provide context? |

Not every symbol needs every concern. Coverage should reflect complexity, exposure, security sensitivity, lifecycle risk, and non-obvious behavior. Public and security-sensitive contracts need more context than self-explanatory private details.

## Comments and names

Prefer, in order:

1. A good type.
2. A good name.
3. A good structure.
4. A contextual comment where necessary.

Do not use comments such as "Increment count," "Handle the request," "Get the value," or "Process the data." They repeat code without conveying knowledge. Useful comments explain why a choice exists, architectural context, invariants, security requirements, non-obvious side effects, lifecycle constraints, concurrency constraints, deliberate tradeoffs, or compatibility history.

## Relationships

Human-authored documentation should explain conceptual relationships, not attempt to maintain exhaustive lists of callers, callees, dependencies, emitted events, or consumers. Such lists become stale. The planned Application Graph is intended to supply authoritative machine-derived relationships.

## Documentation identity and links

Kongono intends to support stable documentation identifiers connecting a source symbol, Application Graph node, documentation concept, IDE, CLI, and AI tooling. A conceptual example is `principals.resolution`, but no syntax or mechanism is selected. Documentation IDs are experimental. Do not add custom PHP attributes, annotations, or stable identifiers until authorized.

Future navigation may connect a symbol or comment to version-correct documentation explaining purpose, usage, examples, security, warnings, non-use cases, and related concepts. The linking mechanism remains open.

## Testability

Documentation should be written so future tooling can validate broken references, invalid examples, missing public API documentation, missing security coverage, deprecated API references, and invalid symbol links. Phase 0A does not implement those checks.

## Stability language

- **Internal:** not a supported consumer contract.
- **Experimental:** mechanism or API may change and must not be treated as stable.
- **Public:** intended for consumers but not necessarily stable unless explicitly stated.
- **Stable:** governed by an approved compatibility policy; no such framework API exists in Phase 0A.
- **Deprecated:** still present temporarily but scheduled for replacement or removal under an approved policy.

Always distinguish repository reality from planned architecture. Avoid present-tense claims that imply unimplemented framework functionality exists.
