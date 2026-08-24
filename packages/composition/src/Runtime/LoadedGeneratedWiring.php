<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Exception\GeneratedWiringLoadException;
use ReflectionMethod;
use ReflectionNamedType;

/** Holds one validated loaded assembly and its one-use startup boundary. */
#[Api(ApiStatus::Experimental)]
final class LoadedGeneratedWiring
{
    private bool $started = false;

    /**
     * @internal Constructed only by GeneratedWiringLoader after exact loading.
     * @param class-string<GeneratedApplicationBoundary> $applicationClass
     * @param class-string<GeneratedOperationBoundary> $operationClass
     */
    public function __construct(
        private readonly string $wiringSha256,
        private readonly string $applicationClass,
        private readonly string $operationClass,
        private readonly ReflectionMethod $start,
    ) {}

    private function __clone(): void {}

    public function wiringSha256(): string
    {
        return $this->wiringSha256;
    }

    /** @return class-string<GeneratedApplicationBoundary> */
    public function applicationClass(): string
    {
        return $this->applicationClass;
    }

    /** @return class-string<GeneratedOperationBoundary> */
    public function operationClass(): string
    {
        return $this->operationClass;
    }

    public function start(object ...$inputs): GeneratedApplicationBoundary
    {
        if ($this->started) {
            throw new GeneratedWiringLoadException('wiring_already_started');
        }

        $parameters = $this->start->getParameters();
        if (count($parameters) !== count($inputs)) {
            throw new GeneratedWiringLoadException('input_count');
        }
        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull()) {
                throw new GeneratedWiringLoadException('generated_shape');
            }
            $name = $type->getName();
            if (!$inputs[$index] instanceof $name) {
                throw new GeneratedWiringLoadException('input_type');
            }
        }

        $this->started = true;
        $boundary = $this->start->invokeArgs(null, $inputs);
        if (!$boundary instanceof GeneratedApplicationBoundary) {
            throw new GeneratedWiringLoadException('start_result');
        }

        return $boundary;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }
}
