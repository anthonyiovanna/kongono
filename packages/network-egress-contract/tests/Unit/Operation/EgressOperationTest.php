<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Operation;

use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class EgressOperationTest extends TestCase
{
    public function testConstructionAndAccessRetainExactValuesWithoutTouchingBody(): void
    {
        $stream = new OperationBodyProbe();
        $request = new ServerRequest('POST', 'https://example.test/path?private=query', body: $stream);
        $identity = new EgressOperationIdentity('operation');
        $origin = new CanonicalOrigin(OriginScheme::Https, 'example.test', 443);
        $authority = new EgressAuthorityReference('authority', 1);
        $policy = new EgressPolicyReference('policy', str_repeat('A', 64));
        $budget = self::budget();
        $operation = new EgressOperation($identity, $request, $origin, $authority, $policy, $budget);

        self::assertSame($identity, $operation->identity());
        self::assertSame($request, $operation->request());
        self::assertSame($origin, $operation->origin());
        self::assertSame($authority, $operation->authorityReference());
        self::assertSame($policy, $operation->policyReference());
        self::assertSame($budget, $operation->budget());
        self::assertSame(0, $stream->calls);
    }

    public function testOriginMismatchAndInvalidUriFailClosedWithoutReadingBody(): void
    {
        $stream = new OperationBodyProbe();
        try {
            new EgressOperation(
                new EgressOperationIdentity('operation'),
                new ServerRequest('POST', 'https://example.test/path', body: $stream),
                new CanonicalOrigin(OriginScheme::Https, 'other.test', 443),
                new EgressAuthorityReference('authority', 1),
                new EgressPolicyReference('policy', str_repeat('A', 64)),
                self::budget(),
            );
            self::fail('Expected origin mismatch.');
        } catch (InvalidNetworkEgressContractException $exception) {
            self::assertSame('KGN-EGRESS-CONTRACT-1506', $exception->diagnosticId());
            self::assertSame(0, $stream->calls);
            self::assertStringNotContainsString('example.test', (string) $exception);
        }
    }

    private static function budget(): EgressBudget
    {
        return new EgressBudget(1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1);
    }
}

final class OperationBodyProbe implements StreamInterface
{
    public int $calls = 0;
    public function __toString(): string
    {
        return $this->fail();
    }
    public function close(): void
    {
        $this->fail();
    }
    public function detach()
    {
        return $this->fail();
    }
    public function getSize(): ?int
    {
        return $this->fail();
    }
    public function tell(): int
    {
        return $this->fail();
    }
    public function eof(): bool
    {
        return $this->fail();
    }
    public function isSeekable(): bool
    {
        return $this->fail();
    }
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->fail();
    }
    public function rewind(): void
    {
        $this->fail();
    }
    public function isWritable(): bool
    {
        return $this->fail();
    }
    public function write(string $string): int
    {
        return $this->fail();
    }
    public function isReadable(): bool
    {
        return $this->fail();
    }
    public function read(int $length): string
    {
        return $this->fail();
    }
    public function getContents(): string
    {
        return $this->fail();
    }
    public function getMetadata(?string $key = null)
    {
        return $this->fail();
    }

    private function fail(): never
    {
        ++$this->calls;
        throw new RuntimeException('Body stream access is forbidden.');
    }
}
