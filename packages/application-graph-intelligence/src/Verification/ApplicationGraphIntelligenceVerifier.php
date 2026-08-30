<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;

/** Independently compares every allowed view value without repair or rederivation. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphIntelligenceVerifier
{
    public function verify(
        ApplicationGraphArtifactInspection $inspection,
        ApplicationGraphIntelligenceView $view,
    ): ApplicationGraphIntelligenceVerification {
        $identityMatches = $inspection->artifactSha256() === $view->artifactSha256()
            && $inspection->graphIdentity()->value() === $view->graphIdentity()->value();
        $contractMatches = $this->sameInteger(
            $inspection->contractVersion()->value,
            $view->contractVersion()->value,
        );
        $totalsMatch = $inspection->nodeCount() === $view->nodeCount()
            && $inspection->relationCount() === $view->relationCount();

        $nodeVocabularyMatches = true;
        foreach (NodeType::cases() as $type) {
            $nodeVocabularyMatches = $nodeVocabularyMatches
                && $inspection->nodeTypeCount($type) === $view->nodeTypeCount($type);
        }

        $relationVocabularyMatches = true;
        foreach (RelationType::cases() as $type) {
            $relationVocabularyMatches = $relationVocabularyMatches
                && $inspection->relationTypeCount($type) === $view->relationTypeCount($type);
        }

        $visibilitySemanticsMatch = ApplicationGraphIntelligenceView::SCOPE === $view->scope()
            && ApplicationGraphIntelligenceView::VISIBILITY === $view->visibility()
            && ApplicationGraphIntelligenceView::CURRENTNESS === $view->currentness()
            && ApplicationGraphIntelligenceView::AUTHORITY === $view->authority()
            && ApplicationGraphIntelligenceView::UNCERTAINTIES === $view->uncertainties();

        $checks = [
            'identity-match' => $identityMatches,
            'contract-match' => $contractMatches,
            'totals-match' => $totalsMatch,
            'node-vocabulary-match' => $nodeVocabularyMatches,
            'relation-vocabulary-match' => $relationVocabularyMatches,
            'visibility-semantics-match' => $visibilitySemanticsMatch,
        ];
        $failures = [];
        foreach ($checks as $checkId => $passed) {
            if (!$passed) {
                $failures[] = $checkId . '-failed';
            }
        }

        return new ApplicationGraphIntelligenceVerification(
            [] === $failures ? ApplicationGraphIntelligenceVerdict::Pass : ApplicationGraphIntelligenceVerdict::Fail,
            $view->role(),
            $inspection->artifactSha256(),
            $inspection->graphIdentity(),
            $checks,
            $failures,
        );
    }

    private function sameInteger(int $expected, int $observed): bool
    {
        return $expected === $observed;
    }
}
