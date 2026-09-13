<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Http;

use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\ViewHttp\Exception\ViewHttpAdaptationException;
use Kongono\ViewHttp\Http\CompletedViewResponseAdapter;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use Kongono\ViewHttp\Tests\Fixture\AdversarialResponse;
use Kongono\ViewHttp\Tests\Fixture\AdversarialStream;
use Kongono\ViewHttp\Tests\Fixture\ViewHttpFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class CompletedViewResponseAdapterTest extends TestCase
{
    public function testAdapterDerivesOnlyContentLength(): void
    {
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result('abc'), ViewHttpFixture::policy());
        self::assertSame('3', $response->getHeaderLine('Content-Length'));
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertCount(2, $response->getHeaders());
        self::assertSame('abc', $response->getBody()->getContents());
    }

    public function testAdapterPreservesExactExplicitResponseState(): void
    {
        $policy = ViewHttpFixture::policy(203, 'Non-Authoritative Information', [new ViewResponseHeader('X-Trace', ['one', 'two'])]);
        $response = ViewHttpFixture::adapter()->adapt(ViewHttpFixture::result('<p>x</p>'), $policy);
        self::assertSame(203, $response->getStatusCode());
        self::assertSame('Non-Authoritative Information', $response->getReasonPhrase());
        self::assertSame(['one', 'two'], $response->getHeader('X-Trace'));
        self::assertSame('<p>x</p>', (string) $response->getBody());
    }

    public function testSequentialCallsRetainNoState(): void
    {
        $adapter = ViewHttpFixture::adapter();
        for ($index = 0; $index < 40; ++$index) {
            $bytes = 'output-' . $index;
            $response = $adapter->adapt(ViewHttpFixture::result($bytes), ViewHttpFixture::policy(200 + ($index % 5), ''));
            self::assertSame($bytes, (string) $response->getBody());
            self::assertSame((string) strlen($bytes), $response->getHeaderLine('Content-Length'));
            self::assertSame(200 + ($index % 5), $response->getStatusCode());
        }
    }

    public function testInjectedFactoryHeaderFailsClosed(): void
    {
        $responses = new class implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return (new ResponseFactory())->createResponse($code, $reasonPhrase)->withHeader('X-Injected', 'denied');
            }
        };
        $adapter = new CompletedViewResponseAdapter($responses, new StreamFactory());
        try {
            $adapter->adapt(ViewHttpFixture::result('private-output'), ViewHttpFixture::policy());
            self::fail('Expected injected response state to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame('KGN-VIEW-HTTP-1844', $exception->diagnosticId());
            self::assertStringContainsString('unexpected_header', (string) $exception);
            self::assertStringNotContainsString('private-output', (string) $exception);
        }
    }

    public function testAlteredStreamBytesFailClosed(): void
    {
        $streams = new class implements StreamFactoryInterface {
            public function createStream(string $content = ''): StreamInterface
            {
                return (new StreamFactory())->createStream($content . 'altered');
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                return (new StreamFactory())->createStreamFromFile($filename, $mode);
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                return (new StreamFactory())->createStreamFromResource($resource);
            }
        };
        $adapter = new CompletedViewResponseAdapter(new ResponseFactory(), $streams);
        try {
            $adapter->adapt(ViewHttpFixture::result('private-output'), ViewHttpFixture::policy());
            self::fail('Expected altered stream state to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame('KGN-VIEW-HTTP-1842', $exception->diagnosticId());
            self::assertStringContainsString('size_mismatch', (string) $exception);
            self::assertStringNotContainsString('private-output', (string) $exception);
        }
    }

    #[DataProvider('streamAdversaries')]
    public function testStreamAdversariesFailClosed(string $mode, bool $retainsPrevious): void
    {
        $streams = new class ($mode) implements StreamFactoryInterface {
            public function __construct(private readonly string $mode) {}

            public function createStream(string $content = ''): StreamInterface
            {
                return new AdversarialStream((new StreamFactory())->createStream($content), $this->mode);
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                return (new StreamFactory())->createStreamFromFile($filename, $mode);
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                return (new StreamFactory())->createStreamFromResource($resource);
            }
        };
        try {
            (new CompletedViewResponseAdapter(new ResponseFactory(), $streams))->adapt(ViewHttpFixture::result('private-stream-output'), ViewHttpFixture::policy());
            self::fail('Expected the adversarial stream to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame('KGN-VIEW-HTTP-1842', $exception->diagnosticId());
            self::assertSame($retainsPrevious, null !== $exception->getPrevious());
            self::assertStringNotContainsString('private-stream', $exception->getMessage());
            self::assertStringNotContainsString('private-stream', (string) $exception);
        }
    }

    /** @return iterable<string, array{string, bool}> */
    public static function streamAdversaries(): iterable
    {
        yield 'unreadable' => ['not_readable', false];
        yield 'not seekable' => ['not_seekable', false];
        yield 'wrong size' => ['size_mismatch', false];
        yield 'wrong bytes' => ['content_mismatch', false];
        yield 'rewind failure' => ['rewind_throw', true];
        yield 'content read failure' => ['contents_throw', true];
        yield 'wrong final position' => ['position_mismatch', false];
    }

    public function testStreamFactoryThrowableIsRetainedWithoutDisclosure(): void
    {
        $private = new RuntimeException('private-stream-factory-message');
        $streams = new class ($private) implements StreamFactoryInterface {
            public function __construct(private readonly RuntimeException $private) {}

            public function createStream(string $content = ''): StreamInterface
            {
                throw $this->private;
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                throw $this->private;
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                throw $this->private;
            }
        };
        try {
            (new CompletedViewResponseAdapter(new ResponseFactory(), $streams))->adapt(ViewHttpFixture::result('private-output'), ViewHttpFixture::policy());
            self::fail('Expected the stream factory to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame($private, $exception->getPrevious());
            self::assertSame('KGN-VIEW-HTTP-1842', $exception->diagnosticId());
            self::assertStringNotContainsString('private-stream-factory-message', (string) $exception);
            self::assertStringNotContainsString('private-output', (string) $exception);
        }
    }

    #[DataProvider('responseAdversaries')]
    public function testResponseAdversariesFailClosed(string $mode, string $diagnostic, bool $retainsPrevious): void
    {
        $responses = new class ($mode) implements ResponseFactoryInterface {
            public function __construct(private readonly string $mode) {}

            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return new AdversarialResponse((new ResponseFactory())->createResponse($code, $reasonPhrase), $this->mode);
            }
        };
        try {
            (new CompletedViewResponseAdapter($responses, new StreamFactory()))->adapt(ViewHttpFixture::result('private-response-output'), ViewHttpFixture::policy(headers: [new ViewResponseHeader('X-Policy', ['private-header-value'])]));
            self::fail('Expected the adversarial response to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame($diagnostic, $exception->diagnosticId());
            self::assertSame($retainsPrevious, null !== $exception->getPrevious());
            self::assertStringNotContainsString('private-response', $exception->getMessage());
            self::assertStringNotContainsString('private-header-value', (string) $exception);
            self::assertStringNotContainsString('private-altered', (string) $exception);
        }
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function responseAdversaries(): iterable
    {
        yield 'wrong status' => ['status_mismatch', 'KGN-VIEW-HTTP-1843', false];
        yield 'wrong reason' => ['reason_mismatch', 'KGN-VIEW-HTTP-1843', false];
        yield 'status getter throws' => ['status_throw', 'KGN-VIEW-HTTP-1843', true];
        yield 'reason getter throws' => ['reason_throw', 'KGN-VIEW-HTTP-1843', true];
        yield 'body mutation throws' => ['with_body_throw', 'KGN-VIEW-HTTP-1844', true];
        yield 'header mutation throws' => ['with_header_throw', 'KGN-VIEW-HTTP-1844', true];
        yield 'wrong body' => ['body_mismatch', 'KGN-VIEW-HTTP-1844', false];
        yield 'body getter throws' => ['body_getter_throw', 'KGN-VIEW-HTTP-1844', true];
        yield 'unexpected header' => ['unexpected_header', 'KGN-VIEW-HTTP-1844', false];
        yield 'wrong header values' => ['header_mismatch', 'KGN-VIEW-HTTP-1844', false];
        yield 'headers getter throws' => ['headers_throw', 'KGN-VIEW-HTTP-1844', true];
    }

    public function testResponseFactoryThrowableIsRetainedWithoutDisclosure(): void
    {
        $private = new RuntimeException('private-response-factory-message');
        $responses = new class ($private) implements ResponseFactoryInterface {
            public function __construct(private readonly RuntimeException $private) {}

            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                throw $this->private;
            }
        };
        try {
            (new CompletedViewResponseAdapter($responses, new StreamFactory()))->adapt(ViewHttpFixture::result('private-output'), ViewHttpFixture::policy());
            self::fail('Expected the response factory to fail closed.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertSame($private, $exception->getPrevious());
            self::assertSame('KGN-VIEW-HTTP-1843', $exception->diagnosticId());
            self::assertStringNotContainsString('private-response-factory-message', (string) $exception);
            self::assertStringNotContainsString('private-output', (string) $exception);
        }
    }

    public function testFailureThenSuccessRetainsNoOperationState(): void
    {
        $responses = new class implements ResponseFactoryInterface {
            private int $calls = 0;

            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                ++$this->calls;
                $response = (new ResponseFactory())->createResponse($code, $reasonPhrase);

                return 1 === $this->calls ? new AdversarialResponse($response, 'unexpected_header') : $response;
            }
        };
        $adapter = new CompletedViewResponseAdapter($responses, new StreamFactory());
        try {
            $adapter->adapt(ViewHttpFixture::result('private-failure'), ViewHttpFixture::policy());
            self::fail('Expected the first operation to fail.');
        } catch (ViewHttpAdaptationException) {
            self::assertSame('public-success', (string) $adapter->adapt(ViewHttpFixture::result('public-success'), ViewHttpFixture::policy())->getBody());
        }
    }

    public function testSuccessThenFailureRetainsNoOperationState(): void
    {
        $responses = new class implements ResponseFactoryInterface {
            private int $calls = 0;

            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                ++$this->calls;
                $response = (new ResponseFactory())->createResponse($code, $reasonPhrase);

                return 1 === $this->calls ? $response : new AdversarialResponse($response, 'unexpected_header');
            }
        };
        $adapter = new CompletedViewResponseAdapter($responses, new StreamFactory());
        self::assertSame('public-success', (string) $adapter->adapt(ViewHttpFixture::result('public-success'), ViewHttpFixture::policy())->getBody());
        try {
            $adapter->adapt(ViewHttpFixture::result('private-failure'), ViewHttpFixture::policy());
            self::fail('Expected the second operation to fail.');
        } catch (ViewHttpAdaptationException $exception) {
            self::assertStringNotContainsString('public-success', (string) $exception);
            self::assertStringNotContainsString('private-failure', (string) $exception);
        }
    }

    public function testInterleavedAdaptersShareNoMutableState(): void
    {
        $left = ViewHttpFixture::adapter();
        $right = ViewHttpFixture::adapter();
        for ($index = 0; $index < 24; ++$index) {
            $first = $left->adapt(ViewHttpFixture::result('left-' . $index), ViewHttpFixture::policy(201, 'Created'));
            $second = $right->adapt(ViewHttpFixture::result('right-' . $index), ViewHttpFixture::policy(202, 'Accepted'));
            self::assertSame('left-' . $index, (string) $first->getBody());
            self::assertSame(201, $first->getStatusCode());
            self::assertSame('right-' . $index, (string) $second->getBody());
            self::assertSame(202, $second->getStatusCode());
        }
    }
}
