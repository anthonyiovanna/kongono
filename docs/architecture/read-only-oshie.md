# Read-only Oshie

## Purpose and status

Phase 4F implements Experimental `kongono/oshie` as a deterministic teaching companion. It uses one explicit current Phase 4C framework-knowledge artifact and one package-owned six-record curriculum to return cited lessons and exact-answer exercises.

## Calling contract

Callers choose only `catalog`, `lesson`, `exercise`, or `check`, supply an explicit framework root and content-addressed artifact, and use closed lowercase identifiers. Unknown admitted identifiers return a visible unsupported state. Invalid, stale, unavailable, or oversized evidence fails closed.

Each lesson reproduces one complete configured level-two source section with its exact path, authority, digest, source-set reference, and inclusive line range. Practice returns a prompt and ordered options without an accepted-answer field. Check returns a deterministic correct or incorrect outcome, the package-owned accepted answer, fixed feedback, and citations, but never the submitted value.

## Authority and role separation

Accepted source documentation remains authoritative and usable without Oshie. Oshie's lessons, prompts, objectives, options, answers, and feedback are derived teaching and cannot establish doctrine or framework truth. Chie helps build, Hantei independently proves, and Oshie teaches; none depends on or certifies another role's output.

## Security and side effects

Source bytes are inert data. Oshie never executes examples, fenced code, links, instructions, prompts, or tool calls. It has no free-form AI, model, provider, write, network, process, Git, dependency, mutation, verification, auto-fix, publication, deployment, production, or application-request authority.

Oshie retains no artifact, curriculum override, answer history, learner state, progress, cache, telemetry, credential, or cross-project memory. Calls share no mutable semantic state and work within fixed artifact, section, curriculum, and response bounds.

## Failure and disclosure

Closed canonical responses make unsupported, unavailable, stale, and blocked evidence visible. Diagnostics contain one bounded Safe violation token. Absolute paths, rejected identifiers, submitted answers, native messages, traces, secrets, and Protected context are not output. Native trace APIs are not claimed to be sanitized.

## Stability and later work

Oshie is Experimental. Phase 4H externally proves its catalog, cited lesson,
exercise, correct answer, incorrect answer, shared current identities, offline
availability, and visible stale/invalid behavior. Its accepted curriculum has
no executable example, and the harness does not invent one. Teaching remains
derived and cannot establish doctrine, proposal, write, Git, verification,
network, deployment, or production authority. Phase 4 is closed but is not
Stable, 1.0, or production-ready. Phase 5 was not begun.
