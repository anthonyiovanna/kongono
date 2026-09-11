<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Unit\Inspection;

use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\OpenAiResponsesInspectionBindingException;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\OpenAiResponsesRuntimeEvidenceException;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspectionRequest;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspector;
use Kongono\ModelProviderOpenAiResponsesInspection\Tests\Support\OpenAiResponsesInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesInspectorTest extends TestCase
{
    public function testExactProfileProducesDeterministicCanonicalEvidenceWithoutRuntime(): void
    {
        $profile = OpenAiResponsesInspectionFactory::profile();
        $inspector = new OpenAiResponsesInspector();
        $first = $inspector->inspect(new OpenAiResponsesInspectionRequest($profile));
        $second = $inspector->inspect(new OpenAiResponsesInspectionRequest($profile));

        self::assertSame($first->identity(), $second->identity());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(OpenAiResponsesInspection::SCHEMA, $first->toArray()['schema']);
        self::assertFalse($first->runtimeEvidenceSupplied());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
    }

    /** @return iterable<string, array{InvocationOutcome}> */
    public static function outcomes(): iterable
    {
        yield 'succeeded' => [InvocationOutcome::Succeeded];
        yield 'failed' => [InvocationOutcome::Failed];
        yield 'cancelled' => [InvocationOutcome::Cancelled];
        yield 'rejected' => [InvocationOutcome::Rejected];
    }

    #[DataProvider('outcomes')]
    public function testEveryTerminalOutcomeRemainsHonestBoundedEvidence(InvocationOutcome $outcome): void
    {
        $fixture = OpenAiResponsesInspectionFactory::fixture();
        $profile = OpenAiResponsesInspectionFactory::profile($fixture);
        $inspection = (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest(
            $profile,
            OpenAiResponsesInspectionFactory::runtime($fixture, $outcome),
        ));

        self::assertSame($outcome->value, $inspection->tokens()['outcome']);
        self::assertSame(InvocationOutcome::Succeeded === $outcome, $inspection->resultPresent());
        self::assertSame(InvocationOutcome::Succeeded === $outcome, $inspection->usagePresent());
        self::assertTrue($inspection->cleanupComplete());
    }

    public function testMixedProfileComponentsFailClosed(): void
    {
        $first = OpenAiResponsesInspectionFactory::profile(OpenAiResponsesInspectionFactory::fixture('first'));
        $second = OpenAiResponsesInspectionFactory::profile(OpenAiResponsesInspectionFactory::fixture('second'));
        $mixed = new OpenAiResponsesApplicationProfile(
            $first->providerProfile(),
            $second->modelInvocationProfile(),
            $first->externalServiceProfile(),
            $first->identity(),
        );

        $this->expectException(OpenAiResponsesInspectionBindingException::class);
        (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest($mixed));
    }

    public function testForeignRuntimeFailsClosedWithoutDisclosingItsIdentity(): void
    {
        $profile = OpenAiResponsesInspectionFactory::profile(OpenAiResponsesInspectionFactory::fixture('first'));
        $foreign = OpenAiResponsesInspectionFactory::fixture('foreign-secret');
        try {
            (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest(
                $profile,
                OpenAiResponsesInspectionFactory::runtime($foreign, InvocationOutcome::Failed),
            ));
            self::fail('Foreign runtime evidence must fail.');
        } catch (OpenAiResponsesRuntimeEvidenceException $failure) {
            self::assertStringNotContainsString('foreign-secret', (string) $failure);
            self::assertSame('runtime_binding', $failure->diagnosticContext()->fields()[0]->value());
        }
    }
}
