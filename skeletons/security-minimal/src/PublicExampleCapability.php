<?php

declare(strict_types=1);

namespace KongonoSecurityMinimal;

use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\SecurityEnforcement\ProtectedCapability;

/** @implements ProtectedCapability<string> */
final class PublicExampleCapability implements ProtectedCapability
{
    private bool $executed = false;

    public function action(): Action
    {
        return new Action('public.example.read');
    }

    public function resource(): ResourceReference
    {
        return new ResourceReference(new ResourceType('public.example'), new ResourceId('fixed-public-result'));
    }

    public function execute(): string
    {
        $this->executed = true;

        return 'public-example-ok';
    }

    public function wasExecuted(): bool
    {
        return $this->executed;
    }
}
