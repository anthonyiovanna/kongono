<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Console;

use JsonException;
use RuntimeException;
use Throwable;

/** Executes the closed real-process Phase 8 structured-CLI acceptance profile. */
final class ConsoleAcceptance
{
    private const int FAILURE = 1;
    private const int MAX_STREAM_BYTES = 262_144;
    private const int PROCESS_TIMEOUT_SECONDS = 60;
    private const string FAILURE_BYTES = "phase8_acceptance_failed\n";
    private const string ENVIRONMENT_SECRET = 'Phase8HEnvironmentSecret-DoNotDisclose';

    private string $repositoryRoot = '';

    private string $source = '';

    private string $proxy = '';

    private int $caseCount = 0;

    /** @var list<string> */
    private array $observedOutput = [];

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource                $stdout
     * @param resource                $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        $application = null;
        $before = null;
        $failed = false;

        try {
            $this->require(array_is_list($arguments) && [] === $arguments, 'invalid_invocation');
            $this->require(is_resource($stdout) && is_resource($stderr), 'invalid_output_ports');
            $root = realpath(dirname(__DIR__, 3));
            if (false === $root || is_link($root) || !is_dir($root)) {
                throw new RuntimeException('repository_root_invalid');
            }
            $this->repositoryRoot = self::normalize($root);
            $this->source = $this->ordinaryExecutable($this->repositoryRoot . '/packages/console-application/bin/kongo');
            $this->proxy = $this->ordinaryExecutable($this->repositoryRoot . '/vendor/bin/kongo');
            $before = $this->repositoryStatus();
            $application = TemporaryApplication::create($this->repositoryRoot);
            $this->accept($application);
        } catch (Throwable) {
            $failed = true;
        } finally {
            if (null !== $application) {
                try {
                    $root = $application->root();
                    $application->cleanup();
                    $this->require(!file_exists($root) && !is_link($root), 'temporary_residue');
                } catch (Throwable) {
                    $failed = true;
                }
            }
            if (null !== $before) {
                try {
                    $this->require($before === $this->repositoryStatus(), 'repository_status_changed');
                } catch (Throwable) {
                    $failed = true;
                }
            }
        }

        if ($failed || $this->caseCount < 1) {
            $this->write($stderr, self::FAILURE_BYTES);

            return self::FAILURE;
        }

        $success = json_encode([
            'status' => 'ok',
            'profile' => 'kongono-phase8-cli',
            'case_count' => $this->caseCount,
            'cleanup' => 'complete',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->write($stdout, $success);

        return 0;
    }

    private function accept(TemporaryApplication $application): void
    {
        $expectedHelp = [
            'identifier' => 'application:build',
            'summary' => 'Build exact current application composition outputs',
            'interaction' => 'none',
            'mutation' => 'mutation_capable',
            'inputs' => [
                ['name' => 'application-root', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ['name' => 'output-directory', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
            ],
        ];
        $expectedGlobalHelp = [
            [
                'identifier' => 'application-graph:inspect',
                'summary' => 'Inspect one authoritative application graph artifact',
                'interaction' => 'none',
                'mutation' => 'read_only',
                'inputs' => [
                    ['name' => 'artifact-path', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ],
            ],
            $expectedHelp,
            [
                'identifier' => 'observability:check',
                'summary' => 'Check exact current observability configuration evidence',
                'interaction' => 'none',
                'mutation' => 'read_only',
                'inputs' => [
                    ['name' => 'application-root', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                    ['name' => 'output-directory', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ],
            ],
            [
                'identifier' => 'observability:inspect',
                'summary' => 'Inspect planned observability configuration evidence',
                'interaction' => 'none',
                'mutation' => 'read_only',
                'inputs' => [
                    ['name' => 'application-root', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                    ['name' => 'output-directory', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ],
            ],
            [
                'identifier' => 'security:check',
                'summary' => 'Check exact current security policy evidence',
                'interaction' => 'none',
                'mutation' => 'read_only',
                'inputs' => [
                    ['name' => 'application-root', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                    ['name' => 'output-directory', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ],
            ],
            [
                'identifier' => 'security:inspect',
                'summary' => 'Inspect planned security policy evidence',
                'interaction' => 'none',
                'mutation' => 'read_only',
                'inputs' => [
                    ['name' => 'application-root', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                    ['name' => 'output-directory', 'requirement' => 'required', 'sensitivity' => 'ordinary'],
                ],
            ],
        ];
        $expectedHumanHelp = "Command: application:build\n"
            . "Summary: Build exact current application composition outputs\n"
            . "Usage: kongo application:build --application-root=<value> --output-directory=<value>\n"
            . "Interaction: none\nMutation: mutation_capable\nInputs:\n"
            . "  --application-root=<value> (required, ordinary)\n"
            . "  --output-directory=<value> (required, ordinary)\n";
        $expectedGlobalHumanHelp = "Command: application-graph:inspect\n"
            . "Summary: Inspect one authoritative application graph artifact\n"
            . "Usage: kongo application-graph:inspect --artifact-path=<value>\n"
            . "Interaction: none\nMutation: read_only\nInputs:\n"
            . "  --artifact-path=<value> (required, ordinary)\n\n"
            . $expectedHumanHelp
            . "\nCommand: observability:check\n"
            . "Summary: Check exact current observability configuration evidence\n"
            . "Usage: kongo observability:check --application-root=<value> --output-directory=<value>\n"
            . "Interaction: none\nMutation: read_only\nInputs:\n"
            . "  --application-root=<value> (required, ordinary)\n"
            . "  --output-directory=<value> (required, ordinary)\n"
            . "\nCommand: observability:inspect\n"
            . "Summary: Inspect planned observability configuration evidence\n"
            . "Usage: kongo observability:inspect --application-root=<value> --output-directory=<value>\n"
            . "Interaction: none\nMutation: read_only\nInputs:\n"
            . "  --application-root=<value> (required, ordinary)\n"
            . "  --output-directory=<value> (required, ordinary)\n"
            . "\nCommand: security:check\n"
            . "Summary: Check exact current security policy evidence\n"
            . "Usage: kongo security:check --application-root=<value> --output-directory=<value>\n"
            . "Interaction: none\nMutation: read_only\nInputs:\n"
            . "  --application-root=<value> (required, ordinary)\n"
            . "  --output-directory=<value> (required, ordinary)\n"
            . "\nCommand: security:inspect\n"
            . "Summary: Inspect planned security policy evidence\n"
            . "Usage: kongo security:inspect --application-root=<value> --output-directory=<value>\n"
            . "Interaction: none\nMutation: read_only\nInputs:\n"
            . "  --application-root=<value> (required, ordinary)\n"
            . "  --output-directory=<value> (required, ordinary)\n";

        $directHumanHelp = $this->execute($application, $this->source, []);
        $this->expect($directHumanHelp, 0, $expectedGlobalHumanHelp, '');
        $this->human($directHumanHelp->stdout());
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: application-graph:inspect'), 'human_help_command_count');
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: application:build'), 'human_help_command_count');
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: observability:check'), 'human_help_command_count');
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: observability:inspect'), 'human_help_command_count');
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: security:check'), 'human_help_command_count');
        $this->require(1 === substr_count($directHumanHelp->stdout(), 'Command: security:inspect'), 'human_help_command_count');
        $this->caseCompleted();

        $directMachineHelp = $this->execute($application, $this->source, ['--json']);
        $this->expectExitAndChannel($directMachineHelp, 0, true);
        $directHelpDocument = $this->machine($directMachineHelp->stdout());
        $this->require(['schema', 'version', 'commands'] === array_keys($directHelpDocument), 'help_keys_invalid');
        $this->require('kongono.command-help' === $directHelpDocument['schema'] && 1 === $directHelpDocument['version'], 'help_identity_invalid');
        $this->require($expectedGlobalHelp === $directHelpDocument['commands'], 'help_semantics_invalid');
        $this->caseCompleted();

        $proxyHumanHelp = $this->execute($application, $this->proxy, ['application:build', '--help']);
        $this->expect($proxyHumanHelp, 0, $expectedHumanHelp, '');
        $this->human($proxyHumanHelp->stdout());
        $this->caseCompleted();

        $proxyMachineHelp = $this->execute($application, $this->proxy, ['--json', 'application:build', '--help']);
        $this->expectExitAndChannel($proxyMachineHelp, 0, true);
        $proxyHelpDocument = $this->machine($proxyMachineHelp->stdout());
        $this->require([
            'schema' => 'kongono.command-help',
            'version' => 1,
            'commands' => [$expectedHelp],
        ] === $proxyHelpDocument, 'proxy_help_mismatch');
        $this->caseCompleted();

        $unknown = $this->execute($application, $this->source, ['--json', 'unknown:command']);
        $this->expectExitAndChannel($unknown, 2, true);
        $this->expectFailureDocument($this->machine($unknown->stdout()), 2, 'invalid_input');
        $this->caseCompleted();

        $application->useInvalidSource();
        $invalidBefore = $application->outputIdentity();
        $invalid = $this->execute($application, $this->proxy, $this->buildArguments($application, '--plan', true));
        $this->expectExitAndChannel($invalid, 3, true);
        $this->expectFailureDocument($this->machine($invalid->stdout()), 3, 'precondition');
        $this->require([] === $application->outputFiles() && $invalidBefore === $application->outputIdentity(), 'invalid_source_published');
        $application->useOriginalSource();
        $this->caseCompleted();

        $beforePlan = $application->treeIdentity();
        $plan = $this->execute($application, $this->source, $this->buildArguments($application, '--plan', true));
        $this->expectExitAndChannel($plan, 0, true);
        $planDocument = $this->machine($plan->stdout());
        $planFields = $this->successFields($planDocument, 'plan');
        $this->require(true === $planFields['publication_required'], 'plan_publication_decision_invalid');
        $this->require([] === $application->outputFiles() && $beforePlan === $application->treeIdentity(), 'plan_mutated_application');
        $this->caseCompleted();

        $humanPlan = $this->execute($application, $this->source, $this->buildArguments($application, '--plan', false));
        $this->expectExitAndChannel($humanPlan, 0, true);
        $this->human($humanPlan->stdout());
        $this->require($this->humanResult($planFields) === $humanPlan->stdout(), 'human_plan_semantics_invalid');
        $this->require([] === $application->outputFiles(), 'human_plan_published');
        $this->caseCompleted();

        $apply = $this->execute($application, $this->proxy, [...$this->buildArguments($application, '--apply', true), '--no-interaction']);
        $this->expectExitAndChannel($apply, 0, true);
        $applyFields = $this->successFields($this->machine($apply->stdout()), 'apply');
        $this->require(false === $applyFields['publication_required'], 'apply_publication_decision_invalid');
        $this->require($this->identities($planFields) === $this->identities($applyFields), 'apply_identity_mismatch');
        $expectedFiles = [$planFields['artifact_filename'], $planFields['generated_php_filename']];
        sort($expectedFiles, SORT_STRING);
        $this->require($expectedFiles === $application->outputFiles(), 'apply_output_inventory_invalid');
        $this->require($planFields['artifact_sha256'] === hash('sha256', $application->readOwned($application->outputPath($planFields['artifact_filename']))), 'artifact_bytes_mismatch');
        $this->require($planFields['generated_wiring_sha256'] === hash('sha256', $application->readOwned($application->outputPath($planFields['generated_php_filename']))), 'wiring_bytes_mismatch');
        $this->caseCompleted();

        $outputIdentity = $application->outputIdentity();
        $planAfterApply = $this->execute($application, $this->source, $this->buildArguments($application, '--plan', true));
        $this->expectExitAndChannel($planAfterApply, 0, true);
        $afterFields = $this->successFields($this->machine($planAfterApply->stdout()), 'plan');
        $this->require(false === $afterFields['publication_required'], 'current_plan_publication_invalid');
        $this->require($this->identities($planFields) === $this->identities($afterFields), 'current_plan_identity_mismatch');
        $this->require($outputIdentity === $application->outputIdentity(), 'current_plan_changed_outputs');
        $this->caseCompleted();

        $repeatApply = $this->execute($application, $this->source, [...$this->buildArguments($application, '--apply', true), '--no-interaction']);
        $this->expectExitAndChannel($repeatApply, 0, true);
        $repeatFields = $this->successFields($this->machine($repeatApply->stdout()), 'apply');
        $this->require($this->identities($applyFields) === $this->identities($repeatFields), 'repeat_apply_identity_mismatch');
        $this->require($outputIdentity === $application->outputIdentity(), 'repeat_apply_changed_outputs');
        $this->caseCompleted();

        $application->useVariantSource();
        $variantPlan = $this->execute($application, $this->proxy, $this->buildArguments($application, '--plan', true));
        $this->expectExitAndChannel($variantPlan, 0, true);
        $variantFields = $this->successFields($this->machine($variantPlan->stdout()), 'plan');
        $this->require(true === $variantFields['publication_required'], 'variant_plan_not_new');
        $this->require($this->identities($variantFields) !== $this->identities($planFields), 'variant_identity_unchanged');
        $sentinelPath = $application->createSentinel($variantFields['artifact_filename']);
        $variantSibling = $application->outputPath($variantFields['generated_php_filename']);
        $conflictIdentity = $application->outputIdentity();
        $conflict = $this->execute($application, $this->source, [...$this->buildArguments($application, '--apply', true), '--no-interaction']);
        $this->expectExitAndChannel($conflict, 3, true);
        $this->expectFailureDocument($this->machine($conflict->stdout()), 3, 'precondition');
        $this->require($application->sentinel() === $application->readOwned($sentinelPath), 'sentinel_changed');
        $this->require(!file_exists($variantSibling) && !is_link($variantSibling), 'conflict_partial_sibling');
        $this->require($conflictIdentity === $application->outputIdentity(), 'conflict_changed_outputs');
        $application->removeOwnedFile($sentinelPath);
        $this->caseCompleted();

        $application->useOriginalSource();
        $healthyDirect = $this->execute($application, $this->source, $this->buildArguments($application, '--plan', true));
        $healthyProxy = $this->execute($application, $this->proxy, $this->buildArguments($application, '--plan', true));
        $this->expectExitAndChannel($healthyDirect, 0, true);
        $this->expectExitAndChannel($healthyProxy, 0, true);
        $this->require($healthyDirect->stdout() === $healthyProxy->stdout(), 'edge_result_mismatch');
        $healthyFields = $this->successFields($this->machine($healthyDirect->stdout()), 'plan');
        $this->require(false === $healthyFields['publication_required'], 'recovery_not_current');
        $this->require($outputIdentity === $application->outputIdentity(), 'recovery_changed_outputs');
        $this->caseCompleted();

        $this->require(
            $expectedGlobalHumanHelp === $directHumanHelp->stdout()
            && $expectedGlobalHelp === $directHelpDocument['commands']
            && $expectedHumanHelp === $proxyHumanHelp->stdout()
            && [$expectedHelp] === $proxyHelpDocument['commands'],
            'help_parity_lost',
        );
        $this->caseCompleted();

        $allOutput = implode('', $this->observedOutput);
        foreach ([
            $application->root(),
            str_replace('/', '\\', $application->root()),
            $application->namespaceMarker(),
            $application->sentinel(),
            $application->rejectedMarker(),
            self::ENVIRONMENT_SECRET,
            'final readonly class RootVariant',
            'Stack trace',
            'stack trace',
            'debug',
            "\033",
        ] as $protected) {
            $this->require(!str_contains($allOutput, $protected), 'protected_output_disclosed');
        }
        $this->caseCompleted();
    }

    /** @return list<string> */
    private function buildArguments(TemporaryApplication $application, string $intent, bool $machine): array
    {
        $arguments = [];
        if ($machine) {
            $arguments[] = '--json';
        }
        $arguments[] = 'application:build';
        $arguments[] = $intent;
        $arguments[] = '--application-root=' . $application->root();
        $arguments[] = '--output-directory=' . $application->outputDirectory();

        return $arguments;
    }

    /** @param list<string> $arguments */
    private function execute(TemporaryApplication $application, string $edge, array $arguments): ProcessResult
    {
        $command = array_merge(
            [PHP_BINARY, '-d', 'auto_prepend_file=' . $application->preload(), $edge],
            $arguments,
        );
        $result = $this->process(
            $command,
            $this->repositoryRoot,
            ['KONGONO_PHASE8_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET],
        );
        $this->observedOutput[] = $result->stdout();
        $this->observedOutput[] = $result->stderr();

        return $result;
    }

    /** @return array<string, mixed> */
    private function machine(string $bytes): array
    {
        $this->require(strlen($bytes) <= self::MAX_STREAM_BYTES, 'machine_output_unbounded');
        $this->require(!str_contains($bytes, "\r") && !str_contains($bytes, "\033") && 1 === substr_count($bytes, "\n") && str_ends_with($bytes, "\n"), 'machine_framing_invalid');
        $this->require(1 === preg_match('//u', $bytes), 'machine_utf8_invalid');
        try {
            $document = json_decode(substr($bytes, 0, -1), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw new RuntimeException('machine_json_invalid', 0, $failure);
        }
        $document = $this->object($document, 'machine_document_invalid');
        $canonical = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->require($bytes === $canonical, 'machine_json_not_canonical');

        return $document;
    }

    private function human(string $bytes): void
    {
        $this->require('' !== $bytes && strlen($bytes) <= self::MAX_STREAM_BYTES, 'human_output_invalid');
        $this->require(!str_contains($bytes, "\r") && !str_contains($bytes, "\033") && str_ends_with($bytes, "\n"), 'human_framing_invalid');
        $this->require(1 === preg_match('//u', $bytes), 'human_utf8_invalid');
    }

    /** @param array<string, mixed> $document */
    private function expectFailureDocument(array $document, int $status, string $category): void
    {
        $this->require(['schema', 'version', 'outcome', 'exit_status', 'failure', 'data'] === array_keys($document), 'failure_keys_invalid');
        $this->require('kongono.command-result' === $document['schema'] && 1 === $document['version'], 'failure_schema_invalid');
        $this->require($status === $document['exit_status'] && null === $document['data'], 'failure_status_invalid');
        $failure = $this->object($document['failure'], 'failure_projection_invalid');
        $this->require(['category', 'diagnostic_id'] === array_keys($failure), 'failure_projection_invalid');
        $this->require($category === $failure['category'] && is_string($failure['diagnostic_id']) && 1 === preg_match('/\AKGN-[A-Z0-9-]+-[0-9]{4}\z/D', $failure['diagnostic_id']), 'failure_semantics_invalid');
    }

    /** @param array<string, mixed> $document
     *  @return array{artifact_filename: string, artifact_sha256: string, execution_intent: string, generated_php_filename: string, generated_wiring_sha256: string, output_count: int, publication_required: bool}
     */
    private function successFields(array $document, string $intent): array
    {
        $this->require(['schema', 'version', 'outcome', 'exit_status', 'failure', 'data'] === array_keys($document), 'result_keys_invalid');
        $this->require('kongono.command-result' === $document['schema'] && 1 === $document['version'], 'result_schema_invalid');
        $this->require('succeeded' === $document['outcome'] && 0 === $document['exit_status'] && null === $document['failure'], 'result_outcome_invalid');
        $data = $this->object($document['data'], 'result_data_invalid');
        $this->require(['fields', 'protected_field_count'] === array_keys($data) && 0 === $data['protected_field_count'], 'result_data_invalid');
        $fields = $data['fields'];
        if (!is_array($fields) || !array_is_list($fields) || 7 !== count($fields)) {
            throw new RuntimeException('result_field_count_invalid');
        }
        $values = [];
        foreach ($fields as $field) {
            $field = $this->object($field, 'result_field_invalid');
            $this->require(['name', 'kind', 'value'] === array_keys($field), 'result_field_invalid');
            $name = $field['name'];
            if (!is_string($name) || array_key_exists($name, $values)) {
                throw new RuntimeException('result_field_name_invalid');
            }
            $values[$name] = $field['value'];
        }
        $this->require([
            'artifact_filename',
            'artifact_sha256',
            'execution_intent',
            'generated_php_filename',
            'generated_wiring_sha256',
            'output_count',
            'publication_required',
        ] === array_keys($values), 'result_field_order_invalid');
        $artifactFilename = $values['artifact_filename'];
        $artifactSha256 = $values['artifact_sha256'];
        $executionIntent = $values['execution_intent'];
        $generatedPhpFilename = $values['generated_php_filename'];
        $generatedWiringSha256 = $values['generated_wiring_sha256'];
        $outputCount = $values['output_count'];
        $publicationRequired = $values['publication_required'];
        if (
            !is_string($artifactFilename)
            || basename($artifactFilename) !== $artifactFilename
            || !is_string($generatedPhpFilename)
            || basename($generatedPhpFilename) !== $generatedPhpFilename
        ) {
            throw new RuntimeException('result_filename_invalid');
        }
        if (
            !is_string($artifactSha256)
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $artifactSha256)
            || !is_string($generatedWiringSha256)
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $generatedWiringSha256)
        ) {
            throw new RuntimeException('result_sha_invalid');
        }
        if (!is_string($executionIntent) || $intent !== $executionIntent || 2 !== $outputCount || !is_bool($publicationRequired)) {
            throw new RuntimeException('result_intent_invalid');
        }

        return [
            'artifact_filename' => $artifactFilename,
            'artifact_sha256' => $artifactSha256,
            'execution_intent' => $executionIntent,
            'generated_php_filename' => $generatedPhpFilename,
            'generated_wiring_sha256' => $generatedWiringSha256,
            'output_count' => $outputCount,
            'publication_required' => $publicationRequired,
        ];
    }

    /** @param array{artifact_filename: string, artifact_sha256: string, execution_intent: string, generated_php_filename: string, generated_wiring_sha256: string, output_count: int, publication_required: bool} $fields */
    private function humanResult(array $fields): string
    {
        return "Succeeded.\n"
            . 'Artifact filename: ' . $fields['artifact_filename'] . "\n"
            . 'Artifact SHA-256: ' . $fields['artifact_sha256'] . "\n"
            . 'Execution intent: ' . $fields['execution_intent'] . "\n"
            . 'Generated PHP filename: ' . $fields['generated_php_filename'] . "\n"
            . 'Generated wiring SHA-256: ' . $fields['generated_wiring_sha256'] . "\n"
            . 'Output count: ' . $fields['output_count'] . "\n"
            . 'Publication required: ' . ($fields['publication_required'] ? 'true' : 'false') . "\n";
    }

    /** @param array{artifact_filename: string, artifact_sha256: string, execution_intent: string, generated_php_filename: string, generated_wiring_sha256: string, output_count: int, publication_required: bool} $fields
     *  @return array{artifact: string, wiring: string}
     */
    private function identities(array $fields): array
    {
        return ['artifact' => $fields['artifact_sha256'], 'wiring' => $fields['generated_wiring_sha256']];
    }

    private function expect(ProcessResult $result, int $exit, string $stdout, string $stderr): void
    {
        $this->require($result->started() && null === $result->blockedReason(), 'process_not_completed');
        $this->require($exit === $result->exitCode() && $stdout === $result->stdout() && $stderr === $result->stderr(), 'process_evidence_mismatch');
    }

    private function expectExitAndChannel(ProcessResult $result, int $exit, bool $stdout): void
    {
        $this->require($result->started() && null === $result->blockedReason() && $exit === $result->exitCode(), 'process_exit_invalid');
        if ($stdout) {
            $this->require('' !== $result->stdout() && '' === $result->stderr(), 'process_channel_invalid');
        } else {
            $this->require('' === $result->stdout() && '' !== $result->stderr(), 'process_channel_invalid');
        }
    }

    /**
     * @param list<string>               $command
     * @param array<string, string>|null $environment
     */
    private function process(array $command, string $workingDirectory, ?array $environment = null): ProcessResult
    {
        if ([] === $command) {
            return new ProcessResult(false, null, '', '', 'invalid_process');
        }
        $pipes = [];
        $process = @proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            $environment,
            ['bypass_shell' => true, 'suppress_errors' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new ProcessResult(false, null, '', '', 'process_start_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $blocked = null;
        $observedExit = null;
        $deadline = microtime(true) + self::PROCESS_TIMEOUT_SECONDS;
        try {
            while (true) {
                $this->drain($pipes[1], $stdout);
                $this->drain($pipes[2], $stderr);
                if (strlen($stdout) > self::MAX_STREAM_BYTES || strlen($stderr) > self::MAX_STREAM_BYTES) {
                    $blocked = 'output_limit';
                    proc_terminate($process);
                    break;
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $blocked = 'timeout';
                    proc_terminate($process);
                    break;
                }
                usleep(10_000);
            }
            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
        } finally {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
        }
        $closedExit = proc_close($process);
        $exit = null !== $observedExit ? $observedExit : ($closedExit >= 0 ? $closedExit : null);

        return new ProcessResult(true, $exit, substr($stdout, 0, self::MAX_STREAM_BYTES), substr($stderr, 0, self::MAX_STREAM_BYTES), $blocked);
    }

    /** @param resource $stream */
    private function drain(mixed $stream, string &$buffer): void
    {
        while (true) {
            $chunk = fread($stream, 65_536);
            if (false === $chunk || '' === $chunk) {
                return;
            }
            $buffer .= $chunk;
        }
    }

    private function repositoryStatus(): string
    {
        $result = $this->process(['git', '-c', 'core.excludesfile=', '-C', $this->repositoryRoot, 'status', '--porcelain=v1', '--untracked-files=all'], $this->repositoryRoot);
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode() && '' === $result->stderr(), 'git_status_failed');

        return $result->stdout();
    }

    private function ordinaryExecutable(string $path): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_file($resolved) || !is_readable($resolved)) {
            throw new RuntimeException('executable_invalid');
        }

        return self::normalize($resolved);
    }

    private function caseCompleted(): void
    {
        ++$this->caseCount;
    }

    private function require(bool $condition, string $token): void
    {
        if (!$condition) {
            throw new RuntimeException($token);
        }
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $bytes): void
    {
        for ($offset = 0, $length = strlen($bytes); $offset < $length;) {
            $written = fwrite($stream, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('acceptance_output_failed');
            }
            $offset += $written;
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $token): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException($token);
        }
        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException($token);
            }
            $object[$key] = $item;
        }

        return $object;
    }
}
