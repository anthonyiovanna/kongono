<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Request;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class RequestTest extends TestCase
{
    public function testOutboundRequestImplementsPsrContractAndDerivesTargetAndHost(): void
    {
        $body = (new StreamFactory())->createStream('body');
        $request = new Request('POST', 'https://example.test:8443/path?q=1#fragment', ['X-Test' => 'one'], $body, '1.0');
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/path?q=1', $request->getRequestTarget());
        self::assertSame('example.test:8443', $request->getHeaderLine('Host'));
        self::assertSame('one', $request->getHeaderLine('X-Test'));
        self::assertSame('1.0', $request->getProtocolVersion());
        self::assertSame($body, $request->getBody());
        $body->close();
    }

    public function testAllCopyOperationsLeaveOriginalSnapshotUnchanged(): void
    {
        $body = (new StreamFactory())->createStream('original');
        $replacement = (new StreamFactory())->createStream('replacement');
        $request = (new Request('POST', 'https://old.test/base?x=1', ['Host' => 'fixed.test', 'X-Test' => 'one'], $body))
            ->withRequestTarget('/explicit');
        self::assertInstanceOf(Request::class, $request);
        $snapshot = $this->snapshot($request);
        $copies = [
            $request->withProtocolVersion('2'),
            $request->withHeader('X-Test', 'two'),
            $request->withAddedHeader('X-Test', 'two'),
            $request->withoutHeader('X-Test'),
            $request->withBody($replacement),
            $request->withRequestTarget('*'),
            $request->withMethod('PATCH'),
            $request->withUri(new Uri('https://new.test/path')),
            $request->withUri(new Uri('https://new.test/path'), true),
        ];
        foreach ($copies as $copy) {
            self::assertNotSame($request, $copy);
            self::assertNotSame($snapshot, $this->snapshot($copy));
            self::assertSame($snapshot, $this->snapshot($request));
        }
        self::assertSame('new.test', $copies[7]->getHeaderLine('Host'));
        self::assertSame('fixed.test', $copies[8]->getHeaderLine('Host'));
        $body->close();
        $replacement->close();
    }

    public function testExplicitHostAndValidationRulesMatchMessageConventions(): void
    {
        self::assertSame('explicit.test', (new Request('GET', 'https://derived.test', ['hOsT' => 'explicit.test']))->getHeaderLine('host'));
        self::assertSame('/', (new Request('GET', 'https://example.test'))->getRequestTarget());
        self::assertSame('new.test', (new Request('GET', '/'))->withUri(new Uri('https://new.test'), true)->getHeaderLine('Host'));
        foreach ([static fn() => new Request('bad method', '/'), static fn() => (new Request('GET', '/'))->withRequestTarget('bad target')] as $operation) {
            try {
                $operation();
                self::fail('Invalid request input must fail.');
            } catch (InvalidMessageArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(RequestInterface $request): array
    {
        return [
            'protocol' => $request->getProtocolVersion(),
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
            'target' => $request->getRequestTarget(),
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ];
    }
}
