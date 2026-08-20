<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Stream;
use Kongono\Http\Message\Message\UploadedFile;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;

final class ServerRequestTest extends TestCase
{
    public function testConstructorMethodTargetAndHostRules(): void
    {
        $request = new ServerRequest('pAtCh', 'https://user:pass@example.com:8443/path?q#f');
        self::assertSame('pAtCh', $request->getMethod());
        self::assertSame('/path?q', $request->getRequestTarget());
        self::assertSame('example.com:8443', $request->getHeaderLine('host'));
        self::assertSame('/custom-target', $request->withRequestTarget('/custom-target')->getRequestTarget());
        self::assertSame('original', (new ServerRequest('GET', 'https://new.test', headers: ['hOsT' => 'original']))->getHeaderLine('host'));
        self::assertSame('example.com', (new ServerRequest('GET', 'http://user:pass@example.com:80'))->getHeaderLine('Host'));
        self::assertSame('example.com:8080', (new ServerRequest('GET', 'http://user:pass@example.com:8080'))->getHeaderLine('Host'));
        $uri = new Uri('https://object.test/object');
        self::assertSame($uri, (new ServerRequest('GET', $uri))->getUri());
    }

    public function testWithUriDecisionTableAndExplicitTarget(): void
    {
        $request = new ServerRequest('GET', 'https://old.test/a', headers: ['Host' => 'custom']);
        self::assertSame('new.test:8080', $request->withUri(new Uri('http://new.test:8080'))->getHeaderLine('Host'));
        self::assertSame('custom', $request->withUri(new Uri('/relative'))->getHeaderLine('Host'));
        self::assertSame('custom', $request->withUri(new Uri('http://new.test'), true)->getHeaderLine('Host'));
        $empty = new ServerRequest('GET', '/relative', headers: ['Host' => '']);
        self::assertSame('new.test', $empty->withUri(new Uri('http://new.test'), true)->getHeaderLine('Host'));
        self::assertSame('/fixed', $request->withRequestTarget('/fixed')->withUri(new Uri('/other'))->getRequestTarget());
    }

    public function testPreserveHostUsesTheFirstOrderedValue(): void
    {
        $emptyFirst = new ServerRequest('GET', '/', headers: ['hOsT' => ['', 'later.test']]);
        self::assertSame(['new.test'], $emptyFirst->withUri(new Uri('https://user:pass@new.test:443'), true)->getHeader('host'));
        self::assertSame(['', 'later.test'], $emptyFirst->withUri(new Uri('/hostless'), true)->getHeader('host'));

        $nonEmptyFirst = new ServerRequest('GET', '/', headers: ['Host' => ['first.test', 'later.test']]);
        self::assertSame(['first.test', 'later.test'], $nonEmptyFirst->withUri(new Uri('https://new.test'), true)->getHeader('Host'));
        self::assertSame(['new.test:8443'], $nonEmptyFirst->withUri(new Uri('https://user:pass@new.test:8443'), false)->getHeader('Host'));
    }

    public function testEveryHostDecisionTableRowIsExplicit(): void
    {
        $present = new ServerRequest('GET', '/', headers: ['hOsT' => 'present.test']);
        self::assertSame('new.test', $present->withUri(new Uri('https://new.test'), false)->getHeaderLine('Host'));
        self::assertSame('present.test', $present->withUri(new Uri('/hostless'), false)->getHeaderLine('Host'));
        self::assertSame('present.test', $present->withUri(new Uri('https://new.test'), true)->getHeaderLine('Host'));

        $absent = new ServerRequest('GET', '/');
        self::assertSame('new.test', $absent->withUri(new Uri('https://new.test'), true)->getHeaderLine('Host'));
        self::assertFalse($absent->withUri(new Uri('/hostless'), true)->hasHeader('Host'));

        $empty = new ServerRequest('GET', '/', headers: ['Host' => '']);
        self::assertSame('new.test', $empty->withUri(new Uri('https://new.test'), true)->getHeaderLine('Host'));
        self::assertSame([''], $empty->withUri(new Uri('/hostless'), true)->getHeader('Host'));
    }

    public function testRequestTargetsAndMethodTokenBoundariesArePreserved(): void
    {
        $request = new ServerRequest("!#$%&'*+-.^_`|~09AZaz", 'https://example.test/path?q#fragment');
        self::assertSame("!#$%&'*+-.^_`|~09AZaz", $request->getMethod());
        self::assertSame('/path?q', $request->getRequestTarget());
        foreach (['*', '/origin?x=1', 'https://example.test/absolute', 'example.test:443'] as $target) {
            self::assertSame($target, $request->withRequestTarget($target)->getRequestTarget());
        }
        foreach (['', 'bad target', "bad\t", "bad\0", "bad\x7F"] as $target) {
            try {
                $request->withRequestTarget($target);
                self::fail('Invalid targets must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1007', $exception->diagnosticId());
            }
        }
        foreach (['', 'bad method', 'GET/', "GET\0"] as $method) {
            try {
                $request->withMethod($method);
                self::fail('Invalid methods must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1006', $exception->diagnosticId());
            }
        }
    }

    public function testRequestDomainsAreIsolatedAndObjectsRemainShared(): void
    {
        $object = new \stdClass();
        $server = ['s' => &$object];
        $request = (new ServerRequest('GET', '/', $server))->withCookieParams(['c' => 1])->withQueryParams(['q' => 2])->withParsedBody($object)->withAttribute('a', $object);
        $server['new'] = true;
        self::assertSame(['s' => $object], $request->getServerParams());
        self::assertSame(['c' => 1], $request->getCookieParams());
        self::assertSame(['q' => 2], $request->getQueryParams());
        self::assertSame($object, $request->getParsedBody());
        self::assertSame($object, $request->getAttribute('a'));
        self::assertSame('fallback', $request->getAttribute('missing', 'fallback'));
        self::assertSame([], $request->withoutAttribute('a')->getAttributes());
        self::assertSame('/', (string) $request->getUri());
    }

    public function testUploadedTreeAndParsedBodyValidation(): void
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        $stream = new \Kongono\Http\Message\Message\Stream($resource);
        $upload = new UploadedFile($stream);
        self::assertSame(['a' => [['b' => $upload]]], (new ServerRequest('GET', '/'))->withUploadedFiles(['a' => [['b' => $upload]]])->getUploadedFiles());
        foreach ([static fn() => (new ServerRequest('GET', '/'))->withUploadedFiles(['bad' => 1]), static fn() => (new ServerRequest('GET', '/'))->withParsedBody('bad'), static fn() => new ServerRequest('bad method', '/'), static fn() => (new ServerRequest('GET', '/'))->withRequestTarget("") ] as $operation) {
            try {
                $operation();
                self::fail('Invalid request data must fail.');
            } catch (InvalidMessageArgumentException) {
                self::addToAssertionCount(1);
            }
        }
        $stream->close();
    }

    public function testUploadedTreeFailureReportsDeterministicDepthAndKind(): void
    {
        try {
            (new ServerRequest('GET', '/'))->withUploadedFiles(['outer' => [['bad' => 42]]]);
            self::fail('An invalid leaf must fail.');
        } catch (InvalidMessageArgumentException $exception) {
            self::assertSame('KGN-HTTP-1027', $exception->diagnosticId());
            self::assertSame([2, 'int'], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
        }

        foreach ([false, 1, 1.5, 'body'] as $body) {
            try {
                (new ServerRequest('GET', '/'))->withParsedBody($body);
                self::fail('Scalar parsed bodies must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1028', $exception->diagnosticId());
            }
        }
        self::assertNull((new ServerRequest('GET', '/'))->withParsedBody(null)->getParsedBody());
        self::assertSame(['a' => 1], (new ServerRequest('GET', '/'))->withParsedBody(['a' => 1])->getParsedBody());
        $object = new \stdClass();
        self::assertSame($object, (new ServerRequest('GET', '/'))->withParsedBody($object)->getParsedBody());
    }

    public function testAttributePresencePreservesEveryFalseyValue(): void
    {
        $object = new \stdClass();
        $values = ['null' => null, 'false' => false, 'zero' => 0, 'empty' => '', 'array' => [], 'object' => $object];
        $request = new ServerRequest('GET', '/');
        foreach ($values as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        self::assertSame('fallback', $request->getAttribute('absent', 'fallback'));
        foreach ($values as $name => $value) {
            self::assertSame($value, $request->getAttribute($name, 'fallback'));
        }
        self::assertNull($request->withoutAttribute('null')->getAttribute('null'));
        self::assertSame('fallback', $request->withoutAttribute('null')->getAttribute('null', 'fallback'));
    }

    public function testAllRequestDomainsRemainIndependentAndTopLevelReferencesDetach(): void
    {
        $shared = new \stdClass();
        $serverSlot = 'server-before';
        $cookieSlot = 'cookie-before';
        $querySlot = 'query-before';
        $parsedSlot = 'parsed-before';
        $request = new ServerRequest('GET', '/', ['slot' => &$serverSlot]);
        $request = $request->withCookieParams(['slot' => &$cookieSlot]);
        $request = $request->withQueryParams(['slot' => &$querySlot]);
        $request = $request->withParsedBody(['slot' => &$parsedSlot]);
        $request = $request->withAttribute('shared', $shared);
        $serverSlot = $cookieSlot = $querySlot = $parsedSlot = 'after';

        self::assertSame(['slot' => 'server-before'], $request->getServerParams());
        self::assertSame(['slot' => 'cookie-before'], $request->getCookieParams());
        self::assertSame(['slot' => 'query-before'], $request->getQueryParams());
        self::assertSame(['slot' => 'parsed-before'], $request->getParsedBody());
        self::assertSame($shared, $request->getAttribute('shared'));
        $shared->changed = true;
        self::assertTrue($request->getAttribute('shared')->changed);
        self::assertSame('/', (string) $request->getUri());
        self::assertSame([], $request->getHeaders());
    }

    public function testRequestOperationsDoNotReadOrWriteSuperglobals(): void
    {
        $before = [$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES];
        $request = (new ServerRequest('POST', '/path'))->withCookieParams(['a' => 1])->withQueryParams(['b' => 2])->withParsedBody(['c' => 3])->withAttribute('d', 4);
        self::assertSame('POST', $request->getMethod());
        self::assertSame($before, [$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES]);
    }

    public function testEveryServerRequestCopyMutationLeavesTheCompleteOriginalSnapshotUnchanged(): void
    {
        $bodyResource = fopen('php://temp', 'w+b');
        $replacementBodyResource = fopen('php://temp', 'w+b');
        $uploadResource = fopen('php://temp', 'w+b');
        $replacementUploadResource = fopen('php://temp', 'w+b');
        self::assertIsResource($bodyResource);
        self::assertIsResource($replacementBodyResource);
        self::assertIsResource($uploadResource);
        self::assertIsResource($replacementUploadResource);
        $body = new Stream($bodyResource);
        $replacementBody = new Stream($replacementBodyResource);
        $upload = new UploadedFile(new Stream($uploadResource));
        $replacementUpload = new UploadedFile(new Stream($replacementUploadResource));

        $request = new ServerRequest(
            'POST',
            new Uri('https://old.test/base?old=1'),
            ['server' => 'original'],
            ['Host' => 'old.test', 'X-Test' => ['one']],
            $body,
            '1.0',
        );
        $request = $request
            ->withRequestTarget('/explicit')
            ->withCookieParams(['cookie' => 'original'])
            ->withQueryParams(['query' => 'original'])
            ->withUploadedFiles(['upload' => $upload])
            ->withParsedBody(['parsed' => 'original'])
            ->withAttribute('attribute', 'original');
        self::assertInstanceOf(ServerRequest::class, $request);
        $snapshot = $this->requestSnapshot($request);

        $copies = [
            $request->withProtocolVersion('2'),
            $request->withHeader('X-Test', 'replacement'),
            $request->withAddedHeader('X-Test', 'added'),
            $request->withoutHeader('X-Test'),
            $request->withBody($replacementBody),
            $request->withRequestTarget('*'),
            $request->withMethod('PATCH'),
            $request->withUri(new Uri('https://new.test/replaced'), false),
            $request->withUri(new Uri('https://new.test/preserved'), true),
            $request->withCookieParams(['cookie' => 'changed']),
            $request->withQueryParams(['query' => 'changed']),
            $request->withUploadedFiles(['upload' => $replacementUpload]),
            $request->withParsedBody(['parsed' => 'changed']),
            $request->withAttribute('added', 'value'),
            $request->withoutAttribute('attribute'),
        ];

        foreach ($copies as $copy) {
            self::assertNotSame($request, $copy);
            self::assertNotSame($snapshot, $this->requestSnapshot($copy));
            self::assertSame($snapshot, $this->requestSnapshot($request));
        }
        self::assertSame('new.test', $copies[7]->getHeaderLine('Host'));
        self::assertSame('old.test', $copies[8]->getHeaderLine('Host'));

        $equivalentCopies = [
            $request->withProtocolVersion('1.0'),
            $request->withHeader('X-Test', ['one']),
            $request->withoutHeader('Absent'),
            $request->withBody($body),
            $request->withRequestTarget('/explicit'),
            $request->withMethod('POST'),
            $request->withUri($request->getUri(), false),
            $request->withUri($request->getUri(), true),
            $request->withCookieParams(['cookie' => 'original']),
            $request->withQueryParams(['query' => 'original']),
            $request->withUploadedFiles(['upload' => $upload]),
            $request->withParsedBody(['parsed' => 'original']),
            $request->withAttribute('attribute', 'original'),
            $request->withoutAttribute('absent'),
        ];
        foreach ($equivalentCopies as $copy) {
            self::assertNotSame($request, $copy);
            self::assertSame($snapshot, $this->requestSnapshot($copy));
            self::assertSame($snapshot, $this->requestSnapshot($request));
        }

        $body->close();
        $replacementBody->close();
        $upload->getStream()->close();
        $replacementUpload->getStream()->close();
    }

    /** @return array<string, mixed> */
    private function requestSnapshot(ServerRequest $request): array
    {
        return [
            'protocol' => $request->getProtocolVersion(),
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
            'request_target' => $request->getRequestTarget(),
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'server' => $request->getServerParams(),
            'cookies' => $request->getCookieParams(),
            'query' => $request->getQueryParams(),
            'uploads' => $request->getUploadedFiles(),
            'parsed_body' => $request->getParsedBody(),
            'attributes' => $request->getAttributes(),
        ];
    }
}
