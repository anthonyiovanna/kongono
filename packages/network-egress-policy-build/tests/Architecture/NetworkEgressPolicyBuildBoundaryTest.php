<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class NetworkEgressPolicyBuildBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Artifact/EgressPolicyArtifact.php', 'Artifact/EgressPolicyArtifactBuilder.php',
            'Artifact/EgressPolicyArtifactPublisher.php', 'Artifact/EgressPolicyArtifactValidator.php',
            'Diagnostic/NetworkEgressPolicyBuildDiagnostic.php', 'Exception/EgressPolicyPublicationException.php',
            'Exception/ExceptionInterface.php', 'Exception/InvalidEgressPolicyArtifactException.php',
            'Exception/InvalidEgressPolicySourceException.php', 'Exception/NetworkEgressPolicyBuildException.php',
            'Exception/StaleEgressPolicyArtifactException.php', 'Internal/CanonicalJson.php', 'Internal/PathGuard.php',
            'Policy/AddressScope.php', 'Policy/CookieMode.php', 'Policy/CredentialMode.php',
            'Policy/EgressPolicyIdentity.php', 'Policy/EgressPolicySource.php', 'Policy/ProxyMode.php',
            'Policy/RedirectMode.php', 'Policy/TransportSecurity.php', 'Version/EgressPolicyArtifactVersion.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/NetworkEgressPolicyBuildBoundaryTest.php', 'Security/NetworkEgressPolicyBuildDisclosureTest.php',
            'Support/EgressPolicySourceFactory.php', 'Unit/Artifact/EgressPolicyArtifactBuilderTest.php',
            'Unit/Artifact/EgressPolicyArtifactPublisherTest.php', 'Unit/Artifact/EgressPolicyArtifactTest.php',
            'Unit/Artifact/EgressPolicyArtifactValidatorTest.php', 'Unit/Diagnostic/NetworkEgressPolicyBuildDiagnosticTest.php',
            'Unit/Exception/NetworkEgressPolicyBuildExceptionTest.php', 'Unit/Policy/EgressPolicyIdentityTest.php',
            'Unit/Policy/EgressPolicySourceTest.php', 'Unit/Policy/PolicyModeTest.php',
        ], self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*',
            'kongono/network-egress-contract' => '*',
        ], $manifest['require']);
        foreach ([
            'Kongono\\NetworkEgressPolicyBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\NetworkEgressPolicyBuild\\Diagnostic\\NetworkEgressPolicyBuildDiagnostic' => ApiStatus::Internal,
            'Kongono\\NetworkEgressPolicyBuild\\Exception\\NetworkEgressPolicyBuildException' => ApiStatus::Internal,
            'Kongono\\NetworkEgressPolicyBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
            'Kongono\\NetworkEgressPolicyBuild\\Internal\\PathGuard' => ApiStatus::Internal,
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
        self::assertCount(109, $manifests);
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
            if (!in_array($manifest['name'], ['kongono/external-service-application', 'kongono/external-service-inspection', 'kongono/network-egress-application', 'kongono/network-egress-inspection', 'kongono/network-egress-policy-build', 'kongono/network-egress-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/network-egress-policy-build', $manifest['require']);
            }
        }
        self::assertSame(467, $edges);
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

    public function testSourceContainsNoRuntimeNetworkDiscoveryOrAuthorityBehavior(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['curl_', 'getenv(', 'putenv(', 'dns_', 'fsockopen(', 'stream_socket_', 'shell_exec(', 'proc_open(', 'eval(', 'HttpClient', 'CredentialProvider', 'CookieJar', 'ResolverInterface', 'TransportInterface', 'Registry', 'Command'] as $forbidden) {
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
