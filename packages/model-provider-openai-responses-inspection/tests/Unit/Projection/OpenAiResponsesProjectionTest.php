<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Unit\Projection;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspectionRequest;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesObservationProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesSecurityAuditProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Tests\Support\OpenAiResponsesInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesProjectionTest extends TestCase
{
    public function testNeutralProjectionsAreSafeValuesAndDoNotClaimDeliveryOrSecurity(): void
    {
        $fixture = OpenAiResponsesInspectionFactory::fixture();
        $profile = OpenAiResponsesInspectionFactory::profile($fixture);
        $inspection = (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest(
            $profile,
            OpenAiResponsesInspectionFactory::runtime($fixture),
        ));
        $context = ObservabilityContext::uncorrelated();
        $observation = (new OpenAiResponsesObservationProjector())->project($inspection, $context);
        $audit = (new OpenAiResponsesSecurityAuditProjector())->project($inspection, $context);

        self::assertSame('kongono.openai-responses.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame('kongono.openai-responses.inspection.security', $audit->definition()->identity()->name());
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());

        $keys = array_map(static fn($attribute): string => $attribute->key()->value(), $observation->attributes()->attributes());
        foreach (['identity', 'sha256', 'provider', 'model', 'adapter', 'receipt', 'currency', 'metric', 'path', 'url', 'delivered', 'durable', 'compliant', 'secure'] as $forbidden) {
            self::assertSame([], array_filter($keys, static fn(string $key): bool => str_contains($key, $forbidden)));
        }
    }
}
