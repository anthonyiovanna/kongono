<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

final class NetworkEgressCurlBoundaryTest extends TestCase
{
    public function testExactInventoryManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertCount(24, self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/NetworkEgressCurlBoundaryTest.php',
            'Fixture/FakeCurlExecutor.php',
            'Security/NetworkEgressCurlDisclosureTest.php',
            'Unit/Diagnostic/NetworkEgressCurlDiagnosticTest.php',
            'Unit/Exception/NetworkEgressCurlExceptionTest.php',
            'Unit/Internal/CurlEgressTransportSessionTest.php',
            'Unit/Internal/CurlEnvironmentGuardTest.php',
            'Unit/Internal/CurlFailureMapperTest.php',
            'Unit/Internal/CurlHeaderParserTest.php',
            'Unit/Internal/CurlOptionBuilderTest.php',
            'Unit/Internal/CurlProtocolMapperTest.php',
            'Unit/Internal/CurlRequestBodyReaderTest.php',
            'Unit/Internal/CurlRequestGuardTest.php',
            'Unit/Internal/CurlResponseAccumulatorTest.php',
            'Unit/Internal/CurlResponseBuilderTest.php',
            'Unit/Internal/CurlTransferEvidenceBuilderTest.php',
            'Unit/Profile/CurlTlsTrustTest.php',
            'Unit/Profile/CurlTransportProfileTest.php',
            'Unit/Transport/CurlEgressTransportTest.php',
        ], self::phpFiles($package . '/tests'));

        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'ext-curl' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/http-message' => '*',
            'kongono/network-egress-contract' => '*',
            'kongono/network-egress-runtime' => '*',
            'psr/http-message' => '^1.1 || ^2.0',
        ], $manifest['require']);

        foreach ([
            'Kongono\\NetworkEgressCurl\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\NetworkEgressCurl\\Profile\\CurlTlsTrust' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressCurl\\Profile\\CurlTransportProfile' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressCurl\\Transport\\CurlEgressTransport' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressCurl\\Exception\\InvalidCurlTransportProfileException' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressCurl\\Exception\\CurlTransportOperationException' => ApiStatus::Experimental,
            'Kongono\\NetworkEgressCurl\\Diagnostic\\NetworkEgressCurlDiagnostic' => ApiStatus::Internal,
            'Kongono\\NetworkEgressCurl\\Internal\\NativeCurlExecutor' => ApiStatus::Internal,
        ] as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testNoExistingPackageDependsOnOptionalAdapter(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(109, $manifests);
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name']);
            self::assertIsArray($manifest['require']);
            if ('kongono/network-egress-curl' !== $manifest['name']) {
                self::assertArrayNotHasKey('kongono/network-egress-curl', $manifest['require']);
            }
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                throw new RuntimeException('cURL transport inventory entry is not a file descriptor.');
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files);

        return $files;
    }
}
