<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal {
    /** @internal Test-only namespace seam for otherwise native process calls. */
    function phase3dTestStagingMode(?string $mode): void
    {
        $GLOBALS['kongono_phase3d_staging_mode'] = $mode;
        $GLOBALS['kongono_phase3d_staging_resource'] = null;
    }

    /** @return resource|false */
    function fopen(string $filename, string $mode): mixed
    {
        $testMode = $GLOBALS['kongono_phase3d_staging_mode'] ?? null;
        $resource = match ($testMode) {
            'open_failed' => false,
            'write_failed' => \fopen('php://temp', 'rb'),
            'rewind_failed' => \fopen('php://output', 'wb'),
            default => \fopen($filename, $mode),
        };
        $GLOBALS['kongono_phase3d_staging_resource'] = $resource;

        return $resource;
    }

    function phase3dTestStagingResource(): mixed
    {
        return $GLOBALS['kongono_phase3d_staging_resource'] ?? null;
    }

    function phase3dTestNativeHeaderWarning(bool $enabled): void
    {
        $GLOBALS['kongono_phase3d_native_header_warning'] = $enabled;
    }

    function headers_sent(): bool
    {
        return \headers_sent();
    }

    function http_response_code(int $responseCode): int|bool
    {
        return \http_response_code($responseCode);
    }

    function header(string $header, bool $replace = true): void
    {
        if (true === ($GLOBALS['kongono_phase3d_native_header_warning'] ?? false)) {
            trigger_error('native-warning-sentinel', E_USER_WARNING);
        }

        \header($header, $replace);
    }

    function file_put_contents(string $filename, mixed $data, int $flags = 0): int|false
    {
        return \file_put_contents($filename, $data, $flags);
    }
}

namespace Kongono\PhpCgi\Tests\Unit\Emission {

    use Kongono\ApiMetadata\Api;
    use Kongono\ApiMetadata\ApiStatus;
    use Kongono\Http\Message\Message\Response;
    use Kongono\Http\Message\Message\Stream;
    use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
    use Kongono\PhpCgi\Emission\CgiResponseEmitter;
    use Kongono\PhpCgi\Exception\CgiResponseEmissionException;
    use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
    use Kongono\PhpCgi\Exception\InvalidCgiResponseException;
    use Kongono\PhpCgi\Internal\CgiEmissionTarget;
    use Kongono\PhpCgi\Internal\CgiNativeEmissionTarget;
    use Kongono\PhpCgi\Internal\CgiResponseEmission;
    use Kongono\PhpCgi\Internal\CgiStagedResponse;

    use function Kongono\PhpCgi\Internal\phase3dTestNativeHeaderWarning;
    use function Kongono\PhpCgi\Internal\phase3dTestStagingMode;
    use function Kongono\PhpCgi\Internal\phase3dTestStagingResource;

    use Kongono\PhpCgi\Tests\Fixture\RecordingEmissionTarget;
    use LogicException;
    use PHPUnit\Framework\Attributes\PreserveGlobalState;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\TestCase;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;
    use RuntimeException;
    use Throwable;

    final class CgiResponseEmitterTest extends TestCase
    {
        protected function tearDown(): void
        {
            phase3dTestStagingMode(null);
            phase3dTestNativeHeaderWarning(false);
        }

        public function testSuccessPreservesOrderReplaceSemanticsAndComputedLength(): void
        {
            $target = new RecordingEmissionTarget();
            $body = $this->stream('body');
            CgiResponseEmission::emit(
                new Response(200, ['X-First' => ['one', 'two'], 'x-Second' => ["three\tfour"]], $body, '1.1'),
                'GET',
                $target,
            );

            self::assertSame([
                ['operation' => 'status', 'status' => 200],
                ['operation' => 'header', 'name' => 'X-First', 'value' => 'one', 'replace' => true],
                ['operation' => 'header', 'name' => 'X-First', 'value' => 'two', 'replace' => false],
                ['operation' => 'header', 'name' => 'x-Second', 'value' => "three\tfour", 'replace' => true],
                ['operation' => 'header', 'name' => 'Content-Length', 'value' => '4', 'replace' => true],
                ['operation' => 'body', 'bytes' => 'body'],
            ], $target->emissionCalls());
            self::assertSame(['body'], $target->bodyChunks());
            self::assertTrue($body->eof());
            self::assertTrue($body->isReadable());
            self::assertFalse(is_resource(phase3dTestStagingResource()));
        }

        public function testStatusAndProtocolBoundariesAndAllAcceptedLabelsSucceed(): void
        {
            foreach ([200, 599] as $status) {
                foreach (['1.0', '1.1', '2', '2.0', '3', '3.0'] as $protocol) {
                    $target = new RecordingEmissionTarget();
                    CgiResponseEmission::emit(new Response($status, [], $this->stream(''), $protocol), 'GET', $target);
                    self::assertSame($status, $target->emissionCalls()[0]['status']);
                    self::assertSame('0', $target->emissionCalls()[1]['value']);
                }
            }
        }

        public function testBodyChunkingAndOneMebibyteBoundaryAreExact(): void
        {
            foreach ([8192, 8193, 1048576] as $length) {
                $target = new RecordingEmissionTarget();
                CgiResponseEmission::emit(new Response(200, [], $this->stream(str_repeat('x', $length))), 'POST', $target);
                self::assertSame((string) $length, $target->emissionCalls()[1]['value']);
                $chunks = $target->bodyChunks();
                self::assertSame($length, strlen(implode('', $chunks)));
                if ([] === $chunks) {
                    throw new LogicException('A non-empty staged body must produce output chunks.');
                }
                self::assertLessThanOrEqual(8192, max(array_map('strlen', $chunks)));
                self::assertSame($length % 8192 === 0 ? 0 : 1, count(array_filter($chunks, static fn(string $chunk): bool => strlen($chunk) < 8192)));
            }
        }

        public function testHeadStagesAndCountsButNeverWritesBody(): void
        {
            $body = $this->stream('fully-staged');
            $target = new RecordingEmissionTarget();
            CgiResponseEmission::emit(new Response(200, [], $body), 'HEAD', $target);
            self::assertSame('12', $target->emissionCalls()[1]['value']);
            self::assertSame([], $target->bodyChunks());
            self::assertTrue($body->eof());
        }

        public function testCallerBodyStartsAtCurrentPositionAndRemainsCallerOwned(): void
        {
            $body = $this->stream('prefix-body');
            $body->seek(7);
            $target = new RecordingEmissionTarget();
            CgiResponseEmission::emit(new Response(200, [], $body), 'GET', $target);
            self::assertSame(['body'], $target->bodyChunks());
            self::assertTrue($body->eof());
            self::assertTrue($body->isReadable());
        }

        public function testRepeatedSuccessFailureSuccessCallsLeakNoState(): void
        {
            $first = new RecordingEmissionTarget();
            CgiResponseEmission::emit(new Response(200, [], $this->stream('one')), 'GET', $first);
            $this->assertInvalidResponse(
                new Response(199, [], $this->stream('bad')),
                PhpCgiDiagnostic::InvalidResponseStatus,
                'status_out_of_range',
            );
            $third = new RecordingEmissionTarget();
            CgiResponseEmission::emit(new Response(201, [], $this->stream('three')), 'GET', $third);
            self::assertSame(['one'], $first->bodyChunks());
            self::assertSame(['three'], $third->bodyChunks());
        }

        public function testRequestMethodUsesExistingClosedGrammar(): void
        {
            $target = new RecordingEmissionTarget();
            try {
                CgiResponseEmission::emit(new Response(200, [], $this->stream('')), 'get', $target);
                self::fail('Invalid request method must reject.');
            } catch (InvalidCgiRequestException $failure) {
                self::assertSame(PhpCgiDiagnostic::InvalidRequestMethod->value, $failure->diagnosticId());
                self::assertSame('method_not_uppercase_token', $failure->diagnosticContext()->fields()[0]->value());
                self::assertSame([], $target->emissionCalls());
            }
        }

        public function testStatusAndProtocolRejectionsAreExactAndPrecommit(): void
        {
            foreach ([199, 600] as $status) {
                $this->assertInvalidResponse($this->response($status, '1.1', [], $this->stream('')), PhpCgiDiagnostic::InvalidResponseStatus, 'status_out_of_range');
            }
            $this->assertInvalidResponse(
                $this->response(200, '1.2', [], $this->stream('')),
                PhpCgiDiagnostic::InvalidResponseProtocol,
                'protocol_unsupported',
            );
        }

        public function testHeaderStructuralRejectionsAreExact(): void
        {
            $cases = [
                [[0 => ['x']], 'header_name_not_string'],
                [['X' => [1 => 'x']], 'header_values_not_list'],
                [['X' => [1]], 'header_value_not_string'],
                [['Bad Name' => ['x']], 'header_name_invalid'],
                [['X-Test' => ['x'], 'x-test' => ['y']], 'header_name_duplicate'],
                [['Status' => ['200']], 'header_forbidden'],
                [['content-LENGTH' => ['1']], 'header_forbidden'],
                [['Transfer-ENCODING' => ['chunked']], 'header_forbidden'],
                [['Connection' => ['close']], 'header_forbidden'],
                [['Keep-ALIVE' => ['timeout=5']], 'header_forbidden'],
                [['Proxy-AUTHENTICATE' => ['Basic']], 'header_forbidden'],
                [['Proxy-AUTHORIZATION' => ['Basic value']], 'header_forbidden'],
                [['te' => ['trailers']], 'header_forbidden'],
                [['TRAILER' => ['X-Test']], 'header_forbidden'],
                [['upGRADE' => ['websocket']], 'header_forbidden'],
                [['X' => []], 'header_value_count_invalid'],
                [['X' => array_fill(0, 33, 'x')], 'header_value_count_invalid'],
                [['X' => ["bad\r\nvalue"]], 'header_value_invalid'],
                [['X' => ["bad\0value"]], 'header_value_invalid'],
                [['X' => ["bad\x7Fvalue"]], 'header_value_invalid'],
                [['X' => [str_repeat('x', 8193)]], 'header_value_too_long'],
            ];
            foreach ($cases as [$headers, $token]) {
                $this->assertInvalidResponse(
                    $this->response(200, '1.1', $headers, $this->stream('')),
                    PhpCgiDiagnostic::InvalidResponseHeaders,
                    $token,
                );
            }
        }

        public function testHeaderCountValueAndTotalByteBoundariesAreExact(): void
        {
            $headers = [];
            for ($index = 0; $index < 128; ++$index) {
                $headers[sprintf('X%03d', $index)] = [''];
            }
            CgiResponseEmission::emit($this->response(200, '1.1', $headers, $this->stream('')), 'GET', new RecordingEmissionTarget());
            $headers['Overflow'] = ['x'];
            $this->assertInvalidResponse($this->response(200, '1.1', $headers, $this->stream('')), PhpCgiDiagnostic::InvalidResponseHeaders, 'header_count_exceeded');

            CgiResponseEmission::emit($this->response(200, '1.1', ['X' => array_fill(0, 32, 'v')], $this->stream('')), 'GET', new RecordingEmissionTarget());
            CgiResponseEmission::emit($this->response(200, '1.1', ['X' => [str_repeat('v', 8192)]], $this->stream('')), 'GET', new RecordingEmissionTarget());

            $exact = [];
            for ($index = 0; $index < 8; ++$index) {
                $exact['X' . $index] = [str_repeat('v', 8190)];
            }
            CgiResponseEmission::emit($this->response(200, '1.1', $exact, $this->stream('')), 'GET', new RecordingEmissionTarget());
            $exact['X7'] = [str_repeat('v', 8191)];
            $this->assertInvalidResponse($this->response(200, '1.1', $exact, $this->stream('')), PhpCgiDiagnostic::InvalidResponseHeaders, 'header_bytes_exceeded');
        }

        public function testBodyRejectionsAndBoundaryPlusOneAreExact(): void
        {
            $this->assertInvalidResponse(
                $this->response(200, '1.1', [], $this->scriptedBody('', readable: false)),
                PhpCgiDiagnostic::InvalidResponseBody,
                'body_unreadable',
            );
            $this->assertInvalidResponse(
                $this->response(200, '1.1', [], $this->scriptedBody('', stalled: true)),
                PhpCgiDiagnostic::InvalidResponseBody,
                'body_stalled',
            );
            $this->assertInvalidResponse(
                $this->response(200, '1.1', [], $this->scriptedBody('', overReturn: true)),
                PhpCgiDiagnostic::InvalidResponseBody,
                'body_read_exceeded_request',
            );
            $this->assertInvalidResponse(
                new Response(200, [], $this->stream(str_repeat('x', 1048577))),
                PhpCgiDiagnostic::InvalidResponseBody,
                'body_too_large',
            );
        }

        public function testHeadersSentBeforeAccessAndDuringBodyAccessStopPrecommit(): void
        {
            $response = $this->response(200, '1.1', [], $this->stream(''), getterFailure: new RuntimeException('must-not-access'));
            $target = new RecordingEmissionTarget([true]);
            $this->assertEmissionFailure($response, 'GET', $target, 'headers_already_sent', false);
            self::assertSame([], $target->emissionCalls());

            $target = new RecordingEmissionTarget([false, false, false, false, false, false, false, true]);
            $this->assertEmissionFailure(new Response(200, [], $this->stream('body')), 'GET', $target, 'headers_already_sent', false);
            self::assertSame([], $target->emissionCalls());
            self::assertFalse(is_resource(phase3dTestStagingResource()));
        }

        public function testConsumerGetterAndBodyThrowablesPropagateByIdentity(): void
        {
            $getterFailure = new RuntimeException('getter-identity');
            try {
                CgiResponseEmission::emit($this->response(200, '1.1', [], $this->stream(''), getterFailure: $getterFailure), 'GET', new RecordingEmissionTarget());
                self::fail('Getter failure must propagate.');
            } catch (Throwable $failure) {
                self::assertSame($getterFailure, $failure);
            }

            foreach (['eof', 'read'] as $operation) {
                $bodyFailure = new RuntimeException($operation . '-identity');
                try {
                    CgiResponseEmission::emit(
                        $this->response(200, '1.1', [], $this->scriptedBody('body', throwOperation: $operation, failure: $bodyFailure)),
                        'GET',
                        new RecordingEmissionTarget(),
                    );
                    self::fail('Body failure must propagate.');
                } catch (Throwable $failure) {
                    self::assertSame($bodyFailure, $failure);
                    self::assertFalse(is_resource(phase3dTestStagingResource()));
                }
            }
        }

        public function testPackageOwnedStagingFailuresAreClosedAndUncommitted(): void
        {
            foreach ([
                'open_failed' => 'staging_open_failed',
                'write_failed' => 'staging_write_failed',
                'rewind_failed' => 'staging_rewind_failed',
            ] as $mode => $token) {
                phase3dTestStagingMode($mode);
                $body = 'rewind_failed' === $mode ? '' : 'body';
                $target = new RecordingEmissionTarget();
                $this->assertEmissionFailure(new Response(200, [], $this->stream($body)), 'GET', $target, $token, false);
                self::assertSame([], $target->emissionCalls());
                self::assertFalse(is_resource(phase3dTestStagingResource()));
                phase3dTestStagingMode(null);
            }
        }

        public function testEveryTargetFailureStopsWithoutRetryAndReportsCommitted(): void
        {
            foreach ([
                [new Response(200, [], $this->stream('body')), 1, 'status_emit_failed'],
                [new Response(200, ['X' => ['one', 'two']], $this->stream('body')), 2, 'header_emit_failed'],
                [new Response(200, ['X' => ['one', 'two']], $this->stream('body')), 3, 'header_emit_failed'],
                [new Response(200, [], $this->stream('body')), 2, 'content_length_emit_failed'],
                [new Response(200, [], $this->stream('body')), 3, 'output_write_failed'],
            ] as [$response, $call, $token]) {
                $sentinel = new RuntimeException('target-failure-' . $token);
                $target = new RecordingEmissionTarget(failAtEmissionCall: $call, failure: $sentinel);
                $failure = $this->assertEmissionFailure($response, 'GET', $target, $token, true);
                self::assertSame($sentinel, $failure->getPrevious());
                self::assertCount($call, $target->emissionCalls());
            }
        }

        public function testFalseZeroAndShortOutputWritesFailWithoutRetry(): void
        {
            foreach ([false, 0, 3] as $result) {
                $target = new RecordingEmissionTarget(writeResults: [$result]);
                $this->assertEmissionFailure(new Response(200, [], $this->stream('body')), 'GET', $target, 'output_write_failed', true);
                self::assertSame(['body'], $target->bodyChunks());
            }
        }

        public function testStagedBodyReadFailureIsWrappedOnceAsCommitted(): void
        {
            $resource = \fopen('NUL', 'wb');
            self::assertIsResource($resource);
            $stream = new Stream($resource);
            self::assertSame(4, $stream->write('body'));
            $staged = new CgiStagedResponse(200, '1.1', [], $stream, 4);
            $method = new \ReflectionMethod(CgiResponseEmission::class, 'emitStaged');
            try {
                $method->invoke(null, $staged, 'GET', new RecordingEmissionTarget());
                self::fail('Unreadable staged body must fail.');
            } catch (CgiResponseEmissionException $failure) {
                self::assertSame('staged_body_read_failed', $failure->diagnosticContext()->fields()[0]->value());
                self::assertTrue($failure->commitAttempted());
                self::assertInstanceOf(Throwable::class, $failure->getPrevious());
            } finally {
                $stream->close();
            }
        }

        public function testPublicAndInternalShapesAreExact(): void
        {
            $emitter = new \ReflectionClass(CgiResponseEmitter::class);
            self::assertTrue($emitter->isFinal());
            self::assertSame(ApiStatus::Experimental, $emitter->getAttributes(Api::class)[0]->newInstance()->status);
            self::assertSame([], $emitter->getProperties());
            self::assertNull($emitter->getConstructor());
            self::assertSame(['emit'], $this->declaredPublicMethods($emitter));
            self::assertSame([ResponseInterface::class, 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $emitter->getMethod('emit')->getParameters()));

            $target = new \ReflectionClass(CgiEmissionTarget::class);
            self::assertTrue($target->isInterface());
            self::assertSame(ApiStatus::Internal, $target->getAttributes(Api::class)[0]->newInstance()->status);
            self::assertSame(['emitHeader', 'headersSent', 'setStatus', 'writeBody'], $this->declaredPublicMethods($target));

            $native = new \ReflectionClass(CgiNativeEmissionTarget::class);
            self::assertTrue($native->isFinal());
            self::assertSame([], $native->getProperties());
            self::assertSame([CgiEmissionTarget::class], $native->getInterfaceNames());

            $operation = new \ReflectionClass(CgiResponseEmission::class);
            self::assertTrue($operation->isFinal());
            self::assertSame([], $operation->getProperties());
            self::assertTrue($operation->getConstructor()?->isPrivate());
            self::assertSame(['emit'], $this->declaredPublicMethods($operation));

            $staged = new \ReflectionClass(CgiStagedResponse::class);
            self::assertTrue($staged->isFinal());
            self::assertTrue($staged->isReadOnly());
            self::assertSame(['status', 'protocol', 'headers', 'body', 'length'], array_map(static fn(\ReflectionParameter $parameter): string => $parameter->getName(), $staged->getConstructor()?->getParameters() ?? []));
        }

        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testNativeTargetRestoresHandlerAndPreservesPreviousHandlerThrowable(): void
        {
            $sentinel = new RuntimeException('previous-handler-identity');
            $handler = static function () use ($sentinel): never {
                throw $sentinel;
            };
            $prior = set_error_handler($handler);
            phase3dTestNativeHeaderWarning(true);
            try {
                (new CgiNativeEmissionTarget())->emitHeader('X-Test', 'value', true);
                self::fail('Native warning must fail.');
            } catch (Throwable $failure) {
                self::assertSame($sentinel, $failure);
                $probe = static fn(): bool => false;
                self::assertSame($handler, set_error_handler($probe));
                restore_error_handler();
            } finally {
                phase3dTestNativeHeaderWarning(false);
                restore_error_handler();
                if (null !== $prior) {
                    set_error_handler($prior);
                }
            }
        }

        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testNativeWarningWithoutPreviousHandlerUsesFixedPackageNeutralFailure(): void
        {
            $probe = static fn(): bool => false;
            $phpunitHandler = set_error_handler($probe);
            restore_error_handler();
            restore_error_handler();
            phase3dTestNativeHeaderWarning(true);

            try {
                (new CgiNativeEmissionTarget())->emitHeader('X-Test', 'value', true);
                self::fail('Native warning must fail.');
            } catch (RuntimeException $failure) {
                self::assertSame('Native CGI emission call failed.', $failure->getMessage());
                self::assertStringNotContainsString('native-warning-sentinel', $failure->getMessage());
                self::assertNull(set_error_handler($probe));
                restore_error_handler();
            } finally {
                phase3dTestNativeHeaderWarning(false);
                if (null !== $phpunitHandler) {
                    set_error_handler($phpunitHandler);
                }
            }
        }

        #[RunInSeparateProcess]
        #[PreserveGlobalState(false)]
        public function testNativeTargetSuccessRestoresHandlerAndWritesExactBytes(): void
        {
            self::expectOutputString('native-output');
            $handler = static fn(): bool => false;
            $prior = set_error_handler($handler);
            try {
                $target = new CgiNativeEmissionTarget();
                self::assertFalse($target->headersSent());
                $target->setStatus(204);
                $target->emitHeader('X-Test', 'value', true);
                self::assertSame(13, $target->writeBody('native-output'));
                $probe = static fn(): bool => false;
                self::assertSame($handler, set_error_handler($probe));
                restore_error_handler();
            } finally {
                restore_error_handler();
                if (null !== $prior) {
                    set_error_handler($prior);
                }
            }
        }

        private function stream(string $bytes): Stream
        {
            $resource = \fopen('php://temp', 'w+b');
            self::assertIsResource($resource);
            self::assertSame(strlen($bytes), \fwrite($resource, $bytes));
            self::assertTrue(\rewind($resource));

            return new Stream($resource);
        }

        /** @param array<array-key, mixed> $headers */
        private function response(
            int $status,
            string $protocol,
            array $headers,
            StreamInterface $body,
            ?Throwable $getterFailure = null,
        ): ResponseInterface {
            $response = self::createStub(ResponseInterface::class);
            if (null === $getterFailure) {
                $response->method('getStatusCode')->willReturn($status);
            } else {
                $response->method('getStatusCode')->willThrowException($getterFailure);
            }
            $response->method('getProtocolVersion')->willReturn($protocol);
            $response->method('getHeaders')->willReturn($headers);
            $response->method('getBody')->willReturn($body);

            return $response;
        }

        private function scriptedBody(
            string $bytes,
            bool $readable = true,
            bool $stalled = false,
            bool $overReturn = false,
            ?string $throwOperation = null,
            ?Throwable $failure = null,
        ): StreamInterface {
            return new class ($bytes, $readable, $stalled, $overReturn, $throwOperation, $failure) implements StreamInterface {
                private int $position = 0;

                public function __construct(
                    private readonly string $bytes,
                    private readonly bool $readable,
                    private readonly bool $stalled,
                    private readonly bool $overReturn,
                    private readonly ?string $throwOperation,
                    private readonly ?Throwable $failure,
                ) {}

                public function __toString(): string
                {
                    return '';
                }
                public function close(): void {}
                public function detach()
                {
                    return null;
                }
                public function getSize(): ?int
                {
                    return '' === $this->bytes ? null : strlen($this->bytes);
                }
                public function tell(): int
                {
                    return $this->position;
                }
                public function eof(): bool
                {
                    if ('eof' === $this->throwOperation) {
                        throw $this->failure ?? new RuntimeException('eof-failure');
                    }
                    if ($this->stalled || $this->overReturn) {
                        return false;
                    }

                    return $this->position >= strlen($this->bytes);
                }
                public function isSeekable(): bool
                {
                    return false;
                }
                public function seek(int $offset, int $whence = SEEK_SET): void
                {
                    throw new LogicException();
                }
                public function rewind(): void
                {
                    throw new LogicException();
                }
                public function isWritable(): bool
                {
                    return false;
                }
                public function write(string $string): int
                {
                    throw new LogicException();
                }
                public function isReadable(): bool
                {
                    return $this->readable;
                }
                public function read(int $length): string
                {
                    if ('read' === $this->throwOperation) {
                        throw $this->failure ?? new RuntimeException('read-failure');
                    }
                    if ($this->stalled) {
                        return '';
                    }
                    if ($this->overReturn) {
                        return str_repeat('x', $length + 1);
                    }
                    $chunk = substr($this->bytes, $this->position, $length);
                    $this->position += strlen($chunk);

                    return $chunk;
                }
                public function getContents(): string
                {
                    return '';
                }
                public function getMetadata(?string $key = null)
                {
                    return null;
                }
            };
        }

        private function assertInvalidResponse(
            ResponseInterface $response,
            PhpCgiDiagnostic $diagnostic,
            string $token,
        ): void {
            $target = new RecordingEmissionTarget();
            try {
                CgiResponseEmission::emit($response, 'GET', $target);
                self::fail('Invalid response must reject.');
            } catch (InvalidCgiResponseException $failure) {
                self::assertSame($diagnostic->value, $failure->diagnosticId());
                self::assertSame($token, $failure->diagnosticContext()->fields()[0]->value());
                self::assertSame([], $target->emissionCalls());
            }
        }

        private function assertEmissionFailure(
            ResponseInterface $response,
            string $requestMethod,
            RecordingEmissionTarget $target,
            string $token,
            bool $commitAttempted,
        ): CgiResponseEmissionException {
            try {
                CgiResponseEmission::emit($response, $requestMethod, $target);
                self::fail('Emission must fail.');
            } catch (CgiResponseEmissionException $failure) {
                self::assertSame($token, $failure->diagnosticContext()->fields()[0]->value());
                self::assertSame($commitAttempted, $failure->commitAttempted());

                return $failure;
            }
        }

        /**
         * @template T of object
         * @param \ReflectionClass<T> $reflection
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
}
