<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Diagnostic;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class AgentDeclarationBuildDiagnosticTest extends TestCase
{
    public function testExactIdentifiersAndSafeBoundedSchemas(): void
    {
        self::assertSame([
            'KGN-AGENT-DECLARATION-1310',
            'KGN-AGENT-DECLARATION-1311',
            'KGN-AGENT-DECLARATION-1312',
            'KGN-AGENT-DECLARATION-1313',
            'KGN-AGENT-DECLARATION-1314',
        ], array_column(AgentDeclarationBuildDiagnostic::cases(), 'value'));

        foreach (AgentDeclarationBuildDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame('violation', $fields[0]->key());
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertContains($field->key(), ['violation', 'entry_count', 'entry_position', 'artifact_bytes']);
            }
        }
    }
}
