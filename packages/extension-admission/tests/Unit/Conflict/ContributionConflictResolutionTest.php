<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Conflict;

use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use PHPUnit\Framework\TestCase;

final class ContributionConflictResolutionTest extends TestCase
{
    public function testItPreservesTheExactApplicationOwnedWinner(): void
    {
        $contribution = new ContributionIdentity('routes');
        $winner = new ExtensionIdentity('acme/search');
        $resolution = new ContributionConflictResolution($contribution, $winner);
        self::assertSame($contribution, $resolution->contributionIdentity());
        self::assertSame($winner, $resolution->winningExtensionIdentity());
    }
}
