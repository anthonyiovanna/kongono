<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\Stream;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testDefaultsAndCopyOnChangeAreExact(): void
    {
        $response = new Response();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getReasonPhrase());
        self::assertSame('1.1', $response->getProtocolVersion());
        self::assertSame([], $response->getHeaders());
        foreach ([$response->withStatus(200), $response->withProtocolVersion('1.1'), $response->withoutHeader('Absent'), $response->withBody($response->getBody())] as $copy) {
            self::assertNotSame($response, $copy);
        }
    }

    public function testStatusProtocolReasonAndHeadersRespectBoundaries(): void
    {
        self::assertSame(100, (new Response(100))->getStatusCode());
        self::assertSame(599, (new Response(599))->getStatusCode());
        foreach ([99, 600] as $status) {
            $this->expectInvalid(static fn() => new Response($status));
        }
        foreach (['1.0', '1.1', '2', '2.0', '3', '3.0'] as $version) {
            self::assertSame($version, (new Response(protocolVersion: $version))->getProtocolVersion());
        }
        foreach (['', ' 1.1', '1.1 ', 'HTTP/1.1', '4', "1.1\0", "1.1\n"] as $version) {
            $this->expectInvalid(static fn() => new Response(protocolVersion: $version));
        }
        self::assertSame("accepted\tphrase\x80", (new Response(reasonPhrase: "accepted\tphrase\x80"))->getReasonPhrase());
        foreach (["bad\rphrase", "bad\nphrase", "bad\0phrase", "bad\x01phrase", "bad\x7Fphrase"] as $phrase) {
            $this->expectInvalid(static fn() => new Response(reasonPhrase: $phrase));
        }
    }

    public function testBodyIsSharedUntilExplicitlyReplaced(): void
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        $stream = new Stream($resource);
        $response = new Response(body: $stream);
        $copy = $response->withHeader('X', 'v');
        $stream->write('shared');
        self::assertSame($stream, $copy->getBody());
        self::assertSame(6, $copy->getBody()->getSize());
        $stream->close();
    }

    public function testBodyReplacementDoesNotReadOrCloseEitherStream(): void
    {
        $firstResource = fopen('php://temp', 'w+b');
        $secondResource = fopen('php://temp', 'w+b');
        self::assertIsResource($firstResource);
        self::assertIsResource($secondResource);
        $first = new Stream($firstResource);
        $second = new Stream($secondResource);
        $first->write('first');
        $second->write('second');
        $response = new Response(body: $first);
        $copy = $response->withBody($second);
        self::assertSame(5, $first->tell());
        self::assertSame(6, $second->tell());
        self::assertTrue($first->isReadable());
        self::assertTrue($second->isReadable());
        self::assertSame($first, $response->getBody());
        self::assertSame($second, $copy->getBody());
        $first->close();
        $second->close();
    }

    public function testHeaderCopiesPreserveOriginalAndReturnDetachedArrays(): void
    {
        $response = new Response(headers: ['X-Test' => ['one', 'two']]);
        $copy = $response->withHeader('X-Test', 'new')->withAddedHeader('X-Test', 'next');
        self::assertSame(['one', 'two'], $response->getHeader('x-test'));
        self::assertSame(['new', 'next'], $copy->getHeader('x-test'));
        self::assertSame([], $copy->withoutHeader('x-test')->getHeaders());
        $headers = $response->getHeaders();
        $headers['X-Test'][0] = 'changed';
        self::assertSame(['one', 'two'], $response->getHeader('x-test'));
    }

    public function testEveryResponseCopyMutationLeavesTheCompleteOriginalSnapshotUnchanged(): void
    {
        $originalResource = fopen('php://temp', 'w+b');
        $replacementResource = fopen('php://temp', 'w+b');
        self::assertIsResource($originalResource);
        self::assertIsResource($replacementResource);
        $originalBody = new Stream($originalResource);
        $replacementBody = new Stream($replacementResource);
        $response = new Response(201, ['X-Test' => ['one']], $originalBody, '1.0', 'Original reason');
        $snapshot = $this->responseSnapshot($response);

        $copies = [
            $response->withProtocolVersion('2'),
            $response->withHeader('X-Test', 'replacement'),
            $response->withAddedHeader('X-Test', 'added'),
            $response->withoutHeader('X-Test'),
            $response->withBody($replacementBody),
            $response->withStatus(202, 'Changed reason'),
        ];
        $expected = [
            ['2', ['X-Test' => ['one']], $originalBody, 201, 'Original reason'],
            ['1.0', ['X-Test' => ['replacement']], $originalBody, 201, 'Original reason'],
            ['1.0', ['X-Test' => ['one', 'added']], $originalBody, 201, 'Original reason'],
            ['1.0', [], $originalBody, 201, 'Original reason'],
            ['1.0', ['X-Test' => ['one']], $replacementBody, 201, 'Original reason'],
            ['1.0', ['X-Test' => ['one']], $originalBody, 202, 'Changed reason'],
        ];

        foreach ($copies as $index => $copy) {
            self::assertNotSame($response, $copy);
            self::assertSame($expected[$index], $this->responseSnapshot($copy));
            self::assertSame($snapshot, $this->responseSnapshot($response));
        }

        $equivalentCopies = [
            $response->withProtocolVersion('1.0'),
            $response->withHeader('X-Test', ['one']),
            $response->withoutHeader('Absent'),
            $response->withBody($originalBody),
            $response->withStatus(201, 'Original reason'),
        ];
        foreach ($equivalentCopies as $copy) {
            self::assertNotSame($response, $copy);
            self::assertSame($snapshot, $this->responseSnapshot($copy));
            self::assertSame($snapshot, $this->responseSnapshot($response));
        }

        $originalBody->close();
        $replacementBody->close();
    }

    /** @return array{string, array<array-key, array<array-key, string>>, \Psr\Http\Message\StreamInterface, int, string} */
    private function responseSnapshot(Response $response): array
    {
        return [$response->getProtocolVersion(), $response->getHeaders(), $response->getBody(), $response->getStatusCode(), $response->getReasonPhrase()];
    }

    private function expectInvalid(callable $operation): void
    {
        try {
            $operation();
            self::fail('Input must fail.');
        } catch (InvalidMessageArgumentException) {
            self::addToAssertionCount(1);
        }
    }
}
