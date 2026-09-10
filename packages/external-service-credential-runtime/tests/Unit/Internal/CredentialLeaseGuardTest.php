<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Internal;

use Kongono\ExternalServiceContract\Credential\CredentialLeaseIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Internal\CredentialLeaseGuard;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialLease;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseGuardTest extends TestCase
{
    /** @return iterable<string, array{string, string, CredentialLeaseState, string}> */
    public static function invalidRelations(): iterable
    {
        yield 'reference' => ['other', 'purpose-a', CredentialLeaseState::Acquired, 'lease_reference_mismatch'];
        yield 'purpose' => ['opaque-a', 'purpose-b', CredentialLeaseState::Acquired, 'lease_purpose_mismatch'];
        yield 'state' => ['opaque-a', 'purpose-a', CredentialLeaseState::InUse, 'lease_state_invalid'];
    }

    #[DataProvider('invalidRelations')]
    public function testInitialRelationsFailClosed(string $referenceValue, string $purposeValue, CredentialLeaseState $state, string $violation): void
    {
        $identity = new CredentialSourceIdentity('source-a');
        $request = new CredentialLeaseRequest(new ExternalServiceOperationIdentity('operation-a'), new CredentialReference($identity, 'opaque-a'), new CredentialPurpose('purpose-a'), 10);
        $lease = new FakeCredentialLease(new CredentialLeaseIdentity('lease-a'), new CredentialReference($identity, $referenceValue), new CredentialPurpose($purposeValue), $state, 'borrowed');
        try {
            CredentialLeaseGuard::validateInitial($request, $lease);
            self::fail('Invalid relation must fail.');
        } catch (InvalidCredentialLeaseRuntimeException $failure) {
            self::assertSame($violation, $failure->violation());
        }
    }

    public function testPostMaterialAndRetirementStatesAreExact(): void
    {
        $identity = new CredentialSourceIdentity('source-a');
        $lease = new FakeCredentialLease(new CredentialLeaseIdentity('lease-a'), new CredentialReference($identity, 'opaque-a'), new CredentialPurpose('purpose-a'), CredentialLeaseState::Acquired, 'borrowed');
        CredentialLeaseGuard::validateAfterMaterial($lease);
        $lease->retire();
        CredentialLeaseGuard::validateRetired($lease);
        self::addToAssertionCount(2);
    }
}
