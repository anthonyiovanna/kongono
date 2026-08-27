<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Phase 9B identity-access failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum IdentityAccessDiagnostic: string
{
    case Principal = 'KGN-ACCESS-1001';
    case Authentication = 'KGN-ACCESS-1002';
    case Authorization = 'KGN-ACCESS-1003';
    case Resource = 'KGN-ACCESS-1004';
    case SecurityContext = 'KGN-ACCESS-1005';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Construct neutral identity and access values',
                'Identity and access contract failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'identity-access',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Principal => 'principal',
            self::Authentication => 'authentication',
            self::Authorization => 'authorization',
            self::Resource => 'resource',
            self::SecurityContext => 'security_context',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Principal => 'Invalid principal value',
            self::Authentication => 'Invalid authentication outcome',
            self::Authorization => 'Invalid authorization value',
            self::Resource => 'Invalid resource value',
            self::SecurityContext => 'Invalid security context',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Principal => 'Provide an exact principal value within the documented neutral bounds.',
            self::Authentication => 'Construct one documented authentication outcome shape.',
            self::Authorization => 'Provide an exact action or authorization value within the documented neutral bounds.',
            self::Resource => 'Provide an exact resource value within the documented neutral bounds.',
            self::SecurityContext => 'Construct an explicit anonymous or authenticated security context.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Principal => 'correct_principal',
            self::Authentication => 'correct_authentication',
            self::Authorization => 'correct_authorization',
            self::Resource => 'correct_resource',
            self::SecurityContext => 'correct_security_context',
        };
    }
}
