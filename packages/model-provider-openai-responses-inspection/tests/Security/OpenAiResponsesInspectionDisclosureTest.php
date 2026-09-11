<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Security;

use Kongono\ModelProviderOpenAiResponsesInspection\Derivation\OpenAiResponsesIntelligenceDeriver;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspectionRequest;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesObservationProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesSecurityAuditProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;
use Kongono\ModelProviderOpenAiResponsesInspection\Tests\Support\OpenAiResponsesInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesInspectionDisclosureTest extends TestCase
{
    public function testEverySerializedAndProjectedSurfaceOmitsRawAndArbitraryLabels(): void
    {
        $fixture = OpenAiResponsesInspectionFactory::fixture();
        $profile = OpenAiResponsesInspectionFactory::profile($fixture);
        $inspection = (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest(
            $profile,
            OpenAiResponsesInspectionFactory::runtime($fixture),
        ));
        $view = (new OpenAiResponsesIntelligenceDeriver())->derive($inspection, OpenAiResponsesInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new OpenAiResponsesObservationProjector())->project($inspection, $context);
        $audit = (new OpenAiResponsesSecurityAuditProjector())->project($inspection, $context);
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        foreach ([OpenAiResponsesInspectionFactory::SECRET, OpenAiResponsesInspectionFactory::RESULT_SECRET, 'openai-test-model', 'profile.openai.responses', 'plan.openai.responses', 'invocation.openai.responses', 'receipt.openai.inspection', 'result.openai.inspection', '/api?'] as $secret) {
            self::assertStringNotContainsString($secret, $surface);
        }
    }
}
