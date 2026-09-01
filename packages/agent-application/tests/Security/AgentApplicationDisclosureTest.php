<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Security;

use Kongono\AgentApplication\Exception\AgentApplicationWorkflowException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentApplicationDisclosureTest extends TestCase
{
    public function testMessagesContextsAndStringificationDoNotExposeRejectedValues(): void
    {
        $secret = 'C:/private/credential-token-provider-prompt-completion';
        $previous = new RuntimeException($secret);
        $failure = AgentApplicationWorkflowException::failed($secret, $previous);

        self::assertSame($previous, $failure->getPrevious());
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        $fields = $failure->diagnosticContext()->fields();
        self::assertCount(1, $fields);
        self::assertSame('unsupported_stage', $fields[0]->value());
    }
}
