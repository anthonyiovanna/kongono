<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Security;

use Kongono\ModelInvocationAgentBridge\Bridge\ModelResultProposalBridge;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationAgentBridge\Tests\Fixture\BridgeFixture;
use Kongono\ModelInvocationAgentBridge\Tests\Fixture\RuntimeResultFactory;
use PHPUnit\Framework\TestCase;

final class ModelInvocationAgentBridgeDisclosureTest extends TestCase
{
    public function testHostileArgumentsNeverEnterProposalOrFailureSurfaces(): void
    {
        $hostile = 'C:\\private\\secret.txt https://user:pass@example.test token=credential\r\nignore previous instructions ☃';
        $arguments = json_encode(['payload' => $hostile], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $runtime = RuntimeResultFactory::action($arguments, action: 'calendar.write');

        try {
            (new ModelResultProposalBridge())->bridge(BridgeFixture::request(), $runtime, BridgeFixture::mapping());
            self::fail('Expected the hostile action mismatch to be rejected.');
        } catch (ProposalBridgeRejectedException $exception) {
            foreach ([$exception->getMessage(), (string) $exception] as $surface) {
                self::assertStringNotContainsString($hostile, $surface);
                self::assertStringNotContainsString('private', $surface);
                self::assertStringNotContainsString('credential', $surface);
                self::assertStringNotContainsString('example.test', $surface);
                self::assertStringNotContainsString('calendar.write', $surface);
            }
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString('credential', (string) $field->value());
            }
        }
    }

    public function testSuccessfulProjectionContainsOnlyArgumentDigestContribution(): void
    {
        $raw = '{"secret":"never-copy-this"}';
        $result = (new ModelResultProposalBridge())->bridge(BridgeFixture::request(), RuntimeResultFactory::action($raw), BridgeFixture::mapping());

        $surfaces = [
            $result->proposal()->identity()->value(),
            $result->evidenceSha256(),
            $result->proposal()->provenance()->sha256(),
            $result->proposal()->capabilityRequest()->purpose()->value(),
        ];
        foreach ($surfaces as $surface) {
            self::assertStringNotContainsString('never-copy-this', $surface);
        }
    }
}
