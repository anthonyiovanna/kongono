<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewHttp\Internal\ViewHttpGuard;

/** Carries one validated caller-owned response header. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewResponseHeader
{
    /** @var list<string> */
    private array $values;

    /** @param array<array-key, mixed> $values */
    public function __construct(private string $name, array $values)
    {
        $this->values = ViewHttpGuard::header($name, $values);
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<string> */
    public function values(): array
    {
        return $this->values;
    }
}
