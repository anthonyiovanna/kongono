<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class ZeroParameterService implements EmptyServiceInterface
{
    public function __construct() {}
}
