<?php

declare(strict_types=1);

namespace Kongono\Ai;

use Closure;
use Kongono\Ai\Agent\RequiresApproval;
use ReflectionFunction;

final readonly class ToolDefinition
{
    /** @param array<string, mixed> $parametersSchema JSON Schema object */
    public function __construct(
        public string $name,
        public string $description,
        public array $parametersSchema = ['type' => 'object', 'properties' => []],
        public bool $requiresApproval = false,
    ) {}

    /**
     * Build a ToolDefinition from a callable/first-class method handler, automatically detecting
     * method-, function-, or class-level `#[RequiresApproval]` attributes.
     *
     * @param array<string, mixed> $parametersSchema JSON Schema object
     */
    public static function fromCallable(
        string $name,
        string $description,
        array $parametersSchema,
        callable $handler,
        bool $requiresApproval = false,
    ): self {
        $detected = $requiresApproval || self::callableRequiresApproval($handler);

        return new self(
            name: $name,
            description: $description,
            parametersSchema: $parametersSchema,
            requiresApproval: $detected,
        );
    }

    public static function callableRequiresApproval(callable $handler): bool
    {
        $closure = $handler instanceof Closure ? $handler : Closure::fromCallable($handler);
        $ref = new ReflectionFunction($closure);
        if ([] !== $ref->getAttributes(RequiresApproval::class)) {
            return true;
        }
        $scopeClass = $ref->getClosureScopeClass();
        if (null !== $scopeClass && [] !== $scopeClass->getAttributes(RequiresApproval::class)) {
            return true;
        }
        $calledClass = $ref->getClosureCalledClass();
        if (null !== $calledClass && [] !== $calledClass->getAttributes(RequiresApproval::class)) {
            return true;
        }

        return false;
    }
}
