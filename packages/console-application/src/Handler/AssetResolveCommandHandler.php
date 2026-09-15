<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Exception\ExceptionInterface as AssetApplicationException;
use Kongono\AssetApplication\Summary\ResolvedAssetSummary;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Throwable;

/** Resolves one logical asset from exact-current metadata under explicit browser policy. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolveCommandHandler implements CommandHandler
{
    public function __construct(private AssetApplicationWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::AssetResolve->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || CommandExecutionIntent::Observe !== $invocation->executionIntent()
            || 8 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        try {
            $values = [];
            foreach (['application-root', 'source-file', 'output-directory', 'logical-identity', 'public-base-url', 'cache-policy', 'integrity-policy', 'preload-policy'] as $name) {
                $input = $invocation->input(new CommandInputName($name));
                if (null === $input) {
                    return $this->invalid();
                }
                $values[$name] = $input->value()->reveal();
            }
            $definition = new AssetApplicationDefinition($values['application-root'], $values['source-file'], $values['output-directory']);
            $policy = new AssetResolutionPolicy(
                new PublicAssetBaseUrl($values['public-base-url']),
                AssetCachePolicy::from($values['cache-policy']),
                AssetIntegrityPolicy::from($values['integrity-policy']),
                AssetPreloadPolicy::from($values['preload-policy']),
            );
            $summary = ResolvedAssetSummary::fromResolved($this->workflow->resolve($definition, new LogicalAssetIdentity($values['logical-identity']), $policy));
        } catch (AssetApplicationException) {
            return $this->precondition();
        } catch (\ValueError) {
            return $this->invalid();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded($this->record($summary));
    }

    private function record(ResolvedAssetSummary $summary): CommandResultRecord
    {
        return new CommandResultRecord([
            $this->integer('byte_count', 'Byte count', $summary->byteCount()),
            $this->text('cache_control', 'Cache-Control', $summary->cacheControl()),
            $this->text('content_identity', 'Content identity', $summary->contentIdentity()),
            $this->text('content_type', 'Content type', $summary->contentType()),
            $this->integer('dependency_count', 'Dependency count', $summary->dependencyCount()),
            $this->text('etag', 'ETag', $summary->etag()),
            $this->text('integrity', 'Integrity', $summary->integrity() ?? ''),
            $this->text('kind', 'Kind', $summary->kind()),
            $this->text('logical_identity', 'Logical identity', $summary->logicalIdentity()),
            $this->text('preload', 'Preload', $summary->preload()),
            $this->text('public_url', 'Public URL', $summary->publicUrl()),
            $this->text('schema', 'Schema', $summary->schema()),
            $this->integer('version', 'Version', $summary->version()),
        ]);
    }

    private function text(string $name, string $label, string $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::text($value));
    }
    private function integer(string $name, string $label, int $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::integer($value));
    }
    private function field(string $name, string $label, CommandResultValue $value): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel($label), $value, CommandResultFieldDisclosure::Safe);
    }
    private function invalid(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::InvalidInput, ConsoleApplicationDiagnostic::InvalidProcessRequest->value));
    }
    private function precondition(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::Precondition, ConsoleApplicationDiagnostic::BuildPrecondition->value));
    }
    private function infrastructure(): CommandResult
    {
        return CommandResult::failed(new CommandFailure(CommandFailureCategory::Infrastructure, ConsoleApplicationDiagnostic::ApplicationFailure->value));
    }
}
