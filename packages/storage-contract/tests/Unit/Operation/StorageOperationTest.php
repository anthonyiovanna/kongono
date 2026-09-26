<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Operation;

use Kongono\StorageContract\Operation\StorageOperation;
use PHPUnit\Framework\TestCase;

final class StorageOperationTest extends TestCase
{
    public function testOperationVocabularyIsExact(): void
    {
        self::assertSame(['read', 'create', 'replace', 'delete', 'list'], array_column(StorageOperation::cases(), 'value'));
    }
}
