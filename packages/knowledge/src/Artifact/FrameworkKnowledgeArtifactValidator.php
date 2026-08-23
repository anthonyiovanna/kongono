<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Internal\PathGuard;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;

/** Validates canonical structure and exact equality with current admitted sources. */
#[Api(ApiStatus::Experimental)]
final class FrameworkKnowledgeArtifactValidator
{
    public function validateStructure(string $artifactBytes): FrameworkKnowledgeArtifact
    {
        if (strlen($artifactBytes) > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
        }
        if (!str_ends_with($artifactBytes, "\n") || str_ends_with($artifactBytes, "\n\n") || str_contains($artifactBytes, "\r") || str_contains($artifactBytes, "\0") || str_starts_with($artifactBytes, "\xEF\xBB\xBF") || 1 !== preg_match('//u', $artifactBytes)) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'non_canonical_json');
        }

        try {
            $data = CanonicalJson::decodeObject($artifactBytes, 16);
        } catch (JsonException $exception) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_json', $exception);
        }
        if (CanonicalJson::encode($data) !== $artifactBytes) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'non_canonical_json');
        }

        $this->assertKeys($data, ['schema', 'version', 'scope', 'framework', 'knowledge_contract', 'inventory', 'generator', 'source_state', 'sources']);
        if ('kongono.framework-knowledge' !== $data['schema'] || 1 !== $data['version'] || 'phase4-foundation' !== $data['scope'] || 'kongono/framework' !== $data['framework']) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'unsupported_schema');
        }

        $contract = $this->object($data['knowledge_contract']);
        $inventory = $this->object($data['inventory']);
        $generator = $this->object($data['generator']);
        $state = $this->object($data['source_state']);
        $this->assertKeys($contract, ['path', 'sha256']);
        $this->assertKeys($inventory, ['path', 'sha256']);
        $this->assertKeys($generator, ['package', 'contract_version', 'implementation_sha256']);
        $this->assertKeys($state, ['source_set_sha256', 'source_count', 'source_bytes']);
        if ('docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md' !== $contract['path'] || !is_string($contract['sha256'])) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'contract_source_digest');
        }
        if ('packages/knowledge/resources/framework-source-inventory.json' !== $inventory['path'] || !is_string($inventory['sha256']) || !$this->isDigest($inventory['sha256'])) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'malformed_inventory_digest');
        }
        if ('kongono/knowledge' !== $generator['package'] || 1 !== $generator['contract_version'] || !is_string($generator['implementation_sha256']) || !$this->isDigest($generator['implementation_sha256'])) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'malformed_generator_digest');
        }
        if (!is_string($state['source_set_sha256']) || !$this->isDigest($state['source_set_sha256']) || !is_int($state['source_count']) || !is_int($state['source_bytes'])) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_scalar');
        }

        $records = $data['sources'];
        if (!is_array($records) || !array_is_list($records) || 31 !== count($records) || 31 !== $state['source_count']) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
        }
        $sources = [];
        $sourceSetRecords = [];
        $total = 0;
        $previousPath = null;
        $contractDigest = null;
        foreach ($records as $index => $recordValue) {
            $record = $this->object($recordValue);
            $this->assertKeys($record, ['reference', 'ordinal', 'path', 'authority', 'bytes', 'sha256', 'content']);
            if (!is_string($record['reference']) || !is_int($record['ordinal']) || !is_string($record['path']) || !is_string($record['authority']) || !is_int($record['bytes']) || !is_string($record['sha256']) || !is_string($record['content'])) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_scalar');
            }
            try {
                PathGuard::admit($record['path']);
                PathGuard::validateText($record['content']);
            } catch (InvalidKnowledgeSourceException $exception) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_scalar', $exception);
            }
            if (null !== $previousPath && 0 <= strcmp($previousPath, $record['path'])) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_order');
            }
            $authority = KnowledgeSourceAuthority::tryFrom($record['authority']);
            if (null === $authority || !in_array($authority, [KnowledgeSourceAuthority::GoverningAuthority, KnowledgeSourceAuthority::ImplementationEvidence, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation], true)) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_scalar');
            }
            $ordinal = $index + 1;
            if ($ordinal !== $record['ordinal']) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_order');
            }
            if (strlen($record['content']) !== $record['bytes']) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'content_length');
            }
            if (!$this->isDigest($record['sha256']) || hash('sha256', $record['content']) !== $record['sha256']) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'content_digest');
            }
            $total += $record['bytes'];
            if ($total > PathGuard::MAX_TOTAL_SOURCE_BYTES) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
            }
            $sourceSetRecords[] = implode("\t", [$record['path'], $record['authority'], (string) $record['bytes'], $record['sha256']]);
            $sources[] = new KnowledgeSource($record['reference'], $ordinal, $record['path'], $authority, $record['bytes'], $record['sha256'], $record['content']);
            if ($record['path'] === $contract['path']) {
                $contractDigest = $record['sha256'];
            }
            $previousPath = $record['path'];
        }

        $sourceSetSha256 = hash('sha256', implode("\n", $sourceSetRecords));
        if ($sourceSetSha256 !== $state['source_set_sha256']) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'source_set_digest');
        }
        foreach ($sources as $index => $source) {
            if (sprintf('kgn-source-v1:%s:%04d', $sourceSetSha256, $index + 1) !== $source->reference()) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_reference');
            }
        }
        if ($total !== $state['source_bytes']) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'content_length');
        }
        if ($contractDigest !== $contract['sha256']) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'contract_source_digest');
        }

        return new FrameworkKnowledgeArtifact($artifactBytes, hash('sha256', $artifactBytes), $sourceSetSha256, $generator['implementation_sha256'], 31, $total, $sources);
    }

    public function validateCurrent(string $frameworkRoot, string $artifactBytes): FrameworkKnowledgeArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new FrameworkKnowledgeArtifactGenerator())->generate($frameworkRoot);
        if ($current->canonicalBytes() === $artifactBytes) {
            return $artifact;
        }

        $given = CanonicalJson::decodeObject($artifactBytes, 16);
        $expected = CanonicalJson::decodeObject($current->canonicalBytes(), 16);
        $violation = match (true) {
            $given['inventory'] !== $expected['inventory'] => 'inventory_mismatch',
            $given['generator'] !== $expected['generator'] => 'generator_mismatch',
            $given['knowledge_contract'] !== $expected['knowledge_contract'] => 'contract_mismatch',
            default => 'source_state_mismatch',
        };
        throw new StaleKnowledgeArtifactException(KnowledgeDiagnostic::StaleArtifact, $violation);
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string>         $keys
     */
    private function assertKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private function isDigest(string $value): bool
    {
        return 1 === preg_match('/^[a-f0-9]{64}$/D', $value);
    }
}
