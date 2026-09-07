<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Security;

use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Exception\InvocationPlanPublicationException;
use Kongono\ModelInvocationBuild\Tests\Support\InvocationPlanSourceFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelInvocationBuildDisclosureTest extends TestCase
{
    public function testRawMessageAndProtectedLabelsNeverEnterArtifactOrFailures(): void
    {
        $secret = 'literal-sensitive-message';
        $artifact = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create($secret));
        self::assertStringNotContainsString($secret, $artifact->canonicalBytes());
        foreach ([$secret, 'provider.primary', 'model.primary', 'adapter.primary', $artifact->artifactSha256(), 'USD', '1.25'] as $protected) {
            try {
                (new InvocationPlanArtifactValidator())->validateStructure($artifact->canonicalBytes() . $protected);
                self::fail('Expected invalid artifact.');
            } catch (InvalidInvocationPlanArtifactException $failure) {
                self::assertStringNotContainsString($protected, $failure->getMessage());
                self::assertStringNotContainsString($protected, (string) $failure);
                self::assertStringNotContainsString($protected, $failure->diagnosticRemediation());
            }
        }
    }

    public function testUnknownLabelsAndThrowableMessagesAreNotDisclosed(): void
    {
        $failure = InvocationPlanPublicationException::failed('secret-path-value', new RuntimeException('secret-throwable-message'));
        self::assertStringContainsString('unsupported_violation', (string) $failure);
        self::assertStringNotContainsString('secret-path-value', (string) $failure);
        self::assertStringNotContainsString('secret-throwable-message', (string) $failure);
        self::assertSame('secret-throwable-message', $failure->getPrevious()?->getMessage());
    }
}
