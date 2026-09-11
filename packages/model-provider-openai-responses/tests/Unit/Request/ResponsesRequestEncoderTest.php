<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Request;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesRequestEncodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Request\EncodedResponsesRequest;
use Kongono\ModelProviderOpenAiResponses\Request\ResponsesRequestEncoder;
use PHPUnit\Framework\TestCase;

final class ResponsesRequestEncoderTest extends TestCase
{
    public function testSingleAndOrderedMultiRoleBodiesAreExactAndRepeatable(): void
    {
        $profile = $this->profile();
        $encoder = new ResponsesRequestEncoder();
        $single = $encoder->encode($this->request($profile, [new InputMessage(InputRole::User, ModelVisibility::Private, 'private-input-17b')]), $profile);
        self::assertSame('{"input":[{"content":[{"text":"private-input-17b","type":"input_text"}],"role":"user","type":"message"}],"model":"openai-test-model","store":false,"stream":false}', $single->body());
        self::assertSame($single->bodyByteCount(), strlen($single->body()));
        self::assertSame($single->bodySha256(), strtoupper(hash('sha256', $single->body())));
        self::assertSame($single->body(), $encoder->encode($this->request($profile, [new InputMessage(InputRole::User, ModelVisibility::Private, 'private-input-17b')]), $profile)->body());
        self::assertSame($single->body(), (new EncodedResponsesRequest($single->mappingVersion(), $single->body()))->body());
        self::assertSame(EncodedResponsesRequest::MAXIMUM_BODY_BYTES, ResponsesRequestEncoder::MAXIMUM_BODY_BYTES);

        $multi = $encoder->encode($this->request($profile, [
            new InputMessage(InputRole::System, ModelVisibility::Application, 'system input'),
            new InputMessage(InputRole::User, ModelVisibility::Private, 'user input'),
            new InputMessage(InputRole::Assistant, ModelVisibility::Protected, 'assistant input'),
        ]), $profile)->body();
        self::assertSame('{"input":[{"content":[{"text":"system input","type":"input_text"}],"role":"system","type":"message"},{"content":[{"text":"user input","type":"input_text"}],"role":"user","type":"message"},{"content":[{"text":"assistant input","type":"input_text"}],"role":"assistant","type":"message"}],"model":"openai-test-model","store":false,"stream":false}', $multi);
        foreach (['max_output_tokens', 'instructions', 'metadata', 'tools', 'include', 'temperature', 'reasoning', 'truncation', 'previous_response', 'conversation', 'user', 'service_tier', 'prompt_cache_key', 'safety_identifier'] as $forbidden) {
            self::assertStringNotContainsString('"' . $forbidden . '":', $multi);
        }
        $stored = $encoder->encode($this->request($this->profile(storage: ResponseStoragePreference::Enabled), [new InputMessage(InputRole::User, ModelVisibility::Private, 'stored preference')]), $this->profile(storage: ResponseStoragePreference::Enabled));
        self::assertStringContainsString('"store":true,"stream":false', $stored->body());
    }

    public function testToolRoleAndMismatchedProfileFailWithoutDisclosureOrStateLeak(): void
    {
        $profile = $this->profile();
        $encoder = new ResponsesRequestEncoder();
        try {
            $encoder->encode($this->request($profile, [new InputMessage(InputRole::Tool, ModelVisibility::Private, 'secret-like-17b')]), $profile);
            self::fail('Tool input must fail.');
        } catch (ResponsesRequestEncodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1702', $exception->diagnosticId());
            self::assertStringNotContainsString('secret-like-17b', (string) $exception);
        }
        $other = $this->profile('other-test-model');
        $this->expectException(ResponsesRequestEncodingException::class);
        $encoder->encode($this->request($other, [new InputMessage(InputRole::User, ModelVisibility::Private, 'safe input')]), $profile);
    }

    public function testUnsupportedModeStreamingAndExactRequestBodyBoundaryFailClosed(): void
    {
        $profile = $this->profile();
        $encoder = new ResponsesRequestEncoder();
        $message = new InputMessage(InputRole::User, ModelVisibility::Private, 'bounded input');

        foreach ([
            $this->neutralProfile([ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence]),
            $this->neutralProfile([ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence]),
        ] as $neutralProfile) {
            $mode = $neutralProfile->capabilities()->supports(ProviderCapability::StructuredResult) ? ResultMode::Structured : ResultMode::ActionSuggestion;
            try {
                $encoder->encode($this->requestForNeutralProfile($neutralProfile, [$message], $mode, false), $profile);
                self::fail('Unsupported result mode must fail.');
            } catch (ResponsesRequestEncodingException $exception) {
                self::assertSame('KGN-OPENAI-RESPONSES-1702', $exception->diagnosticId());
                self::assertSame('result_mode', $exception->diagnosticContext()->fields()[0]->value());
            }
        }

        $streamingProfile = $this->neutralProfile([ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence]);
        $openAiStreamingProfile = new OpenAiResponsesProfile($streamingProfile, OpenAiResponsesMappingVersion::Version1, ResponseStoragePreference::Disabled);
        $streaming = $encoder->encode($this->requestForNeutralProfile($streamingProfile, [$message], ResultMode::Text, true), $openAiStreamingProfile);
        self::assertTrue($streaming->streaming());
        self::assertStringContainsString('"stream":true', $streaming->body());

        $baselineMessages = array_fill(0, 8, new InputMessage(InputRole::User, ModelVisibility::Private, 'a'));
        $fixedBytes = $encoder->encode($this->request($profile, $baselineMessages), $profile)->bodyByteCount() - 8;
        $encodedContentBytes = ResponsesRequestEncoder::MAXIMUM_BODY_BYTES - $fixedBytes;
        $asciiBytes = 8 + (($encodedContentBytes - 8) % 2);
        $escapedBytes = intdiv($encodedContentBytes - $asciiBytes, 2);
        $contents = str_split(str_repeat('a', $asciiBytes) . str_repeat('\\', $escapedBytes), 32768);
        self::assertCount(8, $contents);
        $boundaryMessages = [];
        foreach ($contents as $content) {
            $boundaryMessages[] = new InputMessage(InputRole::User, ModelVisibility::Private, $content);
        }
        $atBoundary = $encoder->encode($this->request($profile, $boundaryMessages), $profile);
        self::assertSame(ResponsesRequestEncoder::MAXIMUM_BODY_BYTES, $atBoundary->bodyByteCount());

        $contents[7] .= 'a';
        $overBoundaryMessages = [];
        foreach ($contents as $content) {
            $overBoundaryMessages[] = new InputMessage(InputRole::User, ModelVisibility::Private, $content);
        }
        try {
            $encoder->encode($this->request($profile, $overBoundaryMessages), $profile);
            self::fail('A request body above the exact bound must fail.');
        } catch (ResponsesRequestEncodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1704', $exception->diagnosticId());
        }
    }

    private function profile(string $model = 'openai-test-model', ResponseStoragePreference $storage = ResponseStoragePreference::Disabled): OpenAiResponsesProfile
    {
        return new OpenAiResponsesProfile($this->neutralProfile([ProviderCapability::TextResult, ProviderCapability::UsageEvidence], $model), OpenAiResponsesMappingVersion::Version1, $storage);
    }

    /** @param list<InputMessage> $messages */
    private function request(OpenAiResponsesProfile $profile, array $messages): InvocationRequest
    {
        return $this->requestForNeutralProfile($profile->neutralProfile(), $messages, ResultMode::Text, false);
    }

    /** @param list<ProviderCapability> $capabilities */
    private function neutralProfile(array $capabilities, string $model = 'openai-test-model'): ModelProfileReference
    {
        return new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-test'), new ProviderIdentity('openai'), new ModelIdentity($model), new ProviderCapabilitySet($capabilities));
    }

    /** @param list<InputMessage> $messages */
    private function requestForNeutralProfile(ModelProfileReference $profile, array $messages, ResultMode $mode, bool $streaming): InvocationRequest
    {
        return new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invocation-test'), $profile, $messages, $mode, $streaming, new InvocationLimits(262144, 1, 3));
    }
}
