<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionInspection\Exception\InvalidExtensionInspectionValueException;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;

/** Carries deterministic bounded evidence from one independent comparison. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionIntelligenceVerification
{
    public const array CHECK_IDS = ['identity-binding', 'declaration-counts', 'category-lifecycle-counts', 'capability-counts', 'contribution-counts', 'visibility-semantics', 'authority-runtime-semantics'];
    /** @var array<string, bool> */ private array $checkOutcomes;
    /** @var list<string> */ private array $failureTokens;

    /**
     * @param array<array-key, mixed> $checkOutcomes
     * @param array<array-key, mixed> $failureTokens
     */
    public function __construct(private ExtensionIntelligenceVerdict $verdict, private DeveloperIntelligenceRole $role, private string $declarationArtifactSha256, private DeclarationSetIdentity $declarationSetIdentity, array $checkOutcomes, array $failureTokens)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $declarationArtifactSha256)) {
            throw InvalidExtensionInspectionValueException::verification('digest');
        }
        if (self::CHECK_IDS !== array_keys($checkOutcomes)) {
            throw InvalidExtensionInspectionValueException::verification('check_order');
        }
        $accepted = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checkOutcomes[$id])) {
                throw InvalidExtensionInspectionValueException::verification('check_value');
            } $accepted[$id] = $checkOutcomes[$id];
        }
        $expected = [];
        foreach ($accepted as $id => $passed) {
            if (!$passed) {
                $expected[] = $id . '-failed';
            }
        }
        if ($expected !== $failureTokens) {
            throw InvalidExtensionInspectionValueException::verification('failure_order');
        }
        if (($expected === [] ? ExtensionIntelligenceVerdict::Pass : ExtensionIntelligenceVerdict::Fail) !== $verdict) {
            throw InvalidExtensionInspectionValueException::verification('verdict');
        }
        $this->checkOutcomes = $accepted;
        $this->failureTokens = [...$expected];
    }

    public function verdict(): ExtensionIntelligenceVerdict
    {
        return $this->verdict;
    }
    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }
    public function declarationArtifactSha256(): string
    {
        return $this->declarationArtifactSha256;
    }
    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }
    /** @return array<string, bool> */ public function checkOutcomes(): array
    {
        return [...$this->checkOutcomes];
    }
    /** @return list<string> */ public function failureTokens(): array
    {
        return [...$this->failureTokens];
    }
    /** @return array<string, mixed> */ public function toArray(): array
    {
        return ['schema' => 'kongono.extension-intelligence-verification', 'version' => 1, 'verdict' => $this->verdict->value, 'role' => $this->role->value, 'declaration_artifact_sha256' => $this->declarationArtifactSha256, 'declaration_set_identity' => $this->declarationSetIdentity->value(), 'checks' => [...$this->checkOutcomes], 'failures' => [...$this->failureTokens]];
    }
}
