<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\InvalidOpenAiResponsesInspectionException;
use Kongono\ModelProviderOpenAiResponsesInspection\Internal\CanonicalJson;

/** Carries only protected bindings, closed tokens, booleans, and Safe aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesInspection
{
    public const string SCHEMA = 'kongono.openai-responses-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'openai-responses-v1';
    public const string DISCLOSURE = 'safe-aggregate-only';
    public const string EVIDENCE_POSTURE = 'bounded-claim';
    public const string GRAPH_RELATIONSHIP = 'not-established';
    public const string AUTHORITY = 'not-evaluated';
    public const string ROLE_EXECUTION = 'not-performed';
    public const string EXTERNAL_CURRENTNESS = 'not-observed';
    public const string EFFECTS = 'no-replay-repair-recording-exporting-or-certification';

    private string $identity;
    /** @var array<string, string|null> */
    private array $protectedBindings;
    /** @var array<string, string> */
    private array $tokens;
    /** @var array<string, bool> */
    private array $flags;
    /** @var array<string, int> */
    private array $counts;

    /**
     * @param array<array-key, mixed> $protectedBindings
     * @param array<array-key, mixed> $tokens
     * @param array<array-key, mixed> $flags
     * @param array<array-key, mixed> $counts
     */
    public function __construct(
        array $protectedBindings,
        array $tokens,
        array $flags,
        array $counts,
    ) {
        $bindingKeys = ['application_profile_sha256', 'neutral_application_profile_sha256', 'plan_source_sha256', 'plan_artifact_sha256', 'plan_request_sha256', 'external_service_profile_sha256', 'binding_sha256', 'receipt_sha256', 'result_sha256'];
        $tokenKeys = ['mapping_version', 'result_mode', 'storage_preference', 'streaming', 'outcome', 'lifecycle', 'finish_reason', 'evidence_posture'];
        $flagKeys = ['structured_output_present', 'function_definition_present', 'runtime_evidence_present', 'result_present', 'usage_present', 'cost_present', 'events_present', 'cleanup_complete'];
        $countKeys = ['message_count', 'capability_count', 'usage_quantity_count', 'usage_quantity_total', 'event_count', 'attempt_count', 'elapsed_milliseconds', 'retained_evidence_bytes'];
        if ($bindingKeys !== array_keys($protectedBindings) || $tokenKeys !== array_keys($tokens) || $flagKeys !== array_keys($flags) || $countKeys !== array_keys($counts)) {
            throw InvalidOpenAiResponsesInspectionException::invalid('shape');
        }
        $acceptedBindings = [];
        foreach ($bindingKeys as $key) {
            $digest = $protectedBindings[$key];
            $optional = in_array($key, ['receipt_sha256', 'result_sha256'], true);
            if ((null === $digest && !$optional) || (null !== $digest && (!is_string($digest) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)))) {
                throw InvalidOpenAiResponsesInspectionException::invalid('digest');
            }
            $acceptedBindings[$key] = $digest;
        }
        $acceptedTokens = [];
        foreach ($tokenKeys as $key) {
            $token = $tokens[$key];
            if (!is_string($token) || '' === $token || strlen($token) > 96 || 1 !== preg_match('#\A[a-z0-9._/-]+\z#D', $token)) {
                throw InvalidOpenAiResponsesInspectionException::invalid('token');
            }
            $acceptedTokens[$key] = $token;
        }
        $acceptedFlags = [];
        foreach ($flagKeys as $key) {
            $flag = $flags[$key];
            if (!is_bool($flag)) {
                throw InvalidOpenAiResponsesInspectionException::invalid('flag');
            }
            $acceptedFlags[$key] = $flag;
        }
        $acceptedCounts = [];
        foreach ($countKeys as $key) {
            $count = $counts[$key];
            if (!is_int($count) || $count < 0) {
                throw InvalidOpenAiResponsesInspectionException::invalid('aggregate');
            }
            $acceptedCounts[$key] = $count;
        }
        $runtime = $acceptedFlags['runtime_evidence_present'];
        if (!$runtime && (null !== $acceptedBindings['receipt_sha256'] || null !== $acceptedBindings['result_sha256'] || $acceptedFlags['result_present'] || $acceptedFlags['usage_present'] || $acceptedFlags['cost_present'] || $acceptedFlags['events_present'] || $acceptedFlags['cleanup_complete'] || 0 !== array_sum(array_slice($acceptedCounts, 2)))) {
            throw InvalidOpenAiResponsesInspectionException::invalid('runtime_structure');
        }
        if ($acceptedFlags['result_present'] !== (null !== $acceptedBindings['result_sha256']) || $runtime !== (null !== $acceptedBindings['receipt_sha256'])) {
            throw InvalidOpenAiResponsesInspectionException::invalid('runtime_structure');
        }
        $this->protectedBindings = $acceptedBindings;
        $this->tokens = $acceptedTokens;
        $this->flags = $acceptedFlags;
        $this->counts = $acceptedCounts;
        $this->identity = hash('sha256', CanonicalJson::encode(['schema' => self::SCHEMA, 'version' => self::VERSION] + $this->evidencePayload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }
    /** @return array<string, string|null> */ public function protectedBindings(): array
    {
        return $this->protectedBindings;
    }
    /** @return array<string, string> */ public function tokens(): array
    {
        return $this->tokens;
    }
    /** @return array<string, bool> */ public function flags(): array
    {
        return $this->flags;
    }
    /** @return array<string, int> */ public function counts(): array
    {
        return $this->counts;
    }
    public function runtimeEvidenceSupplied(): bool
    {
        return $this->flags['runtime_evidence_present'];
    }
    public function messageCount(): int
    {
        return $this->counts['message_count'];
    }
    public function capabilityCount(): int
    {
        return $this->counts['capability_count'];
    }
    public function usageQuantityCount(): int
    {
        return $this->counts['usage_quantity_count'];
    }
    public function streaming(): bool
    {
        return 'enabled' === $this->tokens['streaming'];
    }
    public function resultPresent(): bool
    {
        return $this->flags['result_present'];
    }
    public function usagePresent(): bool
    {
        return $this->flags['usage_present'];
    }
    public function costPresent(): bool
    {
        return $this->flags['cost_present'];
    }
    public function cleanupComplete(): bool
    {
        return $this->flags['cleanup_complete'];
    }
    public function scope(): string
    {
        return self::SCOPE;
    }
    public function disclosure(): string
    {
        return self::DISCLOSURE;
    }
    public function authority(): string
    {
        return self::AUTHORITY;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['schema' => self::SCHEMA, 'version' => self::VERSION, 'inspection_identity' => $this->identity] + $this->evidencePayload();
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'protected_bindings' => $this->protectedBindings,
            'tokens' => $this->tokens,
            'flags' => $this->flags,
            'counts' => $this->counts,
            'limitations' => [
                'profile_currentness' => 'caller-relative',
                'live_application_provider_network_credential_account_model_state' => self::EXTERNAL_CURRENTNESS,
                'detached_runtime_evidence' => self::EVIDENCE_POSTURE,
                'graph_relationship' => self::GRAPH_RELATIONSHIP,
                'authority' => self::AUTHORITY,
                'role_execution' => self::ROLE_EXECUTION,
                'effects' => self::EFFECTS,
            ],
        ];
    }
}
