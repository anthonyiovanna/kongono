<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Security;

use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CredentialLeaseRuntimeDisclosureTest extends TestCase
{
    public function testHighEntropyAndForeignSentinelsAreAbsentFromTerminalEvidenceAndText(): void
    {
        $sentinels = [
            'material-' . bin2hex(random_bytes(32)),
            'source-' . bin2hex(random_bytes(16)),
            'reference-' . bin2hex(random_bytes(16)),
            'purpose-' . bin2hex(random_bytes(16)),
            'operation-' . bin2hex(random_bytes(16)),
            'lease-' . bin2hex(random_bytes(16)),
            'foreign-' . bin2hex(random_bytes(32)),
        ];
        $result = new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::MaterialUseUncertain,
            CredentialLeaseRuntimeState::Retired,
            new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Consumer, 'consumer_failed'),
            2,
            1,
            1,
            1,
            true,
        );
        $exception = new CredentialLeaseOperationException($result, new RuntimeException($sentinels[6]), 'using');
        $projection = var_export([
            'message' => $exception->getMessage(),
            'text' => (string) $exception,
            'diagnostic_id' => $exception->diagnosticId(),
            'context' => array_map(static fn($field): string => $field->key() . '=' . $field->value(), $exception->diagnosticContext()->fields()),
            'result' => [$result->outcome()->value, $result->state()->value, $result->failure()?->reason()],
        ], true);
        foreach ($sentinels as $sentinel) {
            self::assertStringNotContainsString($sentinel, $projection);
        }
    }

    public function testDocumentationAndProductionMakeNoMaterialErasureOrBuiltInSourceClaim(): void
    {
        $package = dirname(__DIR__, 2);
        $production = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles($package . '/src')));
        foreach (['zeroize(', 'memset(', 'sodium_memzero(', 'getenv(', 'vaultClient', 'refreshToken', 'retry(', 'fallback(', 'networkClient'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $production);
        }
        $readme = (string) file_get_contents($package . '/README.md');
        self::assertStringContainsString('does not promise memory zeroization', $readme);
        self::assertStringContainsString('supplies no credential source', $readme);
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
