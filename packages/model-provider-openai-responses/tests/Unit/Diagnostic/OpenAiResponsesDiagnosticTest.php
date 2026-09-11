<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesDiagnosticTest extends TestCase
{
    public function testClosedIdsTitlesRemediationsAndSafeSchemaAreExact(): void
    {
        $cases = OpenAiResponsesDiagnostic::cases();
        $identifiers = array_column($cases, 'value');
        self::assertSame(array_map(static fn(int $number): string => 'KGN-OPENAI-RESPONSES-' . $number, range(1701, 1723)), $identifiers);
        self::assertSame($identifiers, array_values(array_unique($identifiers)));
        $hostile = 'credential-private-uri-body-header-provider-model-native-17c';
        foreach ($cases as $position => $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            $expected = match ($diagnostic) {
                OpenAiResponsesDiagnostic::InvalidProviderProfile,
                OpenAiResponsesDiagnostic::UnsupportedMappingSurface,
                OpenAiResponsesDiagnostic::RequestEncodingFailed,
                OpenAiResponsesDiagnostic::RequestLimitExceeded,
                OpenAiResponsesDiagnostic::ResponseSyntaxInvalid,
                OpenAiResponsesDiagnostic::ResponseStructureInvalid,
                OpenAiResponsesDiagnostic::ResponseLimitExceeded,
                OpenAiResponsesDiagnostic::ResponseMappingUnsupported => ['violation', 'field', 'limit', 'entry_count', 'entry_position', 'status', 'mapping_version'],
                OpenAiResponsesDiagnostic::InvalidInvocationContext,
                OpenAiResponsesDiagnostic::InvocationLifecycleViolation,
                OpenAiResponsesDiagnostic::ExternalServiceOperationFailed,
                OpenAiResponsesDiagnostic::ResultNormalizationFailed => ['violation', 'stage'],
                OpenAiResponsesDiagnostic::HttpResponseRejected => ['violation', 'stage', 'http_status'],
                OpenAiResponsesDiagnostic::ResponseBodyFailed => ['violation', 'stage', 'limit'],
                OpenAiResponsesDiagnostic::StreamingResponseFramingRejected,
                OpenAiResponsesDiagnostic::StreamingEventMappingRejected,
                OpenAiResponsesDiagnostic::StreamingEventOrderRejected,
                OpenAiResponsesDiagnostic::StreamingResponseCleanupFailed => ['violation', 'stage', 'limit', 'observed_count', 'event_kind'],
                OpenAiResponsesDiagnostic::InvalidStrictJsonSchema => ['violation', 'field', 'limit', 'entry_count', 'mapping_version'],
                OpenAiResponsesDiagnostic::InvalidApplicationProfile => ['violation'],
                OpenAiResponsesDiagnostic::ApplicationWorkflowFailed,
                OpenAiResponsesDiagnostic::ApplicationOperationFailed => ['stage'],
                OpenAiResponsesDiagnostic::ApplicationLifecycleViolation => ['state'],
            };
            self::assertSame($expected, array_map(static fn($field): string => $field->key(), $schema->fields()), (string) $position);
            $requiredCount = in_array($diagnostic, [
                OpenAiResponsesDiagnostic::InvalidInvocationContext,
                OpenAiResponsesDiagnostic::InvocationLifecycleViolation,
                OpenAiResponsesDiagnostic::ExternalServiceOperationFailed,
                OpenAiResponsesDiagnostic::ResultNormalizationFailed,
                OpenAiResponsesDiagnostic::HttpResponseRejected,
                OpenAiResponsesDiagnostic::ResponseBodyFailed,
                OpenAiResponsesDiagnostic::StreamingResponseFramingRejected,
                OpenAiResponsesDiagnostic::StreamingEventMappingRejected,
                OpenAiResponsesDiagnostic::StreamingEventOrderRejected,
                OpenAiResponsesDiagnostic::StreamingResponseCleanupFailed,
            ], true) ? 2 : 1;
            foreach ($schema->fields() as $index => $field) {
                self::assertSame($index < $requiredCount, $field->isRequired());
            }
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            foreach ([$diagnostic->title(), $diagnostic->remediation(), $schema->operation(), $schema->violatedInvariant()] as $safeText) {
                self::assertNotSame('', $safeText);
                self::assertStringNotContainsString($hostile, $safeText);
            }
        }
    }
}
