<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores one detached, ordered entry from the closed source inventory. */
#[Api(ApiStatus::Internal)]
final readonly class KnowledgeSourceDefinition
{
    public function __construct(
        public string $path,
        public KnowledgeSourceAuthority $authority,
    ) {}
}
