<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Evidence;

use Kongono\AgentAdmission\Tests\Support\AgentAdmissionFactory;
use Kongono\IdentityAccess\AuthorizationDecision;
use PHPUnit\Framework\TestCase;

final class CapabilityAuthorizationEvidenceTest extends TestCase
{
    public function testEvidenceExposesOnlyExactCapabilityAndExistingEvaluation(): void
    {
        $evidence = AgentAdmissionFactory::evidence('calendar.read', AuthorizationDecision::Deny);
        self::assertSame('calendar.read', $evidence->capabilityIdentity()->value());
        self::assertSame(AuthorizationDecision::Deny, $evidence->evaluation()->decision());
        self::assertSame(['__construct', 'capabilityIdentity', 'evaluation'], get_class_methods($evidence));
    }
}
