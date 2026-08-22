# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0A

## Mission

You are the **implementation executor** for a new application framework named **Kongono**.

The repository is currently an empty folder named `Kongono`.

Your responsibility in this task is **only to establish the repository foundation, architectural governance, documentation doctrine, and permanent AI-agent operating rules**.

Do **not** begin implementing the Kongono framework kernel, router, container, Application Graph compiler, ORM, CLI runtime, agents, extensions, or other framework functionality during this task.

This is Phase **0A — Repository & Governance Bootstrap**.

---

# 1. ROLE AND AUTHORITY

You are an **executor, not the architect**.

Architectural decisions are supplied externally.

You must implement only what this directive explicitly authorizes.

## Mandatory stop rule

If completing this task requires making an architectural, security, public-API, package-boundary, dependency, protocol, runtime, compatibility, licensing, or data-model decision not explicitly covered here:

**STOP. DO NOT GUESS.**

Report:

1. The exact decision required.
2. Why the task cannot safely proceed without it.
3. The viable options.
4. Tradeoffs of each option.
5. Your recommendation, clearly labeled as a recommendation only.

Do not implement the recommendation until approval is returned.

---

# 2. KONGONO IDENTITY — LOCKED

These values are **LOCKED**.

```text
Project:          Kongono
Framework name:   Kongono Framework
Primary domain:   kongono.com
PHP namespace:    Kongono\\
Composer vendor:  kongono
Official CLI:     kongo
Minimum PHP:      PHP 8.5
Primary language: PHP
```

Do not rename, abbreviate, reinterpret, or replace these values.

The optional `kgo` alias is **not authorized yet**.

Do not implement it.

---

# 3. KONGONO PRODUCT PHILOSOPHY — LOCKED

Kongono is **not a Laravel clone**.

Kongono is a modern application framework designed from this point forward for:

```text
Humans
Software clients
AI agents
```

Its guiding product statement is:

> **Build what comes next.**

Its architectural goal is to make applications understandable to:

```text
Developers
Infrastructure
Tooling
AI agents
Other applications
```

Kongono should preserve the exceptional developer productivity associated with frameworks such as Laravel while adopting a more explicit, typed, deterministic, observable, modular, persistent-runtime-safe, AI-native architecture.

---

# 4. CORE ARCHITECTURAL DOCTRINE — LOCKED

Record these principles clearly in repository documentation.

Do not implement them yet unless specifically authorized by this directive.

## 4.1 Application Graph

The **Application Graph** is intended to become Kongono's authoritative machine-readable representation of an application.

It will eventually describe concepts including:

```text
Modules
Services
Dependencies
Routes
Actions
Models / entities
Schemas
Events
Jobs
Workflows
Policies
Permissions
Principals
Agents
Delegations
Capabilities
Extension contributions
Frontend contracts
External APIs
Documentation relationships
```

The Application Graph must ultimately derive from a **language-neutral Intermediate Representation (IR)**.

PHP is the first source-language frontend.

The Graph/IR must not be conceptually tied permanently to PHP syntax.

The precise Graph serialization/schema is currently **EXPERIMENTAL** and must **not** be finalized during Phase 0A.

---

## 4.2 Microkernel + Capabilities

Kongono will use a **small microkernel plus independently installable capabilities/components**.

The full developer experience must still feel like one coherent framework.

Future capabilities may include:

```text
HTTP
Data
ORM
Migrations
Authentication
Authorization
Events
Queues
Workflows
Cache
Storage
Mail
Notifications
Realtime
Scheduling
AI
Agents
Delegation
MCP
A2A
Frontend integration
Testing
Developer tooling
```

Do not implement these packages yet.

---

## 4.3 Progressive Architecture

Kongono will support:

```text
MVC
Actions / use cases
Modular monoliths
Domain-driven architecture
Ports/adapters
CQRS where justified
Events
Durable workflows
```

MVC remains a friendly default at the HTTP/UI edge.

Business logic should not be structurally trapped inside controllers.

Applications should be able to evolve from simple architecture into more rigorous modular architecture without switching frameworks.

---

## 4.4 Persistent-runtime-first

Kongono must be architected for long-running application processes from the beginning.

Its core lifecycle must not assume that all application state is destroyed after each HTTP request.

Future runtime adapters may support:

```text
FrankenPHP
RoadRunner
OpenSwoole
PHP-FPM
CLI workers
Serverless
Future runtimes
```

No particular runtime may become a hard dependency of the kernel.

---

## 4.5 Polyglot platform

Primary application code:

```text
PHP 8.5+
```

Frontend/browser environment:

```text
TypeScript / JavaScript
```

Potential high-performance framework tooling:

```text
Rust
```

Go may be used selectively for infrastructure/runtime integration where appropriate.

Do not introduce Rust, Go, Node.js, or TypeScript tooling during Phase 0A.

---

## 4.6 Frontend

Kongono will **not build its own JavaScript bundler**.

Vite is the intended default frontend build integration.

Kongono will own an **Asset Platform / integration abstraction**, not the underlying bundler.

Future frontend adapters may support:

```text
React
Vue
Svelte
HTMX
Alpine
Server-rendered HTML
No-bundler/native assets
```

Do not create frontend tooling yet.

---

## 4.7 Observability

OpenTelemetry is intended to be native to Kongono's architectural instrumentation model.

The kernel should eventually emit structured telemetry hooks for framework operations.

Exporters remain replaceable.

Do not add OpenTelemetry dependencies during Phase 0A.

---

## 4.8 APIs

OpenAPI is intended to be generated from Kongono's authoritative application metadata.

Developers should not have to independently maintain application contracts in several places.

Do not implement OpenAPI yet.

---

## 4.9 AI-native development

AI coding agents should interact with structured framework knowledge instead of having to infer application architecture by crawling arbitrary source files.

Future tooling may expose:

```text
Application Graph inspection
Routes
Schemas
Dependencies
Documentation
Tests
Logs
Traces
Architecture rules
Impact analysis
Security information
```

through structured CLI and MCP-style interfaces.

Kongono's architecture must remain provider-independent.

Do not add an AI provider SDK in this phase.

---

## 4.10 Agents as actors

Kongono treats AI agents as potential **first-class application actors**, not merely API clients.

The future identity model is based on a broader concept:

```text
Principal
├── Human
├── Agent
├── Service
├── Organization
└── System
```

Future first-class concepts include:

```text
Delegation
Capability
Trust
Intent
Consent
Constraints
Delegation chains
Revocation
Audit
Receipts
```

Protocols such as MCP and A2A are adapters around Kongono concepts.

They must never define Kongono's internal domain model.

---

## 4.11 Agent Experience

Kongono recognizes three first-class consumption surfaces:

```text
Human Experience
Software/API Experience
Agent Experience
```

An application capability should eventually be definable once and safely exposed through appropriate surfaces where authorized.

---

## 4.12 Extension Platform

Kongono will eventually have a secure plug-and-play Extension Platform.

Extensions should be capable of extending applications without creating unnecessary source-level coupling.

Future extension categories include:

```text
Core
Trusted/in-process
Sandboxed
External
```

The future Extension Host is expected to govern:

```text
Permissions
Capabilities
Lifecycle
Storage
Network access
Secrets
Events
UI contributions
Agent capabilities
Updates
Removal
Health
```

Third-party extensions must not automatically receive unrestricted database, filesystem, secret, shell, or process access.

Future WASM/WASI sandboxing is anticipated but **not a v1 requirement and not part of Phase 0A**.

---

## 4.13 Standards-first interoperability

Kongono should use stable open standards where they strengthen interoperability without compromising architecture.

Likely examples include:

```text
Composer
PSRs
OpenAPI
OpenTelemetry
OAuth/OIDC
MCP
A2A
Web standards
```

External protocols must sit behind Kongono-owned abstractions where appropriate.

---

# 5. ENGINEERING PRINCIPLES — LOCKED

Record and enforce these principles in `AGENTS.md`.

## Explicit over magical

Prefer:

```text
typed
inspectable
deterministic
machine-readable
statically verifiable
```

behavior.

Do not introduce framework-wide hidden magic merely to reduce syntax.

---

## AI may suggest; Kongono must know

Correctness-critical information must be deterministic.

An LLM must never be the authoritative mechanism for determining:

```text
Authorization
Migration safety
Application structure
Dependency legality
API contracts
Security policies
Module boundaries
```

AI may assist.

Kongono itself must know.

---

## Framework invariants over arbitrary choice

Kongono can be flexible at application architecture layers while maintaining strong framework invariants.

Examples of future invariants:

```text
typed DI
Application Graph
structured CLI
operation scopes
architecture validation
security metadata
telemetry
machine-readable contracts
```

---

## Smallest correct implementation

Do not build speculative abstractions merely because they may be useful later.

Do not implement future phases early.

Public APIs create long-term compatibility obligations.

---

# 6. DOCUMENTATION DOCTRINE — LOCKED

This section is extremely important.

Kongono documentation must **never be vague merely for brevity**.

A developer should be able to understand a Kongono component without reverse-engineering it.

## Documentation must explain context

Where relevant, documentation should explain:

```text
PURPOSE
Why does this exist?

CONTEXT
Where does this fit into Kongono?

USE WHEN
What scenarios justify using it?

DO NOT USE WHEN
What common situations should use something else?

INPUT
What is semantically expected?

OUTPUT
What is guaranteed?

SIDE EFFECTS
Database, filesystem, network, events, state changes, etc.

SECURITY
Permissions, trust boundaries, sensitive information, attack implications.

LIFECYCLE
Scope and persistent-runtime implications.

CONCURRENCY
Concurrent execution concerns.

FAILURE
Errors, exceptions, failure states.

PERFORMANCE
Important performance characteristics.

CALLING CONTRACT
What must be true before use?

EXTENSION CONTRACT
Can extensions replace/use/implement it?

AGENT CONTRACT
Can agents interact with it?

STABILITY
Internal / Experimental / Public / Stable / Deprecated.

RELATED CONCEPTS
Other relevant framework concepts or documentation.
```

Not every symbol needs every heading.

Use judgment based on complexity and exposure.

---

## Never write useless comments

The following are unacceptable:

```php
// Increment count.
$count++;

/**
 * Handle the request.
 */

/**
 * Get the value.
 */

/**
 * Process the data.
 */
```

Comments must explain something the code cannot communicate sufficiently through names, types, or structure.

Good comments explain:

```text
Why
Architectural context
Invariants
Security requirements
Non-obvious side effects
Lifecycle constraints
Concurrency constraints
Intentional tradeoffs
Historical compatibility constraints where relevant
```

---

## Prefer good names before comments

Priority:

```text
1. Good type
2. Good name
3. Good structure
4. Contextual comment where necessary
```

Do not compensate for poor naming with comments.

---

## Caller/callee relationships

Do **not** manually maintain exhaustive caller/callee lists in source comments.

Those become stale.

Human-authored documentation should explain conceptual relationships.

The future Application Graph will provide authoritative machine-derived relationships such as:

```text
Called by
Calls
Depends on
Used by modules
Emits events
Consumes events
```

---

## Documentation identity

Kongono will eventually support stable documentation identifiers connecting:

```text
Source symbol
Application Graph node
Documentation concept
IDE
CLI
AI tools
```

Example conceptual ID:

```text
principals.resolution
```

The exact syntax/mechanism is **EXPERIMENTAL**.

Do not add custom PHP attributes or annotations for documentation IDs during Phase 0A.

Document the intent only.

---

## Documentation links

Future Kongono tooling may allow developers to navigate from a symbol/comment directly to version-correct documentation covering:

```text
What it is
Why it exists
How to use it
Examples
Security
Warnings
When not to use it
Related concepts
```

Do not implement the linking mechanism yet.

---

## Documentation must be testable eventually

Future tooling should be capable of checking:

```text
Broken documentation references
Invalid examples
Missing public API docs
Missing security documentation
Deprecated API references
Invalid symbol links
```

Do not implement documentation validation yet.

---

# 7. CLI DOCTRINE — LOCKED

The official executable is:

```text
kongo
```

Future examples:

```bash
kongo new
kongo dev
kongo build
kongo check
kongo test
kongo graph
```

Every meaningful CLI command should eventually support structured machine-readable output where applicable, especially:

```text
--json
--dry-run
--no-interaction
```

Exact CLI architecture is not part of Phase 0A.

Do not create an executable yet.

---

# 8. REPOSITORY MODEL — LOCKED FOR INITIAL DEVELOPMENT

Use a **monorepo** for Kongono's initial development.

Reasons:

```text
Atomic architectural refactors
Cross-component testing
Shared tooling
Coordinated releases during early development
Application Graph work spanning multiple components
```

Components may later be published independently.

Do not implement automated repository splitting or publishing yet.

---

# 9. PHASE 0A FILES TO CREATE

Create this foundation:

```text
Kongono/
├── .editorconfig
├── .gitattributes
├── .gitignore
├── AGENTS.md
├── ARCHITECTURE.md
├── CONTRIBUTING.md
├── README.md
├── SECURITY.md
├── composer.json
│
└── docs/
    ├── doctrine.md
    ├── glossary.md
    ├── documentation-standard.md
    │
    ├── architecture/
    │   ├── overview.md
    │   ├── application-graph.md
    │   ├── microkernel.md
    │   ├── progressive-architecture.md
    │   ├── runtime-model.md
    │   ├── agent-model.md
    │   └── extension-platform.md
    │
    └── adr/
        ├── README.md
        ├── 0001-kongono-identity.md
        ├── 0002-php-85-minimum.md
        ├── 0003-monorepo-initial-development.md
        ├── 0004-microkernel-capability-architecture.md
        ├── 0005-language-neutral-application-graph.md
        ├── 0006-persistent-runtime-first.md
        ├── 0007-polyglot-platform-boundaries.md
        ├── 0008-agents-as-principals.md
        ├── 0009-secure-extension-platform.md
        ├── 0010-vite-integration-strategy.md
        ├── 0011-opentelemetry-observability-strategy.md
        ├── 0012-structured-cli-contract.md
        └── 0013-documentation-doctrine.md
```

Do not create empty placeholder directories for dozens of future components yet.

They will be introduced only when their implementation phase begins.

---

# 10. `composer.json`

Create a minimal root Composer manifest appropriate for early development.

LOCKED requirements:

```json
{
  "name": "kongono/framework",
  "description": "A modern application framework designed for humans, software, and AI agents.",
  "type": "library"
}
```

Require:

```text
PHP >= 8.5
```

Do not add production dependencies.

Do not add development dependencies.

Do not invent package scripts yet.

Do not add a license field because Kongono's open-source license has **not yet been architecturally selected**.

Do not create a `LICENSE` file.

This omission is intentional.

---

# 11. README REQUIREMENTS

`README.md` must be concise compared with the detailed architecture documentation.

It should include:

```text
Kongono
Build what comes next.

What Kongono is

Who Kongono is designed for:
- humans
- software
- AI agents

Current status:
Pre-alpha / architecture and foundation phase

Core principles

PHP 8.5+ requirement

A warning that public APIs do not yet exist and nothing is stable

Links into repository documentation
```

Do not claim that Kongono currently implements features that only exist in the design.

Clearly distinguish:

```text
Current reality
Planned architecture
Long-term direction
```

---

# 12. `AGENTS.md` REQUIREMENTS

This file is permanent operating policy for AI coding agents working in Kongono.

It must contain at minimum:

## Agent role

```text
Executor, not architect.
```

## No assumptions

If architecture is unclear:

```text
STOP
ASK
DO NOT GUESS
```

## No scope expansion

Do not:

```text
Refactor unrelated code
Introduce unrequested dependencies
Implement future phases
Rename architecture
Change package boundaries
Replace chosen technologies
Create public APIs casually
```

## LOCKED / DEFAULT / EXPERIMENTAL semantics

Define:

### LOCKED

Cannot change without architect approval.

### DEFAULT

Preferred implementation choice.

Deviation requires explicit justification and approval if architectural impact exists.

### EXPERIMENTAL

Prototype territory.

Do not prematurely stabilize public APIs.

---

## Documentation requirements

Include the documentation doctrine from this directive.

---

## Completion-report requirements

Include the completion report format from Section 17 below.

---

# 13. ADR STANDARD

Each ADR must contain:

```text
Title
Status
Date
Context
Decision
Rationale
Consequences
Rejected alternatives
What remains open
```

Use today's repository date if determinable from the environment.

If not, use:

```text
2026-08-12
```

ADR statuses in Phase 0A should generally be:

```text
Accepted
```

except where the exact mechanism remains experimental.

Do not invent architectural decisions beyond this directive.

When something remains open, explicitly record it under:

```text
What remains open
```

---

# 14. GLOSSARY

`docs/glossary.md` must establish precise definitions for at least:

```text
Application Graph
Intermediate Representation / IR
Kernel
Capability
Component
Module
Extension
Extension Host
Principal
Human Principal
Agent Principal
Service Principal
Delegation
Intent
Consent
Trust
Action
Workflow
Event
Application Profile
Agent Experience / AX
Developer Experience / DX
Human/User Experience / UX
Persistent Runtime
Structured CLI
Documentation ID
```

Where exact implementation semantics are not yet finalized, say so explicitly.

Do not pretend experimental concepts are already stable APIs.

---

# 15. GIT

If the directory is not already a Git repository:

```bash
git init
```

Do not create a remote.

Do not push anything.

Do not create branches.

Do not commit unless explicitly instructed later.

---

# 16. ENVIRONMENT INSPECTION

Run and report the results of:

```bash
php -v
composer --version
git --version
```

If a command is unavailable:

**Do not install anything.**

Report it.

Composer validation should be run if Composer exists:

```bash
composer validate
```

Do not run `composer install` because there should be no dependencies.

Also run a repository-status inspection at the end.

---

# 17. REQUIRED COMPLETION REPORT

When finished, return exactly this structure:

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
None unless explicitly authorized by this directive.
List any encountered decisions that remain unresolved.

6. DEVIATIONS FROM DIRECTIVE
None, or exact deviations and why.

7. DEPENDENCIES ADDED/REMOVED
Should be none.
If not none, explain why.

8. ENVIRONMENT
PHP version
Composer version
Git version

9. COMMANDS RUN
Exact commands.

10. VALIDATION RESULTS
Composer validation
Git status
Any Markdown or other checks actually performed.

11. DOCUMENTATION REVIEW
Confirm that:
- vague comments/examples were avoided
- planned features are not represented as implemented features
- LOCKED versus EXPERIMENTAL concepts are clear
- architectural documents do not contradict one another

12. WARNINGS / KNOWN ISSUES
Anything unresolved.

13. QUESTIONS / BLOCKERS
Anything requiring architect input.

14. GIT DIFF SUMMARY
Concise description of resulting repository changes.

15. ACCEPTANCE CRITERIA
For every criterion below:
PASS / FAIL

16. NEXT STEP
Do not implement it.
State only:
"Await architect review before Phase 0B."
```

---

# 18. ACCEPTANCE CRITERIA

Every item must pass.

```text
[ ] Git repository initialized if necessary.

[ ] Kongono identity is used consistently.

[ ] kongo is documented as the official future CLI.

[ ] PHP >=8.5 is recorded.

[ ] composer.json contains no external dependencies.

[ ] No LICENSE file or license assumption was created.

[ ] AGENTS.md establishes strict executor behavior.

[ ] No-assumption / stop-and-ask policy is explicit.

[ ] Architectural doctrine is documented.

[ ] Application Graph is clearly identified as foundational.

[ ] Graph serialization/schema remains explicitly experimental.

[ ] Microkernel + capability architecture is documented.

[ ] Initial monorepo decision is documented.

[ ] Persistent-runtime-first architecture is documented.

[ ] PHP/TypeScript/Rust platform boundaries are documented.

[ ] Agent-as-Principal model is documented.

[ ] Delegation/capability concepts are documented.

[ ] Secure extension architecture is documented.

[ ] Vite strategy is documented without implementing Vite.

[ ] OpenTelemetry strategy is documented without adding dependencies.

[ ] Structured CLI doctrine is documented.

[ ] Documentation doctrine is documented in detail.

[ ] Documentation explains context rather than restating code.

[ ] Stable future documentation identifiers are discussed without prematurely selecting syntax.

[ ] README accurately describes Kongono as pre-alpha.

[ ] Planned features are not falsely presented as implemented.

[ ] ADRs reflect only explicitly authorized decisions.

[ ] No framework functionality has been implemented.

[ ] No PHP framework classes have been created.

[ ] No CLI executable has been created.

[ ] No frontend tooling has been created.

[ ] No unnecessary dependencies have been introduced.

[ ] composer validate succeeds if Composer is available.

[ ] Repository remains cleanly organized and understandable.
```

---

# 19. PROHIBITED DURING THIS TASK

Do **not**:

```text
Implement the DI container
Implement Application Graph classes
Choose Graph IDs
Choose Graph serialization
Build kongo CLI
Create router
Create HTTP kernel
Build ORM
Build migrations
Add Symfony components
Add Laravel components
Add PHPStan
Add PHPUnit/Pest
Add Rector
Add Vite
Add Node tooling
Add Rust tooling
Add OpenTelemetry packages
Add OpenAPI packages
Add MCP packages
Add A2A packages
Add AI SDKs
Implement agent runtime
Implement extension runtime
Implement WASM
Create databases
Create Docker files
Create CI/CD
Create GitHub workflows
Create a LICENSE
Choose an open-source license
Create releases
Commit
Push
```

These belong to later architect-approved tasks.

---

# 20. FINAL OPERATING RULE

When faced with ambiguity:

> **Architectural correctness is more important than task completion percentage.**

A partially completed task with a precise question is preferable to a completed task built on an unauthorized assumption.

Begin Phase 0A now.
