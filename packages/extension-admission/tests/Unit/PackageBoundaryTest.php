<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PackageBoundaryTest extends TestCase
{
    public function testManifestDependenciesAndExactInventoryAreBounded(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph-build' => '*',
            'kongono/application-graph-contract' => '*',
            'kongono/diagnostics' => '*',
            'kongono/extension-build' => '*',
            'kongono/extension-contract' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertCount(18, $this->phpFiles($root . '/src'));
        self::assertCount(10, $this->phpFiles($root . '/tests'));
    }

    /** @param class-string $class */
    #[DataProvider('statuses')]
    public function testEveryProductionTypeHasItsExactStatus(string $class, ApiStatus $status): void
    {
        $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
        self::assertCount(1, $attributes);
        self::assertSame($status, $attributes[0]->newInstance()->status);
    }

    /** @return iterable<string, array{class-string, ApiStatus}> */
    public static function statuses(): iterable
    {
        $public = ['Kongono\\ExtensionAdmission\\Exception\\ExceptionInterface'];
        $internal = ['Kongono\\ExtensionAdmission\\Diagnostic\\ExtensionAdmissionDiagnostic', 'Kongono\\ExtensionAdmission\\Internal\\AdmissionGuard', 'Kongono\\ExtensionAdmission\\Internal\\CanonicalAdmission', 'Kongono\\ExtensionAdmission\\Internal\\GraphContributionValidator', 'Kongono\\ExtensionAdmission\\Internal\\AdmissionProjector'];
        $all = [
            'Kongono\\ExtensionAdmission\\Admission\\AdmittedExtension', 'Kongono\\ExtensionAdmission\\Admission\\AdmittedExtensionGraphInput', 'Kongono\\ExtensionAdmission\\Admission\\ExtensionAdmissionBuilder',
            'Kongono\\ExtensionAdmission\\Conflict\\ContributionConflictResolution', 'Kongono\\ExtensionAdmission\\Contribution\\ExtensionGraphContribution',
            'Kongono\\ExtensionAdmission\\Exception\\ExtensionAdmissionException', 'Kongono\\ExtensionAdmission\\Exception\\InvalidExtensionAdmissionException', 'Kongono\\ExtensionAdmission\\Exception\\UnresolvedExtensionConflictException',
            'Kongono\\ExtensionAdmission\\Identity\\AdmissionIdentity', 'Kongono\\ExtensionAdmission\\Projection\\ExtensionGraphProvenance',
            'Kongono\\ExtensionAdmission\\Selection\\ExtensionAdmissionPlan', 'Kongono\\ExtensionAdmission\\Selection\\ExtensionSelection',
        ];
        foreach ($public as $class) {
            yield $class => [$class, ApiStatus::Public];
        }
        foreach ($internal as $class) {
            yield $class => [$class, ApiStatus::Internal];
        }
        foreach ($all as $class) {
            yield $class => [$class, ApiStatus::Experimental];
        }
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
