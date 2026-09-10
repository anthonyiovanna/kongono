<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Execution;

use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeRequest;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialLeaseSource;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialMonotonicClock;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\RecordingCredentialMaterialConsumer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CredentialLeaseRuntimeRequestTest extends TestCase
{
    public function testExactCollaboratorsAreReturnedAndSourceIdentityMustMatchReference(): void
    {
        $identity = new CredentialSourceIdentity('source-a');
        $leaseRequest = new CredentialLeaseRequest(new ExternalServiceOperationIdentity('operation-a'), new CredentialReference($identity, 'opaque-a'), new CredentialPurpose('purpose-a'), 100);
        $source = new FakeCredentialLeaseSource(new RuntimeException('unused'));
        $consumer = new RecordingCredentialMaterialConsumer();
        $clock = new FakeCredentialMonotonicClock([0]);
        $request = new CredentialLeaseRuntimeRequest($leaseRequest, $identity, $source, $consumer, $clock);

        self::assertSame($leaseRequest, $request->leaseRequest());
        self::assertSame($identity, $request->sourceIdentity());
        self::assertSame($source, $request->source());
        self::assertSame($consumer, $request->consumer());
        self::assertSame($clock, $request->clock());

        try {
            new CredentialLeaseRuntimeRequest($leaseRequest, new CredentialSourceIdentity('source-b'), $source, $consumer, $clock);
            self::fail('Mismatched source identity must fail.');
        } catch (InvalidCredentialLeaseRuntimeException $failure) {
            self::assertSame('source_identity_relation', $failure->violation());
            self::assertSame(0, $source->calls);
            self::assertSame(0, $clock->calls);
        }
    }
}
