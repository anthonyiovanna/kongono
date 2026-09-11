<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesProfileException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;

/** Declares one explicit named strict structured-output format. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesStructuredOutput
{
    public function __construct(private string $name, private StrictJsonSchema $schema)
    {
        if (1 !== preg_match('/\A[A-Za-z0-9_-]{1,64}\z/D', $name)) {
            throw InvalidOpenAiResponsesProfileException::invalidProfile('structured_output_name', mappingVersion: OpenAiResponsesMappingVersion::Version2);
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function schema(): StrictJsonSchema
    {
        return $this->schema;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->schema->equals($other->schema);
    }
}
