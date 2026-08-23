<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit;

use Kongono\Hantei\Hantei;
use Kongono\Hantei\HanteiVerdict;
use PHPUnit\Framework\TestCase;

final class HanteiTest extends TestCase
{
    public function testUnavailableSubjectProducesCompleteBlockedEvidenceWithoutDisclosure(): void
    {
        $hostile = 'missing-secret-' . bin2hex(random_bytes(8));
        $report = (new Hantei())->verify(__DIR__ . DIRECTORY_SEPARATOR . $hostile);
        $array = $report->toArray();

        self::assertSame(HanteiVerdict::Blocked, $report->verdict());
        $checks = $array['checks'];
        self::assertIsArray($checks);
        self::assertCount(8, $checks);
        self::assertSame(['subject-unavailable'], $array['uncertainties']);
        self::assertStringNotContainsString($hostile, json_encode($array, JSON_THROW_ON_ERROR));
    }
}
