<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Outcome;

use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRuntimeOutcomeTest extends TestCase
{
    public function testOnlyDocumentedTerminalOutcomesExist(): void
    {
        self::assertCount(5, CredentialLeaseRuntimeOutcome::cases());
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, CredentialLeaseRuntimeOutcome::from('succeeded'));
        self::assertSame(CredentialLeaseRuntimeOutcome::RetirementUncertain, CredentialLeaseRuntimeOutcome::from('retirement_uncertain'));
    }
}
