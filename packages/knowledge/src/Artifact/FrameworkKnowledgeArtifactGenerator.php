<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Internal\PathGuard;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceInventory;

/** Completely regenerates the closed Phase 4 foundation artifact in memory. */
#[Api(ApiStatus::Experimental)]
final class FrameworkKnowledgeArtifactGenerator
{
    public const MAX_ARTIFACT_BYTES = 4194304;
    public const GENERATOR_CONTRACT_VERSION = 1;

    /** @var list<string> */
    private const IMPLEMENTATION_PATHS = [
        'bin/kongono-knowledge',
        'src/Artifact/FrameworkKnowledgeArtifact.php',
        'src/Artifact/FrameworkKnowledgeArtifactGenerator.php',
        'src/Artifact/FrameworkKnowledgeArtifactPublisher.php',
        'src/Artifact/FrameworkKnowledgeArtifactValidator.php',
        'src/Command/KnowledgeCommand.php',
        'src/Diagnostic/KnowledgeDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/InvalidKnowledgeArtifactException.php',
        'src/Exception/InvalidKnowledgeSourceException.php',
        'src/Exception/KnowledgeException.php',
        'src/Exception/KnowledgePublicationException.php',
        'src/Exception/StaleKnowledgeArtifactException.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/PathGuard.php',
        'src/Source/KnowledgeSource.php',
        'src/Source/KnowledgeSourceAuthority.php',
        'src/Source/KnowledgeSourceDefinition.php',
        'src/Source/KnowledgeSourceInventory.php',
    ];

    public function generate(string $frameworkRoot): FrameworkKnowledgeArtifact
    {
        $resolvedRoot = PathGuard::resolveRoot($frameworkRoot);
        $packageRoot = PathGuard::resolveRoot(dirname(__DIR__, 2));
        $inventory = KnowledgeSourceInventory::load($packageRoot);
        $sourceRecords = [];
        $sourceSetRecords = [];
        $rawSources = [];
        $totalBytes = 0;

        foreach ($inventory->definitions() as $position => $definition) {
            $content = PathGuard::read($resolvedRoot, $definition->path);
            $bytes = strlen($content);
            $totalBytes += $bytes;
            if ($totalBytes > PathGuard::MAX_TOTAL_SOURCE_BYTES) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceLimitExceeded, 'total_source_bytes');
            }
            $sha256 = hash('sha256', $content);
            $sourceSetRecords[] = implode("\t", [$definition->path, $definition->authority->value, (string) $bytes, $sha256]);
            $rawSources[] = [$definition, $content, $bytes, $sha256, $position + 1];
        }

        $sourceSetSha256 = hash('sha256', implode("\n", $sourceSetRecords));
        $sources = [];
        foreach ($rawSources as [$definition, $content, $bytes, $sha256, $ordinal]) {
            $reference = sprintf('kgn-source-v1:%s:%04d', $sourceSetSha256, $ordinal);
            $source = new KnowledgeSource($reference, $ordinal, $definition->path, $definition->authority, $bytes, $sha256, $content);
            $sources[] = $source;
            $sourceRecords[] = [
                'reference' => $reference,
                'ordinal' => $ordinal,
                'path' => $definition->path,
                'authority' => $definition->authority->value,
                'bytes' => $bytes,
                'sha256' => $sha256,
                'content' => $content,
            ];
        }

        $implementationSha256 = $this->implementationSha256($packageRoot);
        $contract = $sources[10];
        $data = [
            'schema' => 'kongono.framework-knowledge',
            'version' => 1,
            'scope' => 'phase4-foundation',
            'framework' => 'kongono/framework',
            'knowledge_contract' => ['path' => 'docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md', 'sha256' => $contract->sha256()],
            'inventory' => ['path' => KnowledgeSourceInventory::RESOURCE_PATH, 'sha256' => $inventory->sha256()],
            'generator' => ['package' => 'kongono/knowledge', 'contract_version' => self::GENERATOR_CONTRACT_VERSION, 'implementation_sha256' => $implementationSha256],
            'source_state' => ['source_set_sha256' => $sourceSetSha256, 'source_count' => count($sources), 'source_bytes' => $totalBytes],
            'sources' => $sourceRecords,
        ];
        $canonicalBytes = CanonicalJson::encode($data);
        if (strlen($canonicalBytes) > self::MAX_ARTIFACT_BYTES) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceLimitExceeded, 'artifact_bytes');
        }

        return new FrameworkKnowledgeArtifact(
            $canonicalBytes,
            hash('sha256', $canonicalBytes),
            $sourceSetSha256,
            $implementationSha256,
            count($sources),
            $totalBytes,
            $sources,
        );
    }

    private function implementationSha256(string $packageRoot): string
    {
        $records = [];
        $total = 0;
        foreach (self::IMPLEMENTATION_PATHS as $path) {
            $bytes = PathGuard::read($packageRoot, $path);
            $total += strlen($bytes);
            if ($total > PathGuard::MAX_TOTAL_SOURCE_BYTES) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::SourceLimitExceeded, 'implementation_bytes');
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }
}
