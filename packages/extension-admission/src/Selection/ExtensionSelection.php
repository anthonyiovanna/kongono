<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Selection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Identifies one exact application-selected extension without fallback or priority. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionSelection
{
    public function __construct(
        private ExtensionIdentity $identity,
        private ExtensionVersion $version,
    ) {}

    public function identity(): ExtensionIdentity
    {
        return $this->identity;
    }

    public function version(): ExtensionVersion
    {
        return $this->version;
    }
}
