<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\ExceptionInterface;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Internal\HeaderCollection;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Stream;
use Kongono\Http\Message\Message\UploadedFile;
use Kongono\Http\Message\Message\Uri;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class HttpMessageExceptionTest extends TestCase
{
    public function testNativeCategoriesAndDiagnosticBoundaryArePreserved(): void
    {
        $cause = new RuntimeException('cause');
        $invalid = new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStatusCode, ['status_code' => 99], $cause);
        $runtime = new HttpMessageRuntimeException(HttpMessageDiagnostic::DetachedStream, ['operation' => 'read'], $cause);
        self::assertInstanceOf(InvalidArgumentException::class, $invalid);
        self::assertInstanceOf(RuntimeException::class, $runtime);
        foreach ([$invalid, $runtime] as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        }
    }

    public function testMarkerDeclaresExactlyFourMethodsAndExtendsThrowable(): void
    {
        $reflection = new \ReflectionClass(ExceptionInterface::class);
        $methods = array_map(static fn($method): string => $method->getName(), array_filter($reflection->getMethods(), static fn($method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName()));
        sort($methods);
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methods);
        self::assertTrue($reflection->implementsInterface(Throwable::class));
    }

    public function testConcreteShapesStatusesAndSiblingMarkerSeparationAreExact(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);

        foreach ([InvalidMessageArgumentException::class, HttpMessageRuntimeException::class] as $class) {
            $reflection = new \ReflectionClass($class);
            self::assertTrue($reflection->isFinal());
            self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
            $properties = array_values(array_filter(
                $reflection->getProperties(),
                static fn(\ReflectionProperty $property): bool => $class === $property->getDeclaringClass()->getName(),
            ));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(
                static fn(\ReflectionProperty $property): string => $property->getName(),
                $properties,
            ));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            self::assertCount(3, $reflection->getConstructor()?->getParameters() ?? []);
            self::assertNotSame($class, $reflection->getMethod('__toString')->getDeclaringClass()->getName());
        }

        foreach ([
            \Kongono\Kernel\Exception\ExceptionInterface::class,
            \Kongono\ApplicationGraph\Exception\ExceptionInterface::class,
            \Kongono\DependencyInjection\Exception\ExceptionInterface::class,
            \Kongono\Diagnostics\Exception\ExceptionInterface::class,
            \Kongono\DiagnosticPresentation\Exception\ExceptionInterface::class,
        ] as $sibling) {
            self::assertFalse(is_a(ExceptionInterface::class, $sibling, true));
            self::assertFalse(is_a($sibling, ExceptionInterface::class, true));
        }
    }

    public function testRealRejectedInputsNeverEnterPackageExceptionSurfaces(): void
    {
        $headerName = 'X Private Header';
        $this->assertRejectedRawValue(
            $headerName,
            static fn() => new Response(headers: [$headerName => 'value']),
            HttpMessageDiagnostic::InvalidHeaderName,
            [['name_length', 'Name length', strlen($headerName)]],
        );

        $headerValue = "private-header\0value";
        $this->assertRejectedRawValue(
            $headerValue,
            static fn() => new Response(headers: ['X-Test' => [$headerValue]]),
            HttpMessageDiagnostic::UnsafeHeaderValue,
            [['value_position', 'Value position', 0], ['violation', 'Violation', 'nul']],
        );

        $uriText = 'http://private.example/%Q0';
        $this->assertRejectedRawValue(
            $uriText,
            static fn() => new Uri($uriText),
            HttpMessageDiagnostic::InvalidUriComponent,
            [['component', 'Component', 'path'], ['component_length', 'Component length', 4], ['violation', 'Violation', 'percent_triplet']],
        );

        $uriComponent = 'private host';
        $this->assertRejectedRawValue(
            $uriComponent,
            static fn() => (new Uri())->withHost($uriComponent),
            HttpMessageDiagnostic::InvalidUriComponent,
            [['component', 'Component', 'host'], ['component_length', 'Component length', strlen($uriComponent)], ['violation', 'Violation', 'reg_name']],
        );

        $requestTarget = '/private target';
        $this->assertRejectedRawValue(
            $requestTarget,
            static fn() => (new ServerRequest('GET', '/'))->withRequestTarget($requestTarget),
            HttpMessageDiagnostic::InvalidRequestTarget,
            [['actual_length', 'Actual length', strlen($requestTarget)], ['violation', 'Violation', 'whitespace_or_control']],
        );

        $missingPath = __DIR__ . '/private-missing-stream-file';
        $this->assertRejectedRawValue(
            $missingPath,
            static fn() => (new StreamFactory())->createStreamFromFile($missingPath),
            HttpMessageDiagnostic::StreamOperationFailed,
            [['operation', 'Operation', 'open_file']],
        );

        $uploadResource = fopen('php://temp', 'w+b');
        $failedUploadResource = fopen('php://temp', 'w+b');
        self::assertIsResource($uploadResource);
        self::assertIsResource($failedUploadResource);
        $uploadStream = new Stream($uploadResource);
        $failedUploadStream = new Stream($failedUploadResource);
        try {
            $upload = new UploadedFile($uploadStream);
            $target = "private-target\0path";
            $this->assertRejectedRawValue(
                $target,
                static fn() => $upload->moveTo($target),
                HttpMessageDiagnostic::InvalidUploadTarget,
                [['target_length', 'Target length', strlen($target)], ['violation', 'Violation', 'nul']],
            );

            $filename = 'private-client-filename.txt';
            $mediaType = 'private/client-media';
            $failedUpload = new UploadedFile($failedUploadStream, null, UPLOAD_ERR_PARTIAL, $filename, $mediaType);
            $this->assertRejectedRawValues(
                [$filename, $mediaType],
                static fn() => $failedUpload->getStream(),
                HttpMessageDiagnostic::UploadedStreamUnavailable,
                [['state', 'State', 'upload_error']],
            );
        } finally {
            $uploadStream->close();
            $failedUploadStream->close();
        }

        $parsedBody = 'private-parsed-body';
        $this->assertRejectedRawValue(
            $parsedBody,
            static fn() => (new ServerRequest('POST', '/'))->withParsedBody($parsedBody),
            HttpMessageDiagnostic::InvalidParsedBody,
            [['actual_value_kind', 'Actual value kind', 'string']],
        );
    }

    public function testRealFailuresEmitOnlyClosedTextVocabulariesAndBoundedValueKinds(): void
    {
        $vocabularies = [
            'violation' => ['empty', 'whitespace_or_control', 'nul', 'carriage_return', 'line_feed', 'control', 'del', 'grammar', 'structure', 'host', 'port', 'authority', 'syntax', 'non_ascii', 'ip_literal', 'reg_name', 'percent_triplet'],
            'operation' => ['open_temp', 'write_temp', 'open_file', 'tell', 'seek', 'write', 'read', 'get_contents', 'open_target', 'read_source', 'copy_target', 'close_target'],
            'state' => ['moved', 'upload_error'],
            'component' => ['scheme', 'host', 'userinfo', 'path', 'query', 'fragment'],
            'first_unexpected_key_kind' => ['int', 'string'],
        ];

        $missingPath = __DIR__ . '/closed-vocabulary-missing-file';
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        $stateStream = new Stream($resource);
        try {
            $failures = [
                ['violation', static fn() => new Response(headers: ['X' => "value\0tail"])],
                ['operation', static fn() => (new StreamFactory())->createStreamFromFile($missingPath)],
                ['state', static fn() => (new UploadedFile($stateStream, error: UPLOAD_ERR_PARTIAL))->getStream()],
                ['component', static fn() => (new Uri())->withHost('invalid host')],
                ['first_unexpected_key_kind', static fn() => new HeaderCollection(['X' => [1 => 'value']])],
                ['first_unexpected_key_kind', static fn() => new HeaderCollection(['X' => ['unexpected' => 'value']])],
            ];
            foreach ($failures as [$fieldKey, $operation]) {
                $exception = $this->capturePackageException($operation);
                $values = [];
                foreach ($exception->diagnosticContext()->fields() as $field) {
                    $values[$field->key()] = $field->value();
                }
                self::assertArrayHasKey($fieldKey, $values);
                self::assertContains($values[$fieldKey], $vocabularies[$fieldKey]);
            }

            $object = new class {};
            $kindException = $this->capturePackageException(static fn() => new HeaderCollection(['X' => [$object]]));
            $kind = $kindException->diagnosticContext()->fields()[1]->value();
            self::assertIsString($kind);
            self::assertLessThanOrEqual(64, strlen($kind));
            self::assertSame(substr(get_debug_type($object), 0, 64), $kind);
        } finally {
            $stateStream->close();
        }
    }

    /**
     * @param callable(): mixed $operation
     * @param list<array{string, string, string|int|bool}> $fields
     */
    private function assertRejectedRawValue(string $raw, callable $operation, HttpMessageDiagnostic $diagnostic, array $fields): void
    {
        $this->assertRejectedRawValues([$raw], $operation, $diagnostic, $fields);
    }

    /**
     * @param list<string> $rawValues
     * @param callable(): mixed $operation
     * @param list<array{string, string, string|int|bool}> $fields
     */
    private function assertRejectedRawValues(array $rawValues, callable $operation, HttpMessageDiagnostic $diagnostic, array $fields): void
    {
        try {
            $operation();
            self::fail('Expected a first-party HTTP message exception.');
        } catch (ExceptionInterface $exception) {
            self::assertInstanceOf(Throwable::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->getMessage());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($diagnostic->contextSchema()->operation(), $exception->diagnosticContext()->operation());
            self::assertSame($diagnostic->contextSchema()->violatedInvariant(), $exception->diagnosticContext()->violatedInvariant());
            self::assertSame($fields, array_map(
                static fn($field): array => [$field->key(), $field->label(), $field->value()],
                $exception->diagnosticContext()->fields(),
            ));
            $surface = implode('|', [
                $exception->diagnosticId(),
                $exception->getMessage(),
                $exception->diagnosticTitle(),
                $exception->diagnosticRemediation(),
                $exception->diagnosticContext()->operation(),
                $exception->diagnosticContext()->violatedInvariant(),
                ...array_map(static fn($field): string => $field->key() . '|' . $field->label() . '|' . (string) $field->value(), $exception->diagnosticContext()->fields()),
            ]);
            foreach ($rawValues as $raw) {
                self::assertStringNotContainsString($raw, $surface);
            }
        }
    }

    /** @param callable(): mixed $operation */
    private function capturePackageException(callable $operation): ExceptionInterface
    {
        try {
            $operation();
            self::fail('Expected a first-party HTTP message exception.');
        } catch (ExceptionInterface $exception) {
            return $exception;
        }
    }
}
