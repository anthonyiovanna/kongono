<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Pairs inert action evidence; it is not a proposal, grant, or callable. */
#[Api(ApiStatus::Experimental)]
final readonly class ActionSuggestion
{
    public function __construct(
        private ActionName $name,
        private ActionArguments $arguments,
    ) {}

    public function name(): ActionName
    {
        return $this->name;
    }

    public function arguments(): ActionArguments
    {
        return $this->arguments;
    }

    public function equals(self $other): bool
    {
        return $this->name->equals($other->name) && $this->arguments->equals($other->arguments);
    }
}
