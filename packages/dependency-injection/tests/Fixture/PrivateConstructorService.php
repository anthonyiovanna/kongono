<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class PrivateConstructorService implements EmptyServiceInterface
{
    private function __construct() {}
}
