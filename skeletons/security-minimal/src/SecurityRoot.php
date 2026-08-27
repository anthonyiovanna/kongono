<?php

declare(strict_types=1);

namespace KongonoSecurityMinimal;

use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\SecurityOperationRoot;

/** Exposes the exact generated operation Security Context to typed application collaborators. */
final readonly class SecurityRoot implements SecurityOperationRoot
{
    public function __construct(private SecurityContext $context) {}

    public function context(): SecurityContext
    {
        return $this->context;
    }
}
