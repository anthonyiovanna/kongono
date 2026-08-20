<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;

/** Reports fail-closed refusal by an irreversibly retired runtime adapter. */
#[Api(ApiStatus::Experimental)]
final class RetiredWebRuntimeException extends WebRuntimeException
{
    public function __construct()
    {
        parent::__construct(WebRuntimeDiagnostic::RuntimeRetired);
    }
}
