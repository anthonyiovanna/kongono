<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Profile;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesProfileException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesProfileTest extends TestCase
{
    public function testExactProfileIsImmutableAndEqualByValue(): void
    {
        $profile = $this->profile(ResponseStoragePreference::Enabled);
        self::assertSame('openai', $profile->neutralProfile()->providerIdentity()->value());
        self::assertSame('openai-test-model', $profile->neutralProfile()->modelIdentity()->value());
        self::assertSame([ProviderCapability::TextResult, ProviderCapability::UsageEvidence], $profile->neutralProfile()->capabilities()->capabilities());
        $detachedCapabilities = $profile->neutralProfile()->capabilities()->capabilities();
        $detachedCapabilities[] = ProviderCapability::CostEvidence;
        self::assertSame([ProviderCapability::TextResult, ProviderCapability::UsageEvidence], $profile->neutralProfile()->capabilities()->capabilities());
        self::assertSame(OpenAiResponsesMappingVersion::Version1, $profile->mappingVersion());
        self::assertTrue($profile->storagePreference()->store());
        self::assertTrue($profile->equals($this->profile(ResponseStoragePreference::Enabled)));
        self::assertFalse($profile->equals($this->profile(ResponseStoragePreference::Disabled)));
        self::assertStringContainsString('mappingVersion', $this->methodSource('equals'));
    }

    /** @param list<ProviderCapability> $capabilities */
    #[DataProvider('invalidProfileProvider')]
    public function testInvalidProfilesFailClosed(string $provider, array $capabilities): void
    {
        $this->expectException(InvalidOpenAiResponsesProfileException::class);
        new OpenAiResponsesProfile(
            new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-test'), new ProviderIdentity($provider), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet($capabilities)),
            OpenAiResponsesMappingVersion::Version1,
            ResponseStoragePreference::Disabled,
        );
    }

    public function testExactStreamingCapabilitySetIsAccepted(): void
    {
        $profile = new OpenAiResponsesProfile(
            $this->neutralProfile([ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence]),
            OpenAiResponsesMappingVersion::Version1,
            ResponseStoragePreference::Disabled,
        );
        self::assertSame([ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence], $profile->neutralProfile()->capabilities()->capabilities());
    }

    public function testVersionTwoAcceptsOnlyMatchingStructuredOrActionDefinitions(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{},"required":[],"additionalProperties":false}');
        $structuredDefinition = new OpenAiResponsesStructuredOutput('result_format', $schema);
        $functionDefinition = new OpenAiResponsesFunctionDefinition(new ActionName('record_result'), $schema);
        $structured = new OpenAiResponsesProfile(
            $this->neutralProfile([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]),
            OpenAiResponsesMappingVersion::Version2,
            ResponseStoragePreference::Disabled,
            $structuredDefinition,
        );
        $action = new OpenAiResponsesProfile(
            $this->neutralProfile([ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence]),
            OpenAiResponsesMappingVersion::Version2,
            ResponseStoragePreference::Disabled,
            functionDefinition: $functionDefinition,
        );

        self::assertSame($structuredDefinition, $structured->structuredOutput());
        self::assertSame($functionDefinition, $action->functionDefinition());
        self::assertTrue($structured->equals(new OpenAiResponsesProfile($structured->neutralProfile(), OpenAiResponsesMappingVersion::Version2, ResponseStoragePreference::Disabled, new OpenAiResponsesStructuredOutput('result_format', new StrictJsonSchema($schema->canonicalJson())))));
        self::assertFalse($structured->equals($action));
        self::assertFalse($structured->equals($this->profile(ResponseStoragePreference::Disabled)));

        foreach ([
            [$this->neutralProfile([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]), null, null],
            [$this->neutralProfile([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]), null, $functionDefinition],
            [$this->neutralProfile([ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence]), null, null],
            [$this->neutralProfile([ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence]), $structuredDefinition, null],
            [$this->neutralProfile([ProviderCapability::TextResult, ProviderCapability::UsageEvidence]), $structuredDefinition, null],
            [$this->neutralProfile([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]), $structuredDefinition, $functionDefinition],
        ] as [$neutral, $structuredOutput, $function]) {
            try {
                new OpenAiResponsesProfile($neutral, OpenAiResponsesMappingVersion::Version2, ResponseStoragePreference::Disabled, $structuredOutput, $function);
                self::fail('A mismatched Version 2 capability/definition relation must fail.');
            } catch (InvalidOpenAiResponsesProfileException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1701', $failure->diagnosticId());
            }
        }
    }

    /** @return iterable<string, array{string, list<ProviderCapability>}> */
    public static function invalidProfileProvider(): iterable
    {
        yield 'provider' => ['other', [ProviderCapability::TextResult, ProviderCapability::UsageEvidence]];
        yield 'missing usage' => ['openai', [ProviderCapability::TextResult]];
        yield 'structured' => ['openai', [ProviderCapability::TextResult, ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]];
        yield 'action suggestion' => ['openai', [ProviderCapability::TextResult, ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence]];
        yield 'streaming plus cost' => ['openai', [ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence, ProviderCapability::CostEvidence]];
        yield 'cost' => ['openai', [ProviderCapability::TextResult, ProviderCapability::UsageEvidence, ProviderCapability::CostEvidence]];
    }

    private function profile(ResponseStoragePreference $storage): OpenAiResponsesProfile
    {
        return new OpenAiResponsesProfile(
            new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-test'), new ProviderIdentity('openai'), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet([ProviderCapability::TextResult, ProviderCapability::UsageEvidence])),
            OpenAiResponsesMappingVersion::Version1,
            $storage,
        );
    }

    /** @param list<ProviderCapability> $capabilities */
    private function neutralProfile(array $capabilities): ModelProfileReference
    {
        return new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-stream-test'), new ProviderIdentity('openai'), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet($capabilities));
    }

    private function methodSource(string $method): string
    {
        $reflection = new \ReflectionMethod(OpenAiResponsesProfile::class, $method);
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
