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
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseIncompleteReason;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResponsesResponseDecoderTest extends TestCase
{
    public function testSyntheticFixturesDecodeWithExactOptionalEvidence(): void
    {
        [$request, $profile] = $this->context();
        $decoder = new ResponsesResponseDecoder();
        $completed = $decoder->decode($request, $profile, $this->fixture('completed-text.json'));
        self::assertSame('synthetic output', $completed->text()->value());
        self::assertNull($completed->usage());
        self::assertNull($completed->incompleteReason());
        $usage = $decoder->decode($request, $profile, $this->fixture('completed-text-usage.json'));
        $usageEvidence = $usage->usage();
        self::assertNotNull($usageEvidence);
        self::assertSame(7, $usageEvidence->inputTokens());
        self::assertSame(5, $usageEvidence->outputTokens());
        self::assertSame(12, $usageEvidence->totalTokens());
        $usageProperties = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), (new \ReflectionClass($usageEvidence))->getProperties());
        sort($usageProperties, SORT_STRING);
        self::assertSame(['inputTokens', 'outputTokens', 'totalTokens'], $usageProperties);
        $incomplete = $decoder->decode($request, $profile, $this->fixture('incomplete-max-output.json'));
        self::assertSame(ResponseIncompleteReason::MaxTokens, $incomplete->incompleteReason());
        self::assertTrue($completed->equals($decoder->decode($request, $profile, $this->fixture('completed-text.json'))));
    }

    public function testNullableTerminalMembersAndBothIncompleteReasonsMapExactly(): void
    {
        [$request, $profile] = $this->context();
        $decoder = new ResponsesResponseDecoder();
        self::assertNull($decoder->decode($request, $profile, self::body([], ['usage']))->usage());
        self::assertNull($decoder->decode($request, $profile, self::body(['usage' => null]))->usage());

        foreach (['max_tokens' => ResponseIncompleteReason::MaxTokens, 'content_filter' => ResponseIncompleteReason::ContentFilter] as $reason => $expected) {
            $body = self::body([
                'incomplete_details' => ['reason' => $reason],
                'output' => [self::message('partial', 'incomplete')],
                'status' => 'incomplete',
            ]);
            self::assertSame($expected, $decoder->decode($request, $profile, $body)->incompleteReason());
        }
    }

    #[DataProvider('invalidBodies')]
    public function testMalformedDuplicateUnsupportedAndMismatchedBodiesFailClosed(string $fixture): void
    {
        [$request, $profile] = $this->context();
        $this->expectException(ResponsesResponseDecodingException::class);
        (new ResponsesResponseDecoder())->decode($request, $profile, $this->fixture($fixture));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBodies(): iterable
    {
        yield 'malformed' => ['malformed.json'];
        yield 'duplicate' => ['duplicate-key.json'];
        yield 'unknown output' => ['unknown-output-item.json'];
        yield 'model mismatch' => ['model-mismatch.json'];
    }

    #[DataProvider('invalidInlineBodies')]
    public function testInvalidSyntaxShapeUsageAndUnsupportedSurfacesFailWithoutDisclosure(string $body): void
    {
        [$request, $profile] = $this->context();
        try {
            (new ResponsesResponseDecoder())->decode($request, $profile, $body);
            self::fail('Invalid response must fail.');
        } catch (ResponsesResponseDecodingException $exception) {
            $surfaces = $exception->getMessage() . (string) $exception . $exception->diagnosticTitle() . $exception->diagnosticRemediation();
            foreach ($exception->diagnosticContext()->fields() as $field) {
                $surfaces .= $field->key() . '=' . $field->value();
            }
            self::assertStringNotContainsString('secret-like-17b', $surfaces);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidInlineBodies(): iterable
    {
        yield 'invalid utf8' => ["{\"secret-like-17b\":\"\xFF\"}"];
        yield 'top list' => ['[]'];
        yield 'wrong object' => [self::body(['object' => 'other'])];
        yield 'missing id' => [self::body([], ['id'])];
        yield 'failed status' => [self::body(['status' => 'failed'])];
        yield 'cancelled status' => [self::body(['status' => 'cancelled'])];
        yield 'nonterminal status' => [self::body(['status' => 'in_progress'])];
        yield 'status mismatch' => [self::body(['status' => 'incomplete'])];
        yield 'unknown content' => [self::body(['output' => [['content' => [['annotations' => [], 'text' => 'secret-like-17b', 'type' => 'refusal']], 'id' => 'msg_unknown', 'role' => 'assistant', 'status' => 'completed', 'type' => 'message']]])];
        yield 'structured content' => [self::body(['output' => [['content' => [['annotations' => [], 'text' => 'secret-like-17b', 'type' => 'output_json']], 'id' => 'msg_structured', 'role' => 'assistant', 'status' => 'completed', 'type' => 'message']]])];
        yield 'multiple text parts' => [self::body(['output' => [['content' => [['annotations' => [], 'text' => 'one', 'type' => 'output_text'], ['annotations' => [], 'text' => 'two', 'type' => 'output_text']], 'id' => 'msg_multiple', 'role' => 'assistant', 'status' => 'completed', 'type' => 'message']]])];
        yield 'nonempty annotations' => [self::body(['output' => [self::message('secret-like-17b', annotations: [['type' => 'citation']])]])];
        yield 'annotations wrong type' => [self::body(['output' => [self::message('secret-like-17b', annotations: 'invalid')]])];
        yield 'missing message id' => [self::body(['output' => [self::message(omitId: true)]])];
        yield 'invalid message id' => [self::body(['output' => [self::message(id: 'secret-like-17b')]])];
        yield 'oversized message id' => [self::body(['output' => [self::message(id: 'msg_' . str_repeat('a', 129))]])];
        yield 'usage float' => [self::body(['usage' => ['input_tokens' => 1.0, 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'usage string' => [self::body(['usage' => ['input_tokens' => '1', 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'usage relation' => [self::body(['usage' => ['input_tokens' => 4, 'output_tokens' => 3, 'total_tokens' => 2]])];
        yield 'usage overflow' => [self::body(['usage' => ['input_tokens' => 2147483648, 'output_tokens' => 1, 'total_tokens' => 2147483648]])];
        yield 'usage negative' => [self::body(['usage' => ['input_tokens' => -1, 'output_tokens' => 1, 'total_tokens' => 1]])];
        yield 'usage missing member' => [self::body(['usage' => ['input_tokens' => 1, 'output_tokens' => 1]])];
        yield 'usage unknown member' => [self::body(['usage' => ['cached_tokens' => 1, 'input_tokens' => 1, 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'input details list' => [self::body(['usage' => ['input_tokens' => 1, 'input_tokens_details' => [], 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'input details unknown member' => [self::body(['usage' => ['input_tokens' => 1, 'input_tokens_details' => ['cached_tokens' => 1, 'unknown' => 0], 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'cached tokens wrong type' => [self::body(['usage' => ['input_tokens' => 1, 'input_tokens_details' => ['cached_tokens' => '1'], 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'cached tokens negative' => [self::body(['usage' => ['input_tokens' => 1, 'input_tokens_details' => ['cached_tokens' => -1], 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'cached tokens overflow' => [self::body(['usage' => ['input_tokens' => 2147483647, 'input_tokens_details' => ['cached_tokens' => 2147483648], 'output_tokens' => 1, 'total_tokens' => 2147483647]])];
        yield 'cached tokens exceed input' => [self::body(['usage' => ['input_tokens' => 1, 'input_tokens_details' => ['cached_tokens' => 2], 'output_tokens' => 1, 'total_tokens' => 2]])];
        yield 'output details unknown member' => [self::body(['usage' => ['input_tokens' => 1, 'output_tokens' => 1, 'output_tokens_details' => ['reasoning_tokens' => 1, 'unknown' => 0], 'total_tokens' => 2]])];
        yield 'reasoning tokens exceed output' => [self::body(['usage' => ['input_tokens' => 1, 'output_tokens' => 1, 'output_tokens_details' => ['reasoning_tokens' => 2], 'total_tokens' => 2]])];
        yield 'completed details' => [self::body(['incomplete_details' => ['reason' => 'content_filter']])];
        yield 'legacy incomplete reason' => [self::body(['incomplete_details' => ['reason' => 'max_output_tokens'], 'output' => [self::message(status: 'incomplete')], 'status' => 'incomplete'])];
        yield 'tool call' => [self::body(['output' => [['arguments' => '{}', 'name' => 'synthetic', 'type' => 'function_call']]])];
        yield 'multiple messages' => [self::body(['output' => [self::message(), self::message()]])];
        yield 'too many output items' => [self::body(['output' => array_fill(0, ResponsesResponseDecoder::MAXIMUM_OUTPUT_ITEMS + 1, self::message())])];
        yield 'nested duplicate' => [str_replace('"text":"secret-like-17b"', '"text":"secret-like-17b","text":"secret-like-17b"', self::body())];
        yield 'overflowed JSON integer' => [str_replace('"status":"completed"', '"status":"completed","usage":{"input_tokens":9223372036854775808,"output_tokens":1,"total_tokens":9223372036854775808}', self::body())];
        yield 'excessive JSON depth' => ['{"unknown":' . str_repeat('[', ResponsesResponseDecoder::MAXIMUM_DEPTH + 1) . '0' . str_repeat(']', ResponsesResponseDecoder::MAXIMUM_DEPTH + 1) . ',"id":"resp_depth","model":"openai-test-model","object":"response","output":[],"status":"completed"}'];
    }

    public function testResponseAndOutputByteBoundsAreExactAndMixedOperationsDoNotLeakState(): void
    {
        [$request, $profile] = $this->context(8);
        $decoder = new ResponsesResponseDecoder();
        $valid = self::body(['output' => [self::message('12345678')]]);
        self::assertSame('12345678', $decoder->decode($request, $profile, $valid)->text()->value());
        try {
            $decoder->decode($request, $profile, self::body(['output' => [self::message('123456789')]]));
            self::fail('Oversized output must fail.');
        } catch (ResponsesResponseDecodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1707', $exception->diagnosticId());
        }
        self::assertSame('12345678', $decoder->decode($request, $profile, $valid)->text()->value());
        $this->expectException(ResponsesResponseDecodingException::class);
        $decoder->decode($request, $profile, str_repeat(' ', ResponsesResponseDecoder::MAXIMUM_BODY_BYTES + 1));
    }

    public function testNeutralOutputAndWholeResponseBodyBoundsAreExact(): void
    {
        [$request, $profile] = $this->context(262144);
        $decoder = new ResponsesResponseDecoder();
        $maximumText = str_repeat('x', 262144);
        self::assertSame($maximumText, $decoder->decode($request, $profile, self::body(['output' => [self::message($maximumText)]]))->text()->value());
        try {
            $decoder->decode($request, $profile, self::body(['output' => [self::message($maximumText . 'x')]]));
            self::fail('Text above the exact neutral bound must fail.');
        } catch (ResponsesResponseDecodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1707', $exception->diagnosticId());
        }

        $bodyWithEmptyExtension = self::body(['provider_extension' => '']);
        $paddingBytes = ResponsesResponseDecoder::MAXIMUM_BODY_BYTES - strlen($bodyWithEmptyExtension);
        self::assertGreaterThan(0, $paddingBytes);
        $atBoundary = self::body(['provider_extension' => str_repeat('p', $paddingBytes)]);
        self::assertSame(ResponsesResponseDecoder::MAXIMUM_BODY_BYTES, strlen($atBoundary));
        self::assertSame('secret-like-17b', $decoder->decode($request, $profile, $atBoundary)->text()->value());
        try {
            $decoder->decode($request, $profile, $atBoundary . ' ');
            self::fail('Response body above the exact bound must fail.');
        } catch (ResponsesResponseDecodingException $exception) {
            self::assertSame('KGN-OPENAI-RESPONSES-1707', $exception->diagnosticId());
        }
    }

    /** @return array{InvocationRequest, OpenAiResponsesProfile} */
    private function context(int $maximumOutputBytes = 262144): array
    {
        $neutral = new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile-test'), new ProviderIdentity('openai'), new ModelIdentity('openai-test-model'), new ProviderCapabilitySet([ProviderCapability::TextResult, ProviderCapability::UsageEvidence]));
        $profile = new OpenAiResponsesProfile($neutral, OpenAiResponsesMappingVersion::Version1, ResponseStoragePreference::Disabled);
        $request = new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invocation-test'), $neutral, [new InputMessage(InputRole::User, ModelVisibility::Private, 'synthetic input')], ResultMode::Text, false, new InvocationLimits($maximumOutputBytes, 1, 3));

        return [$request, $profile];
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/Fixture/Response/' . $name);
    }

    /** @param array<string, mixed> $overrides
     * @param list<string> $omit
     */
    private static function body(array $overrides = [], array $omit = []): string
    {
        $body = array_replace([
            'id' => 'resp_inline',
            'incomplete_details' => null,
            'model' => 'openai-test-model',
            'object' => 'response',
            'output' => [self::message('secret-like-17b')],
            'status' => 'completed',
        ], $overrides);
        foreach ($omit as $key) {
            unset($body[$key]);
        }

        return json_encode($body, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);
    }

    /** @param list<array<string, mixed>>|string $annotations
     * @return array<string, mixed>
     */
    private static function message(
        string $text = 'synthetic output',
        string $status = 'completed',
        string $id = 'msg_inline',
        array|string $annotations = [],
        bool $omitId = false,
    ): array {
        $message = ['content' => [['annotations' => $annotations, 'text' => $text, 'type' => 'output_text']], 'id' => $id, 'role' => 'assistant', 'status' => $status, 'type' => 'message'];
        if ($omitId) {
            unset($message['id']);
        }

        return $message;
    }
}
