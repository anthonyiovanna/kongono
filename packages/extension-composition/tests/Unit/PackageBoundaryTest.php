<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class PackageBoundaryTest extends TestCase
{
    public function testExactInventoryDependenciesStatusesAndProhibitedSurface(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertCount(23, $source = self::phpFiles($package . '/src'));
        self::assertSame([
            'Artifact/GeneratedExtensionWiringArtifact.php',
            'Diagnostic/ExtensionCompositionDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionCompositionException.php',
            'Exception/ExtensionWiringPublicationException.php',
            'Exception/InvalidExtensionGrantPlanException.php',
            'Exception/InvalidGeneratedExtensionWiringException.php',
            'Generated/ExtensionWiringGenerator.php',
            'Generated/ExtensionWiringPublisher.php',
            'Generated/ExtensionWiringValidator.php',
            'Grant/CapabilityDecision.php',
            'Grant/CapabilityDisposition.php',
            'Grant/CapabilityProviderLifetime.php',
            'Identity/GrantPlanIdentity.php',
            'Implementation/TrustedExtensionImplementation.php',
            'Internal/CanonicalJson.php',
            'Internal/ExtensionGrantPlanValidator.php',
            'Internal/GeneratedExtensionWiringEmitter.php',
            'Internal/GeneratedExtensionWiringImplementation.php',
            'Internal/PathGuard.php',
            'Plan/ExtensionGrantPlan.php',
            'Runtime/GeneratedExtensionApplicationBoundary.php',
            'Runtime/GeneratedExtensionOperationBoundary.php',
        ], $source);
        self::assertSame([
            'Unit/Diagnostic/ExtensionCompositionDiagnosticTest.php',
            'Unit/Exception/ExtensionCompositionExceptionTest.php',
            'Unit/Generated/ExtensionWiringGeneratorTest.php',
            'Unit/Generated/ExtensionWiringPublicationTest.php',
            'Unit/Generated/ExtensionWiringValidationTest.php',
            'Unit/Grant/CapabilityDecisionTest.php',
            'Unit/Implementation/TrustedExtensionImplementationTest.php',
            'Unit/PackageBoundaryTest.php',
            'Unit/Plan/ExtensionGrantPlanTest.php',
            'Unit/TrustedExtensionWiringAdversarialTest.php',
        ], self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/extension-composition', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/extension-admission' => '*',
            'kongono/extension-build' => '*', 'kongono/extension-contract' => '*', 'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);

        $public = ['Kongono\\ExtensionComposition\\Exception\\ExceptionInterface'];
        $experimental = [
            'Kongono\\ExtensionComposition\\Artifact\\GeneratedExtensionWiringArtifact', 'Kongono\\ExtensionComposition\\Exception\\ExtensionCompositionException',
            'Kongono\\ExtensionComposition\\Exception\\InvalidExtensionGrantPlanException', 'Kongono\\ExtensionComposition\\Exception\\InvalidGeneratedExtensionWiringException',
            'Kongono\\ExtensionComposition\\Exception\\ExtensionWiringPublicationException', 'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringGenerator',
            'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringPublisher', 'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringValidator',
            'Kongono\\ExtensionComposition\\Grant\\CapabilityDecision', 'Kongono\\ExtensionComposition\\Grant\\CapabilityDisposition',
            'Kongono\\ExtensionComposition\\Grant\\CapabilityProviderLifetime', 'Kongono\\ExtensionComposition\\Identity\\GrantPlanIdentity',
            'Kongono\\ExtensionComposition\\Implementation\\TrustedExtensionImplementation', 'Kongono\\ExtensionComposition\\Plan\\ExtensionGrantPlan',
            'Kongono\\ExtensionComposition\\Runtime\\GeneratedExtensionApplicationBoundary', 'Kongono\\ExtensionComposition\\Runtime\\GeneratedExtensionOperationBoundary',
        ];
        $internal = [
            'Kongono\\ExtensionComposition\\Diagnostic\\ExtensionCompositionDiagnostic', 'Kongono\\ExtensionComposition\\Internal\\CanonicalJson',
            'Kongono\\ExtensionComposition\\Internal\\ExtensionGrantPlanValidator', 'Kongono\\ExtensionComposition\\Internal\\GeneratedExtensionWiringEmitter',
            'Kongono\\ExtensionComposition\\Internal\\GeneratedExtensionWiringImplementation', 'Kongono\\ExtensionComposition\\Internal\\PathGuard',
        ];
        foreach ([ApiStatus::Public->value => $public, ApiStatus::Experimental->value => $experimental, ApiStatus::Internal->value => $internal] as $status => $classes) {
            foreach ($classes as $class) {
                $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
                self::assertCount(1, $attributes);
                self::assertSame($status, $attributes[0]->newInstance()->status->value);
            }
        }
        $combined = '';
        foreach ($source as $relative) {
            $combined .= file_get_contents($package . '/src/' . $relative);
        }
        foreach (['Kongono\\Composition', 'Kongono\\ApplicationGraph', 'Security\\Permit', 'Principal'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combined);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files, SORT_STRING);
        return $files;
    }
}
