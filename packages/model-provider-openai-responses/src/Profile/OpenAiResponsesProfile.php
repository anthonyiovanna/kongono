<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesProfileException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;

/** Binds one exact neutral profile to one closed provider mapping version. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesProfile
{
    public function __construct(
        private ModelProfileReference $neutralProfile,
        private OpenAiResponsesMappingVersion $mappingVersion,
        private ResponseStoragePreference $storagePreference,
        private ?OpenAiResponsesStructuredOutput $structuredOutput = null,
        private ?OpenAiResponsesFunctionDefinition $functionDefinition = null,
    ) {
        if ('openai' !== $neutralProfile->providerIdentity()->value()) {
            throw InvalidOpenAiResponsesProfileException::invalidProfile('provider_identity', mappingVersion: $mappingVersion);
        }
        $capabilities = $neutralProfile->capabilities()->capabilities();
        $valid = match ($mappingVersion) {
            OpenAiResponsesMappingVersion::Version1 => in_array($capabilities, [
                [ProviderCapability::TextResult, ProviderCapability::UsageEvidence],
                [ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence],
            ], true) && null === $structuredOutput && null === $functionDefinition,
            OpenAiResponsesMappingVersion::Version2 => ([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence] === $capabilities
                    && null !== $structuredOutput && null === $functionDefinition)
                || ([ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence] === $capabilities
                    && null === $structuredOutput && null !== $functionDefinition),
        };
        if (!$valid) {
            throw InvalidOpenAiResponsesProfileException::invalidProfile('capability_set', mappingVersion: $mappingVersion);
        }
    }

    public function neutralProfile(): ModelProfileReference
    {
        return $this->neutralProfile;
    }

    public function mappingVersion(): OpenAiResponsesMappingVersion
    {
        return $this->mappingVersion;
    }

    public function storagePreference(): ResponseStoragePreference
    {
        return $this->storagePreference;
    }

    public function structuredOutput(): ?OpenAiResponsesStructuredOutput
    {
        return $this->structuredOutput;
    }

    public function functionDefinition(): ?OpenAiResponsesFunctionDefinition
    {
        return $this->functionDefinition;
    }

    public function equals(self $other): bool
    {
        return match ($this->mappingVersion) {
            OpenAiResponsesMappingVersion::Version1 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => $this->storagePreference === $other->storagePreference
                    && $this->neutralProfile->equals($other->neutralProfile)
                    && null === $other->structuredOutput
                    && null === $other->functionDefinition,
                OpenAiResponsesMappingVersion::Version2 => false,
            },
            OpenAiResponsesMappingVersion::Version2 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => false,
                OpenAiResponsesMappingVersion::Version2 => $this->storagePreference === $other->storagePreference
                    && $this->neutralProfile->equals($other->neutralProfile)
                    && (null === $this->structuredOutput
                        ? null === $other->structuredOutput
                        : null !== $other->structuredOutput && $this->structuredOutput->equals($other->structuredOutput))
                    && (null === $this->functionDefinition
                        ? null === $other->functionDefinition
                        : null !== $other->functionDefinition && $this->functionDefinition->equals($other->functionDefinition)),
            },
        };
    }
}
