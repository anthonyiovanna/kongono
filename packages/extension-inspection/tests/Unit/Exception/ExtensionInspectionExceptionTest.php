<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Exception;

use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;
use PHPUnit\Framework\TestCase;

final class ExtensionInspectionExceptionTest extends TestCase
{
    public function testStringificationIsDeterministicAndNondisclosing(): void
    {
        $failure = ExtensionInspectionLoadException::declaration('private-caller-value');
        self::assertStringContainsString('KGN-EXTENSION-INSPECTION-1002', (string) $failure);
        self::assertStringNotContainsString('private-caller-value', (string) $failure);
    }
}
