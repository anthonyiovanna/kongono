<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Lifecycle;

use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use PHPUnit\Framework\TestCase;

final class ExternalServiceLifecycleStateTest extends TestCase
{
    public function testClosedLifecycleVocabulariesAreExact(): void
    {
        self::assertSame(['acquired', 'in_use', 'retiring', 'retired', 'uncertain'], array_column(CredentialLeaseState::cases(), 'value'));
        self::assertSame(['prepared', 'resolving', 'resolved', 'leasing', 'leased', 'invoking', 'retiring', 'retired', 'failed'], array_column(ExternalServiceLifecycleState::cases(), 'value'));
    }
}
