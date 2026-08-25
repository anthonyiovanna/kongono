<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Fixture;

use Kongono\DatabaseApplication\DataOperationRoot;

final readonly class TestRoot implements DataOperationRoot
{
    public function __construct(public int $ordinal) {}
}
