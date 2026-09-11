# Kongono Model Provider: OpenAI Responses

## Status and purpose

This optional Experimental package implements Kongono mapping version
`kongono-openai-responses/1` for accepted text and pull-based text streaming and
mapping version `kongono-openai-responses/2` for accepted Phase 17E strict non-
streaming structured results and inert function-call suggestions. The one-shot
adapter sits above the provider-neutral Phase 14 contract and composes the
accepted Phase 16 application and Phase 15 egress boundaries. It adds no
provider SDK, ambient credential or endpoint, retry, tool runtime, second
provider operation, or live OpenAI support claim. Current Phase 17F adds the
first supported exact-current application-owned composition path.

Use it when an application-owned `ModelProfileReference` declares provider
`openai`, an explicit model identity, and one exact supported profile: Version
1 text and usage, Version 1 text streaming and usage, Version 2 structured
result and usage with one explicit strict schema, or Version 2 action suggestion
and usage with one explicit strict function definition. The wrapper also
requires an explicit `ResponseStoragePreference`; its `store` boolean is a
request preference, not proof of retention, deletion, privacy, compliance, or
erasure.

Do not use this package for Version 2 streaming, refusals, multiple functions,
function output, tool execution, action effects, provider discovery, retries,
or direct network work. The adapter delegates one authorized operation through
application-supplied Phase 16 and Phase 15 collaborators. A returned function
call remains inert neutral evidence and is never resolved or invoked.

## Deterministic request mapping

`ResponsesRequestEncoder` accepts one exact supported `InvocationRequest`
containing one through 64 System, User, or Assistant
messages. It emits compact UTF-8 JSON with recursively ASCII-ordered object
keys, preserves message order, fixes `/v1/responses` and `application/json`,
and explicitly emits `store` and the validated `stream` choice. Version 2
structured mode adds exactly one strict `text.format` JSON-schema object.
Version 2 action mode disables parallel tool calls, requires tool choice, and
adds exactly one strict function definition. The complete body is limited to
524,288 bytes and is accompanied by its byte count and uppercase SHA-256.

The encoder deliberately omits `max_output_tokens`: the neutral request limit
is measured in output bytes and is not interchangeable with provider tokens.
It also emits no instructions, metadata, tools, include list, temperature,
reasoning, continuation, cache, safety, service-tier, or arbitrary option
field.

## Strict response mapping

`ResponsesResponseDecoder` accepts one complete body of at most 2,097,152
bytes and JSON depth at most 64. It requires valid UTF-8, valid JSON, a
top-level object, and no duplicate object member names at any depth. Mapping
version 1 accepts only one terminal `completed` or `incomplete` response with
one assistant message and one `output_text` content item. The message requires
a bounded `msg_...` identifier that is validated but not returned. The content
item requires an empty `annotations` list. Output text must fit both the
request's byte limit and the neutral `ResultText` rules.

Terminal `usage` may be absent or null. A non-null usage object contains exact
nonnegative PHP integers for input, output, and total tokens, each at most
2,147,483,647; total cannot be less than either component. The exact known
`input_tokens_details.cached_tokens` and
`output_tokens_details.reasoning_tokens` objects may be present when their
bounded counts do not exceed the matching aggregate. Those detail counts are
validated but not retained or exposed. Completed `incomplete_details` may be
absent or null; incomplete responses accept only `max_tokens` or
`content_filter`. Request-side `max_output_tokens` remains deliberately
omitted because the neutral limit is bytes, not tokens. Unknown top-level
members are ignored only after whole-body syntax and duplicate validation.
Unknown nested members, nonempty annotations, and unsupported item, content,
refusal, structured, tool, function, streaming, failed, cancelled, or
ambiguous shapes fail closed.

Mapping Version 2 accepts only completed responses. Structured mode requires
exactly one completed assistant message and one `output_text` with empty
annotations. Its text must be duplicate-safe JSON object bytes that validate
locally without coercion against the exact stored schema and are normalized to
canonical structured `ResultText`. Action mode requires exactly one completed
`function_call` with bounded IDs, the exact selected name, and duplicate-safe
JSON object arguments that validate against the exact stored parameters. The
canonical arguments become one neutral `ActionSuggestion`; the package performs
no lookup, reflection, dispatch, approval, authorization, execution, function-
output request, or second provider operation.

Strict schemas have an object root and support only bounded object, array,
string, integer, number, boolean, and null nodes. Object properties are closed
and all are required exactly once; arrays have one item schema; scalar enums
are optional, nonempty, duplicate-free, and exact-type. Duplicate members,
unknown keywords, references, recursion, unions, conditions, formats, defaults,
inference, and coercion fail closed. Definitions are explicit provider-profile
input and Kongono never invents them. Schema-valid output remains untrusted
evidence, not a truth claim.

## Pull-based text streaming

Mapping Version 1 may select the exact text, streaming, and usage profile. One
Internal consumer-driven SSE session reads bounded chunks, accepts only the
closed ordered text-event subset, emits contiguous neutral events and one
terminal result, and closes its body exactly once. It has no background reader,
reconnect, replay, WebSocket/realtime surface, or immediate first-token promise;
Phase 15 may already have buffered the response.

Decoded identifiers, model names, text, statuses, incomplete reasons, and
usage are bounded untrusted provider claims. They do not prove identity,
billing, cost, safety, truth, authorization, delivery, retention, or external
effect. The Phase 17B decoder itself does not convert them into a Phase 14
`ModelResult`; only the Phase 17C normalizer does so after the complete
one-shot boundary succeeds.

## One-shot invocation adapter

`OpenAiResponsesInvocationAdapter` has identity `openai.responses.v1` and may
open once. Its explicit immutable context supplies the expected plan identity,
one application factory, operation and receipt identities, credential purpose
and lifetime, current authority, cancellation, and clocks. `open()` validates
and encodes only; it does not create the external-service application or touch
credentials, resolution, or transport. Closing before the first pull releases
the encoded request without creating the application.

On first pull, the session creates one fresh Ready application with the exact
profile advertised by the factory and runs it once. Borrowed credential
material constructs one `POST` request to the binding's canonical HTTPS origin,
empty base path, and `/v1/responses`, with only JSON Accept/Content-Type and
Bearer authorization headers. The adapter never hard-codes a public OpenAI
hostname. Empty, over-16,384-byte, or header-unsafe credential material fails
without disclosure, and the adapter, context, session, result, and receipt do
not retain it.

The response must have status 200, exactly one valid `application/json` content
type with at most an optional UTF-8 charset, and a readable body within the
decoder bound. The body is read in bounded chunks without stringification,
rewind, or unbounded `getContents()` and receives exactly one close attempt.
The normalized Phase 14 result preserves the request version, invocation,
profile, supported finish reason, optional aggregate usage, and most-
restrictive input visibility. Its identity hashes the mapping version and raw
provider response identifier rather than exposing that identifier.

A successful session emits one result and then only `null`; close is
idempotent. There is one attempt and no retry, fallback, hedging, replay,
redirect policy, polling, or background work. Failed-before-effect,
uncertain-effect, cancelled-before-effect, and cancelled-after-possible-effect
outcomes remain terminal failures.

## Exact-current application workflow

`OpenAiResponsesApplicationProfileFactory` binds one exact Phase 14 invocation-
application profile, one selected provider profile, and one exact Phase 16
external-service-application profile. It reconstructs both lower profiles,
requires adapter identity `openai.responses.v1`, proves the neutral request and
provider profile match, enforces the accepted mapping/mode combinations, and
requires an HTTPS endpoint with an empty base path. Its canonical identity
contains bounded digests and closed tokens only; it omits prompts, messages,
schemas, arguments, results, endpoints, credential references, and credentials.

The supported developer path is explicit:

```php
$workflow = new OpenAiResponsesApplicationBuildWorkflow();
$current = $publishingRun
    ? $workflow->build(
        $applicationRoot,
        $planArtifactFile,
        $invocationPlanSource,
        $generatedPhpDirectory,
        $openAiResponsesProfile,
        $externalServiceApplicationProfile,
    )
    : $workflow->check( // Write-free: verifies existing exact output only.
        $applicationRoot,
        $planArtifactFile,
        $invocationPlanSource,
        $generatedPhpDirectory,
        $openAiResponsesProfile,
        $externalServiceApplicationProfile,
    );

$result = $current->application()->run(new OpenAiResponsesApplicationRunInput(
    $externalServiceApplicationFactory,
    $externalServiceOperationIdentity,
    $credentialPurpose,
    $credentialMaximumLifetimeMilliseconds,
    $egressReceiptIdentity,
    $egressAttemptIdentity,
    $egressAuthorityReference,
    $egressAuthorityCurrentness,
    $egressCancellation,
    $egressClock,
    $credentialClock,
    $invocationReceiptIdentity,
    $invocationCancellation,
    $invocationClock,
));
```

Every value is application supplied; the package provides no defaults,
discovery, registry, service locator, global client, or environment lookup.
`build()` delegates publication and loading to the accepted Phase 14 workflow;
`check()` delegates its write-free currentness check. The returned application
is single-use. Reentry, overlap, and reuse fail before another effect, while
the accepted lower runtimes retain cleanup and uncertainty ownership.

## Diagnostics and lifecycle

Diagnostics `KGN-OPENAI-RESPONSES-1701` through `1723` use closed Safe context
tokens only. Exceptions do not present request or response content, raw JSON,
provider identifiers, model names, endpoints, headers, native messages, or
previous-exception text. Native traces remain ordinary PHP traces and are not
claimed to be sanitized.

Mapping values are immutable or stateless. The single-use adapter and session
release operation references on success, failure, cancellation, or close.
Equal mapping inputs produce equal bytes or decoded evidence, returned arrays
are detached, and mixed sequential operations retain no global or ambient
state. The adapter itself performs no filesystem, environment, DNS, socket, or
cURL access; those effects remain behind explicit Phase 15/16 collaborators.

Phase 17B.1 corrects this narrow envelope from an architect-selected documented
shape. The mapping and synthetic fixtures remain offline evidence; they do not
establish live provider compatibility or certification.

## Stability and next boundary

The mapping version, profile, storage preference, exception marker, leaf
exceptions, application factory, invocation context, adapter, and invocation
exception are Experimental. The session, state enum, provider request factory,
body reader, result normalizer, and other mapping declarations are Internal. No
declaration is Public or Stable. Phase 17A-G are Accepted; Phase 17H remains
Planned and separately unauthorized.

See [ADR 0172](../../docs/adr/0172-deterministic-openai-responses-offline-protocol-mapping.md),
 the [Phase 17C decision](../../docs/adr/0173-one-shot-openai-responses-invocation-adapter.md),
 the [Phase 17D decision](../../docs/adr/0174-pull-based-openai-responses-sse-streaming.md),
the [Phase 17E decision](../../docs/adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md),
the [Phase 17F decision](../../docs/adr/0176-exact-current-openai-responses-application-workflow.md),
the [Phase 17G decision](../../docs/adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md),
the [inspection guide](../../docs/architecture/openai-responses-inspection-and-evidence.md),
the [provider-adapter guide](../../docs/architecture/openai-responses-provider-adapter.md),
and the [Phase 17 roadmap](../../docs/architecture/phase-17-roadmap.md).
