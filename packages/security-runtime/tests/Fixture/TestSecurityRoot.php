<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Tests\Fixture;

use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\SecurityOperationRoot;

final readonly class TestSecurityRoot implements SecurityOperationRoot
{
    public function __construct(
        public int $ordinal,
        public SecurityContext $securityContext,
    ) {}
}
