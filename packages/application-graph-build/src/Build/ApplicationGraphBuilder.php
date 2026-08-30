<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Internal\GraphConstructionValidator;

/** Admits one complete IR record set and returns its canonical Graph facts. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphBuilder
{
    public const int MAX_NODES = 65536;
    public const int MAX_RELATIONS = 262144;

    /**
     * @param array<array-key, mixed> $nodeRecords
     * @param array<array-key, mixed> $relationRecords
     */
    public function build(array $nodeRecords, array $relationRecords): AuthoritativeApplicationGraph
    {
        return (new GraphConstructionValidator())->construct($nodeRecords, $relationRecords);
    }
}
