<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Declaration;

use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded declaration identity without admitting an agent. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentDeclarationIdentity
{
    public function __construct(private string $value)
    {
        if (
            strlen($value) < 1
            || strlen($value) > 128
            || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)
        ) {
            throw InvalidAgentDeclarationException::invalid('declaration_identity_grammar');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
