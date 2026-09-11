<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Profile;

use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileIdentity;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileIdentity;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesApplicationProfileException;
use Kongono\ModelProviderOpenAiResponses\Internal\CanonicalApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesApplicationFixture;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesApplicationProfileTest extends TestCase
{
    public function testIdentityIsDeterministicVersionedAndNondisclosing(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $factory = new OpenAiResponsesApplicationProfileFactory();
            $again = $factory->create($fixture->adapterFixture->openAiProfile, $fixture->modelProfile, $fixture->adapterFixture->profile());
            self::assertTrue($fixture->profile->identity()->equals($again->identity()));
            $bytes = CanonicalApplicationProfile::bytes($fixture->adapterFixture->openAiProfile, $fixture->modelProfile, $fixture->adapterFixture->profile());
            self::assertStringStartsWith("contract=kongono-openai-responses-application-profile/1\n", $bytes);
            self::assertStringEndsWith("\n", $bytes);
            foreach (['bounded system input', 'bounded private input', 'openai-test-model', 'api.openai.invalid', 'reference.openai.responses', 'fixture-credential'] as $private) {
                self::assertStringNotContainsString($private, $bytes);
            }
        } finally {
            $fixture->cleanup();
        }
    }

    public function testIdentityChangesForStorageModeSchemaAndComponentEvidence(): void
    {
        $text = new OpenAiResponsesApplicationFixture(build: false);
        $structured = new OpenAiResponsesApplicationFixture(\Kongono\ModelInvocationContract\Invocation\ResultMode::Structured, build: false);
        try {
            $factory = new OpenAiResponsesApplicationProfileFactory();
            $enabled = new OpenAiResponsesProfile(
                $text->adapterFixture->openAiProfile->neutralProfile(),
                $text->adapterFixture->openAiProfile->mappingVersion(),
                ResponseStoragePreference::Enabled,
            );
            $enabledProfile = $factory->create($enabled, $text->modelProfile, $text->adapterFixture->profile());
            self::assertNotSame($text->profile->identity()->value(), $enabledProfile->identity()->value());
            self::assertNotSame($text->profile->identity()->value(), $structured->profile->identity()->value());

            $changedSchema = new StrictJsonSchema('{"additionalProperties":false,"properties":{"answer":{"type":"integer"}},"required":["answer"],"type":"object"}');
            $changedProvider = new OpenAiResponsesProfile(
                $structured->adapterFixture->openAiProfile->neutralProfile(),
                $structured->adapterFixture->openAiProfile->mappingVersion(),
                $structured->adapterFixture->openAiProfile->storagePreference(),
                new OpenAiResponsesStructuredOutput('answer_format', $changedSchema),
            );
            self::assertNotSame(
                $structured->profile->identity()->value(),
                $factory->create($changedProvider, $structured->modelProfile, $structured->adapterFixture->profile())->identity()->value(),
            );
            self::assertStringNotContainsString($changedSchema->canonicalJson(), CanonicalApplicationProfile::bytes($changedProvider, $structured->modelProfile, $structured->adapterFixture->profile()));
        } finally {
            $text->cleanup();
            $structured->cleanup();
        }
    }

    public function testStaleComponentProfilesAreRejected(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $staleModel = new ModelInvocationApplicationProfile(
                $fixture->modelProfile->source(),
                $fixture->modelProfile->artifact(),
                new ModelInvocationApplicationProfileIdentity(str_repeat('a', 64)),
            );
            $this->assertInvalid($fixture->adapterFixture->openAiProfile, $staleModel, $fixture->adapterFixture->profile());

            $external = $fixture->adapterFixture->profile();
            $staleExternal = new ExternalServiceApplicationProfile(
                $external->bindingSource(),
                $external->bindingArtifact(),
                $external->binding(),
                $external->networkEgressProfile(),
                new ExternalServiceApplicationProfileIdentity(str_repeat('b', 64)),
            );
            $this->assertInvalid($fixture->adapterFixture->openAiProfile, $fixture->modelProfile, $staleExternal);
        } finally {
            $fixture->cleanup();
        }
    }

    public function testCrossProfileAndEndpointMismatchesFailClosed(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $source = $fixture->source;
            $wrongAdapterSource = new InvocationPlanSource(
                $source->identity(),
                new AdapterIdentity('adapter.wrong'),
                $source->request(),
                $source->budget(),
            );
            $wrongBytes = (new InvocationPlanArtifactBuilder())->build($wrongAdapterSource)->canonicalBytes();
            $wrongModel = (new ModelInvocationApplicationProfileFactory())->create($wrongAdapterSource, $wrongBytes);
            $this->assertInvalid($fixture->adapterFixture->openAiProfile, $wrongModel, $fixture->adapterFixture->profile());

            $neutral = $source->request()->profile();
            $differentNeutral = new ModelProfileReference(
                $neutral->version(),
                new ProfileIdentity('profile.openai.responses.different'),
                $neutral->providerIdentity(),
                $neutral->modelIdentity(),
                $neutral->capabilities(),
            );
            $wrongProvider = new OpenAiResponsesProfile(
                $differentNeutral,
                $fixture->adapterFixture->openAiProfile->mappingVersion(),
                ResponseStoragePreference::Disabled,
            );
            $this->assertInvalid($wrongProvider, $fixture->modelProfile, $fixture->adapterFixture->profile());

            foreach ([
                new OpenAiResponsesAdapterFixture(endpointScheme: OriginScheme::Http),
                new OpenAiResponsesAdapterFixture(endpointBasePath: '/private'),
            ] as $endpointFixture) {
                $profile = (new ModelInvocationApplicationProfileFactory())->create($endpointFixture->source, $endpointFixture->artifactBytes);
                $this->assertInvalid($endpointFixture->openAiProfile, $profile, $endpointFixture->profile());
            }
        } finally {
            $fixture->cleanup();
        }
    }

    private function assertInvalid(OpenAiResponsesProfile $provider, ModelInvocationApplicationProfile $model, ExternalServiceApplicationProfile $external): void
    {
        try {
            (new OpenAiResponsesApplicationProfileFactory())->create($provider, $model, $external);
            self::fail('Mismatched exact-current evidence must fail.');
        } catch (InvalidOpenAiResponsesApplicationProfileException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1720', $failure->diagnosticId());
        }
    }
}
