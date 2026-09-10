<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Credential;

use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRequestTest extends TestCase
{
    public function testRequestRetainsExactFiniteEvidence(): void
    {
        $request = self::request(300000);
        self::assertSame('operation.one', $request->operationIdentity()->value());
        self::assertSame('credential.reference', $request->reference()->reference());
        self::assertSame('invoke', $request->purpose()->value());
        self::assertSame(300000, $request->maximumLifetimeMilliseconds());
        self::assertTrue($request->equals(self::request(300000)));
    }

    public function testLifetimeBoundsFailClosed(): void
    {
        foreach ([0, 300001] as $lifetime) {
            try {
                self::request($lifetime);
                self::fail('Expected credential lease lifetime rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                self::assertSame('KGN-EXTERNAL-SERVICE-CONTRACT-1605', $exception->diagnosticId());
            }
        }
    }

    private static function request(int $lifetime): CredentialLeaseRequest
    {
        return new CredentialLeaseRequest(
            new ExternalServiceOperationIdentity('operation.one'),
            new CredentialReference(new CredentialSourceIdentity('source.one'), 'credential.reference'),
            new CredentialPurpose('invoke'),
            $lifetime,
        );
    }
}
