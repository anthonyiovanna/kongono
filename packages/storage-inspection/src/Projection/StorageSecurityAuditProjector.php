<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use Kongono\StorageInspection\Exception\StorageInspectionProjectionException;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Throwable;

/** Constructs fixed Safe audit evidence without recording or certification. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageSecurityAuditProjector
{
    public function project(StorageInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $observation = (new StorageObservationProjector())->project($inspection, $context);
            $attributes = $observation->attributes()->attributes();

            return new SecurityAuditEvidence($context, $this->definition($attributes), SecurityAuditOutcome::Succeeded, new AttributeSet($attributes));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof StorageInspectionProjectionException) {
                throw $failure;
            }
            throw StorageInspectionProjectionException::failed('security_audit', $failure);
        }
    }

    /** @param list<SignalAttribute> $attributes */
    private function definition(array $attributes): SecurityAuditDefinition
    {
        $fields = [];
        foreach ($attributes as $attribute) {
            $fields[] = new SecurityAuditField(
                $attribute->key(),
                $attribute->value()->kind(),
                DiagnosticDisclosure::Safe,
                true,
                [$attribute->value()],
            );
        }

        return new SecurityAuditDefinition(
            new SignalIdentity('kongono.storage.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema($fields),
        );
    }
}
