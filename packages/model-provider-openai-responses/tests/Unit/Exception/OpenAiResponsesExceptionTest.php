<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Exception;

use Kongono\ModelProviderOpenAiResponses\Exception\ExceptionInterface;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesProfileException;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesRequestEncodingException;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventKind;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenAiResponsesExceptionTest extends TestCase
{
    public function testFactoriesPreservePreviousIdentityButNeverPresentRawValues(): void
    {
        $raw = 'secret-like-17b';
        $previous = new RuntimeException($raw);
        $exceptions = [
            InvalidOpenAiResponsesProfileException::invalidProfile($raw, $previous),
            ResponsesRequestEncodingException::unsupportedSurface($raw, -1, $previous),
            ResponsesRequestEncodingException::encodingFailed($raw, $previous),
            ResponsesRequestEncodingException::limitExceeded($raw, -1, $previous),
            ResponsesResponseDecodingException::invalidSyntax($raw, $previous),
            ResponsesResponseDecodingException::invalidStructure($raw, $raw, $raw, $previous),
            ResponsesResponseDecodingException::limitExceeded($raw, -1, -1, $previous),
            ResponsesResponseDecodingException::unsupportedMapping($raw, $raw, -1, $previous),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-OPENAI-RESPONSES-' . $number, range(1701, 1708)), array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions));
        foreach ($exceptions as $exception) {
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
            self::assertStringNotContainsString($raw, $exception->diagnosticTitle());
            self::assertStringNotContainsString($raw, $exception->diagnosticRemediation());
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($raw, (string) $field->value());
            }
        }
    }

    public function testInvocationExceptionFamilyIsCompleteAndNondisclosing(): void
    {
        $raw = 'credential-private-uri-body-header-provider-model-native-17c';
        $previous = new RuntimeException($raw);
        $exceptions = [
            OpenAiResponsesInvocationException::invalidContext($raw, $previous),
            OpenAiResponsesInvocationException::lifecycle($raw, $previous),
            OpenAiResponsesInvocationException::externalService($raw, $previous),
            OpenAiResponsesInvocationException::httpResponse($raw, 599, $previous),
            OpenAiResponsesInvocationException::responseBody($raw, $previous, $previous, 64),
            OpenAiResponsesInvocationException::normalization($raw, $previous),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-OPENAI-RESPONSES-' . $number, range(1709, 1714)), array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions));
        foreach ($exceptions as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
            foreach ([$exception->getMessage(), $exception->diagnosticTitle(), $exception->diagnosticRemediation(), (string) $exception] as $safeText) {
                self::assertStringNotContainsString($raw, $safeText);
            }
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($raw, (string) $field->value());
            }
        }
    }

    public function testStreamingFactoriesUseOnlyClosedSafeEvidence(): void
    {
        $raw = new RuntimeException('private-stream-failure');
        $failures = [
            OpenAiResponsesInvocationException::streamingFraming('body_read', previous: $raw),
            OpenAiResponsesInvocationException::streamingMapping('delta', eventKind: ResponsesSseEventKind::OutputTextDelta, previous: $raw),
            OpenAiResponsesInvocationException::streamingOrder('provider_events', 4096, 4097, previous: $raw),
            OpenAiResponsesInvocationException::streamingCleanup($raw, $raw),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-OPENAI-RESPONSES-' . $number, range(1715, 1718)), array_map(static fn($failure): string => $failure->diagnosticId(), $failures));
        foreach ($failures as $failure) {
            self::assertStringNotContainsString('private-stream-failure', (string) $failure);
        }
    }
}
