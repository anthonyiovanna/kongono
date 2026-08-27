<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidGeneratedOperationInputsException;

/** Carries zero through thirty-two ordered object inputs for one generated operation. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedOperationInputs
{
    private const MAX_INPUTS = 32;

    /** @param list<object> $inputs */
    private function __construct(private array $inputs) {}

    private function __clone(): void {}

    public static function none(): self
    {
        return new self([]);
    }

    public static function of(object ...$inputs): self
    {
        if (count($inputs) > self::MAX_INPUTS) {
            throw new InvalidGeneratedOperationInputsException(
                CompositionDiagnostic::InvalidGeneratedOperationInputs,
                'input_count_limit',
            );
        }

        return new self(array_values($inputs));
    }

    public function count(): int
    {
        return count($this->inputs);
    }

    /** @internal Generated assemblies are the only intended caller. */
    public function validateCount(int $expectedCount): void
    {
        if ($expectedCount < 0 || $expectedCount > self::MAX_INPUTS || $expectedCount !== count($this->inputs)) {
            throw new InvalidGeneratedOperationInputsException(
                CompositionDiagnostic::InvalidGeneratedOperationInputs,
                'input_count',
            );
        }
    }

    /**
     * Validates and returns one exact positional input for generated code.
     *
     * @internal Generated assemblies are the only intended caller.
     * @template T of object
     * @param class-string<T> $expectedContract
     * @return T
     */
    public function validatedAt(int $position, string $expectedContract): object
    {
        $input = $this->inputs[$position] ?? null;
        if (null === $input || !$input instanceof $expectedContract) {
            throw new InvalidGeneratedOperationInputsException(
                CompositionDiagnostic::InvalidGeneratedOperationInputs,
                'input_position_or_type',
            );
        }

        return $input;
    }
}
