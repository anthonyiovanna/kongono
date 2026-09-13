<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Security;

use Kongono\ViewBuild\Artifact\CompiledViewArtifactValidator;
use Kongono\ViewBuild\Exception\InvalidCompiledViewArtifactException;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Exception\ViewArtifactPublicationException;
use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ViewBuildDisclosureTest extends TestCase
{
    public function testFailuresDoNotDiscloseSourceThrowableOrPaths(): void
    {
        $secret = 'TOP-SECRET-TEMPLATE-TEXT';
        $failure = InvalidViewSourceException::invalid('source_json', new \RuntimeException($secret));
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        self::assertStringNotContainsString(__DIR__, (string) $failure);
    }

    public function testDistinctiveSourceLiteralReferencePathGeneratedAndThrowableBytesStayUndisclosed(): void
    {
        $secrets = [
            'RAW-SOURCE-7df827',
            'LITERAL-85a602',
            'REFERENCE-11f343',
            'C:/PRIVATE/PATH-686cab',
            'GENERATED-BYTES-57f9ad',
            'THROWABLE-113eab',
        ];
        $artifact = BuildProbe::artifact();
        $failures = [
            InvalidViewSourceException::invalid('source_json', new \RuntimeException(implode('|', $secrets))),
            InvalidCompiledViewArtifactException::invalid('generated_regeneration', new \RuntimeException($secrets[4])),
            ViewArtifactPublicationException::failed('invalid_output_directory', new \RuntimeException($secrets[3])),
        ];
        try {
            (new CompiledViewArtifactValidator())->validateStructure(
                str_replace($artifact->generatedPhpSha256(), hash('sha256', $secrets[4]), $artifact->metadataBytes()),
                $secrets[4],
            );
            self::fail('Expected generated evidence rejection.');
        } catch (InvalidCompiledViewArtifactException $generatedFailure) {
            $failures[] = $generatedFailure;
        }

        foreach ($failures as $evidenceFailure) {
            foreach ($secrets as $secret) {
                self::assertStringNotContainsString($secret, $evidenceFailure->getMessage());
                self::assertStringNotContainsString($secret, (string) $evidenceFailure);
            }
        }
    }

    public function testProductionHasNoForbiddenDependencyOrRuntimeSurface(): void
    {
        $source = '';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $source .= (string) file_get_contents($file->getPathname());
            }
        }
        foreach (['Psr\\Http', 'ContainerInterface', 'PDO', 'CurlHandle', 'Chie', 'eval(', 'node_modules', 'Twig', 'Blade'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
