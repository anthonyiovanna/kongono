<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Admission;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Exception\UnresolvedExtensionConflictException;
use Kongono\ExtensionAdmission\Internal\AdmissionGuard;
use Kongono\ExtensionAdmission\Internal\AdmissionProjector;
use Kongono\ExtensionAdmission\Internal\CanonicalAdmission;
use Kongono\ExtensionAdmission\Internal\GraphContributionValidator;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Throwable;

/** Proves exact current selection, conflicts, attribution, and complete Graph IR structure. */
#[Api(ApiStatus::Experimental)]
final class ExtensionAdmissionBuilder
{
    /**
     * @param array<array-key, mixed> $declarations
     * @param array<array-key, mixed> $contributionBundles
     */
    public function build(
        array $declarations,
        ExtensionAdmissionPlan $plan,
        array $contributionBundles,
    ): AdmittedExtensionGraphInput {
        try {
            $declarations = AdmissionGuard::declarations($declarations);
            $artifact = (new ExtensionDeclarationArtifactBuilder())->build($declarations);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidExtensionAdmissionException::invalid('declaration_set_mismatch', 'declaration', previous: $failure);
        }
        if (!$artifact->declarationSetIdentity()->equals($plan->declarationSetIdentity())) {
            throw InvalidExtensionAdmissionException::invalid('declaration_set_mismatch', 'declaration');
        }

        $bundles = AdmissionGuard::contributions($contributionBundles);
        /** @var array<string, ExtensionDeclaration> $declarationsByIdentity */
        $declarationsByIdentity = [];
        foreach ($declarations as $declaration) {
            $declarationsByIdentity[$declaration->identity()->value()] = $declaration;
        }

        /** @var array<string, ExtensionDeclaration> $selected */
        $selected = [];
        $admittedExtensions = [];
        foreach ($plan->selections() as $selection) {
            $identity = $selection->identity()->value();
            $declaration = $declarationsByIdentity[$identity] ?? null;
            if (null === $declaration) {
                throw InvalidExtensionAdmissionException::invalid('selection_missing', 'selection');
            }
            if (!$selection->version()->equals($declaration->version())) {
                throw InvalidExtensionAdmissionException::invalid('selection_version_mismatch', 'selection');
            }
            $selected[$identity] = $declaration;
            $admittedExtensions[] = new AdmittedExtension(
                $declaration->identity(),
                $declaration->version(),
                array_map(static fn(ContributionDeclaration $contribution) => $contribution->identity(), $declaration->contributions()),
            );
        }

        /** @var array<string, ExtensionGraphContribution> $bundlesByKey */
        $bundlesByKey = [];
        foreach ($bundles as $bundle) {
            $key = $bundle->extensionIdentity()->value() . "\0" . $bundle->contributionIdentity()->value();
            $bundlesByKey[$key] = $bundle;
        }

        /** @var array<string, list<string>> $claimants */
        $claimants = [];
        /** @var array<string, ExtensionGraphContribution> $validatedBundles */
        $validatedBundles = [];
        $validator = new GraphContributionValidator();
        foreach ($selected as $extensionIdentity => $declaration) {
            foreach ($declaration->contributions() as $contribution) {
                $contributionIdentity = $contribution->identity()->value();
                $key = $extensionIdentity . "\0" . $contributionIdentity;
                $bundle = $bundlesByKey[$key] ?? null;
                if (null === $bundle) {
                    throw InvalidExtensionAdmissionException::invalid('bundle_missing', 'contribution');
                }
                $validator->validate($declaration, $contribution, $bundle);
                $validatedBundles[$key] = $bundle;
                $claimants[$contributionIdentity] ??= [];
                $claimants[$contributionIdentity][] = $extensionIdentity;
            }
        }
        if (count($validatedBundles) !== count($bundles)) {
            throw InvalidExtensionAdmissionException::invalid('bundle_extra', 'contribution');
        }

        $resolutions = [];
        foreach ($plan->conflictResolutions() as $resolution) {
            $identity = $resolution->contributionIdentity()->value();
            $extensions = $claimants[$identity] ?? null;
            if (null === $extensions) {
                throw UnresolvedExtensionConflictException::conflict('unknown_resolution');
            }
            if (count($extensions) < 2) {
                throw UnresolvedExtensionConflictException::conflict('unnecessary_resolution');
            }
            $winner = $resolution->winningExtensionIdentity()->value();
            if (!in_array($winner, $extensions, true)) {
                throw UnresolvedExtensionConflictException::conflict('nonclaimant_winner');
            }
            $resolutions[$identity] = $winner;
        }

        ksort($claimants, SORT_STRING);
        $winningBundles = [];
        $winningContributionIdentities = [];
        foreach ($claimants as $contributionIdentity => $extensions) {
            if (count($extensions) > 1 && !isset($resolutions[$contributionIdentity])) {
                throw UnresolvedExtensionConflictException::conflict('missing_resolution');
            }
            $winner = count($extensions) > 1 ? $resolutions[$contributionIdentity] : $extensions[0];
            $winningBundles[] = $validatedBundles[$winner . "\0" . $contributionIdentity];
            $winningContributionIdentities[] = $contributionIdentity;
        }
        if (count($resolutions) !== count($plan->conflictResolutions())) {
            throw UnresolvedExtensionConflictException::conflict('unknown_resolution');
        }

        [$nodes, $relations] = (new AdmissionProjector())->project($winningBundles);
        $identity = CanonicalAdmission::identity(
            $artifact->declarationSetIdentity(),
            $admittedExtensions,
            $plan->conflictResolutions(),
            $winningContributionIdentities,
            $nodes,
            $relations,
        );

        return new AdmittedExtensionGraphInput(
            $artifact->declarationSetIdentity(),
            $identity,
            [...$admittedExtensions],
            $plan->conflictResolutions(),
            $nodes,
            $relations,
        );
    }
}
