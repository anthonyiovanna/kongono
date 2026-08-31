<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Loading;

use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;
use Kongono\ExtensionInspection\Loading\ExtensionInspectionLoader;
use PHPUnit\Framework\TestCase;

final class ExtensionInspectionLoaderTest extends TestCase
{
    public function testMissingInputsAreRejectedWithoutPathDisclosure(): void
    {
        $private = 'private-' . bin2hex(random_bytes(8));
        try {
            (new ExtensionInspectionLoader())->load(__DIR__ . '/' . $private . '.json', __DIR__ . '/' . $private . '.php');
            self::fail('Expected load rejection.');
        } catch (ExtensionInspectionLoadException $failure) {
            self::assertStringNotContainsString($private, (string) $failure);
        }
    }
}
