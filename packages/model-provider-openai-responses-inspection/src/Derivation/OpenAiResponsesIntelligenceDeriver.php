<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidenceProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;

/** Derives one deterministic evidence view without executing a developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesIntelligenceDeriver
{
    public function derive(
        OpenAiResponsesInspection $inspection,
        OpenAiResponsesInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): OpenAiResponsesIntelligenceView {
        return new OpenAiResponsesIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new OpenAiResponsesGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
