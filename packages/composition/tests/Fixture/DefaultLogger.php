<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class DefaultLogger implements Logger
{
    public function close(): void {}
}
