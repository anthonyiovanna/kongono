<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Integration;

use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesOneShotAdapterTest extends TestCase
{
    public function testRealNeutralRuntimeComposesOneDeterministicLocalOperation(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $adapter = new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context);
        $request = new InvocationRuntimeRequest(
            $fixture->source,
            $fixture->artifactBytes,
            new ReceiptIdentity('receipt.model.openai.responses'),
            $adapter,
            new class implements InvocationCancellation {
                public function isCancellationRequested(): bool
                {
                    return false;
                }
            },
            new class implements InvocationMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );
        $result = (new ModelInvocationRuntime())->run($request);
        self::assertSame('succeeded', $result->receipt()->outcome()->value);
        self::assertSame('synthetic output', $result->result()?->text()?->value());
        self::assertSame([], $result->events());
        self::assertFalse($fixture->source->request()->streaming());
        self::assertSame(1, $fixture->createCalls);
        self::assertSame(1, $fixture->lease?->materialCalls);
        self::assertSame(1, $fixture->resolver?->calls);
        self::assertSame(1, $fixture->transport?->openCalls);
    }
}
