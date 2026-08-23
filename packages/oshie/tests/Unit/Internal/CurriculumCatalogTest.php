<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit\Internal;

use Kongono\Oshie\Exception\InvalidArtifactException;
use Kongono\Oshie\Internal\CurriculumCatalog;
use PHPUnit\Framework\TestCase;

final class CurriculumCatalogTest extends TestCase
{
    public function testCanonicalResourceHasExactIdentityOrderAndMappings(): void
    {
        $catalog = CurriculumCatalog::load();
        self::assertSame(['role-separation', 'knowledge-authority', 'php-support', 'diagnostic-safety', 'quality-gates', 'phase-sequence'], array_column($catalog->lessons(), 'lesson_id'));
        self::assertSame(['role-verifier', 'doctrine-authority', 'supported-minor-window', 'unstructured-detail', 'quality-first-gate', 'phase-after-4f'], array_map(static fn(array $lesson): string => $lesson['exercise']['exercise_id'], $catalog->lessons()));
        $path = dirname(__DIR__, 3) . '/resources/phase4-foundation-v1.json';
        $bytes = (string) file_get_contents($path);
        self::assertSame(CurriculumCatalog::RESOURCE_SHA256, hash('sha256', $bytes));
        self::assertStringEndsWith("\n", $bytes);
        self::assertStringNotContainsString("\r", $bytes);
    }

    public function testChangedDuplicateUnknownOrReorderedResourceIsRejected(): void
    {
        $bytes = (string) file_get_contents(dirname(__DIR__, 3) . '/resources/phase4-foundation-v1.json');
        foreach ([str_replace('role-separation', 'role-duplicate', $bytes), substr($bytes, 0, -1) . " \n"] as $changed) {
            try {
                CurriculumCatalog::fromBytes($changed);
                self::fail('Changed curriculum was admitted.');
            } catch (InvalidArtifactException $exception) {
                self::assertSame('KGN-OSHIE-1005', $exception->diagnosticId());
            }
        }
    }
}
