<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Selection;

use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionSelectionTest extends TestCase
{
    public function testItPreservesOneExactIdentityAndVersion(): void
    {
        $identity = new ExtensionIdentity('acme/search');
        $version = new ExtensionVersion('v1');
        $selection = new ExtensionSelection($identity, $version);

        self::assertSame($identity, $selection->identity());
        self::assertSame($version, $selection->version());
    }
}
