<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;

/** Emits one fixed ordinary-PHP template from validated secret-free values. */
#[Api(ApiStatus::Internal)]
final class GeneratedBindingsEmitter
{
    /** @param array<string, mixed> $metadata
     * @param list<ServiceBinding> $bindings
     */
    public static function emit(array $metadata, array $bindings): string
    {
        try {
            $json = CanonicalJson::encodeInline($metadata);
        } catch (JsonException $failure) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_json', previous: $failure);
        }
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $json,
            '',
            'return [',
            "    'schema' => 'kongono.external-service-bindings',",
            "    'version' => 1,",
            "    'contract_version' => 1,",
            "    'source_identity' => " . self::literal(self::text($metadata, 'source_identity')) . ',',
            "    'source_sha256' => " . self::literal(self::text($metadata, 'source_sha256')) . ',',
            "    'binding_artifact_sha256' => " . self::literal(self::text($metadata, 'binding_artifact_sha256')) . ',',
            "    'bindings' => [",
        ];
        foreach ($bindings as $binding) {
            foreach (self::bindingLines($binding) as $line) {
                $lines[] = $line;
            }
        }
        $lines[] = '    ],';
        $lines[] = '];';

        $bytes = implode("\n", $lines) . "\n";
        if (strlen($bytes) > CanonicalJson::MAX_GENERATED_BYTES) {
            throw InvalidGeneratedExternalServiceBindingsException::limitExceeded(strlen($bytes), CanonicalJson::MAX_GENERATED_BYTES);
        }

        return $bytes;
    }

    /** @return list<string> */
    private static function bindingLines(ServiceBinding $binding): array
    {
        $origin = $binding->endpoint()->origin();
        $lines = [
            '        new \\Kongono\\ExternalServiceContract\\Binding\\ServiceBinding(',
            '            new \\Kongono\\ExternalServiceContract\\Binding\\ServiceBindingIdentity(' . self::literal($binding->identity()->value()) . '),',
            '            new \\Kongono\\ExternalServiceContract\\Binding\\ServiceProfileIdentity(' . self::literal($binding->profile()->value()) . '),',
            '            new \\Kongono\\ExternalServiceContract\\Endpoint\\ServiceEndpoint(',
            '                new \\Kongono\\NetworkEgressContract\\Origin\\CanonicalOrigin(',
            '                    \\Kongono\\NetworkEgressContract\\Origin\\OriginScheme::' . $origin->scheme()->name . ',',
            '                    ' . self::literal($origin->host()) . ',',
            '                    ' . $origin->port() . ',',
            '                ),',
            '                new \\Kongono\\ExternalServiceContract\\Endpoint\\EndpointBasePath(' . self::literal($binding->endpoint()->basePath()->value()) . '),',
            '            ),',
            '            new \\Kongono\\ExternalServiceContract\\Credential\\CredentialReference(',
            '                new \\Kongono\\ExternalServiceContract\\Credential\\CredentialSourceIdentity(' . self::literal($binding->credentialReference()->source()->value()) . '),',
            '                ' . self::literal($binding->credentialReference()->reference()) . ',',
            '            ),',
            '            new \\Kongono\\NetworkEgressContract\\Capability\\TransportCapabilitySet([',
        ];
        foreach ($binding->requiredCapabilities()->all() as $capability) {
            $lines[] = '                \\Kongono\\NetworkEgressContract\\Capability\\TransportCapability::' . $capability->name . ',';
        }
        $lines[] = '            ]),';
        $lines[] = '        ),';

        return $lines;
    }

    private static function literal(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    /** @param array<string, mixed> $metadata */
    private static function text(array $metadata, string $key): string
    {
        $value = $metadata[$key] ?? null;
        if (!is_string($value)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_shape', $key);
        }

        return $value;
    }

    private function __construct() {}
}
