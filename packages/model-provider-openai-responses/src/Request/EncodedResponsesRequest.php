<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Request;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesRequestEncodingException;
use Kongono\ModelProviderOpenAiResponses\Internal\CanonicalJson;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use stdClass;

/** Carries bounded request bytes as inert mapping evidence without transport authority. */
#[Api(ApiStatus::Internal)]
final readonly class EncodedResponsesRequest
{
    public const string RELATIVE_PATH = '/v1/responses';
    public const string MEDIA_TYPE = 'application/json';
    public const int MAXIMUM_BODY_BYTES = 524288;

    private int $bodyByteCount;
    private string $bodySha256;
    private bool $streaming;

    public function __construct(
        private OpenAiResponsesMappingVersion $mappingVersion,
        private string $body,
    ) {
        if (strlen($body) > self::MAXIMUM_BODY_BYTES) {
            throw ResponsesRequestEncodingException::limitExceeded('request_body_bytes', self::MAXIMUM_BODY_BYTES, mappingVersion: $mappingVersion);
        }
        if ('' === $body || 1 !== preg_match('//u', $body)) {
            throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $mappingVersion);
        }

        try {
            $decoded = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
            if (!str_starts_with($body, '{') || !$decoded instanceof stdClass) {
                throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $mappingVersion);
            }
            $object = self::objectMembers($decoded, $mappingVersion);
            $expectedKeys = match ($mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => ['input', 'model', 'store', 'stream'],
                OpenAiResponsesMappingVersion::Version2 => array_key_exists('text', $object)
                    ? ['input', 'model', 'store', 'stream', 'text']
                    : ['input', 'model', 'parallel_tool_calls', 'store', 'stream', 'tool_choice', 'tools'],
            };
            if ($expectedKeys !== array_keys($object)
                || !is_bool($object['stream'])
                || (OpenAiResponsesMappingVersion::Version2 === $mappingVersion && $object['stream'])
                || CanonicalJson::encode(self::canonicalObject($object)) !== $body
            ) {
                throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $mappingVersion);
            }
        } catch (JsonException) {
            throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $mappingVersion);
        }

        $this->bodyByteCount = strlen($body);
        $this->bodySha256 = strtoupper(hash('sha256', $body));
        $this->streaming = $object['stream'];
    }

    /** @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private static function canonicalObject(array $value): array
    {
        ksort($value, SORT_STRING);
        foreach ($value as $key => $member) {
            $value[$key] = self::canonicalValue($member);
        }

        return $value;
    }

    private static function canonicalValue(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $members = self::objectMembers($value);
            ksort($members, SORT_STRING);
            $copy = new stdClass();
            foreach ($members as $key => $member) {
                $copy->{$key} = self::canonicalValue($member);
            }

            return $copy;
        }
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            $object = [];
            foreach ($value as $key => $member) {
                if (!is_string($key)) {
                    throw ResponsesRequestEncodingException::encodingFailed('json_encoding');
                }
                $object[$key] = $member;
            }

            return self::canonicalObject($object);
        }

        return array_map(self::canonicalValue(...), $value);
    }

    /** @return array<string, mixed> */
    private static function objectMembers(stdClass $value, OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1): array
    {
        $members = [];
        foreach (get_object_vars($value) as $key => $member) {
            if (!is_string($key)) {
                throw ResponsesRequestEncodingException::encodingFailed('json_encoding', mappingVersion: $mappingVersion);
            }
            $members[$key] = $member;
        }

        return $members;
    }

    public function mappingVersion(): OpenAiResponsesMappingVersion
    {
        return $this->mappingVersion;
    }

    public function relativePath(): string
    {
        return self::RELATIVE_PATH;
    }

    public function mediaType(): string
    {
        return self::MEDIA_TYPE;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function bodyByteCount(): int
    {
        return $this->bodyByteCount;
    }

    public function bodySha256(): string
    {
        return $this->bodySha256;
    }

    public function streaming(): bool
    {
        return $this->streaming;
    }

    public function equals(self $other): bool
    {
        return match ($this->mappingVersion) {
            OpenAiResponsesMappingVersion::Version1 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => $this->body === $other->body,
                OpenAiResponsesMappingVersion::Version2 => false,
            },
            OpenAiResponsesMappingVersion::Version2 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => false,
                OpenAiResponsesMappingVersion::Version2 => $this->body === $other->body,
            },
        };
    }
}
