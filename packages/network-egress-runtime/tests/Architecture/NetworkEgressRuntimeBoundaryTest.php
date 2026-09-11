<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class NetworkEgressRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertCount(31, self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/NetworkEgressRuntimeBoundaryTest.php',
            'Fixture/FakeAuthorityCurrentness.php', 'Fixture/FakeCancellation.php', 'Fixture/FakeClock.php',
            'Fixture/FakeResolver.php', 'Fixture/FakeTransport.php', 'Fixture/FakeTransportSession.php',
            'Fixture/RuntimeFixture.php', 'Security/NetworkEgressRuntimeDisclosureTest.php',
            'Unit/Address/ResolutionEvidenceTest.php', 'Unit/Address/ResolvedAddressTest.php',
            'Unit/Diagnostic/NetworkEgressRuntimeDiagnosticTest.php',
            'Unit/Exception/NetworkEgressRuntimeExceptionTest.php',
            'Unit/Execution/EgressRuntimeRequestTest.php', 'Unit/Runtime/NetworkEgressRuntimeTest.php',
            'Unit/Transport/EgressTransportEmissionTest.php',
        ], self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/network-egress-contract' => '*',
            'kongono/network-egress-policy-build' => '*',
            'psr/http-message' => '^1.1 || ^2.0',
        ], $manifest['require']);
        foreach ([
            'Kongono\\NetworkEgressRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\NetworkEgressRuntime\\Runtime\\NetworkEgressRuntime' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressRuntime\\Diagnostic\\NetworkEgressRuntimeDiagnostic' => ApiStatus::Internal,
            'Kongono\\NetworkEgressRuntime\\Internal\\AddressClassifier' => ApiStatus::Internal,
        ] as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testTopologyIsExactAcyclicAndHasNoReverseConsumer(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(89, $manifests);
        $graph = [];
        $edges = 0;
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name']);
            self::assertIsArray($manifest['require']);
            $graph[$manifest['name']] = [];
            foreach ($manifest['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    $graph[$manifest['name']][] = $dependency;
                    ++$edges;
                }
            }
            if (in_array($manifest['name'], ['kongono/external-service-application', 'kongono/external-service-contract', 'kongono/external-service-inspection', 'kongono/external-service-resolver-system', 'kongono/model-provider-openai-responses', 'kongono/network-egress-application', 'kongono/network-egress-curl', 'kongono/network-egress-inspection'], true)) {
                self::assertSame('*', $manifest['require']['kongono/network-egress-runtime']);
            } elseif ('kongono/network-egress-runtime' !== $manifest['name']) {
                self::assertArrayNotHasKey('kongono/network-egress-runtime', $manifest['require']);
            }
        }
        self::assertSame(375, $edges);
        $visit = static function (string $name, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($name, $path);
            $path[$name] = true;
            foreach ($graph[$name] ?? [] as $dependency) {
                $visit($dependency, $path);
            }
        };
        foreach (array_keys($graph) as $name) {
            $visit($name);
        }
    }

    public function testSourceContainsNoConcreteNetworkDiscoveryOrAmbientAuthority(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['curl_', 'getenv(', 'putenv(', 'dns_', 'gethostby', 'fsockopen(', 'stream_socket_', 'shell_exec(', 'proc_open(', 'eval(', 'CredentialProvider', 'CookieJar', 'Registry', 'Command'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
