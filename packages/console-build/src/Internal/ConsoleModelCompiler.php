<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\Console\Exception\InvalidConsoleValueException;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;
use Kongono\ConsoleBuild\Model\ConsoleModel;

/** Compiles strict command definitions exclusively through Console invariant owners. */
#[Api(ApiStatus::Internal)]
final class ConsoleModelCompiler
{
    public const MAX_COMMANDS = 256;
    public const MAX_INPUTS_PER_COMMAND = 64;

    /** @param array<string, mixed> $source */
    public function compile(array $source): ConsoleModel
    {
        $this->assertKeys($source, ['schema', 'version', 'commands'], ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourceSchema);
        if ('kongono.command-source' !== ($source['schema'] ?? null) || 1 !== ($source['version'] ?? null)) {
            $this->fail(ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourceSchema);
        }

        return new ConsoleModel($this->specifications($source['commands'] ?? null));
    }

    /** @param array<string, mixed> $data */
    public function hydrateModel(array $data): ConsoleModel
    {
        $this->assertKeys($data, ['schema', 'version', 'commands'], ConsoleBuildDiagnostic::InvalidArtifact, ConsoleBuildFailureCategory::ArtifactStructure);
        if ('kongono.command-model' !== ($data['schema'] ?? null) || 1 !== ($data['version'] ?? null)) {
            $this->fail(ConsoleBuildDiagnostic::InvalidArtifact, ConsoleBuildFailureCategory::ArtifactStructure);
        }

        return new ConsoleModel($this->specifications($data['commands'] ?? null));
    }

    /** @return array<string, mixed> */
    public function modelData(ConsoleModel $model): array
    {
        return [
            'schema' => 'kongono.command-model',
            'version' => 1,
            'commands' => array_map(static fn(CommandSpecification $command): array => [
                'identifier' => $command->definition()->identifier()->value(),
                'summary' => $command->definition()->summary()->value(),
                'interaction' => $command->definition()->interactionPolicy()->value,
                'mutation' => $command->definition()->mutationPolicy()->value,
                'inputs' => array_map(static fn(CommandInputDefinition $input): array => [
                    'name' => $input->name()->value(),
                    'required' => CommandInputRequirement::Required === $input->requirement(),
                    'sensitivity' => $input->sensitivity()->value,
                ], $command->inputs()),
            ], $model->specifications()->commands()),
        ];
    }

    private function specifications(mixed $value): CommandSpecificationSet
    {
        $commands = $this->list($value);
        if ([] === $commands || count($commands) > self::MAX_COMMANDS) {
            $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
        }

        $specifications = [];
        foreach ($commands as $valueCommand) {
            $command = $this->object($valueCommand);
            $this->assertKeys($command, ['identifier', 'summary', 'interaction', 'mutation', 'inputs'], ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
            $interaction = is_string($command['interaction'] ?? null) ? CommandInteractionPolicy::tryFrom($command['interaction']) : null;
            $mutation = is_string($command['mutation'] ?? null) ? CommandMutationPolicy::tryFrom($command['mutation']) : null;
            if (null === $interaction || null === $mutation) {
                $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
            }

            $inputValues = $this->list($command['inputs'] ?? null);
            if (count($inputValues) > self::MAX_INPUTS_PER_COMMAND) {
                $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
            }
            $inputs = [];
            foreach ($inputValues as $valueInput) {
                $input = $this->object($valueInput);
                $this->assertKeys($input, ['name', 'required', 'sensitivity'], ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
                $sensitivity = is_string($input['sensitivity'] ?? null) ? CommandInputSensitivity::tryFrom($input['sensitivity']) : null;
                if (!is_bool($input['required'] ?? null) || null === $sensitivity) {
                    $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
                }
                try {
                    $inputs[] = new CommandInputDefinition(
                        new CommandInputName($this->string($input['name'] ?? null)),
                        $input['required'] ? CommandInputRequirement::Required : CommandInputRequirement::Optional,
                        $sensitivity,
                    );
                } catch (InvalidConsoleValueException) {
                    $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
                }
            }

            try {
                $specifications[] = new CommandSpecification(new CommandDefinition(
                    new CommandIdentifier($this->string($command['identifier'] ?? null)),
                    new CommandSummary($this->string($command['summary'] ?? null)),
                    $interaction,
                    $mutation,
                ), $inputs);
            } catch (InvalidConsoleValueException) {
                $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
            }
        }

        try {
            return new CommandSpecificationSet($specifications);
        } catch (InvalidConsoleValueException) {
            $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            $this->fail(ConsoleBuildDiagnostic::InvalidModel, ConsoleBuildFailureCategory::Model);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function assertKeys(array $value, array $keys, ConsoleBuildDiagnostic $diagnostic, ConsoleBuildFailureCategory $category): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            $this->fail($diagnostic, $category);
        }
    }

    private function fail(ConsoleBuildDiagnostic $diagnostic, ConsoleBuildFailureCategory $category): never
    {
        throw InvalidConsoleSourceException::invalid($diagnostic, $category);
    }
}
