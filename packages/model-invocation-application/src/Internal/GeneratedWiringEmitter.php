<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\InvalidGeneratedModelInvocationWiringException;

/** Emits one fixed ordinary-PHP template from bounded validated metadata. */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringEmitter
{
    /** @param array<string, mixed> $metadata */
    public static function emit(array $metadata): string
    {
        try {
            $json = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_json', $failure);
        }
        $namespace = self::text($metadata, 'generated_namespace');
        $applicationProfile = self::text($metadata, 'application_profile_identity');
        $plan = self::text($metadata, 'plan_identity');
        $invocation = self::text($metadata, 'invocation_identity');
        $modelProfile = self::text($metadata, 'model_profile_identity');
        $provider = self::text($metadata, 'provider_identity');
        $model = self::text($metadata, 'model_identity');
        $adapter = self::text($metadata, 'adapter_identity');
        $artifact = self::text($metadata, 'artifact_sha256');
        $source = self::text($metadata, 'source_sha256');
        $request = self::text($metadata, 'request_sha256');

        return <<<PHP
<?php

declare(strict_types=1);

// {$json}

namespace {$namespace};

use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationApplicationBoundary;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplication;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;

final class ModelInvocationApplicationBoundary implements GeneratedModelInvocationApplicationBoundary
{
    public function start(ModelInvocationApplicationProfile \$profile, InvocationAdapter \$adapter): ModelInvocationApplication
    {
        \$source = \$profile->source();
        \$modelProfile = \$source->request()->profile();
        if (
            !hash_equals('{$applicationProfile}', \$profile->identity()->value())
            || '{$plan}' !== \$source->identity()->value()
            || '{$invocation}' !== \$source->request()->identity()->value()
            || '{$modelProfile}' !== \$modelProfile->identity()->value()
            || '{$provider}' !== \$modelProfile->providerIdentity()->value()
            || '{$model}' !== \$modelProfile->modelIdentity()->value()
            || '{$adapter}' !== \$source->adapterIdentity()->value()
            || !hash_equals('{$artifact}', \$profile->artifact()->artifactSha256())
            || !hash_equals('{$source}', \$profile->artifact()->sourceSha256())
            || !hash_equals('{$request}', \$profile->artifact()->requestSha256())
        ) {
            throw InvalidModelInvocationApplicationProfileException::invalid('generated_profile_mismatch');
        }

        return new ModelInvocationApplication(\$profile, \$adapter);
    }
}
PHP . "\n";
    }

    /** @param array<string, mixed> $metadata */
    private static function text(array $metadata, string $key): string
    {
        $value = $metadata[$key] ?? null;
        if (!is_string($value)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_shape');
        }

        return $value;
    }

    private function __construct() {}
}
