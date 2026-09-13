<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries the canonical ordinal-sorted compiled view set. */
#[Api(ApiStatus::Internal)]
final readonly class CompiledViewModel
{
    /** @var list<CompiledViewDefinition> */
    private array $views;

    /** @param list<CompiledViewDefinition> $views */
    public function __construct(array $views)
    {
        $this->views = [...$views];
    }

    /** @return list<CompiledViewDefinition> */
    public function views(): array
    {
        return $this->views;
    }
    public function viewCount(): int
    {
        return count($this->views);
    }

    public function equals(self $other): bool
    {
        if (count($this->views) !== count($other->views)) {
            return false;
        }
        foreach ($this->views as $position => $view) {
            if (!$view->equals($other->views[$position])) {
                return false;
            }
        }

        return true;
    }
}
