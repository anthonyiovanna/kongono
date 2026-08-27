<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityEnforcement\Diagnostic\SecurityEnforcementDiagnostic;

/** Reports a fixed fail-closed enforcement outcome without retaining inputs. */
#[Api(ApiStatus::Experimental)]
final class ProtectedCapabilityEnforcementException extends SecurityEnforcementException
{
    private function __construct(SecurityEnforcementDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function contextMismatch(): self
    {
        return new self(SecurityEnforcementDiagnostic::ContextMismatch);
    }

    public static function actionMismatch(): self
    {
        return new self(SecurityEnforcementDiagnostic::ActionMismatch);
    }

    public static function resourceMismatch(): self
    {
        return new self(SecurityEnforcementDiagnostic::ResourceMismatch);
    }

    public static function decisionRejected(): self
    {
        return new self(SecurityEnforcementDiagnostic::DecisionRejected);
    }
}
