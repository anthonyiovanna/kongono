<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Response;

use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Response\DecodedResponsesResponse;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseIncompleteReason;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseStatus;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseUsage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecodedResponsesResponseTest extends TestCase
{
    public function testDecodedEvidenceIsImmutableAndEqualByValue(): void
    {
        $response = new DecodedResponsesResponse(OpenAiResponsesMappingVersion::Version1, 'resp_test-1', 'openai-test-model', ResponseStatus::Incomplete, ResponseIncompleteReason::ContentFilter, ResultMode::Text, new ResultText('private-output-17b'), null, new ResponseUsage(4, 3, 7));
        self::assertSame('resp_test-1', $response->responseId());
        self::assertSame('openai-test-model', $response->modelClaim());
        self::assertSame(ResponseStatus::Incomplete, $response->status());
        self::assertSame(ResponseIncompleteReason::ContentFilter, $response->incompleteReason());
        self::assertSame('private-output-17b', $response->text()->value());
        self::assertSame(7, $response->usage()?->totalTokens());
        self::assertTrue($response->equals(new DecodedResponsesResponse(OpenAiResponsesMappingVersion::Version1, 'resp_test-1', 'openai-test-model', ResponseStatus::Incomplete, ResponseIncompleteReason::ContentFilter, ResultMode::Text, new ResultText('private-output-17b'), null, new ResponseUsage(4, 3, 7))));
        self::assertStringContainsString('mappingVersion', $this->methodSource('equals'));
    }

    public function testStatusAndUsageRelationsFailClosed(): void
    {
        $this->expectException(ResponsesResponseDecodingException::class);
        new DecodedResponsesResponse(OpenAiResponsesMappingVersion::Version1, 'resp_test', 'openai-test-model', ResponseStatus::Completed, ResponseIncompleteReason::ContentFilter, ResultMode::Text, new ResultText('text'), null, null);
    }

    public function testDirectModelClaimAcceptsTheExactMaximumGrammarBoundary(): void
    {
        $modelClaim = 'a' . str_repeat('b', 127);
        $response = new DecodedResponsesResponse(OpenAiResponsesMappingVersion::Version1, 'resp_test', $modelClaim, ResponseStatus::Completed, null, ResultMode::Text, new ResultText('text'), null, null);
        self::assertSame(128, strlen($response->modelClaim()));
        self::assertSame($modelClaim, $response->modelClaim());
    }

    #[DataProvider('invalidModelClaimProvider')]
    public function testDirectModelClaimInvariantIsBoundedAndNondisclosing(string $modelClaim): void
    {
        try {
            new DecodedResponsesResponse(OpenAiResponsesMappingVersion::Version1, 'resp_test', $modelClaim, ResponseStatus::Completed, null, ResultMode::Text, new ResultText('text'), null, null);
            self::fail('An invalid direct model claim must fail.');
        } catch (ResponsesResponseDecodingException $exception) {
            self::assertSame('model_claim', $exception->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString($modelClaim, (string) $exception);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidModelClaimProvider(): iterable
    {
        yield 'invalid grammar' => ['Secret-Like-Model'];
        yield 'oversized' => ['a' . str_repeat('b', 128)];
    }

    private function methodSource(string $method): string
    {
        $reflection = new \ReflectionMethod(DecodedResponsesResponse::class, $method);
        $fileName = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        if (false === $fileName || false === $startLine || false === $endLine) {
            self::fail('The reflected source method must have concrete file lines.');
        }
        $lines = file($fileName, FILE_IGNORE_NEW_LINES);
        if (false === $lines) {
            self::fail('The reflected source file must be readable.');
        }

        return implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }
}
