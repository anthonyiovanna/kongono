# Kongono Agent Admission

`kongono/agent-admission` is the opt-in Experimental Phase 13D boundary for
deterministic application-owned Agent Principal admission. It validates one
exact current Phase 13C declaration artifact, reconciles an application-
supplied authentication verification and explicit authorization evaluations,
and returns immutable authority-plan evidence.

The package denies undeclared, unrequested required, missing-authorization,
denied, and indeterminate capabilities by default. Authentication and policy
evaluation occur before this boundary and remain application-owned. The
builder never invokes an authenticator, verifier, authorization engine,
evaluator, model, provider, tool, capability, or effect.

Authority-plan evidence is deterministic and inert. Admission is not a grant,
enforcement, current revocation proof, sandbox, callable, resolver, or
permission to execute work. A later effect boundary must independently prove
current authority. Phase 13E's separate opt-in Agent Runtime does so through an
explicit caller-owned currentness port before each possible effect and does not
change this package's admission semantics. See the
[Agent Principal Runtime](../../docs/architecture/agent-principal-runtime.md).
Phase 13F's separate opt-in Agent Application binds one exact plan into
generated and explicit wiring but does not make the plan a grant or replace
that runtime currentness check. See the
[Agent Application Workflow](../../docs/architecture/agent-application-workflow.md).

The package depends only on Agent Declaration Build, Agent Principal Contract,
API Metadata, Authentication, Authorization, Diagnostics, Identity Access,
and PHP 8.5. Its exception marker is Public; its values, enums, builder, and
invalid-value exception are Experimental; diagnostic and helper types are
Internal.
