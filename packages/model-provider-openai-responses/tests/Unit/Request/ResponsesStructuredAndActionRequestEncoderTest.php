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
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Request\ResponsesRequestEncoder;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\TestCase;

final class ResponsesStructuredAndActionRequestEncoderTest extends TestCase
{
    private const string SCHEMA = '{"type":"object","properties":{"answer":{"type":"string"}},"required":["answer"],"additionalProperties":false}';

    public function testStructuredBodyAndHashAreExact(): void
    {
        [$request, $profile] = $this->context(ResultMode::Structured);
        $encoded = (new ResponsesRequestEncoder())->encode($request, $profile);
        $expected = '{"input":[{"content":[{"text":"synthetic input","type":"input_text"}],"role":"user","type":"message"}],"model":"openai-test-model","store":false,"stream":false,"text":{"format":{"name":"answer_format","schema":{"additionalProperties":false,"properties":{"answer":{"type":"string"}},"required":["answer"],"type":"object"},"strict":true,"type":"json_schema"}}}';
        self::assertSame($expected, $encoded->body());
        self::assertSame(strtoupper(hash('sha256', $expected)), $encoded->bodySha256());
        self::assertSame('261682E6A74CB715FB624E0A4DCD78A1F9007C0AB72426CCBBECE3363AC2E5B6', $encoded->bodySha256());
    }

    public function testActionBodyAndHashAreExactAndContainsOnlyOneInertFunction(): void
    {
        [$request, $profile] = $this->context(ResultMode::ActionSuggestion);
        $encoded = (new ResponsesRequestEncoder())->encode($request, $profile);
        $expected = '{"input":[{"content":[{"text":"synthetic input","type":"input_text"}],"role":"user","type":"message"}],"model":"openai-test-model","parallel_tool_calls":false,"store":false,"stream":false,"tool_choice":"required","tools":[{"name":"record_answer","parameters":{"additionalProperties":false,"properties":{"answer":{"type":"string"}},"required":["answer"],"type":"object"},"strict":true,"type":"function"}]}';
        self::assertSame($expected, $encoded->body());
        self::assertSame(strtoupper(hash('sha256', $expected)), $encoded->bodySha256());
        self::assertSame('1E45D36F0E53F538392CC9636469BBC23270BD597F7D06337C9EB644E176DCE2', $encoded->bodySha256());
        foreach (['function_call_output', 'previous_response_id', 'conversation', 'background', 'web_search', 'file_search', 'computer', 'shell', 'code_interpreter', 'image_generation', 'mcp'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded->body());
        }
    }

    /** @return array{InvocationRequest, OpenAiResponsesProfile} */
    private function context(ResultMode $mode): array
    {
        $capability = ResultMode::Structured === $mode ? ProviderCapability::StructuredResult : ProviderCapability::ActionSuggestion;
        $neutral = new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-v2'), new ProviderIdentity('openai'), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet([$capability, ProviderCapability::UsageEvidence]));
        $schema = new StrictJsonSchema(self::SCHEMA);
        $profile = new OpenAiResponsesProfile(
            $neutral,
            OpenAiResponsesMappingVersion::Version2,
            ResponseStoragePreference::Disabled,
            ResultMode::Structured === $mode ? new OpenAiResponsesStructuredOutput('answer_format', $schema) : null,
            ResultMode::ActionSuggestion === $mode ? new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), $schema) : null,
        );
        $request = new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invocation-v2'), $neutral, [new InputMessage(InputRole::User, ModelVisibility::Private, 'synthetic input')], $mode, false, new InvocationLimits(4096, 1, 3));

        return [$request, $profile];
    }
}
