<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Returns a deterministic, unapplied, reversible review candidate and bounded provenance. */
#[Api(ApiStatus::Experimental)]
final readonly class ChiePatchProposal
{
    /** @var list<string> */
    private array $warnings;
    private string $proposalSha256;

    /** @param list<string> $warnings */
    public function __construct(
        private string $frameworkArtifactSha256,
        private string $frameworkSourceSetSha256,
        private string $targetPath,
        private string $baseSha256,
        private string $proposedSha256,
        private string $forwardDiff,
        private string $reverseDiff,
        private ChiePatchProvenance $provenance,
        private ChiePatchHandoff $hanteiHandoff,
        array $warnings,
    ) {
        $expectedWarnings = ['generated-candidate-not-authority', 'candidate-not-applied', 'candidate-not-verified'];
        if (
            !$this->sha($frameworkArtifactSha256)
            || !$this->sha($frameworkSourceSetSha256)
            || !$this->sha($baseSha256)
            || !$this->sha($proposedSha256)
            || $targetPath !== $provenance->path()
            || $baseSha256 !== $provenance->sourceSha256()
            || $proposedSha256 !== $hanteiHandoff->expectedTargetSha256()
            || $warnings !== $expectedWarnings
            || strlen($forwardDiff) > 65536
            || strlen($reverseDiff) > 65536
            || !str_starts_with($forwardDiff, "--- a/{$targetPath}\n+++ b/{$targetPath}\n@@ ")
            || !str_starts_with($reverseDiff, "--- a/{$targetPath}\n+++ b/{$targetPath}\n@@ ")
            || !str_ends_with($forwardDiff, "\n")
            || !str_ends_with($reverseDiff, "\n")
            || str_contains($forwardDiff . $reverseDiff, "\r")
        ) {
            throw InvalidChieValueException::proposal();
        }
        $this->warnings = [...$warnings];
        $payload = json_encode($this->semanticArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 32) . "\n";
        $this->proposalSha256 = strtoupper(hash('sha256', $payload));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $semantic = $this->semanticArray();

        return array_slice($semantic, 0, 12, true)
            + ['proposal_sha256' => $this->proposalSha256]
            + array_slice($semantic, 12, null, true);
    }

    public function proposalSha256(): string
    {
        return $this->proposalSha256;
    }

    public function forwardDiff(): string
    {
        return $this->forwardDiff;
    }

    public function reverseDiff(): string
    {
        return $this->reverseDiff;
    }

    public function proposedSha256(): string
    {
        return $this->proposedSha256;
    }

    /** @return array<string, mixed> */
    private function semanticArray(): array
    {
        return [
            'schema' => 'kongono.chie-patch-proposal',
            'version' => 1,
            'role' => 'chie',
            'status' => ChieProposalStatus::Candidate->value,
            'operation' => 'propose',
            'rule' => 'exact-line-replacement-v1',
            'authority' => 'current-explanatory-documentation',
            'framework_artifact_sha256' => $this->frameworkArtifactSha256,
            'framework_source_set_sha256' => $this->frameworkSourceSetSha256,
            'target_path' => $this->targetPath,
            'base_sha256' => $this->baseSha256,
            'proposed_sha256' => $this->proposedSha256,
            'forward_diff' => $this->forwardDiff,
            'reverse_diff' => $this->reverseDiff,
            'provenance' => $this->provenance->toArray(),
            'hantei_handoff' => $this->hanteiHandoff->toArray(),
            'warnings' => [...$this->warnings],
        ];
    }

    private function sha(string $value): bool
    {
        return 1 === preg_match('/^[A-F0-9]{64}$/D', $value);
    }
}
