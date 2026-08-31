<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExtensionAdmission\Diagnostic\ExtensionAdmissionDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionAdmissionDiagnosticTest extends TestCase
{
    public function testTheSoleDiagnosticIsFixedBoundedAndClassified(): void
    {
        $diagnostic = ExtensionAdmissionDiagnostic::AdmissionFailed;
        self::assertSame('KGN-EXTENSION-ADMISSION-1201', $diagnostic->value);
        self::assertSame(DiagnosticDisclosure::Safe, $diagnostic->contextSchema()->fields()[0]->disclosure());
        self::assertSame(DiagnosticDisclosure::Protected, $diagnostic->contextSchema()->fields()[3]->disclosure());
    }
}
