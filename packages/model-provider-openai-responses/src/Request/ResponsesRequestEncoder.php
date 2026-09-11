<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Request;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesRequestEncodingException;
use Kongono\ModelProviderOpenAiResponses\Internal\CanonicalJson;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;

/** Deterministically encodes one supported versioned request without I/O. */
#[Api(ApiStatus::Internal)]
final class ResponsesRequestEncoder
{
    public const int MAXIMUM_BODY_BYTES = EncodedResponsesRequest::MAXIMUM_BODY_BYTES;

    public function encode(InvocationRequest $request, OpenAiResponsesProfile $profile): EncodedResponsesRequest
    {
        if (!$this->supports($request, $profile)) {
            throw ResponsesRequestEncodingException::unsupportedSurface('result_mode', mappingVersion: $profile->mappingVersion());
        }
        if (!$request->profile()->equals($profile->neutralProfile())) {
            throw ResponsesRequestEncodingException::unsupportedSurface('profile_mismatch', mappingVersion: $profile->mappingVersion());
        }

        $input = [];
        foreach ($request->messages() as $position => $message) {
            if (InputRole::Tool === $message->role()) {
                throw ResponsesRequestEncodingException::unsupportedSurface('input_role', $position, mappingVersion: $profile->mappingVersion());
            }
            $input[] = [
                'content' => [[
                    'text' => $message->content(),
                    'type' => 'input_text',
                ]],
                'role' => $message->role()->value,
                'type' => 'message',
            ];
        }

        try {
            $mapping = [
                'input' => $input,
                'model' => $profile->neutralProfile()->modelIdentity()->value(),
            ];
            if (ResultMode::ActionSuggestion === $request->resultMode()) {
                $definition = $profile->functionDefinition();
                if (null === $definition) {
                    throw ResponsesRequestEncodingException::unsupportedSurface('result_mode', mappingVersion: $profile->mappingVersion());
                }
                $mapping['parallel_tool_calls'] = false;
            }
            $mapping['store'] = $profile->storagePreference()->store();
            $mapping['stream'] = $request->streaming();
            if (ResultMode::Structured === $request->resultMode()) {
                $definition = $profile->structuredOutput();
                if (null === $definition) {
                    throw ResponsesRequestEncodingException::unsupportedSurface('result_mode', mappingVersion: $profile->mappingVersion());
                }
                $mapping['text'] = ['format' => [
                    'name' => $definition->name(),
                    'schema' => $definition->schema()->providerValue(),
                    'strict' => true,
                    'type' => 'json_schema',
                ]];
            } elseif (ResultMode::ActionSuggestion === $request->resultMode()) {
                $mapping['tool_choice'] = 'required';
                $mapping['tools'] = [[
                    'name' => $definition->name()->value(),
                    'parameters' => $definition->parameters()->providerValue(),
                    'strict' => true,
                    'type' => 'function',
                ]];
            }
            $body = CanonicalJson::encode($mapping);
        } catch (JsonException) {
            throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $profile->mappingVersion());
        }

        if (strlen($body) > self::MAXIMUM_BODY_BYTES) {
            throw ResponsesRequestEncodingException::limitExceeded('request_body_bytes', self::MAXIMUM_BODY_BYTES, mappingVersion: $profile->mappingVersion());
        }

        return new EncodedResponsesRequest($profile->mappingVersion(), $body);
    }

    private function supports(InvocationRequest $request, OpenAiResponsesProfile $profile): bool
    {
        return match ($profile->mappingVersion()) {
            \Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion::Version1 => ResultMode::Text === $request->resultMode(),
            \Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion::Version2 => !$request->streaming()
                && in_array($request->resultMode(), [ResultMode::Structured, ResultMode::ActionSuggestion], true),
        };
    }
}
