<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Source;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Internal\PathGuard;

/** Loads and owns the exact canonical 31-source Phase 4 foundation inventory. */
#[Api(ApiStatus::Internal)]
final readonly class KnowledgeSourceInventory
{
    public const RESOURCE_PATH = 'packages/knowledge/resources/framework-source-inventory.json';
    public const MAX_INVENTORY_BYTES = 65536;
    public const MAX_SOURCE_RECORDS = 64;
    public const REQUIRED_SOURCE_RECORDS = 31;

    /** @var list<array{path: string, authority: string}> */
    private const EXPECTED_SOURCES = [
        ['path' => '.php-cs-fixer.dist.php', 'authority' => 'implementation-evidence'],
        ['path' => 'AGENTS.md', 'authority' => 'governing-authority'],
        ['path' => 'ARCHITECTURE.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'CONTRIBUTING.md', 'authority' => 'governing-authority'],
        ['path' => 'README.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'SECURITY.md', 'authority' => 'governing-authority'],
        ['path' => 'composer.json', 'authority' => 'implementation-evidence'],
        ['path' => 'composer.lock', 'authority' => 'implementation-evidence'],
        ['path' => 'docs/adr/0059-rolling-current-minor-php-baseline.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/adr/0060-human-centered-developer-intelligence-family.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/adr/0062-deterministic-framework-knowledge-artifact.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/architecture/application-graph.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/architecture/developer-intelligence.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/architecture/framework-knowledge-artifact.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/architecture/knowledge-foundation.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/architecture/overview.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/architecture/phase-4-roadmap.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/doctrine.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/documentation-standard.md', 'authority' => 'governing-authority'],
        ['path' => 'docs/engineering/api-status.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/dependency-security.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/error-handling.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/package-layout.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/php-support-policy.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/testing.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/engineering/toolchain.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'docs/glossary.md', 'authority' => 'current-explanatory-documentation'],
        ['path' => 'packages/knowledge/composer.json', 'authority' => 'implementation-evidence'],
        ['path' => 'phpstan.neon.dist', 'authority' => 'implementation-evidence'],
        ['path' => 'phpunit.xml.dist', 'authority' => 'implementation-evidence'],
    ];

    /** @var list<KnowledgeSourceDefinition> */
    private array $definitions;

    /** @param list<KnowledgeSourceDefinition> $definitions */
    private function __construct(private string $canonicalBytes, private string $sha256, array $definitions)
    {
        $this->definitions = $definitions;
    }

    public static function load(string $packageRoot): self
    {
        $path = rtrim(str_replace('\\', '/', $packageRoot), '/') . '/resources/framework-source-inventory.json';
        if (!is_file($path) || is_link($path)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_shape');
        }
        $bytes = file_get_contents($path);
        if (false === $bytes) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_json');
        }

        return self::fromBytes($bytes);
    }

    public static function fromBytes(string $bytes): self
    {
        if (strlen($bytes) > self::MAX_INVENTORY_BYTES) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'inventory_limit');
        }

        try {
            PathGuard::validateText($bytes);
            $data = CanonicalJson::decodeObject($bytes, 16);
        } catch (InvalidKnowledgeSourceException|JsonException $exception) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_json', $exception);
        }

        if (CanonicalJson::encode($data) !== $bytes) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'non_canonical_json');
        }
        if (array_keys($data) !== ['schema', 'version', 'scope', 'sources']
            || 'kongono.framework-knowledge-source-inventory' !== ($data['schema'] ?? null)
            || 1 !== ($data['version'] ?? null)
            || 'phase4-foundation' !== ($data['scope'] ?? null)
        ) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'unsupported_schema');
        }

        $sources = $data['sources'] ?? null;
        if (!is_array($sources) || !array_is_list($sources) || count($sources) > self::MAX_SOURCE_RECORDS) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_shape');
        }
        if (self::REQUIRED_SOURCE_RECORDS !== count($sources)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_source_entry');
        }
        $definitions = [];
        $previous = null;
        foreach ($sources as $source) {
            if (!is_array($source) || array_keys($source) !== ['path', 'authority'] || !is_string($source['path'] ?? null) || !is_string($source['authority'] ?? null)) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_source_entry');
            }
            PathGuard::admit($source['path']);
            if (null !== $previous && 0 <= strcmp($previous, $source['path'])) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, $previous === $source['path'] ? 'duplicate_source_path' : 'unordered_source_path');
            }
            $authority = KnowledgeSourceAuthority::tryFrom($source['authority']);
            if (null === $authority || !in_array($authority, [KnowledgeSourceAuthority::GoverningAuthority, KnowledgeSourceAuthority::ImplementationEvidence, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation], true)) {
                throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'unsupported_authority');
            }
            $definitions[] = new KnowledgeSourceDefinition($source['path'], $authority);
            $previous = $source['path'];
        }
        if ($sources !== self::EXPECTED_SOURCES) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidInventory, 'invalid_source_entry');
        }

        return new self($bytes, hash('sha256', $bytes), $definitions);
    }

    /** @return list<KnowledgeSourceDefinition> */
    public function definitions(): array
    {
        return [...$this->definitions];
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
