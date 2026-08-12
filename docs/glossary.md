# Kongono Glossary

These definitions establish architectural vocabulary, not public PHP APIs. Unless identified as locked direction, exact implementation semantics remain experimental or open.

## Action

A planned explicit application operation or use case that coordinates business behavior behind an authorized calling surface. Its invocation, metadata, transaction, and Graph contracts remain open.

## Agent Experience / AX

The experience presented to AI agents through structured, authorized, machine-readable capabilities and context. AX is first-class alongside DX and UX, but no AX protocol or API is selected.

## Agent Principal

A Principal representing an AI agent that may act only within its identity, permissions, capabilities, constraints, and delegated authority. It is not merely an API client. Resolution and execution semantics remain open.

## Application Graph

Kongono's planned authoritative, machine-readable representation of an application, derived from a language-neutral IR. Phase 0D implements only its generic structural prerequisite; domain schemas, stable identities, serialization, storage, compilation, security metadata, and the complete authoritative representation remain experimental or open.

## Application Graph Structural Snapshot

The Phase 0D immutable in-memory model of nodes and directed relations. It validates exact structural invariants, provides canonical iteration and exact node lookup, and contains no domain payload, provenance, serialization, discovery, or compiler behavior. It is not yet the full authoritative Application Graph.

## API Status

Machine-readable compatibility and supported-use metadata attached to each production class-like type. Phase 0C defines Internal, Experimental, and Public. A type's status applies to its public and protected members; it is not authorization, package visibility, an Application Graph node, or a security boundary.

## Application Profile

A planned coherent selection or configuration of Kongono capabilities for a class of applications or deployments. The profile model, names, composition rules, and packaging are not yet defined.

## Capability

A coherent unit of framework or application ability, such as HTTP, authorization, events, or workflows, with explicit contracts and dependencies. Capability discovery, metadata, packaging, and runtime semantics remain open.

## Component

An independently installable framework package or implementation unit that may provide one or more capabilities. `kongono/kernel` is the first physical package but is a lifecycle core, not authorization for any planned capability package.

## Canonical Graph Order

The deterministic Phase 0D iteration order independent of constructor input order. Nodes compare by binary Node Key value; relations compare by binary source-key value, Relation Kind value, then target-key value. No locale-aware comparison or Unicode normalization occurs.

## Consent

An actor's recorded authorization for a specified action or use of authority, subject to scope, constraints, lifecycle, and revocation. Consent semantics and evidence formats remain open.

## Delegation

A governed grant of authority from one Principal to another, constrained by capability, intent, consent, policy, time, scope, and revocation. Delegation chains, receipts, and enforcement formats are not yet specified.

## Developer Experience / DX

The experience of building, understanding, testing, operating, and evolving a Kongono application. Kongono aims for high productivity through explicit and inspectable mechanisms rather than hidden magic.

## Documentation ID

A planned stable identifier connecting a documentation concept with source symbols, Application Graph nodes, IDEs, CLI tools, and AI tools. The exact syntax and mechanism are experimental; no identifier API or annotation exists.

## Event

A planned explicit record or notification that something relevant occurred. Delivery, durability, ordering, schema, transaction, and Graph semantics remain open.

## Extension

An independently supplied contribution that adds behavior or other capabilities through the governed Extension Platform without unnecessary source-level coupling. Extensions may eventually be core, trusted/in-process, sandboxed, or external; trust and packaging contracts remain open.

## Extension Host

The planned governance boundary for extension permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health. It is not implemented, and its sandboxing or process model is not selected.

## Graph Exception Boundary

The Public `Kongono\ApplicationGraph\Exception\ExceptionInterface` marker implemented by failures deliberately thrown by the Application Graph package. It is separate from the Kernel exception marker; native engine errors are not wrapped merely to enter it.

## Experimental API

A contract available to early adopters and evaluators only with explicit awareness of instability. It carries no backward-compatibility promise before promotion and may be extended only as its documentation permits. Experimental does not mean production-guaranteed.

## Human Principal

A Principal representing a human actor. Authentication, identity resolution, credential, session, and policy semantics are not yet defined.

## Human/User Experience / UX

The experience presented to people using a Kongono application. UX is one of three first-class consumption surfaces alongside Software/API Experience and Agent Experience.

## Intent

A structured expression of the outcome a Principal seeks. Intent may inform authorization, delegation, auditing, or agent interaction, but its schema and enforcement role remain open.

## Internal API

A type intended only for Kongono framework internals. External use and extension are unsupported, and no backward-compatibility promise applies unless a later Accepted contract says otherwise. Internal describes support, not PHP visibility.

## Intermediate Representation / IR

A planned language-neutral representation from which the Application Graph derives. PHP is the first source-language frontend, but the IR must not be permanently tied to PHP syntax. Its schema and compilation model are experimental.

## Kernel

The small, runtime-independent core whose Phase 0C implementation owns boot and shutdown state, operation-scope identity and completion, active-operation accounting, lifecycle misuse detection, the Kernel exception family, and API-status metadata. Responsibilities beyond this Experimental boundary remain unspecified.

## Kernel Exception Boundary

The Public `Kongono\Kernel\Exception\ExceptionInterface` marker implemented by every failure deliberately thrown by the Phase 0C Kernel package. Native engine errors are not wrapped merely to enter this boundary, and concrete lifecycle exception behavior remains Experimental.

## Kernel Lifecycle

The boot/operation/shutdown contract for one Kernel instance: boot once, begin and end overlapping operations while booted, refuse shutdown while operations remain, then shut down terminally. It provides no runtime adapter or ambient current operation.

## Kernel State

One of the Phase 0C lifecycle values `Unbooted`, `Booted`, or `Shutdown`. Shutdown is terminal; invalid transitions leave the current state unchanged.

## Module

An explicit application architecture boundary that groups related behavior and contracts. It is distinct from a Composer component or external extension. Module metadata, dependency rules, and runtime semantics remain open.

## Node

One immutable Phase 0D structural item containing exactly a Node Key and Node Kind. Its identity inside a snapshot is the exact key value, not PHP object identity; it contains no payload, metadata, provenance, or behavior.

## Node Key

A non-empty opaque string identifying one Node inside the snapshot and producer contract that supplied it. Its value is preserved exactly. It is graph-local, not a stable cross-build Application Graph ID, Documentation ID, database ID, URI, UUID, package coordinate, or PHP symbol.

## Node Kind

A non-empty, exactly preserved open label carried by a Node. It is not a built-in framework vocabulary, registry entry, or finalized domain schema.

## Operation Scope

An opaque object whose identity marks one operation owned by one booted Kernel. Scopes may overlap and must be ended explicitly through their owner. Phase 0C scopes contain no ID, metadata, context, callback, serialization, or self-ending behavior, and no global current-operation pointer exists.

## Persistent Runtime

A runtime in which the process and some resources may outlive one request, job, command, or other operation. Kongono must make operation scope, cleanup, and isolation explicit rather than assume process teardown.

## Principal

The planned general identity abstraction for an actor that can be authenticated, authorized, delegated to, constrained, and audited. Principal kinds may include Human, Agent, Service, Organization, and System. Exact data and API models remain open.

## Public API

A supported consumer contract governed by Kongono's versioning policy and release maturity. Public does not mean Stable or unchangeable forever, particularly before 1.0; extension is permitted only as the specific contract documents.

## Relation

One immutable directed Phase 0D connection whose structure is exactly source Node Key, Relation Kind, and target Node Key. Self-relations and cycles are valid; exact duplicate triples are not.

## Relation Kind

A non-empty, exactly preserved open label carried by a Relation. It establishes no built-in dependency, call, event, module, or security vocabulary.

## Service Principal

A Principal representing a software service or machine workload. Credential, identity, trust, rotation, and authorization semantics remain open.

## Structured CLI

The doctrine that meaningful `kongo` commands should support deterministic, machine-readable, automation-safe interaction, including `--json`, `--dry-run`, and `--no-interaction` where applicable. The CLI architecture and exact command contracts are not yet defined.

## Trust

The evaluated confidence and policy relationship under which a Principal, extension, credential, or system may act. Trust is not a substitute for explicit authorization. Its sources, scoring or categorization, and enforcement semantics remain open.

## Workflow

A planned coordination of multiple actions or steps, potentially durable and long-running, with explicit state and failure behavior. Definition, persistence, replay, compensation, and Graph contracts remain open.
