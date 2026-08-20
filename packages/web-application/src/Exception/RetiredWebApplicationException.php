<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;

/** Reports fail-closed refusal by an irreversibly retired application object. */
#[Api(ApiStatus::Experimental)]
final class RetiredWebApplicationException extends WebApplicationException
{
    public function __construct()
    {
        parent::__construct(WebApplicationDiagnostic::ApplicationRetired);
    }
}
