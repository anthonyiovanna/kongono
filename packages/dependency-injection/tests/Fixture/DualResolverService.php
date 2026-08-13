<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

use Kongono\DependencyInjection\Contract\ResolverInterface;
use RuntimeException;

final class DualResolverService implements DualResolverServiceInterface, ResolverInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T
     */
    public function resolve(string $contract): object
    {
        throw new RuntimeException($contract);
    }
}
