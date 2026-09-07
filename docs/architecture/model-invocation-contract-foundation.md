# Model Invocation Contract Foundation

> **Phase 14G current boundary:** the separate
> [`kongono/model-invocation-runtime`](model-invocation-runtime.md) now consumes
> these inert values under exact-current plan binding. The optional
> [model-result bridge](model-result-agent-proposal-bridge.md) reduces only one
> eligible action suggestion through trusted application mapping into inert
> proposal evidence. The optional
> [application workflow](model-invocation-application-workflow.md) binds one
> exact-current profile and one direct adapter for one terminal runtime
> operation. The optional [inspection leaf](model-invocation-inspection-and-evidence.md)
> consumes these values only as explicit bounded evidence and never invokes a
> provider or loads generated PHP. The graph is 75 packages / 289 local edges /
> acyclic; Phase 14H remains planned.

## Purpose and current boundary

Phase 14B implements the optional Experimental
`kongono/model-invocation-contract` package. It gives applications one neutral,
versioned, immutable vocabulary for describing bounded model-invocation inputs
and evidence without importing a provider SDK or granting execution authority.

Use it when application code needs exact provider/model labels, a declared
profile capability set, a bounded request, normalized result or stream event,
usage/cost evidence, lifecycle vocabulary, visibility, or a receipt claim. Do
not use it to discover a provider, store credentials, call a model, authorize an
effect, build or load an invocation plan, serialize values, bridge automatically
to an Agent, record telemetry, or prove external truth.

The package depends only on API Metadata, Diagnostics, and PHP. Its current
in-repository production consumers are Model Invocation Build, Runtime, Agent
Bridge, Application, and Inspection. The repository topology is 75 packages /
289 local production edges / acyclic.

## Profiles and capabilities

`ModelProfileReference` contains only contract version, profile identity,
provider identity, model identity, and a canonical `ProviderCapabilitySet`.
It contains no endpoint, credential, SDK object, transport, retry/fallback,
price, account, region, or live-availability claim.

```php
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;

$profile = new ModelProfileReference(
    ContractVersion::Version1,
    new ProfileIdentity('support.primary'),
    new ProviderIdentity('application.provider'),
    new ModelIdentity('application.model_v1'),
    new ProviderCapabilitySet([
        ProviderCapability::TextResult,
        ProviderCapability::Streaming,
        ProviderCapability::UsageEvidence,
    ]),
);
```

Capabilities must be a nonempty PHP list, unique, and in declaration order.
Declaration is an application claim, not discovery, compatibility, entitlement,
availability, or currentness.

## Messages, requests, and limits

Identities use 1-128 raw bytes matching
`[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*`. A message has an exact role, intended
visibility, and 1-32,768 bytes of trimmed valid UTF-8. LF and TAB are allowed;
CR, NUL, C1, other C0 controls, invalid UTF-8, and edge whitespace are rejected.

```php
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;

$request = new InvocationRequest(
    ContractVersion::Version1,
    new InvocationIdentity('invocation.support_1'),
    $profile,
    [new InputMessage(InputRole::User, ModelVisibility::Private, 'Summarize this request.')],
    ResultMode::Text,
    true,
    new InvocationLimits(maxOutputBytes: 16384, maxStreamEvents: 256, maxUsageEntries: 8),
);
```

A request contains 1-64 exact messages and at most 262,144 aggregate content
bytes. `maxOutputBytes` is 1-1,048,576, `maxStreamEvents` is 1-4,096, and
`maxUsageEntries` is 0-16. Result mode must be declared; streaming requires the
streaming capability. These limits are inert declarations, not runtime meters.

## Results and action suggestions

Text/structured results require `ResultText` and prohibit an action suggestion.
Action-suggestion mode requires an `ActionSuggestion`, prohibits text, and must
use finish reason `action_suggested`; that finish reason is prohibited in other
modes. Result text is 1-262,144 bytes. `ActionArguments` preserves 2-65,536
bytes of trimmed valid JSON text whose top level is an object.

An action suggestion is inert provider-originated evidence. It is not an Agent
proposal, capability request, grant, callable, tool execution, authorization,
or proof of effect. Usage or cost evidence on a result requires the matching
declared profile capability. Validation proves shape only, not truth, safety,
policy compliance, delivery, billing, or application effect.

## Streaming, usage, cost, and receipts

Stream sequences are 1-4,096. `started`, `completed`, and `failed` contain no
payload or usage; `content_delta`, `action_name`, and
`action_arguments_delta` contain only a 1-8,192-byte payload; `usage` contains
only `UsageEvidence`. Events are independent partial evidence. The package does
not enforce continuity, finalization, transport state, retry, or lifecycle
transition.

Usage evidence contains 1-16 unique quantities in ascending ordinal
`metric|unit` order. Quantities are nonnegative integers. Cost evidence contains
`provider_reported` or `estimated`, an exact three-uppercase-letter currency,
and a nonnegative decimal string with at most 18 integer and 9 fractional
digits. Neither usage nor cost is tokenization, quota, billing, settlement,
price-discovery, exchange, authorization, or budget truth.

Receipts accept 0-16 attempts. `rejected` requires zero; every other outcome
requires at least one. `succeeded` requires a result identity; all other
outcomes prohibit one. Usage/cost claims require matching capabilities. A
receipt is not a replay token, provider acceptance, delivery guarantee, durable
audit record, billing record, authorization, non-repudiation, or proof of
external truth.

## Lifecycle, visibility, and nondisclosure

Invocation states (`prepared`, `active`, `streaming`, `completed`, `failed`,
`cancelled`, `retired`) are vocabulary only. Visibility (`private`,
`protected`, `application`) states intended handling and grants no access.

Construction failures use nine fixed `KGN-MODEL-CONTRACT-1401` through `1409`
diagnostics. Context contains only allowlisted Safe labels, counts, positions,
limits, modes, or event kinds. Unknown labels map to
`unsupported_violation`. Raw identities, content, action arguments,
provider/model input, currency/amount text, credentials, secrets, paths, and
throwable messages never appear in exception messages, context, remediation,
or deterministic stringification.

All values are immutable. Array inputs are detached and array accessors return
fresh arrays. The package performs no filesystem, environment, process,
network, provider, credential, serialization, discovery, global-state, Agent,
authority, or execution behavior, and retains no state between sequential
constructions.

## Stability and next boundary

`ExceptionInterface` is Public. Values, enums, and the concrete invalid-value
exception are Experimental. The guard, diagnostic enum, and abstract exception
are Internal. No API is Stable.

Phase 14C deterministic invocation-plan evidence is implemented separately and
documented in the [plan-artifact guide](model-invocation-plan-artifact.md).
Phase 14D provider-neutral execution and Phase 14F's separate exact-current
application workflow are implemented without widening these inert values. See [ADR
0148](../adr/0148-neutral-versioned-model-invocation-contract-foundation.md),
[ADR 0147](../adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md),
the [Phase 14 roadmap](phase-14-roadmap.md), and the [Agent Principal
Platform](agent-principal-platform.md).

Phase 14H now supplies root-owned external-process evidence for these inert
contracts. It does not turn identities, capabilities, requests, results,
receipts, usage, cost, or visibility into discovery, authentication, authority,
billing truth, or effect. See [Phase 14 external acceptance](phase-14-external-acceptance.md).
