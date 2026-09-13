<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one exact source identity and canonical logical-view set. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewSourceSet
{
    /** @var list<ViewSource> */
    private array $views;

    /** @param list<ViewSource> $views */
    public function __construct(private ViewSourceIdentity $identity, array $views)
    {
        $this->views = [...$views];
    }

    public function identity(): ViewSourceIdentity
    {
        return $this->identity;
    }
    /** @return list<ViewSource> */
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
        if (!$this->identity->equals($other->identity) || count($this->views) !== count($other->views)) {
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
