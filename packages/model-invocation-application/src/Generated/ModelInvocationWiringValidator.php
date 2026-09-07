<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\InvalidGeneratedModelInvocationWiringException;
use Kongono\ModelInvocationApplication\Internal\GeneratedWiringEmitter;
use Kongono\ModelInvocationApplication\Internal\GeneratedWiringImplementation;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileIdentity;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Throwable;

/** Separates structural validation from caller-relative exact-current proof. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringValidator
{
    public function validateStructure(string $bytes): GeneratedModelInvocationWiring
    {
        if ('' === $bytes || strlen($bytes) > ModelInvocationWiringGenerator::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('generated_bytes');
        }
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([A-Za-z0-9_\\\\]+);\n/D', $bytes, $matches)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('generated_header');
        }
        try {
            $decoded = json_decode($matches[1], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_json', $failure);
        }
        $keys = ['schema', 'version', 'contract_version', 'application_profile_identity', 'plan_identity', 'invocation_identity', 'model_profile_identity', 'provider_identity', 'model_identity', 'adapter_identity', 'artifact_sha256', 'source_sha256', 'request_sha256', 'generator_implementation_sha256', 'generated_namespace', 'generated_class', 'generated_interface'];
        if (!is_array($decoded) || array_is_list($decoded) || array_keys($decoded) !== $keys || 'kongono.model-invocation-application.generated-wiring' !== ($decoded['schema'] ?? null) || 1 !== ($decoded['version'] ?? null) || 1 !== ($decoded['contract_version'] ?? null)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_shape');
        }
        /** @var array<string, mixed> $metadata */
        $metadata = $decoded;
        try {
            if (json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !== $matches[1]) {
                throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_json');
            }
        } catch (JsonException $failure) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_json', $failure);
        }
        try {
            $applicationProfile = new ModelInvocationApplicationProfileIdentity($this->digest($metadata['application_profile_identity'] ?? null));
            $plan = new InvocationPlanIdentity($this->text($metadata['plan_identity'] ?? null));
            $invocation = new InvocationIdentity($this->text($metadata['invocation_identity'] ?? null));
            $modelProfile = new ProfileIdentity($this->text($metadata['model_profile_identity'] ?? null));
            $provider = new ProviderIdentity($this->text($metadata['provider_identity'] ?? null));
            $model = new ModelIdentity($this->text($metadata['model_identity'] ?? null));
            $adapter = new AdapterIdentity($this->text($metadata['adapter_identity'] ?? null));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedModelInvocationWiringException::invalid('identity_grammar', $failure);
        }
        $artifact = $this->digest($metadata['artifact_sha256'] ?? null);
        $source = $this->digest($metadata['source_sha256'] ?? null);
        $request = $this->digest($metadata['request_sha256'] ?? null);
        $implementation = $this->digest($metadata['generator_implementation_sha256'] ?? null);
        if (!hash_equals(GeneratedWiringImplementation::sha256(), $implementation)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('implementation_mismatch');
        }
        $namespace = $this->text($metadata['generated_namespace'] ?? null);
        $class = $this->text($metadata['generated_class'] ?? null);
        if ($namespace !== 'KongonoGenerated\\ModelInvocation\\V1\\P' . $applicationProfile->value() || $class !== $namespace . '\\ModelInvocationApplicationBoundary' || GeneratedModelInvocationApplicationBoundary::class !== ($metadata['generated_interface'] ?? null) || $matches[2] !== $namespace) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('identity_grammar');
        }
        try {
            if (!hash_equals(GeneratedWiringEmitter::emit($metadata), $bytes)) {
                throw InvalidGeneratedModelInvocationWiringException::invalid('template_mismatch');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidGeneratedModelInvocationWiringException || $failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedModelInvocationWiringException::invalid('template_mismatch', $failure);
        }

        return new GeneratedModelInvocationWiring($applicationProfile, $plan, $invocation, $modelProfile, $provider, $model, $adapter, $artifact, $source, $request, $implementation, $namespace, $class, $bytes, hash('sha256', $bytes));
    }

    public function validateCurrent(ModelInvocationApplicationProfile $profile, string $bytes): GeneratedModelInvocationWiring
    {
        $validated = $this->validateStructure($bytes);
        $expected = (new ModelInvocationWiringGenerator())->generate($profile);
        if (!hash_equals($expected->canonicalBytes(), $bytes)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('profile_mismatch');
        }

        return $validated;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('identity_grammar');
        }

        return $value;
    }

    private function text(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidGeneratedModelInvocationWiringException::invalid('metadata_shape');
        }

        return $value;
    }
}
