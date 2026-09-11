<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Execution;

use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesInvocationContextTest extends TestCase
{
    public function testContextExposesExactlyItsExplicitOperationInputs(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $context = $fixture->context;
        self::assertTrue($context->expectedPlanIdentity()->equals($fixture->source->identity()));
        self::assertSame($fixture, $context->applicationFactory());
        self::assertSame('operation.openai.responses', $context->operationIdentity()->value());
        self::assertSame('invoke.openai.responses', $context->credentialPurpose()->value());
        self::assertSame(5000, $context->credentialMaximumLifetimeMilliseconds());
        self::assertSame('receipt.openai.responses', $context->receiptIdentity()->value());
        self::assertSame('attempt.openai.responses', $context->attemptIdentity()->value());
        self::assertSame('authority.openai.responses', $context->authorityReference()->identity());
        self::assertCount(12, (new \ReflectionClass($context))->getProperties());
    }

    public function testCredentialLifetimeMustRemainInsideClosedBound(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $context = $fixture->context;
        $this->expectException(OpenAiResponsesInvocationException::class);
        new \Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext(
            $context->expectedPlanIdentity(),
            $context->applicationFactory(),
            $context->operationIdentity(),
            $context->credentialPurpose(),
            300001,
            $context->receiptIdentity(),
            $context->attemptIdentity(),
            $context->authorityReference(),
            $context->authorityCurrentness(),
            $context->cancellation(),
            $context->credentialClock(),
            $context->egressClock(),
        );
    }
}
