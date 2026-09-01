<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Architecture;

use Kongono\AgentAdmission\Admission\AgentAdmissionBuilder;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

final class AgentAdmissionBoundaryTest extends TestCase
{
    public function testManifestAndProductionDependencyBoundaryAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = self::decodeObject($package . '/composer.json');
        self::assertSame('kongono/agent-admission', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/agent-declaration-build' => '*',
            'kongono/agent-principal-contract' => '*',
            'kongono/api-metadata' => '*',
            'kongono/authentication' => '*',
            'kongono/authorization' => '*',
            'kongono/diagnostics' => '*',
            'kongono/identity-access' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
    }

    public function testProductionSourceHasNoAmbientOrEffectAuthorityDependency(): void
    {
        $source = '';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src'));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                throw new RuntimeException('Source iterator returned an unsupported entry.');
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $source .= (string) file_get_contents($file->getPathname());
            }
        }
        foreach ([
            'AuthenticationOrchestrator', 'AuthenticationVerifier', 'AuthorizationEngine', 'AuthorizationEvaluator',
            'Kongono\\ApplicationGraph', 'Kongono\\Composition', 'Kongono\\Extension', 'Kongono\\Persistence',
            'Kongono\\Observability', 'Reflection', 'Filesystem', 'PDO', 'curl_', 'getenv(', 'putenv(',
            'random_', 'microtime(', 'time(', 'scandir(', 'glob(', 'include ', 'require ', '$GLOBALS', '$_ENV', '$_SERVER',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        self::assertTrue((new ReflectionClass(AgentAdmissionBuilder::class))->isFinal());
        self::assertTrue((new ReflectionClass(AgentAuthorityPlan::class))->isReadOnly());
    }

    /** @return array<string, mixed> */
    private static function decodeObject(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Package manifest must be an object.');
        }

        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Package manifest keys must be strings.');
            }
            $object[$key] = $value;
        }

        return $object;
    }
}
