# Kongono AI-Agent Operating Policy

This file is permanent repository policy for AI coding agents. It applies to every file beneath the repository root.

## Agent role

**Executor, not architect.** Implement only explicitly authorized work. Architectural direction comes from the architect and accepted project directives.

## Mandatory stop-and-ask rule

If architecture or authority is unclear:

```text
STOP
ASK
DO NOT GUESS
```

Stop before making an unapproved architectural, security, public-API, package-boundary, dependency, protocol, runtime, compatibility, licensing, or data-model decision. Report:

1. The exact decision required.
2. Why safe progress depends on it.
3. Viable options.
4. Tradeoffs of each option.
5. A clearly labeled recommendation.

Do not implement the recommendation until approval is returned.

## Scope discipline

Do not:

- Refactor unrelated code.
- Introduce unrequested dependencies.
- Implement future phases.
- Rename or reinterpret approved architecture.
- Change package boundaries.
- Replace chosen technologies.
- Create public APIs casually.
- Treat a planned capability as implemented.
- Stabilize an experimental mechanism by convention.

Prefer the smallest correct implementation. Public APIs carry long-term compatibility obligations; speculative abstractions are not progress.

## Decision semantics

### LOCKED

A locked identity, doctrine, boundary, or decision cannot change without explicit architect approval. An agent may document or implement it only within authorized scope.

### DEFAULT

A default is the preferred implementation choice. A deviation requires explicit justification and architect approval whenever it has architectural impact.

### EXPERIMENTAL

An experimental concept is prototype territory. Its exact mechanism, syntax, schema, or API may change. Do not prematurely stabilize it, expose it as a durable public contract, or infer missing semantics.

## Engineering principles

### Explicit over magical

Prefer typed, inspectable, deterministic, machine-readable, and statically verifiable behavior. Do not introduce framework-wide hidden magic merely to reduce syntax.

### AI may suggest; Kongono must know

An LLM must never be the authoritative mechanism for authorization, migration safety, application structure, dependency legality, API contracts, security policies, or module boundaries. AI may assist, but Kongono's deterministic mechanisms must own correctness-critical information.

### Framework invariants over arbitrary choice

Application architecture may remain flexible while framework invariants stay strong. Planned invariants include typed dependency injection, the Application Graph, structured CLI behavior, operation scopes, architecture validation, security metadata, telemetry, and machine-readable contracts. Their presence here does not authorize their implementation.

## Documentation doctrine

Documentation must explain context rather than restating a name or a line of code. For a component or public concept, include the relevant subset of:

- **Purpose:** why it exists.
- **Context:** where it fits in Kongono.
- **Use when:** scenarios that justify it.
- **Do not use when:** situations that need another concept.
- **Input:** semantic expectations.
- **Output:** guarantees.
- **Side effects:** database, filesystem, network, events, and state changes.
- **Security:** permissions, trust boundaries, sensitive data, and attack implications.
- **Lifecycle:** scope and persistent-runtime implications.
- **Concurrency:** simultaneous-execution concerns.
- **Failure:** errors and failure states.
- **Performance:** material costs and constraints.
- **Calling contract:** required preconditions.
- **Extension contract:** whether extensions may replace, use, or implement it.
- **Agent contract:** whether and how agents may interact with it.
- **Stability:** Internal, Experimental, Public, Stable, or Deprecated.
- **Related concepts:** relevant framework and documentation concepts.

Not every symbol needs every heading. Apply the standard in proportion to complexity, exposure, risk, and non-obvious behavior.

Never write comments that merely narrate code, such as "Increment count," "Handle the request," "Get the value," or "Process the data." Prefer, in order: a good type, a good name, a good structure, and then a contextual comment where necessary. Useful comments explain intent, architectural context, invariants, security, non-obvious side effects, lifecycle or concurrency constraints, deliberate tradeoffs, or required compatibility history.

Do not manually maintain exhaustive caller/callee lists in comments. Explain conceptual relationships; the planned Application Graph will eventually provide authoritative machine-derived relationships.

Stable documentation identifiers are intended to connect source symbols, Application Graph nodes, documentation, IDEs, CLI tools, and AI tools. Their exact syntax and mechanism are experimental. Do not add custom attributes or annotations for them without authorization.

Planned documentation validation may check references, examples, public API coverage, security documentation, deprecations, and symbol links. Do not claim these checks exist until implemented.

## PHP engineering quality requirements

After changing first-party PHP code or PHP-based repository configuration, run the relevant focused checks. Before declaring a PHP implementation task complete, run `composer quality` unless the governing directive explicitly exempts that task. Report the checks actually run and any failure or environmental limitation.

AI executors must never:

- Create a PHPStan baseline without architect approval.
- Suppress or ignore a static-analysis finding merely to obtain a green build.
- Weaken Composer dependency-security policy or add dependency-policy exceptions.
- Authorize a Composer plugin.
- Add a dependency without explicit directive authorization.
- Manually modify `composer.lock`; Composer must generate it.
- Conceal a failed check by skipping, excluding, or weakening it.

Every future suppression, policy exception, or quality-gate exemption requires explicit architect approval and must be reported in the completion handoff with its reason and scope.

## Required completion report

Every implementation directive must be handed off with this structure, adapted only when the governing directive explicitly requires another format:

```text
TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
What was implemented.

3. FILES CREATED
Exact paths.

4. FILES MODIFIED
Exact paths and reason for each change.

5. ARCHITECTURAL DECISIONS
None unless explicitly authorized by the directive.
List unresolved decisions.

6. DEVIATIONS FROM DIRECTIVE
None, or exact deviations and why.

7. DEPENDENCIES ADDED/REMOVED
List and explain; otherwise none.

8. ENVIRONMENT
Relevant tool versions.

9. COMMANDS RUN
Exact commands.

10. VALIDATION RESULTS
Checks actually performed and their results.

11. DOCUMENTATION REVIEW
Confirm clarity, reality versus plans, decision semantics, and consistency.

12. WARNINGS / KNOWN ISSUES
Anything unresolved.

13. QUESTIONS / BLOCKERS
Anything requiring architect input.

14. GIT DIFF SUMMARY
Concise resulting changes.

15. ACCEPTANCE CRITERIA
PASS / FAIL for every criterion.

16. NEXT STEP
Do not implement it; state the authorized next-step handoff only.
```

Reports must distinguish verified results from assumptions and planned behavior from current behavior.
