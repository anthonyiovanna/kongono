<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Verification;

use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceDeriver;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;
use Kongono\ExtensionInspection\Tests\Unit\Derivation\Fixture;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerdict;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerifier;
use PHPUnit\Framework\TestCase;

final class ExtensionIntelligenceVerifierTest extends TestCase
{
    public function testExactDerivedEvidencePasses(): void
    {
        $inspection = Fixture::inspection();
        $view = (new ExtensionIntelligenceDeriver())->derive($inspection, DeveloperIntelligenceRole::Oshie);
        self::assertSame(ExtensionIntelligenceVerdict::Pass, (new ExtensionIntelligenceVerifier())->verify($inspection, $view)->verdict());
    }
}
