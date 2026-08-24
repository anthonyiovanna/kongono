<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Internal;

use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\Internal\FrameworkQualityProfile;
use PHPUnit\Framework\TestCase;

final class FrameworkQualityProfileTest extends TestCase
{
    public function testRepositoryProfileMatchesEveryRequiredScriptExactly(): void
    {
        $root = dirname(__DIR__, 5);
        $profile = FrameworkQualityProfile::load($root);

        self::assertSame(array_keys(HanteiCheckEvidence::CHECKS), array_column($profile->checks(), 'check_id'));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $profile->sha256());
        $root = json_decode((string) file_get_contents(dirname(__DIR__, 5) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($root);
        self::assertIsArray($root['scripts']);
        self::assertIsArray($root['scripts']['validate:packages']);
        self::assertCount(19, $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/composition validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/oshie validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertSame('composer --working-dir=skeletons/minimal validate --no-check-publish', $root['scripts']['validate:skeleton']);
        self::assertIsArray($root['scripts']['quality']);
        self::assertSame('@validate:skeleton', $root['scripts']['quality'][2]);
    }

    public function testUnknownProfileIsRejected(): void
    {
        $this->expectException(InvalidHanteiRequestException::class);
        FrameworkQualityProfile::load(dirname(__DIR__, 5), 'caller-profile');
    }
}
