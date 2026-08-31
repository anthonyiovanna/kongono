<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Exception;

use Kongono\ExtensionAdmission\Exception\ExceptionInterface;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionAdmissionExceptionTest extends TestCase
{
    public function testFailurePreservesCauseAndDoesNotDiscloseRejectedValues(): void
    {
        $cause = new RuntimeException('private-cause-sentinel');
        $failure = InvalidExtensionAdmissionException::invalid('selection_missing', 'selection', previous: $cause);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame($cause, $failure->getPrevious());
        self::assertStringNotContainsString('private-cause-sentinel', $failure->getMessage());
        self::assertStringNotContainsString('private-cause-sentinel', (string) $failure);
    }
}
