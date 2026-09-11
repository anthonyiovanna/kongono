<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ModelProviderOpenAiResponsesBoundaryTest extends TestCase
{
    private const array EXPERIMENTAL_TYPES = [
        'Kongono\\ModelProviderOpenAiResponses\\OpenAiResponsesMappingVersion',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesProfile',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\ResponseStoragePreference',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\ExceptionInterface',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\InvalidOpenAiResponsesProfileException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\ResponsesRequestEncodingException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\ResponsesResponseDecodingException',
        'Kongono\\ModelProviderOpenAiResponses\\Adapter\\OpenAiResponsesExternalServiceApplicationFactory',
        'Kongono\\ModelProviderOpenAiResponses\\Adapter\\OpenAiResponsesInvocationAdapter',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\OpenAiResponsesInvocationException',
        'Kongono\\ModelProviderOpenAiResponses\\Execution\\OpenAiResponsesInvocationContext',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\InvalidStrictJsonSchemaException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\InvalidOpenAiResponsesApplicationProfileException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\OpenAiResponsesApplicationLifecycleException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\OpenAiResponsesApplicationOperationException',
        'Kongono\\ModelProviderOpenAiResponses\\Exception\\OpenAiResponsesApplicationWorkflowException',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesFunctionDefinition',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesApplicationProfile',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesApplicationProfileFactory',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesApplicationProfileIdentity',
        'Kongono\\ModelProviderOpenAiResponses\\Profile\\OpenAiResponsesStructuredOutput',
        'Kongono\\ModelProviderOpenAiResponses\\Schema\\StrictJsonSchema',
        'Kongono\\ModelProviderOpenAiResponses\\Runtime\\OpenAiResponsesApplication',
        'Kongono\\ModelProviderOpenAiResponses\\Runtime\\OpenAiResponsesApplicationRunInput',
        'Kongono\\ModelProviderOpenAiResponses\\Runtime\\OpenAiResponsesApplicationState',
        'Kongono\\ModelProviderOpenAiResponses\\Workflow\\CurrentOpenAiResponsesApplicationBuild',
        'Kongono\\ModelProviderOpenAiResponses\\Workflow\\OpenAiResponsesApplicationBuildWorkflow',
    ];

    public function testManifestInventoryDependenciesAndReverseDependenciesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/model-provider-openai-responses', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/external-service-application' => '*',
            'kongono/external-service-contract' => '*',
            'kongono/external-service-credential-runtime' => '*',
            'kongono/http-message' => '*',
            'kongono/model-invocation-application' => '*',
            'kongono/model-invocation-build' => '*',
            'kongono/model-invocation-contract' => '*',
            'kongono/model-invocation-runtime' => '*',
            'kongono/network-egress-contract' => '*',
            'kongono/network-egress-runtime' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        self::assertCount(55, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(35, $this->recursivePhpFiles($package . '/tests'));
        $fixtures = glob($package . '/tests/Fixture/Response/*.json');
        self::assertIsArray($fixtures);
        self::assertCount(11, $fixtures);
        $streamFixtures = glob($package . '/tests/Fixture/Response/*.sse');
        self::assertIsArray($streamFixtures);
        self::assertCount(3, $streamFixtures);

        $reverse = [];
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            if ($path === $package . '/composer.json') {
                continue;
            }
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            foreach (['require', 'require-dev'] as $section) {
                if (isset($candidate[$section]) && is_array($candidate[$section]) && array_key_exists('kongono/model-provider-openai-responses', $candidate[$section])) {
                    $reverse[] = $path;
                }
            }
        }
        self::assertSame([$root . '/packages/model-provider-openai-responses-inspection/composer.json'], $reverse);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        $validations = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($rootPsr4);
        self::assertIsArray($validations);
        self::assertSame('@dev', $rootRequire['kongono/model-provider-openai-responses'] ?? null);
        self::assertSame('packages/model-provider-openai-responses/tests/', $rootPsr4['Kongono\\ModelProviderOpenAiResponses\\Tests\\'] ?? null);
        self::assertSame(1, count(array_keys($validations, 'composer --working-dir=packages/model-provider-openai-responses validate --no-check-publish', true)));
        self::assertCount(89, $validations);
        foreach (['phpstan.neon.dist', '.php-cs-fixer.dist.php', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertStringContainsString('packages/model-provider-openai-responses/src', $contents);
            self::assertStringContainsString('packages/model-provider-openai-responses/tests', $contents);
        }
    }

    public function testEveryProductionDeclarationHasTheExactApiStatus(): void
    {
        $package = dirname(__DIR__, 2);
        $statuses = [];
        foreach ($this->recursivePhpFiles($package . '/src') as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\ModelProviderOpenAiResponses\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $status = $attributes[0]->newInstance()->status;
            $statuses[] = $status;
            self::assertSame(in_array($class, self::EXPERIMENTAL_TYPES, true) ? ApiStatus::Experimental : ApiStatus::Internal, $status, $class);
        }
        self::assertSame(27, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(28, count(array_keys($statuses, ApiStatus::Internal, true)));
    }

    public function testAdapterCompositionUsesOnlyAcceptedInwardEffectsAndNoAmbientSurface(): void
    {
        $package = dirname(__DIR__, 2);
        $source = '';
        foreach ($this->recursivePhpFiles($package . '/src') as $path) {
            $source .= (string) file_get_contents($path);
        }
        foreach (['curl_', 'fsockopen', 'stream_socket_client', 'socket_', 'getaddrinfo', 'gethostby', 'getenv(', '$_ENV', '$_SERVER', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'sleep(', 'usleep(', 'retry', 'api.openai.com'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertStringContainsString('implements InvocationAdapter', $source);
        self::assertStringContainsString('implements InvocationAdapterSession', $source);
        self::assertStringContainsString('ExternalServiceApplicationRunInput', $source);
        self::assertStringContainsString('ExternalServiceApplication', $source);
        self::assertStringContainsString('EgressAuthorityCurrentness', $source);
        self::assertStringContainsString('EgressCancellation', $source);
        self::assertStringContainsString("'/v1/responses'", $source);
        self::assertStringContainsString('OpenAiResponsesStreamingInvocationSession', $source);
        self::assertStringContainsString('ResponsesSseFrameReader', $source);
        self::assertStringContainsString('StrictJsonSchema', $source);
        self::assertStringContainsString('ActionSuggestion', $source);
        self::assertStringContainsString('ModelInvocationApplicationBuildWorkflow', $source);
        self::assertStringContainsString('OpenAiResponsesApplicationBuildWorkflow', $source);
        foreach (['ActionSuggestionExecutor', 'FunctionExecutor', 'ToolExecutor', 'Inspection', 'Phase17G', 'Phase17H'] as $later) {
            self::assertStringNotContainsString($later, $source, $later);
        }
    }

    /** @return list<string> */
    private function recursivePhpFiles(string $directory): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }
}
