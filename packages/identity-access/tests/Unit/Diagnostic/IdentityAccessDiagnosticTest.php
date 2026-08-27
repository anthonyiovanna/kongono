<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\IdentityAccess\Diagnostic\IdentityAccessDiagnostic;
use PHPUnit\Framework\TestCase;

final class IdentityAccessDiagnosticTest extends TestCase
{
    public function testClosedDiagnosticVocabularyAndSafeContextAreExact(): void
    {
        $expected = [
            [IdentityAccessDiagnostic::Principal, 'KGN-ACCESS-1001', 'principal', 'Invalid principal value', 'Provide an exact principal value within the documented neutral bounds.'],
            [IdentityAccessDiagnostic::Authentication, 'KGN-ACCESS-1002', 'authentication', 'Invalid authentication outcome', 'Construct one documented authentication outcome shape.'],
            [IdentityAccessDiagnostic::Authorization, 'KGN-ACCESS-1003', 'authorization', 'Invalid authorization value', 'Provide an exact action or authorization value within the documented neutral bounds.'],
            [IdentityAccessDiagnostic::Resource, 'KGN-ACCESS-1004', 'resource', 'Invalid resource value', 'Provide an exact resource value within the documented neutral bounds.'],
            [IdentityAccessDiagnostic::SecurityContext, 'KGN-ACCESS-1005', 'security_context', 'Invalid security context', 'Construct an explicit anonymous or authenticated security context.'],
        ];

        self::assertCount(5, IdentityAccessDiagnostic::cases());
        foreach ($expected as [$diagnostic, $id, $category, $title, $remediation]) {
            self::assertSame($id, $diagnostic->value);
            self::assertSame($category, $diagnostic->category());
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());

            $context = $diagnostic->context();
            self::assertSame('Construct neutral identity and access values', $context->operation());
            self::assertSame('Identity and access contract failures must remain bounded and non-disclosing', $context->violatedInvariant());
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $context->fields()));
            $fieldValues = [];
            foreach (array_slice($context->fields(), 0, 2) as $valueField) {
                $value = $valueField->value();
                self::assertIsString($value);
                $fieldValues[] = $value;
            }
            self::assertSame(['identity-access', $category], $fieldValues);
            foreach ($context->fields() as $contextField) {
                self::assertSame(DiagnosticDisclosure::Safe, $contextField->disclosure());
            }
        }
    }
}
