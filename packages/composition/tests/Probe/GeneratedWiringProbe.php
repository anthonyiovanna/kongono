<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Probe;

use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Generated\GeneratedWiringGenerator;
use Kongono\Composition\Generated\GeneratedWiringPublisher;
use RuntimeException;

/** Creates one externally inspectable generated file in a caller-owned temporary directory. */
final class GeneratedWiringProbe
{
    /** @return array{path: string, composition: string, model: string, generator: string, wiring: string, namespace: string, peer_composition: string, peer_model: string, peer_wiring: string, peer_namespace: string} */
    public static function publish(string $outputDirectory): array
    {
        $applicationRoot = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-generated-probe-' . bin2hex(random_bytes(12));
        if (!mkdir($applicationRoot . '/src', 0700, true)) {
            throw new RuntimeException('Unable to create generated-wiring probe root.');
        }
        try {
            $fixtureRoot = dirname(__DIR__) . '/Fixture';
            foreach (['ConfigInput.php', 'Logger.php', 'Handler.php', 'DefaultLogger.php', 'HandlerA.php', 'HandlerB.php', 'HandlerCollection.php', 'ApplicationService.php', 'OperationService.php'] as $fixtureName) {
                $target = $applicationRoot . '/src/' . $fixtureName;
                if (!copy($fixtureRoot . '/' . $fixtureName, $target)) {
                    throw new RuntimeException('Unable to copy a generated-wiring probe fixture.');
                }
                require $target;
            }
            self::write($applicationRoot . '/composer.json', "{\"name\":\"example/generated-probe\"}\n");
            self::write($applicationRoot . '/composer.lock', "{\"packages\":[]}\n");
            self::write($applicationRoot . '/kongono.composition.json', self::source());

            $composition = (new CompositionArtifactBuilder())->build($applicationRoot);
            $wiring = (new GeneratedWiringGenerator())->generate($applicationRoot, $composition->canonicalBytes());
            $path = (new GeneratedWiringPublisher())->publish($wiring, $outputDirectory);
            self::write($applicationRoot . '/kongono.composition.json', self::source(true));
            $peerComposition = (new CompositionArtifactBuilder())->build($applicationRoot);
            $peerWiring = (new GeneratedWiringGenerator())->generate($applicationRoot, $peerComposition->canonicalBytes());

            return [
                'path' => $path,
                'composition' => $composition->artifactSha256(),
                'model' => $composition->modelSha256(),
                'generator' => $wiring->generatorImplementationSha256(),
                'wiring' => $wiring->wiringSha256(),
                'namespace' => $wiring->generatedNamespace(),
                'peer_composition' => $peerComposition->artifactSha256(),
                'peer_model' => $peerComposition->modelSha256(),
                'peer_wiring' => $peerWiring->wiringSha256(),
                'peer_namespace' => $peerWiring->generatedNamespace(),
            ];
        } finally {
            self::removeTree($applicationRoot);
        }
    }

    private static function source(bool $reverseServices = false): string
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';
        $source = [
            'schema' => 'kongono.composition',
            'version' => 1,
            'inputs' => [['contract' => $namespace . 'ConfigInput']],
            'services' => [
                ['class' => $namespace . 'ApplicationService', 'lifetime' => 'application', 'arguments' => [
                    ['parameter' => 'config', 'input' => $namespace . 'ConfigInput'],
                    ['parameter' => 'logger', 'service' => $namespace . 'Logger'],
                    ['parameter' => 'handlers', 'service' => $namespace . 'Handler'],
                    ['parameter' => 'fallback', 'default' => true],
                ], 'cleanup' => 'shutdown'],
                ['class' => $namespace . 'DefaultLogger', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => 'close'],
                ['class' => $namespace . 'HandlerA', 'lifetime' => 'transient', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
                ['class' => $namespace . 'HandlerB', 'lifetime' => 'transient', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
                ['class' => $namespace . 'HandlerCollection', 'lifetime' => 'application', 'arguments' => [
                    ['parameter' => 'first', 'service' => $namespace . 'HandlerA'],
                    ['parameter' => 'second', 'service' => $namespace . 'HandlerB'],
                ], 'cleanup' => null],
                ['class' => $namespace . 'OperationService', 'lifetime' => 'operation', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
            ],
            'bindings' => [
                ['contract' => $namespace . 'Handler', 'service' => $namespace . 'HandlerCollection'],
                ['contract' => $namespace . 'Logger', 'service' => $namespace . 'DefaultLogger'],
            ],
            'roots' => [
                ['kind' => 'application', 'contract' => $namespace . 'ApplicationService'],
                ['kind' => 'operation', 'contract' => $namespace . 'OperationService'],
            ],
        ];
        if ($reverseServices) {
            $source['services'] = array_reverse($source['services']);
        }

        return json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    private static function write(string $path, string $bytes): void
    {
        if (strlen($bytes) !== file_put_contents($path, $bytes)) {
            throw new RuntimeException('Unable to write a generated-wiring probe file.');
        }
    }

    private static function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $entries = scandir($directory);
        if (false === $entries) {
            throw new RuntimeException('Unable to inspect generated-wiring probe cleanup.');
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                self::removeTree($path);
            } elseif (!unlink($path)) {
                throw new RuntimeException('Unable to clean a generated-wiring probe file.');
            }
        }
        if (!rmdir($directory)) {
            throw new RuntimeException('Unable to clean the generated-wiring probe root.');
        }
    }

    private function __construct() {}
}
