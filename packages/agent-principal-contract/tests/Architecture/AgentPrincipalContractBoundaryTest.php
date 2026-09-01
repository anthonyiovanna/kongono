<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class AgentPrincipalContractBoundaryTest extends TestCase
{
    private const array INTERNAL_TYPES = [
        'Kongono\\AgentPrincipalContract\\Diagnostic\\AgentPrincipalContractDiagnostic',
        'Kongono\\AgentPrincipalContract\\Exception\\AgentPrincipalContractException',
        'Kongono\\AgentPrincipalContract\\Internal\\ContractGuard',
    ];

    private const array NON_READONLY_CONCRETE_TYPES = [
        'Kongono\\AgentPrincipalContract\\Exception\\InvalidAgentPrincipalContractException',
        'Kongono\\AgentPrincipalContract\\Internal\\ContractGuard',
    ];

    public function testManifestInventoryDependenciesAndReverseDependenciesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/agent-principal-contract',
            'description' => 'Neutral versioned Agent Principal contracts for Kongono Framework.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/identity-access' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\AgentPrincipalContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\AgentPrincipalContract\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(28, self::phpFiles($package . '/src'));
        self::assertCount(13, self::phpFiles($package . '/tests'));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(69, $manifests);
        foreach ($manifests as $candidatePath) {
            $candidate = json_decode((string) file_get_contents($candidatePath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            if (!in_array($candidate['name'], ['kongono/agent-admission', 'kongono/agent-application', 'kongono/agent-declaration-build', 'kongono/agent-inspection', 'kongono/agent-principal-contract', 'kongono/agent-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/agent-principal-contract', $candidate['require']);
            }
        }
    }

    public function testEveryProductionFileOwnsOneExactStatusedDeclaration(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source));
            self::assertSame(1, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source));
        }

        $types = [
            'Kongono\\AgentPrincipalContract\\ContractVersion',
            'Kongono\\AgentPrincipalContract\\Identity\\AgentPrincipalReference',
            'Kongono\\AgentPrincipalContract\\Identity\\OperationReference',
            'Kongono\\AgentPrincipalContract\\Intent\\IntentIdentity',
            'Kongono\\AgentPrincipalContract\\Intent\\IntentPurpose',
            'Kongono\\AgentPrincipalContract\\Intent\\AgentIntent',
            'Kongono\\AgentPrincipalContract\\Proposal\\ProposalIdentity',
            'Kongono\\AgentPrincipalContract\\Proposal\\ActionProposal',
            'Kongono\\AgentPrincipalContract\\Capability\\CapabilityIdentity',
            'Kongono\\AgentPrincipalContract\\Capability\\CapabilityPurpose',
            'Kongono\\AgentPrincipalContract\\Capability\\CapabilityRequirement',
            'Kongono\\AgentPrincipalContract\\Capability\\CapabilityRequest',
            'Kongono\\AgentPrincipalContract\\Constraint\\ConstraintKind',
            'Kongono\\AgentPrincipalContract\\Constraint\\AgentConstraint',
            'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceKind',
            'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceIdentity',
            'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceReference',
            'Kongono\\AgentPrincipalContract\\Provenance\\ProvenanceSource',
            'Kongono\\AgentPrincipalContract\\Provenance\\AgentProvenance',
            'Kongono\\AgentPrincipalContract\\Visibility\\AgentVisibility',
            'Kongono\\AgentPrincipalContract\\Receipt\\ReceiptIdentity',
            'Kongono\\AgentPrincipalContract\\Receipt\\ReceiptOutcome',
            'Kongono\\AgentPrincipalContract\\Receipt\\AgentReceipt',
            'Kongono\\AgentPrincipalContract\\Diagnostic\\AgentPrincipalContractDiagnostic',
            'Kongono\\AgentPrincipalContract\\Exception\\ExceptionInterface',
            'Kongono\\AgentPrincipalContract\\Exception\\AgentPrincipalContractException',
            'Kongono\\AgentPrincipalContract\\Exception\\InvalidAgentPrincipalContractException',
            'Kongono\\AgentPrincipalContract\\Internal\\ContractGuard',
        ];
        foreach ($types as $type) {
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            $expected = 'Kongono\\AgentPrincipalContract\\Exception\\ExceptionInterface' === $type
                ? ApiStatus::Public
                : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $attributes[0]->newInstance()->status);
            if (!$reflection->isInterface() && !$reflection->isEnum() && !$reflection->isAbstract()) {
                self::assertTrue($reflection->isFinal());
                self::assertSame(!in_array($type, self::NON_READONLY_CONCRETE_TYPES, true), $reflection->isReadOnly());
            }
        }
    }

    public function testSourceHasNoAmbientRuntimeDiscoveryProviderOrSideEffectSurface(): void
    {
        $source = implode('', array_map(
            static fn(string $path): string => self::sourceWithoutComments((string) file_get_contents($path)),
            self::phpFiles(dirname(__DIR__, 2) . '/src'),
        ));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'ApplicationGraph', 'Composition', 'ExtensionHost', 'Provider', 'Protocol', 'Registry', 'Loader', 'callable', 'Closure', 'Fiber', 'MCP', 'A2A'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private static function sourceWithoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0]) {
                    continue;
                }
                $code .= $token[1];

                continue;
            }
            $code .= $token;
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
