# ADR 0175: Strict Structured Output and Inert OpenAI Function-Call Mapping

Status: Accepted

Date: 2026-09-10

Scope: Phase 17E only

## Context

ADRs 0171 through 0174 established one optional OpenAI Responses adapter over
the accepted Phase 14 neutral invocation, Phase 16 external-service, and Phase
15 governed-egress boundaries. Mapping version `kongono-openai-responses/1`
supports exact text results and pull-based text streaming. Kongono also needs a
strict non-streaming structured-result profile and a way to retain one provider
function call as neutral evidence without creating tool authority.

Provider schema descriptions, structured values, function names, arguments,
identifiers, usage, and status remain untrusted evidence. They cannot grant
credential, network, capability, proposal, approval, tool, or application-
effect authority.

## Decision

Add mapping version `kongono-openai-responses/2` for exactly two non-streaming
profiles. One profile declares `StructuredResult` then `UsageEvidence` and one
explicit named strict schema. The other declares `ActionSuggestion` then
`UsageEvidence` and one explicit named function with strict parameters. The
existing adapter identity remains `openai.responses.v1`; it names the adapter
lifecycle rather than the provider wire grammar.

The strict schema subset has an object root and supports only object, array,
string, integer, number, boolean, and null nodes. Object properties are closed,
all property names are required exactly once, and `additionalProperties` is
exactly false. Arrays have one item schema. Scalar enums are optional,
nonempty, duplicate-free, and exact-type. Descriptions are bounded provider
input only. Unknown keywords, references, recursion, unions, conditions,
formats, defaults, inference, coercion, and other JSON Schema surfaces fail
closed.

Schema source is bounded to 65,536 bytes and JSON depth 16. Logical depth is
bounded to 8, aggregate nodes to 128, object properties and required entries to
32 each, scalar enum entries to 32, descriptions to 1,024 bytes, and property
names to 64 bytes. Decoding rejects duplicate members. Canonical JSON uses
compact UTF-8, recursively ordinal object-member ordering, preserved array
order, unescaped slashes and Unicode, and no terminal line feed. The explicit
schema is stored and returned as detached evidence; Kongono never invents it.

Structured requests add exactly one `text.format` object with type
`json_schema`, the explicit name and schema, and `strict: true`. Structured
responses must be completed, contain exactly one completed assistant message
with one `output_text` item and empty annotations, contain duplicate-safe JSON
object text, and validate locally against the exact stored schema without
coercion. The normalized `ResultText` contains canonical structured JSON. This
is schema-valid evidence, not proof that the value is truthful.

Action-suggestion requests add exactly one strict function definition, require
one tool choice, and disable parallel tool calls. Responses must be completed
and contain exactly one completed `function_call` whose bounded IDs and exact
name validate. Arguments must be duplicate-safe JSON object text that validates
against the exact stored parameters and is canonicalized into one neutral
`ActionSuggestion`.

The function call is inert. The adapter does not resolve, reflect, invoke,
dispatch, enqueue, approve, authorize, retry, replay, or respond to it. It does
not create `function_call_output` input or a second provider operation. Version
2 streaming is rejected before an external-service application is created.
Version 1 text and text streaming retain their accepted behavior.

## Consequences

Applications may explicitly select one strict structured result or one inert
function-call suggestion through the existing one-attempt adapter and governed
external-service path. Version-aware equality and result identity keep Version
1 and Version 2 evidence distinct. All provider values remain bounded and
nondisclosing failures omit raw schemas, values, arguments, names, identifiers,
messages, endpoints, credentials, and native exception text.

The package gains no provider SDK, schema generator, registry, default model,
ambient credential, parallel transport, tool runtime, effect handler,
application workflow, command, inspection surface, Chie connection, or generic
"OpenAI-compatible" claim. No public endpoint or live model is contacted in
this phase. Provider documentation may drift and cannot override this versioned
repository contract.

The graph remains 88 acyclic production packages and 366 local production
edges. PHP inventories become 1,691 production, 884 package-test, and 88 root-
test files. New schema/profile/exception declarations are Experimental; the
codecs and validator are Internal. No declaration becomes Public or Stable.

## Rejected alternatives

Reinterpreting mapping Version 1 was rejected because it would make accepted
text behavior ambiguous. A generic schema surface or provider-schema inference
was rejected because it would widen compatibility and trust claims. Returning
unvalidated JSON was rejected because the selected profile promises exact
local schema validation. Executing or automatically bridging a function call
was rejected because model output is evidence, not authority. A second request
carrying function output, automatic retry, or multi-function selection was
rejected because each would expand the accepted one-operation lifecycle.

## Current and next status

Phase 17A through Phase 17G are Accepted. Phase 17H remains Planned and requires
separate authenticated authority; it alone may create the milestone checkpoint.
Phase 18 does not begin automatically.

See [ADR 0171](0171-first-party-openai-responses-adapter-boundary.md),
[ADR 0172](0172-deterministic-openai-responses-offline-protocol-mapping.md),
[ADR 0173](0173-one-shot-openai-responses-invocation-adapter.md),
[ADR 0174](0174-pull-based-openai-responses-sse-streaming.md), the
[OpenAI Responses provider-adapter guide](../architecture/openai-responses-provider-adapter.md),
and the [Phase 17 roadmap](../architecture/phase-17-roadmap.md).
