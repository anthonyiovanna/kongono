<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Delivery;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\Http\Message\Message\Response;
use Kongono\PhpCgi\Delivery\CgiWebDelivery;
use Kongono\PhpCgi\Exception\CgiDeliveryCleanupException;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use LogicException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

final class CgiWebDeliveryTest extends TestCase
{
    public function testDeclarationAndPublicSurfaceAreExact(): void
    {
        $reflection = new \ReflectionClass(CgiWebDelivery::class);
        self::assertTrue($reflection->isFinal());
        self::assertFalse($reflection->isReadOnly());
        self::assertSame([WebDeliveryInterface::class], $reflection->getInterfaceNames());
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertTrue($reflection->getMethod('__clone')->isPrivate());
        self::assertFalse($reflection->hasMethod('__destruct'));
        self::assertSame(
            ['capture', 'close', 'evaluateTrustedPresentationPermission', 'fromSnapshot', 'request', 'send'],
            $this->declaredPublicMethods($reflection),
        );
    }

    public function testProjectionIsClosedDetachedAndPermissionDoesNotOpenInput(): void
    {
        $opened = 0;
        $selected = 'GET';
        $server = $this->validServer();
        $server['REQUEST_METHOD'] = &$selected;
        $server['HTTP_X_TEST'] = 'kept';
        $server['UNSELECTED_SECRET'] = 'excluded';
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $server,
            true,
            true,
            static function () use (&$opened): mixed {
                ++$opened;

                return self::input('');
            },
        );
        $selected = 'POST';
        $server['HTTP_X_TEST'] = 'changed';

        self::assertSame(AdapterControlledDeliveryPermission::Allowed, $delivery->evaluateTrustedPresentationPermission());
        self::assertSame(0, $opened);
        $projected = (new \ReflectionProperty(CgiWebDelivery::class, 'server'))->getValue($delivery);
        self::assertIsArray($projected);
        self::assertSame('GET', $projected['REQUEST_METHOD']);
        self::assertSame('kept', $projected['HTTP_X_TEST']);
        self::assertArrayNotHasKey('UNSELECTED_SECRET', $projected);
        $delivery->close();
    }

    public function testNonStringKeyForcesFixedInvalidSentinel(): void
    {
        $server = $this->validServer();
        $server[91] = 'raw-key-and-value-sentinel';
        $delivery = $this->delivery($server);

        self::assertSame(AdapterControlledDeliveryPermission::Denied, $delivery->evaluateTrustedPresentationPermission());
        try {
            $delivery->request();
            self::fail('Non-string server key must reject.');
        } catch (InvalidCgiRequestException $failure) {
            self::assertSame('server_key_not_string', $failure->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString('raw-key-and-value-sentinel', $failure->getMessage());
        } finally {
            $delivery->close();
        }
    }

    public function testPostAndFilePresenceUseOnlyFixedSentinels(): void
    {
        foreach ([[false, true, 'post_data_present'], [true, false, 'upload_data_present']] as [$postEmpty, $filesEmpty, $token]) {
            $delivery = CgiWebDelivery::fromSnapshot(
                'cgi-fcgi',
                $this->validServer(),
                $postEmpty,
                $filesEmpty,
                static fn(): mixed => self::input(''),
            );
            try {
                $delivery->request();
                self::fail('Nonempty form or upload state must reject.');
            } catch (InvalidCgiRequestException $failure) {
                self::assertSame($token, $failure->diagnosticContext()->fields()[0]->value());
            } finally {
                $delivery->close();
            }
        }
    }

    public function testPermissionEvidenceAllowsOnlyExactDirectLoopbackProfile(): void
    {
        foreach ([
            [$this->validServer(), AdapterControlledDeliveryPermission::Allowed],
            [$this->validServer(['REMOTE_ADDR' => '192.0.2.10']), AdapterControlledDeliveryPermission::Denied],
            [$this->validServer(['HTTP_FORWARDED' => 'for=127.0.0.1']), AdapterControlledDeliveryPermission::Denied],
            [$this->validServer(['GATEWAY_INTERFACE' => 'CGI/1.0']), AdapterControlledDeliveryPermission::Denied],
        ] as [$server, $expected]) {
            $delivery = $this->delivery($server);
            self::assertSame($expected, $delivery->evaluateTrustedPresentationPermission());
            $delivery->close();
        }
    }

    public function testValidRequestLazilyReadsExactBodyAndClearsCaptureState(): void
    {
        $opened = 0;
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $this->validServer(['CONTENT_LENGTH' => '4', 'CONTENT_TYPE' => 'application/octet-stream']),
            true,
            true,
            static function () use (&$opened): mixed {
                ++$opened;

                return self::input('body');
            },
        );
        self::assertSame(0, $opened);
        $request = $delivery->request();

        self::assertSame(1, $opened);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('body', (string) $request->getBody());
        foreach (['sapi', 'server', 'postEmpty', 'filesEmpty', 'openInput'] as $property) {
            self::assertNull((new \ReflectionProperty(CgiWebDelivery::class, $property))->getValue($delivery));
        }
        $delivery->close();
    }

    public function testInputOpenFailureAndThrownOpenerPreserveClosedBehavior(): void
    {
        $delivery = CgiWebDelivery::fromSnapshot('cgi-fcgi', $this->validServer(), true, true, static fn(): false => false);
        try {
            $delivery->request();
            self::fail('False opener result must reject.');
        } catch (CgiRequestReadException $failure) {
            self::assertSame('KGN-PHP-CGI-1010', $failure->diagnosticId());
            self::assertSame('input_open_failed', $failure->diagnosticContext()->fields()[0]->value());
        } finally {
            $delivery->close();
        }

        $sentinel = new RuntimeException('opener-identity');
        $delivery = CgiWebDelivery::fromSnapshot('cgi-fcgi', $this->validServer(), true, true, static function () use ($sentinel): never {
            throw $sentinel;
        });
        try {
            $delivery->request();
            self::fail('Opener failure must propagate.');
        } catch (Throwable $failure) {
            self::assertSame($sentinel, $failure);
        } finally {
            $delivery->close();
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMalformedHeadSuppressesGenericBodyAndNonHeadDoesNot(): void
    {
        $head = $this->delivery($this->validServer(['REQUEST_METHOD' => 'HEAD', 'HTTP_HOST' => '']));
        try {
            $head->request();
            self::fail('Malformed HEAD must reject conversion.');
        } catch (InvalidCgiRequestException) {
        }
        $head->send(new Response(500, [], new ClosingTestStream('hidden')));
        $head->close();

        $get = $this->delivery($this->validServer(['REQUEST_METHOD' => 'bad']));
        try {
            $get->request();
            self::fail('Malformed non-HEAD must reject conversion.');
        } catch (InvalidCgiRequestException) {
        }
        $get->send(new Response(500, [], new ClosingTestStream('visible')));
        $get->close();

        self::expectOutputString('visible');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCloseAttemptsDistinctResourcesAndPreservesFirstFailure(): void
    {
        $inputCloseCalls = 0;
        $responseFailure = new RuntimeException('response-close-identity');
        $body = new ClosingTestStream('', $responseFailure);
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $this->validServer(),
            true,
            true,
            static fn(): mixed => self::input(''),
            static function (mixed $input) use (&$inputCloseCalls): bool {
                ++$inputCloseCalls;
                if (!is_resource($input)) {
                    throw new LogicException('Expected test input resource.');
                }

                return fclose($input);
            },
        );
        $delivery->request();
        $delivery->send(new Response(200, [], $body));
        try {
            $delivery->close();
            self::fail('First cleanup failure must propagate.');
        } catch (Throwable $failure) {
            self::assertSame($responseFailure, $failure);
        }
        self::assertSame(1, $body->closeCalls);
        self::assertSame(1, $inputCloseCalls);
        self::expectOutputString('');
    }

    public function testNativeCloserFalseUsesDiagnostic1018AfterStateDetachment(): void
    {
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $this->validServer(),
            true,
            true,
            static fn(): mixed => self::input(''),
            static function (mixed $input) use (&$delivery): bool {
                foreach (['sapi', 'server', 'postEmpty', 'filesEmpty', 'openInput', 'closeInput', 'input', 'request', 'responseBody'] as $property) {
                    self::assertNull((new \ReflectionProperty(CgiWebDelivery::class, $property))->getValue($delivery));
                }
                if (!is_resource($input)) {
                    throw new LogicException('Expected test input resource.');
                }
                fclose($input);

                return false;
            },
        );
        $delivery->request();

        try {
            $delivery->close();
            self::fail('False native close result must reject.');
        } catch (CgiDeliveryCleanupException $failure) {
            self::assertSame('KGN-PHP-CGI-1018', $failure->diagnosticId());
            self::assertSame('input_close_failed', $failure->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString('warning', strtolower($failure->getMessage()));
        }
    }

    public function testExplicitCloserThrowablePropagatesByIdentityAfterRequestBodyCleanup(): void
    {
        $sentinel = new RuntimeException('closer-identity');
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $this->validServer(),
            true,
            true,
            static fn(): mixed => self::input(''),
            static function (mixed $input) use ($sentinel): never {
                if (is_resource($input)) {
                    fclose($input);
                }
                throw $sentinel;
            },
        );
        $request = $delivery->request();
        try {
            $delivery->close();
            self::fail('Explicit closer failure must propagate.');
        } catch (Throwable $failure) {
            self::assertSame($sentinel, $failure);
        }
        self::assertFalse($request->getBody()->isReadable());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSharedRequestAndResponseBodyIsClosedAsOneIdentity(): void
    {
        $inputCloseCalls = 0;
        $delivery = CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $this->validServer(),
            true,
            true,
            static fn(): mixed => self::input(''),
            static function (mixed $input) use (&$inputCloseCalls): bool {
                ++$inputCloseCalls;
                if (!is_resource($input)) {
                    throw new LogicException('Expected test input resource.');
                }

                return fclose($input);
            },
        );
        $request = $delivery->request();
        $sharedBody = $request->getBody();
        $delivery->send(new Response(200, [], $sharedBody));
        $delivery->close();

        self::assertFalse($sharedBody->isReadable());
        self::assertSame(1, $inputCloseCalls);
        self::expectOutputString('');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testResponseEmissionOccursOnceAndCannotRetry(): void
    {
        $delivery = $this->delivery();
        $delivery->request();
        $delivery->send(new Response(200, [], new ClosingTestStream('once')));
        try {
            $delivery->send(new Response(200, [], new ClosingTestStream('twice')));
            self::fail('Second send must reject.');
        } catch (LogicException $failure) {
            self::assertSame('PHP CGI delivery response was already attempted.', $failure->getMessage());
        }
        $delivery->close();
        self::expectOutputString('once');
    }

    public function testAlternatingDeliveryProfilesRetainNoCrossDeliveryState(): void
    {
        $profiles = [
            [$this->validServer(), AdapterControlledDeliveryPermission::Allowed, true],
            [$this->validServer(['HTTP_HOST' => '']), AdapterControlledDeliveryPermission::Allowed, false],
            [$this->validServer(['REMOTE_ADDR' => '192.0.2.10']), AdapterControlledDeliveryPermission::Denied, true],
            [$this->validServer(['REQUEST_METHOD' => 'HEAD']), AdapterControlledDeliveryPermission::Allowed, true],
            [$this->validServer(['REQUEST_METHOD' => 'POST']), AdapterControlledDeliveryPermission::Allowed, true],
        ];

        foreach ($profiles as [$server, $permission, $valid]) {
            $delivery = $this->delivery($server);
            self::assertSame($permission, $delivery->evaluateTrustedPresentationPermission());
            try {
                $request = $delivery->request();
                self::assertTrue($valid);
                self::assertSame($server['REQUEST_METHOD'], $request->getMethod());
            } catch (InvalidCgiRequestException) {
                self::assertFalse($valid);
            } finally {
                $delivery->close();
            }
        }
    }

    public function testRepeatedOutOfOrderAndUseAfterCloseCallsRejectDeterministically(): void
    {
        $delivery = $this->delivery();
        try {
            $delivery->send(new Response());
            self::fail('Send before request must reject.');
        } catch (LogicException $failure) {
            self::assertSame('PHP CGI delivery response emission is out of order.', $failure->getMessage());
        }
        $delivery->request();
        try {
            $delivery->request();
            self::fail('Repeated request must reject.');
        } catch (LogicException $failure) {
            self::assertSame('PHP CGI delivery request was already attempted.', $failure->getMessage());
        }
        $delivery->close();
        try {
            $delivery->request();
            self::fail('Request after close must reject.');
        } catch (LogicException $failure) {
            self::assertSame('PHP CGI delivery is closed.', $failure->getMessage());
        }
        try {
            $delivery->evaluateTrustedPresentationPermission();
            self::fail('Permission evaluation after close must reject.');
        } catch (LogicException $failure) {
            self::assertSame('PHP CGI delivery is closed.', $failure->getMessage());
        }
        $this->expectExceptionMessage('PHP CGI delivery was already closed.');
        $delivery->close();
    }

    /** @param array<array-key, mixed> $server */
    private function delivery(?array $server = null): CgiWebDelivery
    {
        return CgiWebDelivery::fromSnapshot(
            'cgi-fcgi',
            $server ?? $this->validServer(),
            true,
            true,
            static fn(): mixed => self::input(''),
        );
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function validServer(array $changes = []): array
    {
        return array_replace([
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'FCGI_ROLE' => 'RESPONDER',
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => '/',
            'QUERY_STRING' => '',
            'HTTP_HOST' => 'example.test',
            'REMOTE_ADDR' => '127.0.0.1',
        ], $changes);
    }

    /** @return resource */
    private static function input(string $bytes): mixed
    {
        $input = fopen('php://temp', 'w+b');
        self::assertIsResource($input);
        self::assertSame(strlen($bytes), fwrite($input, $bytes));
        self::assertTrue(rewind($input));

        return $input;
    }

    /**
     * @param \ReflectionClass<covariant object> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(\ReflectionClass $reflection): array
    {
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
            ),
        );
        sort($methods);

        return $methods;
    }
}

final class ClosingTestStream implements StreamInterface
{
    public int $closeCalls = 0;
    private int $position = 0;

    public function __construct(
        private readonly string $bytes,
        private readonly ?Throwable $closeFailure = null,
    ) {}

    public function __toString(): string
    {
        return $this->bytes;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }

    public function detach(): mixed
    {
        return null;
    }

    public function getSize(): int
    {
        return strlen($this->bytes);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->bytes);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = $offset;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new LogicException('Test stream is not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $chunk = substr($this->bytes, $this->position, $length);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        return $this->read(strlen($this->bytes) - $this->position);
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metadata = ['mode' => 'rb', 'seekable' => true];

        return null === $key ? $metadata : ($metadata[$key] ?? null);
    }
}
