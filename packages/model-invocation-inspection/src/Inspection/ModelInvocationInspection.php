<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationInspection\Exception\InvalidModelInvocationInspectionException;
use Kongono\ModelInvocationInspection\Internal\CanonicalJson;

/** Carries only protected digests, Safe aggregates, and fixed limitation tokens. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationInspection
{
    public const string SCHEMA = 'kongono.model-invocation-inspection';
    public const int VERSION = 1;
    public const string PROFILE_VERSION = 'model-invocation-application-profile-v1';
    public const string WIRING_VERSION = 'model-invocation-application-generated-wiring-v1';
    public const string SCOPE = 'model-invocation-v1';
    public const string DISCLOSURE = 'safe-aggregate-only';
    public const string SUPPLIED_PROFILE_WIRING = 'exact-supplied-profile';
    public const string EXTERNAL_PROFILE_PROVIDER_CURRENTNESS = 'not-established';
    public const string APPLICATION_STATE = 'not-observed';
    public const string PROVIDER_ACCOUNT_NETWORK_STATE = 'not-observed';
    public const string EXTERNAL_EFFECT = 'not-established';
    public const string AUTHORITY = 'evidence-only';
    public const string GRAPH_RELATIONSHIP = 'not-established';
    public const string TRUSTED_CODE_ISOLATION = 'not-sandboxed';

    private string $identity;

    public function __construct(
        private string $applicationProfileSha256,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $requestSha256,
        private string $generatedWiringSha256,
        private string $generatorImplementationSha256,
        private int $contractVersion,
        private int $messageCount,
        private int $capabilityCount,
        private int $usageLimitCount,
        private bool $streaming,
        private ?InvocationOutcome $runtimeOutcome = null,
        private ?InvocationState $runtimeState = null,
        private ?FinishReason $finishReason = null,
        private int $eventCount = 0,
        private int $usageEntryCount = 0,
        private int $attemptCount = 0,
        private int $elapsedMilliseconds = 0,
        private int $retainedEvidenceBytes = 0,
        private int $outputBytes = 0,
        private bool $resultPresent = false,
        private bool $costPresent = false,
        private bool $cleanupComplete = false,
        private string $profileVersion = self::PROFILE_VERSION,
        private string $wiringVersion = self::WIRING_VERSION,
        private string $scope = self::SCOPE,
        private string $disclosure = self::DISCLOSURE,
        private string $suppliedProfileWiring = self::SUPPLIED_PROFILE_WIRING,
        private string $externalProfileProviderCurrentness = self::EXTERNAL_PROFILE_PROVIDER_CURRENTNESS,
        private string $applicationState = self::APPLICATION_STATE,
        private string $providerAccountNetworkState = self::PROVIDER_ACCOUNT_NETWORK_STATE,
        private string $externalEffect = self::EXTERNAL_EFFECT,
        private string $authority = self::AUTHORITY,
        private string $graphRelationship = self::GRAPH_RELATIONSHIP,
        private string $trustedCodeIsolation = self::TRUSTED_CODE_ISOLATION,
    ) {
        foreach ([$applicationProfileSha256, $artifactSha256, $sourceSha256, $requestSha256, $generatedWiringSha256, $generatorImplementationSha256] as $digest) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw InvalidModelInvocationInspectionException::invalid('digest');
            }
        }
        $counts = [$messageCount, $capabilityCount, $usageLimitCount, $eventCount, $usageEntryCount, $attemptCount, $elapsedMilliseconds, $retainedEvidenceBytes, $outputBytes];
        if (array_any($counts, static fn(int $count): bool => $count < 0) || 1 !== $contractVersion) {
            throw InvalidModelInvocationInspectionException::invalid('aggregate');
        }
        $runtimeSupplied = null !== $runtimeOutcome;
        if ($runtimeSupplied !== (null !== $runtimeState)
            || (!$runtimeSupplied && (null !== $finishReason || 0 !== $eventCount || 0 !== $usageEntryCount || 0 !== $attemptCount || 0 !== $elapsedMilliseconds || 0 !== $retainedEvidenceBytes || 0 !== $outputBytes || $resultPresent || $costPresent || $cleanupComplete))
            || ($runtimeSupplied && InvocationState::Retired !== $runtimeState)
            || ($resultPresent !== (null !== $finishReason))) {
            throw InvalidModelInvocationInspectionException::invalid('runtime_structure');
        }
        if (self::PROFILE_VERSION !== $profileVersion || self::WIRING_VERSION !== $wiringVersion || self::SCOPE !== $scope
            || self::DISCLOSURE !== $disclosure || self::SUPPLIED_PROFILE_WIRING !== $suppliedProfileWiring
            || self::EXTERNAL_PROFILE_PROVIDER_CURRENTNESS !== $externalProfileProviderCurrentness || self::APPLICATION_STATE !== $applicationState
            || self::PROVIDER_ACCOUNT_NETWORK_STATE !== $providerAccountNetworkState || self::EXTERNAL_EFFECT !== $externalEffect
            || self::AUTHORITY !== $authority || self::GRAPH_RELATIONSHIP !== $graphRelationship || self::TRUSTED_CODE_ISOLATION !== $trustedCodeIsolation) {
            throw InvalidModelInvocationInspectionException::invalid('semantic_tokens');
        }
        $this->identity = hash('sha256', CanonicalJson::encode(['schema' => self::SCHEMA, 'version' => self::VERSION] + $this->payload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }
    public function applicationProfileSha256(): string
    {
        return $this->applicationProfileSha256;
    }
    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }
    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
    public function requestSha256(): string
    {
        return $this->requestSha256;
    }
    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }
    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }
    public function contractVersion(): int
    {
        return $this->contractVersion;
    }
    public function messageCount(): int
    {
        return $this->messageCount;
    }
    public function capabilityCount(): int
    {
        return $this->capabilityCount;
    }
    public function usageLimitCount(): int
    {
        return $this->usageLimitCount;
    }
    public function streaming(): bool
    {
        return $this->streaming;
    }
    public function runtimeEvidenceSupplied(): bool
    {
        return null !== $this->runtimeOutcome;
    }
    public function runtimeOutcome(): ?InvocationOutcome
    {
        return $this->runtimeOutcome;
    }
    public function runtimeState(): ?InvocationState
    {
        return $this->runtimeState;
    }
    public function finishReason(): ?FinishReason
    {
        return $this->finishReason;
    }
    public function eventCount(): int
    {
        return $this->eventCount;
    }
    public function usageEntryCount(): int
    {
        return $this->usageEntryCount;
    }
    public function attemptCount(): int
    {
        return $this->attemptCount;
    }
    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }
    public function retainedEvidenceBytes(): int
    {
        return $this->retainedEvidenceBytes;
    }
    public function outputBytes(): int
    {
        return $this->outputBytes;
    }
    public function resultPresent(): bool
    {
        return $this->resultPresent;
    }
    public function costPresent(): bool
    {
        return $this->costPresent;
    }
    public function cleanupComplete(): bool
    {
        return $this->cleanupComplete;
    }
    public function scope(): string
    {
        return $this->scope;
    }
    public function disclosure(): string
    {
        return $this->disclosure;
    }
    public function suppliedProfileWiring(): string
    {
        return $this->suppliedProfileWiring;
    }
    public function externalProfileProviderCurrentness(): string
    {
        return $this->externalProfileProviderCurrentness;
    }
    public function applicationState(): string
    {
        return $this->applicationState;
    }
    public function providerAccountNetworkState(): string
    {
        return $this->providerAccountNetworkState;
    }
    public function externalEffect(): string
    {
        return $this->externalEffect;
    }
    public function authority(): string
    {
        return $this->authority;
    }
    public function graphRelationship(): string
    {
        return $this->graphRelationship;
    }
    public function trustedCodeIsolation(): string
    {
        return $this->trustedCodeIsolation;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['schema' => self::SCHEMA, 'version' => self::VERSION, 'inspection_identity' => $this->identity] + $this->payload();
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'protected_bindings' => [
                'application_profile_sha256' => $this->applicationProfileSha256,
                'artifact_sha256' => $this->artifactSha256,
                'source_sha256' => $this->sourceSha256,
                'request_sha256' => $this->requestSha256,
                'generated_wiring_sha256' => $this->generatedWiringSha256,
                'generator_implementation_sha256' => $this->generatorImplementationSha256,
                'disclosure' => 'protected',
            ],
            'contract_version' => $this->contractVersion,
            'profile_version' => $this->profileVersion,
            'wiring_version' => $this->wiringVersion,
            'message_count' => $this->messageCount,
            'capability_count' => $this->capabilityCount,
            'usage_limit_count' => $this->usageLimitCount,
            'streaming' => $this->streaming,
            'runtime_evidence_supplied' => $this->runtimeEvidenceSupplied(),
            'runtime_summary' => $this->runtimeSummary(),
            'limitations' => [
                'scope' => $this->scope,
                'disclosure' => $this->disclosure,
                'supplied_profile_wiring' => $this->suppliedProfileWiring,
                'external_profile_provider_currentness' => $this->externalProfileProviderCurrentness,
                'application_state' => $this->applicationState,
                'provider_account_network_state' => $this->providerAccountNetworkState,
                'external_effect' => $this->externalEffect,
                'authority' => $this->authority,
                'graph_relationship' => $this->graphRelationship,
                'trusted_generated_in_process_php' => $this->trustedCodeIsolation,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function runtimeSummary(): array
    {
        if (null === $this->runtimeOutcome || null === $this->runtimeState) {
            return [];
        }

        return [
            'outcome' => $this->runtimeOutcome->value,
            'state' => $this->runtimeState->value,
            'finish_reason' => null === $this->finishReason ? 'none' : $this->finishReason->value,
            'event_count' => $this->eventCount,
            'usage_entry_count' => $this->usageEntryCount,
            'attempt_count' => $this->attemptCount,
            'elapsed_milliseconds' => $this->elapsedMilliseconds,
            'retained_evidence_bytes' => $this->retainedEvidenceBytes,
            'output_bytes' => $this->outputBytes,
            'result_present' => $this->resultPresent,
            'cost_present' => $this->costPresent,
            'cleanup_complete' => $this->cleanupComplete,
        ];
    }
}
