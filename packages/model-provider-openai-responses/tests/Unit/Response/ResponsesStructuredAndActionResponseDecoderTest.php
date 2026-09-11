<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Response;

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
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResponsesStructuredAndActionResponseDecoderTest extends TestCase
{
    private const string SCHEMA = '{"type":"object","properties":{"answer":{"type":"string"}},"required":["answer"],"additionalProperties":false}';

    public function testStructuredAndActionFixturesMapCanonicalDetachedEvidence(): void
    {
        [$structuredRequest, $structuredProfile] = $this->context(ResultMode::Structured);
        $decoder = new ResponsesResponseDecoder();
        $structured = $decoder->decode($structuredRequest, $structuredProfile, $this->fixture('completed-structured.json'));
        self::assertSame('{"answer":"accepted"}', $structured->text()->value());
        self::assertNull($structured->actionSuggestion());

        [$actionRequest, $actionProfile] = $this->context(ResultMode::ActionSuggestion);
        $action = $decoder->decode($actionRequest, $actionProfile, $this->fixture('completed-function-call.json'));
        self::assertNull($action->optionalText());
        $suggestion = $action->actionSuggestion();
        self::assertNotNull($suggestion);
        self::assertSame('record_answer', $suggestion->name()->value());
        self::assertSame('{"answer":"accepted"}', $suggestion->arguments()->value());
        self::assertTrue($action->equals($decoder->decode($actionRequest, $actionProfile, $this->fixture('completed-function-call.json'))));
    }

    #[DataProvider('invalidCases')]
    public function testInvalidSchemaCallsIdsNamesShapesAndDuplicateArgumentsFailClosed(ResultMode $mode, string $body): void
    {
        [$request, $profile] = $this->context($mode);
        try {
            (new ResponsesResponseDecoder())->decode($request, $profile, $body);
            self::fail('Invalid version-2 response must fail.');
        } catch (ResponsesResponseDecodingException $failure) {
            self::assertStringNotContainsString('secret-like-17e', (string) $failure);
            $mappingVersion = null;
            foreach ($failure->diagnosticContext()->fields() as $field) {
                if ('mapping_version' === $field->key()) {
                    $mappingVersion = $field->value();
                }
            }
            self::assertSame('version_2', $mappingVersion);
        }
    }

    /** @return iterable<string, array{ResultMode, string}> */
    public static function invalidCases(): iterable
    {
        yield 'structured mismatch' => [ResultMode::Structured, self::fixtureStatic('invalid-structured.json')];
        yield 'structured duplicate value member' => [ResultMode::Structured, str_replace('{\\"answer\\":\\"accepted\\"}', '{\\"answer\\":\\"accepted\\",\\"answer\\":\\"secret-like-17e\\"}', self::fixtureStatic('completed-structured.json'))];
        yield 'function mismatch' => [ResultMode::ActionSuggestion, self::fixtureStatic('invalid-function-call.json')];
        $valid = self::fixtureStatic('completed-function-call.json');
        yield 'name' => [ResultMode::ActionSuggestion, str_replace('record_answer', 'secret-like-17e', $valid)];
        yield 'function id' => [ResultMode::ActionSuggestion, str_replace('fc_synthetic', 'secret-like-17e', $valid)];
        yield 'call id' => [ResultMode::ActionSuggestion, str_replace('call_synthetic', 'secret-like-17e', $valid)];
        yield 'incomplete call' => [ResultMode::ActionSuggestion, str_replace('"status":"completed","type":"function_call"', '"status":"incomplete","type":"function_call"', $valid)];
        yield 'duplicate arguments' => [ResultMode::ActionSuggestion, str_replace('{\\"answer\\":\\"accepted\\"}', '{\\"answer\\":\\"accepted\\",\\"answer\\":\\"secret-like-17e\\"}', $valid)];
        yield 'nonobject arguments' => [ResultMode::ActionSuggestion, str_replace('{\\"answer\\":\\"accepted\\"}', '[]', $valid)];
        yield 'malformed arguments' => [ResultMode::ActionSuggestion, str_replace('{\\"answer\\":\\"accepted\\"}', '{', $valid)];
        yield 'multiple calls' => [ResultMode::ActionSuggestion, self::duplicateOutputItem($valid)];
        foreach (['web_search_call', 'file_search_call', 'computer_call', 'code_interpreter_call', 'shell_call', 'image_generation_call', 'mcp_call', 'custom_tool_call', 'reasoning', 'image', 'audio'] as $unsupportedType) {
            yield 'unsupported ' . $unsupportedType => [ResultMode::ActionSuggestion, str_replace('function_call', $unsupportedType, $valid)];
        }
    }

    public function testStructuredActionAndTextDecodingRemainIsolatedAcrossFailure(): void
    {
        $decoder = new ResponsesResponseDecoder();
        [$structuredRequest, $structuredProfile] = $this->context(ResultMode::Structured);
        self::assertSame('{"answer":"accepted"}', $decoder->decode($structuredRequest, $structuredProfile, $this->fixture('completed-structured.json'))->text()->value());

        [$actionRequest, $actionProfile] = $this->context(ResultMode::ActionSuggestion);
        try {
            $decoder->decode($actionRequest, $actionProfile, $this->fixture('invalid-function-call.json'));
            self::fail('The invalid middle operation must fail.');
        } catch (ResponsesResponseDecodingException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1706', $failure->diagnosticId());
        }
        self::assertSame('record_answer', $decoder->decode($actionRequest, $actionProfile, $this->fixture('completed-function-call.json'))->actionSuggestion()?->name()->value());

        $textFixture = new OpenAiResponsesAdapterFixture();
        self::assertSame('synthetic output', $decoder->decode($textFixture->source->request(), $textFixture->openAiProfile, OpenAiResponsesAdapterFixture::completedBody())->text()->value());
        self::assertSame('{"answer":"accepted"}', $decoder->decode($structuredRequest, $structuredProfile, $this->fixture('completed-structured.json'))->text()->value());
    }

    /** @return array{InvocationRequest, OpenAiResponsesProfile} */
    private function context(ResultMode $mode): array
    {
        $capability = ResultMode::Structured === $mode ? ProviderCapability::StructuredResult : ProviderCapability::ActionSuggestion;
        $neutral = new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-v2'), new ProviderIdentity('openai'), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet([$capability, ProviderCapability::UsageEvidence]));
        $schema = new StrictJsonSchema(self::SCHEMA);
        $profile = new OpenAiResponsesProfile($neutral, OpenAiResponsesMappingVersion::Version2, ResponseStoragePreference::Disabled, ResultMode::Structured === $mode ? new OpenAiResponsesStructuredOutput('answer_format', $schema) : null, ResultMode::ActionSuggestion === $mode ? new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), $schema) : null);
        $request = new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invocation-v2'), $neutral, [new InputMessage(InputRole::User, ModelVisibility::Private, 'synthetic input')], $mode, false, new InvocationLimits(4096, 1, 3));

        return [$request, $profile];
    }

    private function fixture(string $name): string
    {
        return self::fixtureStatic($name);
    }

    private static function fixtureStatic(string $name): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/Fixture/Response/' . $name);
    }

    private static function duplicateOutputItem(string $body): string
    {
        $start = strpos($body, '[{');
        $end = strpos($body, '}],"status"');
        if (false === $start || false === $end) {
            throw new \LogicException('The synthetic response fixture must contain one output item.');
        }
        $item = substr($body, $start + 1, $end - $start);

        return substr($body, 0, $end + 1) . ',' . $item . substr($body, $end + 1);
    }
}
