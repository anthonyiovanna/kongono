<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Generated;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Exception\GeneratedExternalServiceBindingsPublicationException;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindings;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsGenerator;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsPublisher;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class GeneratedExternalServiceBindingsPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-generated-bindings-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
        $resolved = realpath($this->directory);
        self::assertNotFalse($resolved);
        $this->directory = str_replace('\\', '/', $resolved);
    }

    protected function tearDown(): void
    {
        $resolved = realpath($this->directory);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $this->directory || !str_starts_with($this->directory, str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-generated-bindings-')) {
            return;
        }
        $paths = glob($this->directory . '/*');
        if (is_array($paths)) {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
        @rmdir($this->directory);
    }

    public function testPublicationIsCompleteIdempotentNoClobberAndNeverLoadsPhp(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $generated = (new GeneratedExternalServiceBindingsGenerator())->generate($artifact);
        $publisher = new GeneratedExternalServiceBindingsPublisher();
        $path = $publisher->publish($generated, $this->directory);
        self::assertSame($this->directory . '/kongono-external-service-bindings-v1-' . $generated->generatedPhpSha256() . '.php', $path);
        self::assertSame($generated->canonicalBytes(), file_get_contents($path));
        self::assertSame($path, $publisher->publish($generated, $this->directory));
        file_put_contents($path, 'conflict');
        $this->expectException(GeneratedExternalServiceBindingsPublicationException::class);
        $publisher->publish($generated, $this->directory);
    }

    public function testCompleteGeneratedValueIsValidatedBeforeWriting(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $generated = (new GeneratedExternalServiceBindingsGenerator())->generate($artifact);
        $invalid = new GeneratedExternalServiceBindings(
            $generated->canonicalBytes(),
            $generated->generatedPhpSha256(),
            new ExternalServiceBindingSourceIdentity('other.bindings'),
            $generated->sourceSha256(),
            $generated->bindingArtifactSha256(),
            $generated->bindings(),
            $generated->generatorImplementationSha256(),
        );

        try {
            (new GeneratedExternalServiceBindingsPublisher())->publish($invalid, $this->directory);
            self::fail('Expected complete generated value rejection.');
        } catch (GeneratedExternalServiceBindingsPublicationException) {
            self::assertSame([], glob($this->directory . '/*'));
        }
    }
}
