<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Binding;

use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use PHPUnit\Framework\TestCase;

final class ServiceBindingIdentityTest extends TestCase
{
    public function testIdentityGrammarAndEquality(): void
    {
        $binding = new ServiceBindingIdentity('binding.one');
        self::assertSame('binding.one', $binding->value());
        self::assertTrue($binding->equals(new ServiceBindingIdentity('binding.one')));
        $profile = new ServiceProfileIdentity('profile-one');
        self::assertSame('profile-one', $profile->value());
        self::assertTrue($profile->equals(new ServiceProfileIdentity('profile-one')));
        $source = new CredentialSourceIdentity('source_one');
        self::assertSame('source_one', $source->value());
        self::assertTrue($source->equals(new CredentialSourceIdentity('source_one')));
        $lease = new CredentialLeaseIdentity('lease.1');
        self::assertSame('lease.1', $lease->value());
        self::assertTrue($lease->equals(new CredentialLeaseIdentity('lease.1')));
        $operation = new ExternalServiceOperationIdentity('operation.1');
        self::assertSame('operation.1', $operation->value());
        self::assertTrue($operation->equals(new ExternalServiceOperationIdentity('operation.1')));
        $purpose = new CredentialPurpose('purpose_1');
        self::assertSame('purpose_1', $purpose->value());
        self::assertTrue($purpose->equals(new CredentialPurpose('purpose_1')));
    }

    public function testAdjacentBoundsAndInvalidGrammarFailClosed(): void
    {
        self::assertSame(str_repeat('a', 128), (new ServiceBindingIdentity(str_repeat('a', 128)))->value());
        foreach (['', 'Upper', '-edge', 'edge-', str_repeat('a', 129)] as $invalid) {
            try {
                new ServiceBindingIdentity($invalid);
                self::fail('Expected identity rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
        }
    }
}
