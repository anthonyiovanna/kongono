<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Throwable;

/** Converts between exact secret-free binding values and their closed array shape. */
#[Api(ApiStatus::Internal)]
final class BindingArtifactCodec
{
    /** @return array{source_identity: string, bindings: list<array<string, mixed>>} */
    public static function sourceData(ExternalServiceBindingSource $source): array
    {
        return [
            'source_identity' => $source->identity()->value(),
            'bindings' => array_map(self::bindingData(...), $source->bindings()),
        ];
    }

    /** @return array<string, mixed> */
    public static function bindingData(ServiceBinding $binding): array
    {
        $origin = $binding->endpoint()->origin();

        return [
            'binding_identity' => $binding->identity()->value(),
            'service_profile_identity' => $binding->profile()->value(),
            'endpoint' => [
                'scheme' => $origin->scheme()->value,
                'host' => $origin->host(),
                'port' => $origin->port(),
                'base_path' => $binding->endpoint()->basePath()->value(),
            ],
            'credential_reference' => [
                'source_identity' => $binding->credentialReference()->source()->value(),
                'reference' => $binding->credentialReference()->reference(),
            ],
            'required_capabilities' => array_map(static fn(TransportCapability $capability): string => $capability->value, $binding->requiredCapabilities()->all()),
        ];
    }

    public static function source(mixed $value): ExternalServiceBindingSource
    {
        $source = self::object($value, 'source');
        self::keys($source, ['source_identity', 'bindings'], 'source');
        $rawBindings = self::list($source['bindings'] ?? null, 64, 'bindings');
        $bindings = [];
        foreach ($rawBindings as $position => $rawBinding) {
            $bindings[] = self::binding($rawBinding, $position, count($rawBindings));
        }

        try {
            return new ExternalServiceBindingSource(
                new ExternalServiceBindingSourceIdentity(self::text($source['source_identity'] ?? null, 'source_identity')),
                $bindings,
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidExternalServiceBindingArtifactException) {
                throw $failure;
            }
            throw InvalidExternalServiceBindingArtifactException::invalid('source_invalid', 'source', previous: $failure);
        }
    }

    private static function binding(mixed $value, int $position, int $count): ServiceBinding
    {
        $binding = self::object($value, 'bindings', $position);
        self::keys($binding, ['binding_identity', 'service_profile_identity', 'endpoint', 'credential_reference', 'required_capabilities'], 'bindings', $position);
        $endpoint = self::object($binding['endpoint'] ?? null, 'endpoint', $position);
        self::keys($endpoint, ['scheme', 'host', 'port', 'base_path'], 'endpoint', $position);
        $credential = self::object($binding['credential_reference'] ?? null, 'credential_reference', $position);
        self::keys($credential, ['source_identity', 'reference'], 'credential_reference', $position);
        $schemeValue = self::text($endpoint['scheme'] ?? null, 'scheme', $position);
        $scheme = OriginScheme::tryFrom($schemeValue);
        if (null === $scheme) {
            throw InvalidExternalServiceBindingArtifactException::invalid('endpoint_invalid', 'scheme', $count, $position);
        }
        $capabilityValues = self::list($binding['required_capabilities'] ?? null, 11, 'required_capabilities', $position);
        $capabilities = [];
        foreach ($capabilityValues as $capabilityPosition => $rawCapability) {
            if (!is_string($rawCapability) || null === ($capability = TransportCapability::tryFrom($rawCapability))) {
                throw InvalidExternalServiceBindingArtifactException::invalid('capability_invalid', 'required_capabilities', count($capabilityValues), $capabilityPosition);
            }
            $capabilities[] = $capability;
        }

        try {
            return new ServiceBinding(
                new ServiceBindingIdentity(self::text($binding['binding_identity'] ?? null, 'binding_identity', $position)),
                new ServiceProfileIdentity(self::text($binding['service_profile_identity'] ?? null, 'service_profile_identity', $position)),
                new ServiceEndpoint(
                    new CanonicalOrigin(
                        $scheme,
                        self::text($endpoint['host'] ?? null, 'host', $position),
                        self::integer($endpoint['port'] ?? null, 'port', $position, 1, 65_535),
                    ),
                    new EndpointBasePath(self::text($endpoint['base_path'] ?? null, 'base_path', $position)),
                ),
                new CredentialReference(
                    new CredentialSourceIdentity(self::text($credential['source_identity'] ?? null, 'credential_source_identity', $position)),
                    self::text($credential['reference'] ?? null, 'credential_reference', $position),
                ),
                new TransportCapabilitySet($capabilities),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidExternalServiceBindingArtifactException) {
                throw $failure;
            }
            throw InvalidExternalServiceBindingArtifactException::invalid('binding_invalid', 'bindings', $count, $position, previous: $failure);
        }
    }

    /** @param array<string, mixed> $object
     * @param list<string> $keys
     */
    private static function keys(array $object, array $keys, string $field, ?int $position = null): void
    {
        if (array_keys($object) !== $keys) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field, position: $position);
        }
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $field, ?int $position = null): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field, position: $position);
        }

        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field, position: $position);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, int $limit, string $field, ?int $position = null): array
    {
        if (!is_array($value) || !array_is_list($value) || [] === $value || count($value) > $limit) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_list', $field, is_array($value) ? count($value) : null, $position, $limit);
        }

        return $value;
    }

    private static function text(mixed $value, string $field, ?int $position = null): string
    {
        if (!is_string($value)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_scalar', $field, position: $position);
        }

        return $value;
    }

    private static function integer(mixed $value, string $field, int $position, int $minimum, int $maximum): int
    {
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_scalar', $field, position: $position, limit: $maximum);
        }

        return $value;
    }

    private function __construct() {}
}
