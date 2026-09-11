<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesRequestFactory;
use Kongono\ModelProviderOpenAiResponses\Request\ResponsesRequestEncoder;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesRequestFactoryTest extends TestCase
{
    public function testFactoryBuildsTheExactOneShotHttpsRequest(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $encoded = (new ResponsesRequestEncoder())->encode($fixture->source->request(), $fixture->openAiProfile);
        $factory = new OpenAiResponsesRequestFactory($encoded, $fixture->context->operationIdentity());
        $request = $factory->create($fixture->profile()->binding(), $fixture->context->operationIdentity(), 'borrowed-fixture-token');
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.openai.invalid/v1/responses', (string) $request->getUri());
        self::assertSame('/v1/responses', $request->getRequestTarget());
        self::assertSame(['application/json'], $request->getHeader('Accept'));
        self::assertSame(['application/json'], $request->getHeader('Content-Type'));
        self::assertSame(['Bearer borrowed-fixture-token'], $request->getHeader('Authorization'));
        self::assertSame(['api.openai.invalid'], $request->getHeader('Host'));
        self::assertCount(4, $request->getHeaders());
        self::assertSame($encoded->body(), $request->getBody()->getContents());
        $request->getBody()->close();

        $this->expectException(OpenAiResponsesInvocationException::class);
        $factory->create($fixture->profile()->binding(), $fixture->context->operationIdentity(), 'second-token');
    }

    public function testCredentialValidationIsBoundedAndNondisclosing(): void
    {
        foreach (['', "bad\rvalue", "bad\nvalue", "bad\0value", str_repeat('a', 16385)] as $credential) {
            $fixture = new OpenAiResponsesAdapterFixture();
            $encoded = (new ResponsesRequestEncoder())->encode($fixture->source->request(), $fixture->openAiProfile);
            try {
                (new OpenAiResponsesRequestFactory($encoded, $fixture->context->operationIdentity()))
                    ->create($fixture->profile()->binding(), $fixture->context->operationIdentity(), $credential);
                self::fail('Unsafe credential material must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1709', $failure->diagnosticId());
                if ('' !== $credential) {
                    self::assertStringNotContainsString($credential, (string) $failure);
                }
            }
        }
    }

    public function testStreamingRequestUsesExactSseAcceptWithoutChangingContentType(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $encoded = (new ResponsesRequestEncoder())->encode($fixture->source->request(), $fixture->openAiProfile);
        $request = (new OpenAiResponsesRequestFactory($encoded, $fixture->context->operationIdentity()))
            ->create($fixture->profile()->binding(), $fixture->context->operationIdentity(), 'borrowed-stream-token');
        self::assertSame(['text/event-stream'], $request->getHeader('Accept'));
        self::assertSame(['application/json'], $request->getHeader('Content-Type'));
        $request->getBody()->close();
    }
}
