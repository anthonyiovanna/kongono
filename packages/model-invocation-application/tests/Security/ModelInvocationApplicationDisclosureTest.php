<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Security;

use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationWorkflowException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelInvocationApplicationDisclosureTest extends TestCase
{
    public function testExceptionStringAndClosedContextDoNotExposeRejectedSecrets(): void
    {
        $secret = 'C:/private/provider-endpoint-credential-prompt-response';
        $previous = new RuntimeException($secret);
        $failure = ModelInvocationApplicationWorkflowException::failed($secret, $previous);

        self::assertSame($previous, $failure->getPrevious());
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        $fields = $failure->diagnosticContext()->fields();
        self::assertCount(1, $fields);
        self::assertSame('unsupported_stage', $fields[0]->value());
    }
}
