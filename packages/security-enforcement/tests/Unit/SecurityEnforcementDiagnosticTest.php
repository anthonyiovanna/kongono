<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Tests\Unit;

use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\SecurityEnforcement\Diagnostic\SecurityEnforcementDiagnostic;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SecurityEnforcementDiagnosticTest extends TestCase
{
    public function testDiagnosticRangeAndPresentationAreFixedAndNonDisclosing(): void
    {
        self::assertSame([
            'KGN-SECURITY-ENFORCEMENT-1001',
            'KGN-SECURITY-ENFORCEMENT-1002',
            'KGN-SECURITY-ENFORCEMENT-1003',
            'KGN-SECURITY-ENFORCEMENT-1004',
        ], array_column(SecurityEnforcementDiagnostic::cases(), 'value'));

        $secret = 'secret-principal-resource-action-policy.example';
        foreach ([
            ProtectedCapabilityEnforcementException::contextMismatch(),
            ProtectedCapabilityEnforcementException::actionMismatch(),
            ProtectedCapabilityEnforcementException::resourceMismatch(),
            ProtectedCapabilityEnforcementException::decisionRejected(),
        ] as $failure) {
            self::assertStringNotContainsString($secret, $failure->getMessage());
            self::assertStringNotContainsString($secret, (string) $failure);
            self::assertStringNotContainsString($secret, json_encode($failure->diagnosticContext(), JSON_THROW_ON_ERROR));
        }
    }

    public function testReceiptHasNoCapabilityExecutionSurface(): void
    {
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(AuthorizationDecisionReceipt::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
        );

        self::assertNotContains('execute', $methods);
        self::assertNotContains('enforce', $methods);
        self::assertNotContains('replay', $methods);
    }
}
