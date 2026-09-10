<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Diagnostic;

use Kongono\ExternalServiceApplication\Diagnostic\ExternalServiceApplicationDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExternalServiceApplicationDiagnosticTest extends TestCase
{
    public function testVocabularyIsClosedAndContextsAreSafe(): void
    {
        self::assertSame([
            'KGN-EXTERNAL-SERVICE-APPLICATION-1624',
            'KGN-EXTERNAL-SERVICE-APPLICATION-1625',
            'KGN-EXTERNAL-SERVICE-APPLICATION-1626',
            'KGN-EXTERNAL-SERVICE-APPLICATION-1627',
        ], array_map(static fn(ExternalServiceApplicationDiagnostic $diagnostic): string => $diagnostic->value, ExternalServiceApplicationDiagnostic::cases()));

        foreach (ExternalServiceApplicationDiagnostic::cases() as $diagnostic) {
            $field = ExternalServiceApplicationDiagnostic::InvalidProfile === $diagnostic ? 'violation' : 'stage';
            $context = $diagnostic->context([$field => 'closed_token']);
            self::assertSame($field, $context->fields()[0]->key());
            self::assertSame('closed_token', $context->fields()[0]->value());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
