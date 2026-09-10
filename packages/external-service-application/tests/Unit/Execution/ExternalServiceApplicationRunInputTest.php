<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Execution;

use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialMonotonicClock;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressAuthorityCurrentness;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressCancellation;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeExternalServiceRequestFactory;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalServiceApplicationRunInputTest extends TestCase
{
    public function testCarriesExactlyElevenExplicitInputs(): void
    {
        $input = $this->input(5000);

        self::assertSame('operation.external-service', $input->operationIdentity()->value());
        self::assertSame('invoke.service', $input->credentialPurpose()->value());
        self::assertSame(5000, $input->credentialMaximumLifetimeMilliseconds());
        self::assertInstanceOf(FakeExternalServiceRequestFactory::class, $input->requestFactory());
        self::assertSame('receipt.external-service', $input->receiptIdentity()->value());
        self::assertSame('attempt.external-service', $input->attemptIdentity()->value());
        self::assertInstanceOf(FakeCredentialMonotonicClock::class, $input->credentialClock());
        self::assertInstanceOf(EgressMonotonicClock::class, $input->egressClock());
        self::assertCount(11, (new \ReflectionClass($input))->getProperties());
    }

    /** @return iterable<string, array{int}> */
    public static function invalidLifetimes(): iterable
    {
        yield 'zero' => [0];
        yield 'above maximum' => [300001];
    }

    #[DataProvider('invalidLifetimes')]
    public function testLifetimeOutsideClosedContractFails(int $lifetime): void
    {
        $this->expectException(ExternalServiceApplicationOperationException::class);
        $this->input($lifetime);
    }

    private function input(int $lifetime): ExternalServiceApplicationRunInput
    {
        return new ExternalServiceApplicationRunInput(
            new ExternalServiceOperationIdentity('operation.external-service'),
            new CredentialPurpose('invoke.service'),
            $lifetime,
            new FakeExternalServiceRequestFactory(new ServerRequest('GET', 'https://api.example.test/api')),
            new EgressReceiptIdentity('receipt.external-service'),
            new EgressAttemptIdentity('attempt.external-service'),
            new EgressAuthorityReference('authority.external-service', 1),
            new FakeEgressAuthorityCurrentness(),
            new FakeEgressCancellation(),
            new FakeCredentialMonotonicClock([0]),
            new class implements EgressMonotonicClock {
                public function milliseconds(): int
                {
                    return 0;
                }
            },
        );
    }
}
