<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Diagnostic;

use Kongono\AgentAdmission\Diagnostic\AgentAdmissionDiagnostic;
use PHPUnit\Framework\TestCase;

final class AgentAdmissionDiagnosticTest extends TestCase
{
    public function testDiagnosticRegistryIsExactAndContextsAreBounded(): void
    {
        self::assertSame([
            'KGN-AGENT-ADMISSION-1320',
            'KGN-AGENT-ADMISSION-1321',
            'KGN-AGENT-ADMISSION-1322',
            'KGN-AGENT-ADMISSION-1323',
        ], array_column(AgentAdmissionDiagnostic::cases(), 'value'));
        foreach (AgentAdmissionDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame('safe', $diagnostic->context(['violation' => 'fixture'])->fields()[0]->disclosure()->value);
        }
    }
}
