<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\CgiDeliveryCleanupException;
use Kongono\PhpCgi\Exception\CgiEntryShutdownException;
use Kongono\PhpCgi\Exception\CgiOperatorBootstrapException;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\CgiResponseEmissionException;
use Kongono\PhpCgi\Exception\ExceptionInterface;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Kongono\PhpCgi\Exception\InvalidCgiResponseException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class PhpCgiExceptionTest extends TestCase
{
    public function testMarkerAndConcreteShapesAreExactAndSeparated(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        self::assertSame(ApiStatus::Experimental, $marker->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames($marker));

        foreach ([
            \Kongono\Kernel\Exception\ExceptionInterface::class,
            \Kongono\ApplicationGraph\Exception\ExceptionInterface::class,
            \Kongono\DependencyInjection\Exception\ExceptionInterface::class,
            \Kongono\DiagnosticPresentation\Exception\ExceptionInterface::class,
            \Kongono\Diagnostics\Exception\ExceptionInterface::class,
            \Kongono\Http\Message\Exception\ExceptionInterface::class,
            \Kongono\Routing\Exception\ExceptionInterface::class,
            \Kongono\Http\Server\Exception\ExceptionInterface::class,
            \Kongono\Web\Application\Exception\ExceptionInterface::class,
            \Kongono\Web\Runtime\Exception\ExceptionInterface::class,
        ] as $otherMarker) {
            self::assertFalse(is_a(ExceptionInterface::class, $otherMarker, true));
            self::assertFalse(is_a($otherMarker, ExceptionInterface::class, true));
        }

        $invalid = new \ReflectionClass(InvalidCgiRequestException::class);
        $bootstrap = new \ReflectionClass(CgiOperatorBootstrapException::class);
        $read = new \ReflectionClass(CgiRequestReadException::class);
        $invalidResponse = new \ReflectionClass(InvalidCgiResponseException::class);
        $emission = new \ReflectionClass(CgiResponseEmissionException::class);
        $cleanup = new \ReflectionClass(CgiDeliveryCleanupException::class);
        $entryShutdown = new \ReflectionClass(CgiEntryShutdownException::class);
        foreach ([$invalid, $bootstrap, $read, $invalidResponse, $emission, $cleanup, $entryShutdown] as $reflection) {
            self::assertTrue($reflection->isFinal());
            self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
            $expectedProperties = match ($reflection->getName()) {
                CgiResponseEmissionException::class => ['commitAttempted', 'diagnostic', 'diagnosticContext'],
                CgiEntryShutdownException::class => ['diagnostic', 'diagnosticContext', 'primaryFailure', 'shutdownFailure'],
                default => ['diagnostic', 'diagnosticContext'],
            };
            $expectedMethods = match ($reflection->getName()) {
                CgiResponseEmissionException::class => ['__construct', 'commitAttempted', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
                CgiEntryShutdownException::class => ['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle', 'primaryFailure', 'shutdownFailure'],
                default => ['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            };
            self::assertSame($expectedProperties, $this->declaredPropertyNames($reflection));
            self::assertSame($expectedMethods, $this->declaredPublicMethodNames($reflection));
            foreach (['diagnosticContext', 'diagnosticId', 'diagnosticTitle', 'diagnosticRemediation'] as $method) {
                self::assertTrue($reflection->getMethod($method)->isFinal());
            }
            self::assertNotSame($reflection->getName(), $reflection->getMethod('__toString')->getDeclaringClass()->getName());
        }
        $invalidParent = $invalid->getParentClass();
        $bootstrapParent = $bootstrap->getParentClass();
        $readParent = $read->getParentClass();
        $cleanupParent = $cleanup->getParentClass();
        $entryShutdownParent = $entryShutdown->getParentClass();
        self::assertInstanceOf(\ReflectionClass::class, $invalidParent);
        self::assertInstanceOf(\ReflectionClass::class, $bootstrapParent);
        self::assertInstanceOf(\ReflectionClass::class, $readParent);
        self::assertInstanceOf(\ReflectionClass::class, $cleanupParent);
        self::assertInstanceOf(\ReflectionClass::class, $entryShutdownParent);
        self::assertSame(InvalidArgumentException::class, $invalidParent->getName());
        self::assertSame(RuntimeException::class, $bootstrapParent->getName());
        self::assertSame(RuntimeException::class, $readParent->getName());
        self::assertSame(RuntimeException::class, $cleanupParent->getName());
        self::assertSame(RuntimeException::class, $entryShutdownParent->getName());
        $invalidResponseParent = $invalidResponse->getParentClass();
        $emissionParent = $emission->getParentClass();
        self::assertInstanceOf(\ReflectionClass::class, $invalidResponseParent);
        self::assertInstanceOf(\ReflectionClass::class, $emissionParent);
        self::assertSame(InvalidArgumentException::class, $invalidResponseParent->getName());
        self::assertSame(RuntimeException::class, $emissionParent->getName());
        self::assertSame([PhpCgiDiagnostic::class, 'string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $invalid->getConstructor()?->getParameters() ?? []));
        self::assertSame(['string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $bootstrap->getConstructor()?->getParameters() ?? []));
        self::assertSame(['string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $read->getConstructor()?->getParameters() ?? []));
        self::assertSame(['string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $cleanup->getConstructor()?->getParameters() ?? []));
        self::assertSame(['Throwable', 'Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $entryShutdown->getConstructor()?->getParameters() ?? []));
        self::assertSame([PhpCgiDiagnostic::class, 'string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $invalidResponse->getConstructor()?->getParameters() ?? []));
        self::assertSame([PhpCgiDiagnostic::class, 'string', 'bool', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $emission->getConstructor()?->getParameters() ?? []));
    }

    public function testInvalidRequestFailureHasExactEagerSafeMetadataAndPrevious(): void
    {
        $previous = new RuntimeException('raw-previous-sentinel');
        $exception = new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidRequestTarget, 'authority_invalid', $previous);
        self::assertSame('KGN-PHP-CGI-1004', $exception->diagnosticId());
        self::assertSame('Invalid CGI request target', $exception->getMessage());
        self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
        self::assertSame('Supply a bounded origin-form target, matching query string, supported protocol, and valid host', $exception->diagnosticRemediation());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        self::assertSame('authority_invalid', $exception->diagnosticContext()->fields()[0]->value());
    }

    public function testReadFailureUsesOnlyInputDiagnosticAndPreservesPrevious(): void
    {
        $previous = new LogicException('raw-read-previous-sentinel');
        $exception = new CgiRequestReadException('input_early_eof', $previous);
        self::assertSame('KGN-PHP-CGI-1010', $exception->diagnosticId());
        self::assertSame('Unable to read CGI request input', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        self::assertSame('input_early_eof', $exception->diagnosticContext()->fields()[0]->value());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsupported PHP CGI invalid-request diagnostic.');
        new InvalidCgiRequestException(PhpCgiDiagnostic::InputReadFailed, 'input_closed');
    }

    public function testOperatorBootstrapFailureHasExactEagerSafeMetadataAndPrevious(): void
    {
        $previous = new LogicException('raw-bootstrap-previous-sentinel');
        $exception = new CgiOperatorBootstrapException('auto_prepend_file_configured', $previous);

        self::assertSame('KGN-PHP-CGI-1017', $exception->diagnosticId());
        self::assertSame('Unable to bootstrap PHP CGI operator profile', $exception->getMessage());
        self::assertSame($exception->diagnosticTitle(), $exception->getMessage());
        self::assertSame('Run the selected cgi-fcgi profile with every required PHP setting disabled before serving', $exception->diagnosticRemediation());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        self::assertSame('auto_prepend_file_configured', $exception->diagnosticContext()->fields()[0]->value());
    }

    public function testDeliveryCleanupFailureHasExactEagerSafeMetadataAndPrevious(): void
    {
        $previous = new LogicException('raw-cleanup-previous-sentinel');
        $exception = new CgiDeliveryCleanupException('input_close_failed', $previous);

        self::assertSame('KGN-PHP-CGI-1018', $exception->diagnosticId());
        self::assertSame('Unable to close PHP CGI delivery input', $exception->getMessage());
        self::assertSame('Retire the delivery and correct the selected host input-resource failure before serving again', $exception->diagnosticRemediation());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('input_close_failed', $exception->diagnosticContext()->fields()[0]->value());
    }

    public function testEntryShutdownFailurePreservesExactRolesWithoutSafeRawDetail(): void
    {
        $primary = new LogicException('raw-primary-sentinel');
        $shutdown = new RuntimeException('raw-shutdown-sentinel');
        $exception = new CgiEntryShutdownException($primary, $shutdown);

        self::assertSame('KGN-PHP-CGI-1019', $exception->diagnosticId());
        self::assertSame('PHP CGI entry and shutdown both failed', $exception->getMessage());
        self::assertSame('Retire the process and correct both the entry failure and application shutdown failure before serving again', $exception->diagnosticRemediation());
        self::assertSame(0, $exception->getCode());
        self::assertSame($primary, $exception->primaryFailure());
        self::assertSame($primary, $exception->getPrevious());
        self::assertSame($shutdown, $exception->shutdownFailure());
        self::assertSame('entry_and_shutdown_failed', $exception->diagnosticContext()->fields()[0]->value());
        $safeSurface = implode('|', [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
            $exception->diagnosticContext()->fields()[0]->value(),
        ]);
        self::assertStringNotContainsString('raw-primary-sentinel', $safeSurface);
        self::assertStringNotContainsString('raw-shutdown-sentinel', $safeSurface);
    }

    public function testPackageOwnedSurfacesDoNotRetainRawSentinels(): void
    {
        $sentinels = ['raw-method-sentinel', 'raw-target-sentinel', 'raw-host-sentinel', 'raw-header-sentinel', 'raw-query-sentinel', 'raw-cookie-sentinel', 'raw-body-sentinel', 'raw-post-sentinel', 'raw-file-sentinel', 'raw-sapi-sentinel', 'raw-ini-sentinel', 'raw-profile-sentinel'];
        foreach ([
            new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidRequestMethod, 'method_not_uppercase_token', new RuntimeException(implode('|', $sentinels))),
            new CgiOperatorBootstrapException('ini_snapshot_invalid', new RuntimeException(implode('|', $sentinels))),
            new CgiRequestReadException('input_read_failed', new RuntimeException(implode('|', $sentinels))),
            new CgiDeliveryCleanupException('input_close_failed', new RuntimeException(implode('|', $sentinels))),
            new InvalidCgiResponseException(PhpCgiDiagnostic::InvalidResponseHeaders, 'header_value_invalid', new RuntimeException(implode('|', $sentinels))),
            new CgiResponseEmissionException(PhpCgiDiagnostic::ResponseEmissionFailed, 'output_write_failed', true, new RuntimeException(implode('|', $sentinels))),
        ] as $exception) {
            $context = $exception->diagnosticContext();
            $surfaces = [$exception->getMessage(), $exception->diagnosticId(), $exception->diagnosticTitle(), $exception->diagnosticRemediation(), $context->operation(), $context->violatedInvariant()];
            foreach ($context->fields() as $field) {
                $surfaces[] = $field->key();
                $surfaces[] = $field->label();
                $surfaces[] = (string) $field->value();
            }
            foreach ((new \ReflectionClass($exception))->getProperties() as $property) {
                $value = $property->getValue($exception);
                if (is_string($value) || is_int($value) || is_bool($value)) {
                    $surfaces[] = (string) $value;
                }
            }
            $surface = implode('|', $surfaces);
            foreach ($sentinels as $sentinel) {
                self::assertStringNotContainsString($sentinel, $surface);
            }
        }
    }

    public function testResponseExceptionCategoriesAndCommittedStateAreClosed(): void
    {
        $previous = new RuntimeException('response-previous-identity');
        $invalid = new InvalidCgiResponseException(
            PhpCgiDiagnostic::InvalidResponseBody,
            'body_stalled',
            $previous,
        );
        self::assertSame(PhpCgiDiagnostic::InvalidResponseBody->value, $invalid->diagnosticId());
        self::assertSame($previous, $invalid->getPrevious());
        self::assertSame('body_stalled', $invalid->diagnosticContext()->fields()[0]->value());

        $preflight = new CgiResponseEmissionException(
            PhpCgiDiagnostic::ResponsePreflightFailed,
            'headers_already_sent',
            false,
            $previous,
        );
        $emission = new CgiResponseEmissionException(
            PhpCgiDiagnostic::ResponseEmissionFailed,
            'status_emit_failed',
            true,
            $previous,
        );
        self::assertFalse($preflight->commitAttempted());
        self::assertTrue($emission->commitAttempted());
        self::assertSame($previous, $preflight->getPrevious());
        self::assertSame($previous, $emission->getPrevious());

        foreach ([
            static fn(): InvalidCgiRequestException => new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidResponseStatus, 'status_out_of_range'),
            static fn(): InvalidCgiResponseException => new InvalidCgiResponseException(PhpCgiDiagnostic::InvalidRequestBody, 'body_too_large'),
            static fn(): CgiResponseEmissionException => new CgiResponseEmissionException(PhpCgiDiagnostic::ResponsePreflightFailed, 'headers_already_sent', true),
            static fn(): CgiResponseEmissionException => new CgiResponseEmissionException(PhpCgiDiagnostic::ResponseEmissionFailed, 'status_emit_failed', false),
        ] as $invalidConstruction) {
            try {
                $invalidConstruction();
                self::fail('Cross-category or contradictory response failure must reject.');
            } catch (LogicException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /**
     * @param \ReflectionClass<covariant object> $reflection
     * @return list<string>
     */
    private function declaredPropertyNames(\ReflectionClass $reflection): array
    {
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName()));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($names);

        return $names;
    }

    /**
     * @param \ReflectionClass<covariant object> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(\ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()),
        );
        sort($names);

        return $names;
    }
}
