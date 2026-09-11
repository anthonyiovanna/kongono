<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;

/** Declares one inert strict function shape without any callable or authority. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesFunctionDefinition
{
    public function __construct(private ActionName $name, private StrictJsonSchema $parameters) {}

    public function name(): ActionName
    {
        return $this->name;
    }

    public function parameters(): StrictJsonSchema
    {
        return $this->parameters;
    }

    public function equals(self $other): bool
    {
        return $this->name->equals($other->name) && $this->parameters->equals($other->parameters);
    }
}
