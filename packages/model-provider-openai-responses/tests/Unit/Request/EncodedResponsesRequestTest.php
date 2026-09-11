<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Request;

use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesRequestEncodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Request\EncodedResponsesRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EncodedResponsesRequestTest extends TestCase
{
    public function testEvidenceIsExactAndDeterministic(): void
    {
        $body = '{"input":[],"model":"openai-test-model","store":false,"stream":false}';
        $encoded = new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, $body);
        self::assertSame('/v1/responses', $encoded->relativePath());
        self::assertSame('application/json', $encoded->mediaType());
        self::assertSame($body, $encoded->body());
        self::assertSame(strlen($body), $encoded->bodyByteCount());
        self::assertSame(strtoupper(hash('sha256', $body)), $encoded->bodySha256());
        self::assertTrue($encoded->equals(new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, $body)));
        self::assertFalse($encoded->streaming());
        self::assertStringContainsString('mappingVersion', $this->methodSource('equals'));
    }

    public function testVersionTwoPreservesExactCanonicalEvidenceAndVersionedEquality(): void
    {
        $body = '{"input":[],"model":"openai-test-model","store":false,"stream":false,"text":{"format":{"name":"answer_format","schema":{"additionalProperties":false,"properties":{},"required":[],"type":"object"},"strict":true,"type":"json_schema"}}}';
        $encoded = new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version2, $body);

        self::assertSame($body, $encoded->body());
        self::assertSame(strlen($body), $encoded->bodyByteCount());
        self::assertSame(strtoupper(hash('sha256', $body)), $encoded->bodySha256());
        self::assertFalse($encoded->streaming());
        self::assertTrue($encoded->equals(new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version2, $body)));
        self::assertFalse($encoded->equals(new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, '{"input":[],"model":"openai-test-model","store":false,"stream":false}')));
    }

    public function testExactBodyByteBoundaryIsAcceptedAndExceededBoundaryIsNondisclosing(): void
    {
        $prefix = '{"input":"';
        $suffix = '","model":"openai-test-model","store":false,"stream":false}';
        $secret = 'secret-like-direct-encoded';
        $paddingBytes = EncodedResponsesRequest::MAXIMUM_BODY_BYTES - strlen($prefix) - strlen($suffix);
        $body = $prefix . $secret . str_repeat('a', $paddingBytes - strlen($secret)) . $suffix;
        self::assertSame(EncodedResponsesRequest::MAXIMUM_BODY_BYTES, strlen($body));
        self::assertSame($body, (new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, $body))->body());

        try {
            self::assertSame(EncodedResponsesRequest::MAXIMUM_BODY_BYTES + 1, strlen($body . 'a'));
            new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, $body . 'a');
            self::fail('An oversized direct body must fail.');
        } catch (ResponsesRequestEncodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1704', $exception->diagnosticId());
            self::assertStringNotContainsString($secret, (string) $exception);
        }
    }

    #[DataProvider('invalidBodyProvider')]
    public function testDirectConstructionRejectsEveryNonlimitInvariant(string $body): void
    {
        try {
            new EncodedResponsesRequest(OpenAiResponsesMappingVersion::Version1, $body);
            self::fail('Invalid direct encoded evidence must fail.');
        } catch (ResponsesRequestEncodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1703', $exception->diagnosticId());
            self::assertSame('json_encoding', $exception->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString('secret-like-direct-encoded', (string) $exception);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBodyProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'invalid utf8' => ["{\"input\":\"\xFF\",\"model\":\"m\",\"store\":false,\"stream\":false}"];
        yield 'malformed json' => ['{"input":'];
        yield 'top-level list' => ['[]'];
        yield 'duplicate member' => ['{"input":[],"model":"m","model":"m","store":false,"stream":false}'];
        yield 'noncompact bytes' => ['{ "input":[],"model":"m","store":false,"stream":false}'];
        yield 'noncanonical nested order' => ['{"input":[{"b":1,"a":2}],"model":"m","store":false,"stream":false}'];
        yield 'wrong top-level shape' => ['{"input":[],"model":"m","store":false}'];
        yield 'nonboolean stream' => ['{"input":[],"model":"m","store":false,"stream":0}'];
    }

    private function methodSource(string $method): string
    {
        $reflection = new \ReflectionMethod(EncodedResponsesRequest::class, $method);
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
