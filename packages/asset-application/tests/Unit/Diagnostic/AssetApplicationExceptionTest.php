<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Tests\Unit\Diagnostic;

use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Kongono\AssetApplication\Exception\InvalidAssetApplicationException;
use PHPUnit\Framework\TestCase;

final class AssetApplicationExceptionTest extends TestCase
{
    public function testDiagnosticIsBoundedAndNondisclosing(): void
    {
        $secret = 'C:/private/' . bin2hex(random_bytes(8));
        $previous = new \RuntimeException($secret);
        $failure = InvalidAssetApplicationException::invalid('invalid_schema', $previous);
        self::assertStringStartsWith('KGN-ASSET-APPLICATION-', $failure->diagnosticId());
        self::assertSame($previous, $failure->getPrevious());
        self::assertStringNotContainsString($secret, (string) $failure);
        self::assertStringNotContainsString($secret, json_encode($failure->diagnosticContext(), JSON_THROW_ON_ERROR));
        self::assertSame([
            'KGN-ASSET-APPLICATION-1940',
            'KGN-ASSET-APPLICATION-1941',
            'KGN-ASSET-APPLICATION-1942',
            'KGN-ASSET-APPLICATION-1943',
            'KGN-ASSET-APPLICATION-1944',
            'KGN-ASSET-APPLICATION-1945',
        ], array_column(AssetApplicationDiagnostic::cases(), 'value'));
        foreach (AssetApplicationDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertNotSame('', json_encode($diagnostic->context('stage'), JSON_THROW_ON_ERROR));
        }
    }
}
