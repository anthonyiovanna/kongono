<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Selection;

use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionAdmissionPlanTest extends TestCase
{
    public function testEmptyPlanIsValidAndListsAreFresh(): void
    {
        $plan = new ExtensionAdmissionPlan(new DeclarationSetIdentity(str_repeat('a', 64)), [], []);
        self::assertSame([], $plan->selections());
        self::assertSame([], $plan->conflictResolutions());
    }

    public function testSelectionsMustBeCanonicalUniqueLists(): void
    {
        $this->expectException(InvalidExtensionAdmissionException::class);
        new ExtensionAdmissionPlan(new DeclarationSetIdentity(str_repeat('a', 64)), [
            new ExtensionSelection(new ExtensionIdentity('zeta/one'), new ExtensionVersion('v1')),
            new ExtensionSelection(new ExtensionIdentity('alpha/one'), new ExtensionVersion('v1')),
        ], []);
    }

    public function testSelectionListShapeTypeDuplicateAndBoundFailuresAreDeterministic(): void
    {
        $identity = new DeclarationSetIdentity(str_repeat('a', 64));
        $selection = new ExtensionSelection(new ExtensionIdentity('acme/one'), new ExtensionVersion('v1'));
        $cases = [
            ['entry' => $selection],
            ['raw'],
            [$selection, $selection],
            array_fill(0, 65, $selection),
        ];

        foreach ($cases as $case) {
            try {
                new ExtensionAdmissionPlan($identity, $case, []);
                self::fail('Malformed selection input was accepted.');
            } catch (InvalidExtensionAdmissionException $first) {
                try {
                    new ExtensionAdmissionPlan($identity, $case, []);
                    self::fail('Malformed selection input was accepted on repeat.');
                } catch (InvalidExtensionAdmissionException $second) {
                    self::assertSame((string) $first, (string) $second);
                }
            }
        }
    }

    public function testResolutionListsMustBeCanonicalUniqueTypedListsWithinTheExactBound(): void
    {
        $identity = new DeclarationSetIdentity(str_repeat('a', 64));
        $alpha = new ContributionConflictResolution(new ContributionIdentity('alpha'), new ExtensionIdentity('acme/one'));
        $beta = new ContributionConflictResolution(new ContributionIdentity('beta'), new ExtensionIdentity('acme/two'));
        $cases = [
            ['entry' => $alpha],
            ['raw'],
            [$alpha, $alpha],
            [$beta, $alpha],
            array_fill(0, 8193, $alpha),
        ];

        foreach ($cases as $case) {
            $this->expectPlanFailure($identity, $case);
        }
    }

    /** @param array<array-key, mixed> $resolutions */
    private function expectPlanFailure(DeclarationSetIdentity $identity, array $resolutions): void
    {
        try {
            new ExtensionAdmissionPlan($identity, [], $resolutions);
            self::fail('Malformed resolution input was accepted.');
        } catch (InvalidExtensionAdmissionException $failure) {
            self::assertSame('KGN-EXTENSION-ADMISSION-1201', $failure->diagnosticId());
        }
    }
}
