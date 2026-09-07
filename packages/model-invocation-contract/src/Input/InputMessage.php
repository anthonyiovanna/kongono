<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Input;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;

/** Carries one bounded immutable message without policy or execution semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class InputMessage
{
    private string $content;

    public function __construct(
        private InputRole $role,
        private ModelVisibility $visibility,
        string $content,
    ) {
        $this->content = ContractGuard::inputContent($content);
    }

    public function role(): InputRole
    {
        return $this->role;
    }

    public function visibility(): ModelVisibility
    {
        return $this->visibility;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function equals(self $other): bool
    {
        return $this->role === $other->role && $this->visibility === $other->visibility && $this->content === $other->content;
    }
}
