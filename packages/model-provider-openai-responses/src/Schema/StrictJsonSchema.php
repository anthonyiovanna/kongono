<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Schema;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaCodec;
use stdClass;

/** Stores one explicit canonical strict-schema definition as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class StrictJsonSchema
{
    private string $canonicalJson;
    /** @var array<string, mixed> */
    private array $structure;

    public function __construct(string $json)
    {
        $decoded = (new StrictJsonSchemaCodec())->decode($json);
        $this->canonicalJson = $decoded['canonical'];
        $this->structure = $decoded['structure'];
    }

    public function canonicalJson(): string
    {
        return $this->canonicalJson;
    }

    /** @return array<string, mixed> */
    public function structure(): array
    {
        return array_map(self::detach(...), $this->structure);
    }

    public function providerValue(): stdClass
    {
        try {
            $value = json_decode($this->canonicalJson, false, StrictJsonSchemaCodec::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidStrictJsonSchemaException::violation('json_syntax', 'schema');
        }
        if (!$value instanceof stdClass) {
            throw InvalidStrictJsonSchemaException::violation('root_object', 'schema');
        }

        return $value;
    }

    public function equals(self $other): bool
    {
        return $this->canonicalJson === $other->canonicalJson;
    }

    private static function detach(mixed $value): mixed
    {
        return is_array($value) ? array_map(self::detach(...), $value) : $value;
    }
}
