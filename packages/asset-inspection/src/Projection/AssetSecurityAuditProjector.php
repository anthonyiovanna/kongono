<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\AssetInspectionProjectionException;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use Throwable;

/** Constructs fixed Safe audit evidence without recording or certification. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetSecurityAuditProjector
{
    public function project(AssetInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $attributes = $this->attributes($inspection);

            return new SecurityAuditEvidence($context, $this->definition($attributes), SecurityAuditOutcome::Succeeded, new AttributeSet($attributes));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AssetInspectionProjectionException) {
                throw $failure;
            }
            throw AssetInspectionProjectionException::failed('security_audit', $failure);
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
            new SignalIdentity('kongono.asset.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema($fields),
        );
    }

    /** @return list<SignalAttribute> */
    private function attributes(AssetInspection $inspection): array
    {
        $attributes = [
            $this->text('inspection.schema', AssetInspection::SCHEMA),
            $this->integer('inspection.schema_version', AssetInspection::VERSION),
            $this->text('inspection.producer_kind', $inspection->producerKind()),
        ];
        foreach ($inspection->counts() as $key => $value) {
            $attributes[] = $this->integer('inspection.' . $key, $value);
        }
        foreach ($inspection->flags() as $key => $value) {
            $attributes[] = $this->boolean('inspection.' . $key, $value);
        }
        $attributes[] = $this->text('inspection.scope', AssetInspection::SCOPE);
        $attributes[] = $this->text('inspection.posture', AssetInspection::POSTURE);
        $attributes[] = $this->text('inspection.disclosure', AssetInspection::DISCLOSURE);
        $attributes[] = $this->text('inspection.authority', AssetInspection::AUTHORITY);
        $attributes[] = $this->text('inspection.projection', 'constructed-not-recorded');

        return $attributes;
    }

    private function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }

    private function boolean(string $key, bool $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::boolean($value));
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
}
