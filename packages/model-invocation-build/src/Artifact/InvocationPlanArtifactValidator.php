<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Exception\StaleInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Internal\CanonicalJson;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Throwable;

/** Strictly validates inert plan evidence and explicit-source currentness. */
#[Api(ApiStatus::Experimental)]
final class InvocationPlanArtifactValidator
{
    private const int MAX_ARTIFACT_BYTES = 65_536;

    public function validateStructure(string $artifactBytes): InvocationPlanArtifact
    {
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            $this->invalid('artifact_bytes', artifactBytes: strlen($artifactBytes), limit: self::MAX_ARTIFACT_BYTES);
        }
        try {
            $data = CanonicalJson::decodeObject($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($data), $artifactBytes)) {
                $this->invalid('noncanonical_json');
            }
        } catch (JsonException $failure) {
            throw InvalidInvocationPlanArtifactException::invalid('invalid_json', artifactBytes: strlen($artifactBytes), previous: $failure);
        }

        $this->keys($data, ['schema', 'artifact_version', 'contract_version', 'builder', 'plan', 'source_sha256']);
        if ('kongono.model-invocation-plan-artifact' !== ($data['schema'] ?? null) || 1 !== ($data['artifact_version'] ?? null) || 1 !== ($data['contract_version'] ?? null)) {
            $this->invalid('schema_or_version', 'schema');
        }
        $builder = $this->object($data['builder'] ?? null, 'builder');
        $this->keys($builder, ['package', 'version'], 'builder');
        if ('kongono/model-invocation-build' !== ($builder['package'] ?? null) || 1 !== ($builder['version'] ?? null)) {
            $this->invalid('builder_contract', 'builder');
        }

        $plan = $this->object($data['plan'] ?? null, 'plan');
        $this->keys($plan, ['plan_id', 'adapter_id', 'invocation_id', 'profile', 'request', 'budget'], 'plan');
        try {
            $planIdentity = new InvocationPlanIdentity($this->text($plan['plan_id'] ?? null, 'plan_id'));
            $adapterIdentity = new AdapterIdentity($this->text($plan['adapter_id'] ?? null, 'adapter_id'));
            $invocationIdentity = new InvocationIdentity($this->text($plan['invocation_id'] ?? null, 'invocation_id'));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidInvocationPlanArtifactException::invalid('identity_grammar', previous: $failure);
        }

        $profile = $this->object($plan['profile'] ?? null, 'profile');
        $this->keys($profile, ['profile_id', 'provider_id', 'model_id', 'capabilities'], 'profile');
        try {
            new ProfileIdentity($this->text($profile['profile_id'] ?? null, 'profile_id'));
            new ProviderIdentity($this->text($profile['provider_id'] ?? null, 'provider_id'));
            new ModelIdentity($this->text($profile['model_id'] ?? null, 'model_id'));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidInvocationPlanArtifactException::invalid('identity_grammar', previous: $failure);
        }
        $capabilities = $this->capabilities($profile['capabilities'] ?? null);

        $request = $this->object($plan['request'] ?? null, 'request');
        $this->keys($request, ['request_sha256', 'result_mode', 'streaming', 'limits'], 'request');
        $requestSha256 = $this->digest($request['request_sha256'] ?? null, 'request_sha256');
        $mode = ResultMode::tryFrom($this->text($request['result_mode'] ?? null, 'result_mode'));
        $streaming = $request['streaming'] ?? null;
        if (null === $mode || !is_bool($streaming)) {
            $this->invalid('invalid_scalar', null === $mode ? 'result_mode' : 'streaming');
        }
        $this->validateCompatibility($capabilities, $mode, $streaming);

        $limits = $this->object($request['limits'] ?? null, 'limits');
        $this->keys($limits, ['max_output_bytes', 'max_stream_events', 'max_usage_entries'], 'limits');
        $this->boundedInt($limits['max_output_bytes'] ?? null, 1, 1_048_576, 'max_output_bytes');
        $this->boundedInt($limits['max_stream_events'] ?? null, 1, 4_096, 'max_stream_events');
        $this->boundedInt($limits['max_usage_entries'] ?? null, 0, 16, 'max_usage_entries');
        $this->budget($plan['budget'] ?? null);
        $sourceSha256 = $this->digest($data['source_sha256'] ?? null, 'source_sha256');

        return new InvocationPlanArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSha256,
            $requestSha256,
            $planIdentity,
            $adapterIdentity,
            $invocationIdentity,
        );
    }

    public function validateCurrent(InvocationPlanSource $currentSource, string $artifactBytes): InvocationPlanArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new InvocationPlanArtifactBuilder())->build($currentSource);
        if (
            !hash_equals($current->canonicalBytes(), $artifactBytes)
            || !hash_equals($current->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($current->requestSha256(), $artifact->requestSha256())
            || !hash_equals($current->sourceSha256(), $artifact->sourceSha256())
        ) {
            throw StaleInvocationPlanArtifactException::currentnessMismatch();
        }

        return $artifact;
    }

    /** @return list<ProviderCapability> */
    private function capabilities(mixed $value): array
    {
        $list = $this->list($value, 6, 'capabilities', false);
        if ([] === $list) {
            $this->invalid('invalid_list', 'capabilities', 0);
        }
        $order = array_flip(array_map(static fn(ProviderCapability $capability): string => $capability->value, ProviderCapability::cases()));
        $previous = -1;
        $capabilities = [];
        foreach ($list as $position => $raw) {
            if (!is_string($raw)) {
                $this->invalid('invalid_scalar', 'capabilities', count($list), $position);
            }
            $capability = ProviderCapability::tryFrom($raw);
            if (null === $capability || $order[$raw] <= $previous) {
                $this->invalid('noncanonical_order', 'capabilities', count($list), $position);
            }
            $previous = $order[$raw];
            $capabilities[] = $capability;
        }

        return $capabilities;
    }

    /** @param list<ProviderCapability> $capabilities */
    private function validateCompatibility(array $capabilities, ResultMode $mode, bool $streaming): void
    {
        $required = match ($mode) {
            ResultMode::Text => ProviderCapability::TextResult,
            ResultMode::Structured => ProviderCapability::StructuredResult,
            ResultMode::ActionSuggestion => ProviderCapability::ActionSuggestion,
        };
        if (!in_array($required, $capabilities, true) || ($streaming && !in_array(ProviderCapability::Streaming, $capabilities, true))) {
            $this->invalid('capability_compatibility', 'capabilities');
        }
    }

    private function budget(mixed $value): void
    {
        $budget = $this->object($value, 'budget');
        $this->keys($budget, ['max_duration_milliseconds', 'max_attempts', 'max_retained_evidence_bytes', 'usage_limits', 'cost_limit'], 'budget');
        $this->boundedInt($budget['max_duration_milliseconds'] ?? null, 1, 300_000, 'max_duration_milliseconds');
        if (1 !== ($budget['max_attempts'] ?? null)) {
            $this->invalid('invalid_scalar', 'max_attempts');
        }
        $this->boundedInt($budget['max_retained_evidence_bytes'] ?? null, 1, 1_048_576, 'max_retained_evidence_bytes');
        $usage = $this->list($budget['usage_limits'] ?? null, 16, 'usage_limits', true);
        $previous = null;
        foreach ($usage as $position => $raw) {
            $entry = $this->object($raw, 'usage_limits', $position);
            $this->keys($entry, ['metric', 'unit', 'maximum_quantity'], 'usage_limits', $position);
            try {
                $metric = new UsageMetricName($this->text($entry['metric'] ?? null, 'metric', $position));
                $unit = new UsageUnitName($this->text($entry['unit'] ?? null, 'unit', $position));
            } catch (Throwable $failure) {
                if ($failure instanceof \Error) {
                    throw $failure;
                }
                throw InvalidInvocationPlanArtifactException::invalid('identity_grammar', 'usage_limits', count($usage), $position, previous: $failure);
            }
            $this->boundedInt($entry['maximum_quantity'] ?? null, 0, 9_007_199_254_740_991, 'maximum_quantity', $position);
            $key = $metric->value() . '|' . $unit->value();
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                $this->invalid('noncanonical_order', 'usage_limits', count($usage), $position);
            }
            $previous = $key;
        }
        $cost = $budget['cost_limit'] ?? null;
        if (null === $cost) {
            return;
        }
        $costObject = $this->object($cost, 'cost_limit');
        $this->keys($costObject, ['currency', 'maximum_amount'], 'cost_limit');
        try {
            new CurrencyCode($this->text($costObject['currency'] ?? null, 'currency'));
            new DecimalAmount($this->text($costObject['maximum_amount'] ?? null, 'maximum_amount'));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidInvocationPlanArtifactException::invalid('invalid_scalar', 'cost_limit', previous: $failure);
        }
    }

    /**
     * @param array<string, mixed> $object
     * @param list<string>         $expected
     */
    private function keys(array $object, array $expected, ?string $field = null, ?int $position = null): void
    {
        if (array_keys($object) !== $expected) {
            $this->invalid('invalid_shape', $field, position: $position);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, ?string $field = null, ?int $position = null): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape', $field, position: $position);
        }
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                $this->invalid('invalid_shape', $field, position: $position);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(mixed $value, int $maximum, string $field, bool $emptyAllowed): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_list', $field);
        }
        if (count($value) > $maximum || (!$emptyAllowed && [] === $value)) {
            $this->invalid('invalid_list', $field, count($value), limit: $maximum);
        }

        return $value;
    }

    private function text(mixed $value, string $field, ?int $position = null): string
    {
        if (!is_string($value)) {
            $this->invalid('invalid_scalar', $field, position: $position);
        }

        return $value;
    }

    private function digest(mixed $value, string $field): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('digest_grammar', $field);
        }

        return $value;
    }

    private function boundedInt(mixed $value, int $minimum, int $maximum, string $field, ?int $position = null): int
    {
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            $this->invalid('invalid_scalar', $field, position: $position, limit: $maximum);
        }

        return $value;
    }

    private function invalid(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
        ?int $artifactBytes = null,
    ): never {
        throw InvalidInvocationPlanArtifactException::invalid($violation, $field, $count, $position, $limit, $artifactBytes);
    }
}
