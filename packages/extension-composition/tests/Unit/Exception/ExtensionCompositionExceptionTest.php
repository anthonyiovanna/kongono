<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Exception;

use Kongono\ExtensionComposition\Exception\ExceptionInterface;
use Kongono\ExtensionComposition\Exception\ExtensionWiringPublicationException;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
use Kongono\ExtensionComposition\Exception\InvalidGeneratedExtensionWiringException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionCompositionExceptionTest extends TestCase
{
    public function testFamiliesPreserveCauseIdentityAndDiscloseNoPrivateSentinel(): void
    {
        $sentinel = 'C:\\private\\secret-provider.php password=hunter2';
        $cause = new RuntimeException($sentinel);
        $exceptions = [
            InvalidExtensionGrantPlanException::invalid($sentinel, 4, 2, $cause),
            InvalidGeneratedExtensionWiringException::invalid($sentinel, $cause),
            ExtensionWiringPublicationException::failed($sentinel, $cause),
        ];
        foreach ($exceptions as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $exception->diagnosticId());
            self::assertStringNotContainsString($sentinel, $exception->getMessage());
            self::assertStringNotContainsString($sentinel, (string) $exception);
            self::assertSame(0, $exception->getCode());
        }
    }
}
