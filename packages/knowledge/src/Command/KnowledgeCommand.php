<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Command;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactPublisher;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\ExceptionInterface;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\KnowledgePublicationException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Throwable;

/** Implements the exact non-interactive check, generate, and validate grammar. */
#[Api(ApiStatus::Internal)]
final class KnowledgeCommand
{
    /**
     * @param list<string>  $arguments
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function run(array $arguments, $stdout = null, $stderr = null): int
    {
        $stdout ??= STDOUT;
        $stderr ??= STDERR;
        $operation = isset($arguments[0]) && in_array($arguments[0], ['check', 'generate', 'validate'], true) ? $arguments[0] : 'command';

        try {
            $artifact = match ($operation) {
                'check' => $this->check($arguments),
                'generate' => $this->generate($arguments),
                'validate' => $this->validate($arguments),
                default => throw new InvalidKnowledgeSourceException(
                    KnowledgeDiagnostic::InvalidCommand,
                    [] === $arguments ? 'argument_count' : 'unknown_operation',
                ),
            };
            $result = [
                'status' => 'ok',
                'operation' => $operation,
                'artifact_sha256' => $artifact->artifactSha256(),
                'source_set_sha256' => $artifact->sourceSetSha256(),
                'source_count' => $artifact->sourceCount(),
            ];
            if ('generate' === $operation) {
                $result['artifact_file'] = sprintf('kongono-framework-knowledge-v1-%s.json', $artifact->artifactSha256());
            }
            fwrite($stdout, CanonicalJson::encode($result));

            return 0;
        } catch (ExceptionInterface $exception) {
            $fields = $exception->diagnosticContext()->fields();
            $violation = isset($fields[0]) && is_string($fields[0]->value()) ? $fields[0]->value() : 'unexpected_failure';
            fwrite($stderr, CanonicalJson::encode([
                'status' => 'error',
                'operation' => $operation,
                'diagnostic_id' => $exception->diagnosticId(),
                'title' => $exception->diagnosticTitle(),
                'remediation' => $exception->diagnosticRemediation(),
                'violation' => $violation,
            ]));

            return $this->exitCode($exception);
        } catch (Throwable) {
            $diagnostic = KnowledgeDiagnostic::UnexpectedCommandFailure;
            fwrite($stderr, CanonicalJson::encode([
                'status' => 'error',
                'operation' => $operation,
                'diagnostic_id' => $diagnostic->value,
                'title' => $diagnostic->title(),
                'remediation' => $diagnostic->remediation(),
                'violation' => 'unexpected_failure',
            ]));

            return 70;
        }
    }

    /** @param list<string> $arguments */
    private function check(array $arguments): FrameworkKnowledgeArtifact
    {
        if (2 !== count($arguments)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'argument_count');
        }
        $this->requireRoot($arguments[1]);
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($arguments[1]);

        return (new FrameworkKnowledgeArtifactValidator())->validateCurrent($arguments[1], $artifact->canonicalBytes());
    }

    /** @param list<string> $arguments */
    private function generate(array $arguments): FrameworkKnowledgeArtifact
    {
        if (3 !== count($arguments)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'argument_count');
        }
        $this->requireRoot($arguments[1]);
        if (false === realpath($arguments[2]) || !is_dir($arguments[2]) || is_link($arguments[2])) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'invalid_output_directory');
        }
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($arguments[1]);
        (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, $arguments[2]);

        return $artifact;
    }

    /** @param list<string> $arguments */
    private function validate(array $arguments): FrameworkKnowledgeArtifact
    {
        if (3 !== count($arguments)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'argument_count');
        }
        $this->requireRoot($arguments[1]);
        if (!is_file($arguments[2]) || is_link($arguments[2])) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'invalid_artifact_path');
        }
        $size = filesize($arguments[2]);
        if (false === $size || $size > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_shape');
        }
        $bytes = file_get_contents($arguments[2]);
        if (false === $bytes) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'invalid_artifact_path');
        }

        return (new FrameworkKnowledgeArtifactValidator())->validateCurrent($arguments[1], $bytes);
    }

    private function requireRoot(string $root): void
    {
        if (false === realpath($root) || !is_dir($root) || is_link($root)) {
            throw new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidCommand, 'invalid_root');
        }
    }

    private function exitCode(ExceptionInterface $exception): int
    {
        return match (true) {
            $exception instanceof KnowledgePublicationException => 73,
            $exception instanceof InvalidKnowledgeArtifactException,
            $exception instanceof StaleKnowledgeArtifactException,
            'KGN-KNOWLEDGE-1001' === $exception->diagnosticId() => 65,
            'KGN-KNOWLEDGE-1010' === $exception->diagnosticId() => 64,
            default => 66,
        };
    }
}
