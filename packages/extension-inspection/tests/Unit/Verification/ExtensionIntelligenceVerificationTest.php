<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Verification;

use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerdict;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerification;
use PHPUnit\Framework\TestCase;

final class ExtensionIntelligenceVerificationTest extends TestCase
{
    public function testExactSevenOrderedChecksAreAccepted(): void
    {
        $checks = array_fill_keys(ExtensionIntelligenceVerification::CHECK_IDS, true);
        $verification = new ExtensionIntelligenceVerification(ExtensionIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Hantei, str_repeat('1', 64), new DeclarationSetIdentity(str_repeat('2', 64)), $checks, []);
        self::assertSame(ExtensionIntelligenceVerification::CHECK_IDS, array_keys($verification->checkOutcomes()));
    }
}
