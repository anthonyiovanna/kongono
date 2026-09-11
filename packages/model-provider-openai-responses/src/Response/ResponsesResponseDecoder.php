<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Response;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\Internal\ProtocolGuard;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaCodec;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaValidator;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;

/** Strictly decodes one complete supported response body without I/O or retained state. */
#[Api(ApiStatus::Internal)]
final class ResponsesResponseDecoder
{
    public const int MAXIMUM_BODY_BYTES = 2097152;
    public const int MAXIMUM_DEPTH = 64;
    public const int MAXIMUM_OUTPUT_ITEMS = 64;

    public function decode(InvocationRequest $request, OpenAiResponsesProfile $profile, string $body): DecodedResponsesResponse
    {
        $version = $profile->mappingVersion();
        if (strlen($body) > self::MAXIMUM_BODY_BYTES) {
            throw ResponsesResponseDecodingException::limitExceeded('response_body_bytes', self::MAXIMUM_BODY_BYTES, mappingVersion: $version);
        }
        if (1 !== preg_match('//u', $body)) {
            throw ResponsesResponseDecodingException::invalidSyntax('utf8', mappingVersion: $version);
        }

        try {
            $decoded = json_decode($body, true, self::MAXIMUM_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $violation = JSON_ERROR_DEPTH === $exception->getCode() ? 'json_depth' : 'json_syntax';
            throw ResponsesResponseDecodingException::invalidSyntax($violation, mappingVersion: $version);
        }
        if (!str_starts_with(ltrim($body), '{')) {
            throw ResponsesResponseDecodingException::invalidSyntax('top_level_object', mappingVersion: $version);
        }
        $decoded = $this->object($decoded, 'body', $version);
        $this->assertNoDuplicateMembers($body, $version);

        if (!$request->profile()->equals($profile->neutralProfile())) {
            throw ResponsesResponseDecodingException::invalidStructure('model_mismatch', 'model', mappingVersion: $version);
        }
        $this->requireString($decoded, 'object', $version);
        if ('response' !== $decoded['object']) {
            throw ResponsesResponseDecodingException::invalidStructure('object_value', 'object', mappingVersion: $version);
        }
        $id = $this->requireString($decoded, 'id', $version);
        if (1 !== preg_match('/\Aresp_[A-Za-z0-9_-]{1,128}\z/D', $id)) {
            throw ResponsesResponseDecodingException::invalidStructure('response_id', 'id', mappingVersion: $version);
        }
        $model = $this->requireString($decoded, 'model', $version);
        if ($model !== $profile->neutralProfile()->modelIdentity()->value()) {
            throw ResponsesResponseDecodingException::invalidStructure('model_mismatch', 'model', mappingVersion: $version);
        }
        $statusValue = $this->requireString($decoded, 'status', $version);
        $status = ResponseStatus::tryFrom($statusValue);
        if (null === $status) {
            throw ResponsesResponseDecodingException::unsupportedMapping('response_status', 'status', mappingVersion: $version);
        }
        if (OpenAiResponsesMappingVersion::Version2 === $version && ResponseStatus::Completed !== $status) {
            throw ResponsesResponseDecodingException::unsupportedMapping('response_status', 'status', mappingVersion: $version);
        }
        $incompleteReason = $this->decodeIncompleteReason($decoded, $status, $version);
        [$text, $actionSuggestion] = match ($request->resultMode()) {
            ResultMode::Text => [$this->decodeTextOutput($decoded, $status, $request->limits()->maxOutputBytes(), null, $version), null],
            ResultMode::Structured => [$this->decodeStructuredOutput($decoded, $status, $request->limits()->maxOutputBytes(), $profile), null],
            ResultMode::ActionSuggestion => [null, $this->decodeActionOutput($decoded, $status, $profile)],
        };
        $usage = $this->decodeUsage($decoded, $version);

        return new DecodedResponsesResponse(
            $profile->mappingVersion(),
            $id,
            $model,
            $status,
            $incompleteReason,
            $request->resultMode(),
            $text,
            $actionSuggestion,
            $usage,
        );
    }

    /** @param array<string, mixed> $response */
    private function decodeIncompleteReason(array $response, ResponseStatus $status, OpenAiResponsesMappingVersion $version): ?ResponseIncompleteReason
    {
        if (ResponseStatus::Completed === $status) {
            if (array_key_exists('incomplete_details', $response) && null !== $response['incomplete_details']) {
                throw ResponsesResponseDecodingException::invalidStructure('status_relation', 'incomplete_details', $status->value, mappingVersion: $version);
            }

            return null;
        }
        if (!array_key_exists('incomplete_details', $response) || !is_array($response['incomplete_details']) || array_is_list($response['incomplete_details'])) {
            throw ResponsesResponseDecodingException::invalidStructure('incomplete_details', 'incomplete_details', $status->value, mappingVersion: $version);
        }
        /** @var array<string, mixed> $details */
        $details = $response['incomplete_details'];
        $this->assertExactKeys($details, ['reason'], 'incomplete_details', $version);
        $reasonValue = $this->requireString($details, 'reason', $version);
        $reason = ResponseIncompleteReason::tryFrom($reasonValue);
        if (null === $reason) {
            throw ResponsesResponseDecodingException::unsupportedMapping('incomplete_reason', 'incomplete_details', mappingVersion: $version);
        }

        return $reason;
    }

    /** @param array<string, mixed> $response */
    private function decodeTextOutput(
        array $response,
        ResponseStatus $status,
        int $maximumTextBytes,
        ?\Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema $schema,
        OpenAiResponsesMappingVersion $version,
    ): ResultText {
        if (!array_key_exists('output', $response) || !is_array($response['output']) || !array_is_list($response['output'])) {
            throw ResponsesResponseDecodingException::invalidStructure('output_shape', 'output', mappingVersion: $version);
        }
        /** @var list<mixed> $output */
        $output = $response['output'];
        if (count($output) > self::MAXIMUM_OUTPUT_ITEMS) {
            throw ResponsesResponseDecodingException::limitExceeded('output_count', self::MAXIMUM_OUTPUT_ITEMS, count($output), mappingVersion: $version);
        }
        if (1 !== count($output) || !is_array($output[0]) || array_is_list($output[0])) {
            throw ResponsesResponseDecodingException::unsupportedMapping('output_item', 'output', mappingVersion: $version);
        }
        /** @var array<string, mixed> $item */
        $item = $this->object($output[0], 'output_item', $version);
        $this->assertExactKeys($item, ['content', 'id', 'role', 'status', 'type'], 'output_item', $version);
        if ('message' !== $this->requireString($item, 'type', $version) || 'assistant' !== $this->requireString($item, 'role', $version)) {
            throw ResponsesResponseDecodingException::unsupportedMapping('output_item', 'output_item', mappingVersion: $version);
        }
        if (1 !== preg_match('/\Amsg_[A-Za-z0-9_-]{1,128}\z/D', $this->requireString($item, 'id', $version))) {
            throw ResponsesResponseDecodingException::invalidStructure('response_id', 'output_item', mappingVersion: $version);
        }
        if ($status->value !== $this->requireString($item, 'status', $version)) {
            throw ResponsesResponseDecodingException::invalidStructure('status_relation', 'output_item', $status->value, mappingVersion: $version);
        }
        if (!is_array($item['content']) || !array_is_list($item['content']) || 1 !== count($item['content'])) {
            throw ResponsesResponseDecodingException::unsupportedMapping('content_item', 'content', mappingVersion: $version);
        }
        $contentValue = $item['content'][0];
        if (!is_array($contentValue) || array_is_list($contentValue)) {
            throw ResponsesResponseDecodingException::invalidStructure('content_shape', 'content_item', mappingVersion: $version);
        }
        /** @var array<string, mixed> $content */
        $content = $contentValue;
        $this->assertExactKeys($content, ['annotations', 'text', 'type'], 'content_item', $version);
        if ('output_text' !== $this->requireString($content, 'type', $version)) {
            throw ResponsesResponseDecodingException::unsupportedMapping('content_item', 'content_item', mappingVersion: $version);
        }
        if (!is_array($content['annotations']) || !array_is_list($content['annotations'])) {
            throw ResponsesResponseDecodingException::invalidStructure('content_shape', 'content_item', mappingVersion: $version);
        }
        if ([] !== $content['annotations']) {
            throw ResponsesResponseDecodingException::unsupportedMapping('content_item', 'content_item', mappingVersion: $version);
        }
        $text = $this->requireString($content, 'text', $version);
        if (strlen($text) > $maximumTextBytes) {
            throw ResponsesResponseDecodingException::limitExceeded('output_text_bytes', $maximumTextBytes, mappingVersion: $version);
        }
        if (null !== $schema) {
            try {
                $value = json_decode($text, false, StrictJsonSchemaCodec::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
                $this->assertNoDuplicateMembers($text, $version);
            } catch (JsonException) {
                throw ResponsesResponseDecodingException::invalidStructure('schema_value', 'schema', mappingVersion: $version);
            }
            try {
                $text = (new StrictJsonSchemaValidator())->validateValue($value, $schema);
            } catch (\Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException) {
                throw ResponsesResponseDecodingException::invalidStructure('schema_value', 'schema', mappingVersion: $version);
            }
        }
        try {
            return new ResultText($text);
        } catch (\Throwable) {
            throw ResponsesResponseDecodingException::invalidStructure('text_value', 'text', mappingVersion: $version);
        }
    }

    /** @param array<string, mixed> $response */
    private function decodeStructuredOutput(array $response, ResponseStatus $status, int $maximumTextBytes, OpenAiResponsesProfile $profile): ResultText
    {
        $definition = $profile->structuredOutput();
        if (null === $definition) {
            throw ResponsesResponseDecodingException::unsupportedMapping('result_mode', 'output', mappingVersion: $profile->mappingVersion());
        }

        return $this->decodeTextOutput($response, $status, $maximumTextBytes, $definition->schema(), $profile->mappingVersion());
    }

    /** @param array<string, mixed> $response */
    private function decodeActionOutput(array $response, ResponseStatus $status, OpenAiResponsesProfile $profile): ActionSuggestion
    {
        $version = $profile->mappingVersion();
        $definition = $profile->functionDefinition();
        if (null === $definition || ResponseStatus::Completed !== $status) {
            throw ResponsesResponseDecodingException::unsupportedMapping('result_mode', 'output', mappingVersion: $version);
        }
        if (!isset($response['output']) || !is_array($response['output']) || !array_is_list($response['output'])) {
            throw ResponsesResponseDecodingException::invalidStructure('output_shape', 'output', mappingVersion: $version);
        }
        $output = $response['output'];
        if (count($output) > self::MAXIMUM_OUTPUT_ITEMS) {
            throw ResponsesResponseDecodingException::limitExceeded('output_count', self::MAXIMUM_OUTPUT_ITEMS, count($output), mappingVersion: $version);
        }
        if (1 !== count($output) || !is_array($output[0]) || array_is_list($output[0])) {
            throw ResponsesResponseDecodingException::unsupportedMapping('output_item', 'output', mappingVersion: $version);
        }
        $item = $this->object($output[0], 'output_item', $version);
        $this->assertExactKeys($item, ['arguments', 'call_id', 'id', 'name', 'status', 'type'], 'output_item', $version);
        if ('function_call' !== $this->requireString($item, 'type', $version) || 'completed' !== $this->requireString($item, 'status', $version)) {
            throw ResponsesResponseDecodingException::unsupportedMapping('output_item', 'output_item', mappingVersion: $version);
        }
        if (1 !== preg_match('/\Afc_[A-Za-z0-9_-]{1,128}\z/D', $this->requireString($item, 'id', $version))) {
            throw ResponsesResponseDecodingException::invalidStructure('function_id', 'id', mappingVersion: $version);
        }
        if (1 !== preg_match('/\Acall_[A-Za-z0-9_-]{1,128}\z/D', $this->requireString($item, 'call_id', $version))) {
            throw ResponsesResponseDecodingException::invalidStructure('call_id', 'call_id', mappingVersion: $version);
        }
        if ($definition->name()->value() !== $this->requireString($item, 'name', $version)) {
            throw ResponsesResponseDecodingException::invalidStructure('function_name', 'name', mappingVersion: $version);
        }
        $arguments = $this->requireString($item, 'arguments', $version);
        if (strlen($arguments) > 65536) {
            throw ResponsesResponseDecodingException::limitExceeded('action_arguments_bytes', 65536, mappingVersion: $version);
        }
        try {
            $value = json_decode($arguments, false, StrictJsonSchemaCodec::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
            $this->assertNoDuplicateMembers($arguments, $version);
            $canonical = (new StrictJsonSchemaValidator())->validateValue($value, $definition->parameters());
            $actionArguments = new ActionArguments($canonical);
        } catch (\Throwable) {
            throw ResponsesResponseDecodingException::invalidStructure('arguments_value', 'arguments', mappingVersion: $version);
        }

        return new ActionSuggestion($definition->name(), $actionArguments);
    }

    /** @param array<string, mixed> $response */
    private function decodeUsage(array $response, OpenAiResponsesMappingVersion $version): ?ResponseUsage
    {
        if (!array_key_exists('usage', $response) || null === $response['usage']) {
            return null;
        }
        if (!is_array($response['usage']) || array_is_list($response['usage'])) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_type', 'usage', mappingVersion: $version);
        }
        /** @var array<string, mixed> $usage */
        $usage = $response['usage'];
        $allowed = ['input_tokens', 'input_tokens_details', 'output_tokens', 'output_tokens_details', 'total_tokens'];
        foreach (array_keys($usage) as $field) {
            if (!in_array($field, $allowed, true)) {
                throw ResponsesResponseDecodingException::unsupportedMapping('object_members', 'usage', mappingVersion: $version);
            }
        }
        foreach (['input_tokens', 'output_tokens', 'total_tokens'] as $field) {
            if (!array_key_exists($field, $usage) || !is_int($usage[$field])) {
                throw ResponsesResponseDecodingException::invalidStructure('usage_type', 'usage', mappingVersion: $version);
            }
        }

        $aggregate = new ResponseUsage($usage['input_tokens'], $usage['output_tokens'], $usage['total_tokens']);
        $this->validateUsageDetail($usage, 'input_tokens_details', 'cached_tokens', $aggregate->inputTokens(), $version);
        $this->validateUsageDetail($usage, 'output_tokens_details', 'reasoning_tokens', $aggregate->outputTokens(), $version);

        return $aggregate;
    }

    /** @param array<string, mixed> $usage */
    private function validateUsageDetail(array $usage, string $detailField, string $countField, int $aggregate, OpenAiResponsesMappingVersion $version): void
    {
        if (!array_key_exists($detailField, $usage)) {
            return;
        }
        if (!is_array($usage[$detailField]) || array_is_list($usage[$detailField])) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_type', 'usage', mappingVersion: $version);
        }
        /** @var array<string, mixed> $details */
        $details = $usage[$detailField];
        $this->assertExactKeys($details, [$countField], 'usage', $version);
        if (!is_int($details[$countField])) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_type', 'usage', mappingVersion: $version);
        }
        if ($details[$countField] < 0 || $details[$countField] > ResponseUsage::MAXIMUM_TOKEN_VALUE) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_range', 'usage', mappingVersion: $version);
        }
        if ($details[$countField] > $aggregate) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_relation', 'usage', mappingVersion: $version);
        }
    }

    private function assertNoDuplicateMembers(string $json, OpenAiResponsesMappingVersion $version): void
    {
        try {
            ProtocolGuard::assertNoDuplicateMembers($json);
        } catch (ResponsesResponseDecodingException $failure) {
            throw ResponsesResponseDecodingException::invalidSyntax('duplicate_member', $failure, $version);
        }
    }

    /** @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function assertExactKeys(array $object, array $expected, string $field, OpenAiResponsesMappingVersion $version): void
    {
        $keys = array_keys($object);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw ResponsesResponseDecodingException::unsupportedMapping('object_members', $field, mappingVersion: $version);
        }
    }

    /** @param array<string, mixed> $object */
    private function requireString(array $object, string $field, OpenAiResponsesMappingVersion $version): string
    {
        if (!array_key_exists($field, $object)) {
            throw ResponsesResponseDecodingException::invalidStructure('missing_field', $this->safeField($field), mappingVersion: $version);
        }
        if (!is_string($object[$field])) {
            throw ResponsesResponseDecodingException::invalidStructure('field_type', $this->safeField($field), mappingVersion: $version);
        }

        return $object[$field];
    }

    private function safeField(string $field): string
    {
        return match ($field) {
            'object', 'id', 'model', 'status', 'type', 'role', 'reason', 'text' => match ($field) {
                'type', 'role' => 'output_item',
                'reason' => 'incomplete_details',
                default => $field,
            },
            default => 'body',
        };
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $field, OpenAiResponsesMappingVersion $version): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw ResponsesResponseDecodingException::invalidSyntax('top_level_object', mappingVersion: $version);
        }
        $object = [];

        foreach ($value as $key => $member) {
            if (!is_string($key)) {
                throw ResponsesResponseDecodingException::invalidStructure('field_type', $field, mappingVersion: $version);
            }

            $object[$key] = $member;
        }

        return $object;
    }
}
