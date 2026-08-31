<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Implementation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Internal\ExtensionGrantPlanValidator;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Selects one exact application-owned implementation symbol without discovery or trust inference. */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedExtensionImplementation
{
    public function __construct(
        private ExtensionIdentity $identity,
        private ExtensionVersion $version,
        private string $className,
    ) {
        ExtensionGrantPlanValidator::symbol($className);
    }

    public function identity(): ExtensionIdentity
    {
        return $this->identity;
    }
    public function version(): ExtensionVersion
    {
        return $this->version;
    }
    public function className(): string
    {
        return $this->className;
    }
}
