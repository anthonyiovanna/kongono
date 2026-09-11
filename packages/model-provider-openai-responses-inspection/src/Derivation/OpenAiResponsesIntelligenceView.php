<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\InvalidOpenAiResponsesIntelligenceEvidenceException;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidence;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection;
use Kongono\ModelProviderOpenAiResponsesInspection\Internal\CanonicalJson;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;

/** Carries one presentation-neutral role-addressed view without executing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesIntelligenceView
{
    private string $serializationSha256;

    public function __construct(
        private OpenAiResponsesInspectionDeveloperRole $role,
        private string $purpose,
        private OpenAiResponsesInspection $inspection,
        private OpenAiResponsesGraphEvidence $graphEvidence,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $networking = 'not-performed',
        private string $providerObservation = 'not-performed',
        private string $replay = 'not-performed',
        private string $recording = 'not-performed',
        private string $exporting = 'not-performed',
        private string $certification = 'not-performed',
        private string $authority = 'not-evaluated',
        ?string $serializationSha256 = null,
    ) {
        if (!in_array($purpose, ['explain', 'verify', 'teach'], true)
            || !in_array($roleExecution, ['not-performed', 'not-observed'], true)
            || !in_array($repair, ['not-performed', 'not-observed'], true)
            || !in_array($hiddenDataAccess, ['none', 'not-established'], true)
            || 'not-performed' !== $networking || 'not-performed' !== $providerObservation
            || 'not-performed' !== $replay || 'not-performed' !== $recording
            || 'not-performed' !== $exporting || 'not-performed' !== $certification
            || 'not-evaluated' !== $authority) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('role_evidence');
        }
        $expected = hash('sha256', CanonicalJson::encode($this->evidencePayload()));
        if (null !== $serializationSha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256)) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): OpenAiResponsesInspectionDeveloperRole
    {
        return $this->role;
    }
    public function purpose(): string
    {
        return $this->purpose;
    }
    public function inspection(): OpenAiResponsesInspection
    {
        return $this->inspection;
    }
    public function graphEvidence(): OpenAiResponsesGraphEvidence
    {
        return $this->graphEvidence;
    }
    public function roleExecution(): string
    {
        return $this->roleExecution;
    }
    public function repair(): string
    {
        return $this->repair;
    }
    public function hiddenDataAccess(): string
    {
        return $this->hiddenDataAccess;
    }
    public function networking(): string
    {
        return $this->networking;
    }
    public function providerObservation(): string
    {
        return $this->providerObservation;
    }
    public function replay(): string
    {
        return $this->replay;
    }
    public function recording(): string
    {
        return $this->recording;
    }
    public function exporting(): string
    {
        return $this->exporting;
    }
    public function certification(): string
    {
        return $this->certification;
    }
    public function authority(): string
    {
        return $this->authority;
    }
    public function serializationSha256(): string
    {
        return $this->serializationSha256;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->evidencePayload() + ['serialization_sha256' => $this->serializationSha256];
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'schema' => 'kongono.openai-responses-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'role_execution' => $this->roleExecution,
            'repair' => $this->repair,
            'hidden_data_access' => $this->hiddenDataAccess,
            'networking' => $this->networking,
            'provider_observation' => $this->providerObservation,
            'replay' => $this->replay,
            'recording' => $this->recording,
            'exporting' => $this->exporting,
            'certification' => $this->certification,
            'authority' => $this->authority,
        ];
    }
}
