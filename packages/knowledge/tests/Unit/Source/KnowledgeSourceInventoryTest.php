<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Source;

use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Internal\PathGuard;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use Kongono\Knowledge\Source\KnowledgeSourceInventory;
use PHPUnit\Framework\TestCase;

final class KnowledgeSourceInventoryTest extends TestCase
{
    public function testCanonicalResourceHasExactOrderedThirtyOneEntries(): void
    {
        $inventory = KnowledgeSourceInventory::load($this->packageRoot());
        $paths = array_map(static fn($definition): string => $definition->path, $inventory->definitions());
        $sorted = $paths;
        sort($sorted, SORT_STRING);

        self::assertCount(31, $paths);
        self::assertSame($sorted, $paths);
        self::assertSame('.php-cs-fixer.dist.php', $paths[0]);
        self::assertSame('phpunit.xml.dist', $paths[30]);
        self::assertSame(hash('sha256', $inventory->canonicalBytes()), $inventory->sha256());
        self::assertStringEndsWith("\n", $inventory->canonicalBytes());
        self::assertFalse(str_ends_with($inventory->canonicalBytes(), "\n\n"));
    }

    public function testAuthorityMappingIsExactAndUnsupportedClassesRemainRejected(): void
    {
        self::assertSame([
            'governing-authority',
            'implementation-evidence',
            'current-explanatory-documentation',
            'historical-evidence',
            'workspace-observation',
            'derived-or-advisory-material',
        ], array_column(KnowledgeSourceAuthority::cases(), 'value'));

        $data = CanonicalJson::decodeObject(KnowledgeSourceInventory::load($this->packageRoot())->canonicalBytes(), 16);
        $sources = $data['sources'];
        self::assertIsArray($sources);
        $first = $sources[0];
        self::assertIsArray($first);
        $first['authority'] = 'historical-evidence';
        $sources[0] = $first;
        $data['sources'] = $sources;

        try {
            KnowledgeSourceInventory::fromBytes(CanonicalJson::encode($data));
            self::fail('Unsupported authority must be rejected.');
        } catch (InvalidKnowledgeSourceException $exception) {
            self::assertSame(KnowledgeDiagnostic::InvalidInventory->value, $exception->diagnosticId());
            self::assertSame('unsupported_authority', $exception->diagnosticContext()->fields()[0]->value());
        }
    }

    public function testMalformedNonCanonicalDuplicateUnorderedMissingAndOverLimitInventoriesFail(): void
    {
        $valid = KnowledgeSourceInventory::load($this->packageRoot())->canonicalBytes();
        foreach ([
            '{',
            " " . $valid,
            str_replace('"path":"AGENTS.md"', '"path":".php-cs-fixer.dist.php"', $valid),
            str_replace('"path":"AGENTS.md"', '"path":"000.md"', $valid),
            str_replace('{"path":"phpunit.xml.dist","authority":"implementation-evidence"}', '', $valid),
            str_repeat('x', KnowledgeSourceInventory::MAX_INVENTORY_BYTES + 1),
        ] as $invalid) {
            try {
                KnowledgeSourceInventory::fromBytes($invalid);
                self::fail('Invalid inventory must fail.');
            } catch (InvalidKnowledgeSourceException $exception) {
                self::assertSame(KnowledgeDiagnostic::InvalidInventory->value, $exception->diagnosticId());
            }
        }
    }

    public function testDefinitionsAreReturnedAsDetachedLists(): void
    {
        $inventory = KnowledgeSourceInventory::load($this->packageRoot());
        $first = $inventory->definitions();
        array_pop($first);
        $second = $inventory->definitions();

        self::assertCount(30, $first);
        self::assertCount(31, $second);
        self::assertNotSame($first, $second);
        self::assertSame('.php-cs-fixer.dist.php', $second[0]->path);
    }

    public function testPathGrammarAndFixedCountBoundsAreEnforced(): void
    {
        foreach (['', '../README.md', '/README.md', 'C:/README.md', 'a\\b', 'a//b', 'a/./b', 'a/../b', "a\0b"] as $path) {
            try {
                PathGuard::admit($path);
                self::fail('Invalid path grammar must fail.');
            } catch (InvalidKnowledgeSourceException $exception) {
                self::assertContains($exception->diagnosticId(), [KnowledgeDiagnostic::InvalidSourcePath->value, KnowledgeDiagnostic::SourceLimitExceeded->value]);
            }
        }
        PathGuard::admit('docs/adr/0062-deterministic-framework-knowledge-artifact.md');
        self::assertSame(31, KnowledgeSourceInventory::REQUIRED_SOURCE_RECORDS);
        self::assertSame(64, KnowledgeSourceInventory::MAX_SOURCE_RECORDS);
    }

    public function testInventoryUsesOnlyItsExplicitResourceWithoutOptionalDiscovery(): void
    {
        $reflection = new \ReflectionClass(KnowledgeSourceInventory::class);
        self::assertFalse($reflection->isInstantiable());
        self::assertSame('packages/knowledge/resources/framework-source-inventory.json', KnowledgeSourceInventory::RESOURCE_PATH);
        self::assertCount(31, KnowledgeSourceInventory::load($this->packageRoot())->definitions());
        self::assertFileDoesNotExist($this->packageRoot() . '/resources/optional-source-inventory.json');
    }

    private function packageRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
