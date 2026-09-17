<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Internal\ContractGuard;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ViewContractBoundaryTest extends TestCase
{
    private const array INTERNAL_TYPES = [
        'Kongono\\ViewContract\\Diagnostic\\ViewContractDiagnostic',
        'Kongono\\ViewContract\\Exception\\ViewContractException',
        'Kongono\\ViewContract\\Internal\\ContractGuard',
    ];

    public function testManifestInventoryDependenciesAndReverseDependenciesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/view-contract',
            'description' => 'Neutral compiled-view identities, closed presentation values, schemas, and render evidence for Kongono Framework.',
            'type' => 'library',
            'require' => ['php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*'],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ViewContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ViewContract\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(30, self::phpFiles($package . '/src'));
        self::assertCount(17, self::phpFiles($package . '/tests'));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(109, $manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name'] ?? null);
            self::assertIsArray($candidate['require'] ?? null);
            if (in_array($candidate['name'], ['kongono/view-application', 'kongono/view-build', 'kongono/view-http', 'kongono/view-inspection', 'kongono/view-runtime'], true)) {
                self::assertSame('*', $candidate['require']['kongono/view-contract'] ?? null);

                continue;
            }
            if ('kongono/view-contract' !== $candidate['name']) {
                self::assertArrayNotHasKey('kongono/view-contract', $candidate['require']);
            }
        }
    }

    public function testEveryProductionFileOwnsOneExactStatusedDeclaration(): void
    {
        $counts = [ApiStatus::Experimental->value => 0, ApiStatus::Public->value => 0, ApiStatus::Internal->value => 0];
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source));
            self::assertSame(1, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source));
            if (1 !== preg_match('/namespace\s+([^;]+);/', $source, $namespace)) {
                self::fail('Expected one exact namespace declaration.');
            }
            $type = $namespace[1] . '\\' . pathinfo($path, PATHINFO_FILENAME);
            if (!class_exists($type) && !interface_exists($type) && !enum_exists($type)) {
                self::fail('Expected discovered production type to exist.');
            }
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            $expected = 'Kongono\\ViewContract\\Exception\\ExceptionInterface' === $type
                ? ApiStatus::Public
                : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $attributes[0]->newInstance()->status);
            ++$counts[$expected->value];
        }
        self::assertSame(['experimental' => 26, 'public' => 1, 'internal' => 3], $counts);
    }

    public function testSourceHasNoAmbientAuthorityOrEffectSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => self::withoutComments((string) file_get_contents($path)), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'Psr\\Http', 'ContainerInterface', 'callable', 'Closure', 'Fiber', 'eval('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testVersionedStructuralEqualityBeginsWithInternalExhaustiveContractVersionComparison(): void
    {
        $sourceRoot = dirname(__DIR__, 2) . '/src';
        foreach (['Render/RenderRequest.php', 'Render/RenderResult.php', 'Failure/RenderFailure.php'] as $relativePath) {
            $source = (string) file_get_contents($sourceRoot . '/' . $relativePath);
            self::assertSame(1, preg_match(
                '/public function equals\(self \$other\): bool\s*\{\s*return ContractGuard::sameContractVersion\(\$this->contractVersion, \$other->contractVersion\)\s*&&/',
                $source,
            ));
        }

        $guardSource = (string) file_get_contents($sourceRoot . '/Internal/ContractGuard.php');
        self::assertSame(1, preg_match(
            '/public static function sameContractVersion\(\s*ContractVersion \$left,\s*ContractVersion \$right,\s*\): bool \{\s*return match \(\$left\) \{\s*ContractVersion::Version1 => match \(\$right\) \{\s*ContractVersion::Version1 => true,\s*\},\s*\};\s*\}/',
            $guardSource,
        ));
        self::assertStringNotContainsString('default =>', $guardSource);
        self::assertStringNotContainsString('->value', $guardSource);
        self::assertSame([ContractVersion::Version1], ContractVersion::cases());
        self::assertSame(1, ContractVersion::Version1->value);

        $guard = new ReflectionClass(ContractGuard::class);
        self::assertSame(ApiStatus::Internal, $guard->getAttributes(Api::class)[0]->newInstance()->status);
        $comparison = $guard->getMethod('sameContractVersion');
        self::assertTrue($comparison->isPublic());
        self::assertTrue($comparison->isStatic());
        self::assertSame('bool', (string) $comparison->getReturnType());
        self::assertSame(
            [ContractVersion::class, ContractVersion::class],
            array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $comparison->getParameters()),
        );
    }

    private static function withoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /** @return list<string> */
    private static function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
