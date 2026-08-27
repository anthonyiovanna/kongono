<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class OperationInputRoot
{
    public static int $constructionCount = 0;
    public static int $cleanupCount = 0;

    public function __construct(
        private readonly ConfigInput $applicationInput,
        private readonly OperationInput $operationInput,
        private readonly SecondaryOperationInput $secondaryOperationInput,
    ) {
        ++self::$constructionCount;
    }

    /** @return array{ConfigInput, OperationInput, SecondaryOperationInput} */
    public function inputs(): array
    {
        return [$this->applicationInput, $this->operationInput, $this->secondaryOperationInput];
    }

    public function close(): void
    {
        ++self::$cleanupCount;
    }
}
