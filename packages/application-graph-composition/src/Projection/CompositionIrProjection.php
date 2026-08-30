<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphComposition\Exception\InvalidCompositionGraphProjectionException;

/** Holds one detached canonical Composition projection without retaining its artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class CompositionIrProjection
{
    /** @var list<GraphNodeRecord> */
    private array $nodeRecords;

    /** @var list<GraphRelationRecord> */
    private array $relationRecords;

    /**
     * @param array<array-key, mixed> $nodeRecords
     * @param array<array-key, mixed> $relationRecords
     */
    public function __construct(array $nodeRecords, array $relationRecords)
    {
        $this->nodeRecords = self::records($nodeRecords, GraphNodeRecord::class, 'node');
        $this->relationRecords = self::records($relationRecords, GraphRelationRecord::class, 'relation');
    }

    /** @return list<GraphNodeRecord> */
    public function nodeRecords(): array
    {
        return [...$this->nodeRecords];
    }

    /** @return list<GraphRelationRecord> */
    public function relationRecords(): array
    {
        return [...$this->relationRecords];
    }

    /**
     * @template T of object
     * @param array<array-key, mixed> $records
     * @param class-string<T> $type
     * @return list<T>
     */
    private static function records(array $records, string $type, string $recordKind): array
    {
        if (!array_is_list($records)) {
            throw InvalidCompositionGraphProjectionException::conflict('records_not_list', $recordKind, count($records));
        }

        $validated = [];
        foreach ($records as $position => $record) {
            if (!$record instanceof $type) {
                throw InvalidCompositionGraphProjectionException::conflict('invalid_record', $recordKind, count($records), $position);
            }
            $validated[] = $record;
        }

        return $validated;
    }
}
