<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesResultNormalizer;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesResultNormalizerTest extends TestCase
{
    public function testCompletedUsageIdentityAndMostRestrictiveVisibilityAreExact(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $decoded = (new ResponsesResponseDecoder())->decode($fixture->source->request(), $fixture->openAiProfile, OpenAiResponsesAdapterFixture::completedBody());
        $result = (new OpenAiResponsesResultNormalizer())->normalize($fixture->source->request(), $decoded);
        self::assertSame(FinishReason::Completed, $result->finishReason());
        self::assertSame('synthetic output', $result->text()?->value());
        self::assertNull($result->actionSuggestion());
        self::assertNull($result->cost());
        self::assertSame(ModelVisibility::Private, $result->visibility());
        self::assertSame(['input_tokens', 'output_tokens', 'total_tokens'], array_map(static fn($quantity): string => $quantity->metric()->value(), $result->usage()?->quantities() ?? []));
        self::assertSame([7, 5, 12], array_map(static fn($quantity): int => $quantity->quantity(), $result->usage()?->quantities() ?? []));
        $expected = 'openai.responses.' . hash('sha256', 'kongono-openai-responses/1' . "\0" . 'resp_synthetic_completed');
        self::assertSame($expected, $result->identity()->value());
        self::assertStringNotContainsString('resp_synthetic_completed', $result->identity()->value());
    }

    /** @return iterable<string, array{string, FinishReason}> */
    public static function incompleteReasons(): iterable
    {
        yield 'length' => ['max_tokens', FinishReason::Length];
        yield 'filter' => ['content_filter', FinishReason::ContentFiltered];
    }

    #[DataProvider('incompleteReasons')]
    public function testBothIncompleteReasonsAndNullUsageNormalize(string $reason, FinishReason $expected): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream(OpenAiResponsesAdapterFixture::incompleteBody($reason))));
        $decoded = (new ResponsesResponseDecoder())->decode($fixture->source->request(), $fixture->openAiProfile, OpenAiResponsesAdapterFixture::incompleteBody($reason));
        $result = (new OpenAiResponsesResultNormalizer())->normalize($fixture->source->request(), $decoded);
        self::assertSame($expected, $result->finishReason());
        self::assertNull($result->usage());
    }
}
