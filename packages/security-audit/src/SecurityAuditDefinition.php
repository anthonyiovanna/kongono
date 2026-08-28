<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\SignalIdentity;

/** Defines one exact audit identity, retention intent, and field schema. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityAuditDefinition
{
    public function __construct(
        private SignalIdentity $identity,
        private SecurityAuditRetentionIntent $retentionIntent,
        private SecurityAuditSchema $schema,
    ) {}

    public function identity(): SignalIdentity
    {
        return $this->identity;
    }

    public function retentionIntent(): SecurityAuditRetentionIntent
    {
        return $this->retentionIntent;
    }

    public function schema(): SecurityAuditSchema
    {
        return $this->schema;
    }
}
