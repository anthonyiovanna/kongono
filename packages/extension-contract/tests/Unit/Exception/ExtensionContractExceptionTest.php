<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Exception;

use Kongono\ExtensionContract\Exception\ExceptionInterface;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionContractExceptionTest extends TestCase
{
    public function testSevenFactoriesMapDiagnosticsAndPreservePreviousIdentity(): void
    {
        $cause = new RuntimeException('private previous');
        $exceptions = [
            InvalidExtensionContractException::invalidIdentity('extension_identity_grammar'),
            InvalidExtensionContractException::invalidVersion('extension_version_grammar'),
            InvalidExtensionContractException::invalidProvenance('source_digest_grammar'),
            InvalidExtensionContractException::invalidCapabilityRequest('capability_purpose_grammar'),
            InvalidExtensionContractException::invalidContribution('contribution_source_digest_grammar'),
            InvalidExtensionContractException::invalidDeclaration('contributions_not_list', -4, -9),
            InvalidExtensionContractException::contractLimitExceeded('contribution_count', 129, $cause),
        ];

        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-EXTENSION-%04d', $ordinal), range(1201, 1207)),
            array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions),
        );
        self::assertSame($cause, $exceptions[6]->getPrevious());
        self::assertSame(0, $exceptions[5]->diagnosticContext()->fields()[1]->value());
        self::assertSame(0, $exceptions[5]->diagnosticContext()->fields()[2]->value());
    }

    public function testArbitraryPrivateInputsNeverReachMessagesContextsOrStringification(): void
    {
        foreach (['C:\\private\\secret.php', 'password=hunter2', 'é-secret', str_repeat('x', 4096)] as $raw) {
            $exception = InvalidExtensionContractException::invalidDeclaration($raw, 2, 1);
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
            self::assertSame(0, $exception->getCode());
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertNotSame($raw, $field->value());
            }
            self::assertStringContainsString('unsupported_violation', (string) $exception);
        }
    }
}
