<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Lifecycle;

use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRuntimeStateTest extends TestCase
{
    public function testClosedEnumOrderAndValuesAreExact(): void
    {
        self::assertSame(['prepared', 'acquiring', 'acquired', 'using', 'retiring', 'retired', 'failed', 'uncertain'], array_column(CredentialLeaseRuntimeState::cases(), 'value'));
        self::assertSame(['succeeded', 'failed_before_acquisition', 'failed_before_material', 'material_use_uncertain', 'retirement_uncertain'], array_column(CredentialLeaseRuntimeOutcome::cases(), 'value'));
        self::assertSame(['clock', 'source', 'lease_protocol', 'lifetime', 'consumer', 'retirement'], array_column(CredentialLeaseRuntimeFailureKind::cases(), 'value'));
    }
}
