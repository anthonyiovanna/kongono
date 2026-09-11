# Model Invocation Application Workflow

Phase 14F implements the optional Experimental
`kongono/model-invocation-application` package. It binds one exact-current
application-owned invocation profile to deterministic generated wiring, one
directly supplied adapter, and one terminal synchronous Phase 14D operation.
It is an outer application leaf; no existing production package depends on it.

## Use when

Use this workflow when the application already owns:

- one typed Phase 14C `InvocationPlanSource`;
- the exact current content-addressed plan artifact;
- one existing contained output directory;
- one adapter instance whose identity is declared by the plan; and
- one receipt identity, cancellation probe, and monotonic clock for the run.

Do not use it for provider or credential selection, a profile registry,
multiple profiles, automatic retry/fallback, retained conversation state,
background work, deployment, Agent authority, or effect execution.

## Boundary and lifecycle

The profile factory calls the Phase 14C exact-current validator. Its immutable
profile exposes verified plan, invocation, model-profile, adapter, artifact,
source, and request identity evidence. Raw request content is not projected
into the profile identity.

The build workflow snapshots one explicit artifact file, creates the exact
profile, generates deterministic wiring, publishes or write-free checks the
exact expected output, loads that explicit file, and reverifies both snapshots.
It does not scan for profiles, adapters, artifacts, or generated outputs.

The loaded handle starts once. Adapter identity is checked before `open()`.
The application then runs once, moving through `ready`, `active`, and `retired`.
It builds one Phase 14D request and returns the exact runtime result object.
After return or throw it retains no adapter, session, result, or operation-local
state. The Phase 14D runtime alone owns an opened session and its close attempt.

## Minimal typed example

The example uses an application-owned fake adapter and normalized placeholder
evidence; it has no provider SDK, credential source, or network requirement.
`$source`, `$artifactPath`, and `$normalizedEmission` are typed values created by
the existing Phase 14B/14C APIs.

```php
<?php

use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplicationRunInput;
use Kongono\ModelInvocationApplication\Workflow\ModelInvocationApplicationBuildWorkflow;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;

final class OneShotFakeSession implements InvocationAdapterSession
{
    private bool $emitted = false;

    public function __construct(private InvocationAdapterEmission $emission) {}

    public function next(): ?InvocationAdapterEmission
    {
        if ($this->emitted) {
            return null;
        }
        $this->emitted = true;

        return $this->emission;
    }

    public function close(): void {}
}

final readonly class DirectFakeAdapter implements InvocationAdapter
{
    public function __construct(
        private AdapterIdentity $identity,
        private InvocationAdapterSession $session,
    ) {}

    public function identity(): AdapterIdentity
    {
        return $this->identity;
    }

    public function open(InvocationPlanSource $source): InvocationAdapterSession
    {
        return $this->session;
    }
}

final class NeverCancelled implements InvocationCancellation
{
    public function isCancellationRequested(): bool
    {
        return false;
    }
}

final class MonotonicExampleClock implements InvocationMonotonicClock
{
    private int $now = 0;

    public function milliseconds(): int
    {
        return ++$this->now;
    }
}

$current = (new ModelInvocationApplicationBuildWorkflow())->build(
    $applicationRoot,
    $artifactPath,
    $source,
    $generatedOutputDirectory,
);
$adapter = new DirectFakeAdapter(
    $source->adapterIdentity(),
    new OneShotFakeSession($normalizedEmission),
);
$application = $current->loadedWiring()->start($adapter);
$result = $application->run(new ModelInvocationApplicationRunInput(
    new ReceiptIdentity('receipt.example'),
    new NeverCancelled(),
    new MonotonicExampleClock(),
));
```

`check()` follows the same validation and loading path without publication and
requires the exact generated target to exist already. Generated output is
ordinary trusted PHP and is not a sandbox.

## Failures and disclosure

Profile, generated-wiring, publication, load, workflow, lifecycle, and operation
failures use `KGN-MODEL-APPLICATION-1430` through `1436`. Context is closed,
bounded, and Safe. Deterministic strings omit request content, model output,
action arguments, credentials, endpoints, paths, generated bytes, and foreign
exception messages. Where the lower runtime already defines exact receipts,
cleanup failures, and previous throwables, the application preserves them
instead of flattening them. Native trace argument erasure is not claimed.

## Authority separation

The workflow ends at an `InvocationRuntimeResult`. It neither depends on nor
calls the Phase 14E bridge. A later bridge call is a separate explicit caller
action and still creates only inert proposal evidence. No plan, profile, model
result, suggestion, receipt, cost/usage claim, wiring record, or hash becomes
authentication, authorization, a grant, an external effect, or proof of effect.

Phase 14G's separate optional
[inspection leaf](model-invocation-inspection-and-evidence.md) accepts explicit
profile/wiring evidence and an optional completed detached result. It never
loads this package's generated PHP, starts an application, invokes an adapter,
or replays the result.

See [ADR 0152](../adr/0152-exact-current-single-profile-model-invocation-application-workflow.md),
the [runtime guide](model-invocation-runtime.md), the
[bridge guide](model-result-agent-proposal-bridge.md), the
[inspection guide](model-invocation-inspection-and-evidence.md), and the
[Phase 14 roadmap](phase-14-roadmap.md).

Phase 14H externally proves exact-current publication, explicit trusted loading,
one matching caller-supplied adapter, one terminal operation, and retirement.
There is still no registry, default, discovery, fallback, retained session, or
background work. See [Phase 14 external acceptance](phase-14-external-acceptance.md).

## Relationship to Phase 16 service binding

Phase 15A-H remain Accepted and Phase 15 is closed at commit
`b4cb57ffa01eea130a693daee0be605ae7f0dda5`. Phase 16A-H are Accepted and
Phase 16 is closed at commit `3690aab42e3759d3f42e711703d4f23e6588fa85`.

Model wiring, provider labels, plans, results, and receipts cannot choose or
authorize a binding, credential reference or lease, endpoint, resolution
evidence, selected address, transport, provider adapter, or current network
authority. Secrets stay application-owned and out of deterministic evidence
and developer output.

Phase 17A accepts [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md)
and the [adapter guide](openai-responses-provider-adapter.md). Phase 17B accepts
[ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
and adds only the optional offline request/response mapping leaf. It does not
implement this workflow, an adapter session, registry, default provider/model,
automatic retry, or tool effect. Phase 17C accepts [ADR
0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md) and uses the
existing explicit application-owned collaborators for one non-streaming
attempt. Phase 17D accepts [ADR
0174](../adr/0174-pull-based-openai-responses-sse-streaming.md) and selects the
same adapter with the exact streaming capability profile; consumer pulls drive
bounded SSE parsing and neutral events without a registry, workflow change,
background reader, reconnect, replay, or immediate first-token guarantee.
Phase 17E accepts [ADR
0175](../adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
for strict non-streaming structured and inert action profiles. Phase 17F
accepts [ADR 0176](../adr/0176-exact-current-openai-responses-application-workflow.md)
and adds one provider-specific outer workflow that delegates its explicit
`build()` and write-free `check()` to this accepted workflow, then runs one
single-use application through the returned exact loaded wiring. It does not
change this provider-neutral package or its ownership rules. Phase 17G adds a
separate optional offline inspection leaf; Phase 17H remains Planned and
separately unauthorized.
