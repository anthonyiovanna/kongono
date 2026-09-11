<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Support;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringPublisher;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplication;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationRunInput;
use Kongono\ModelProviderOpenAiResponses\Workflow\CurrentOpenAiResponsesApplicationBuild;
use Kongono\ModelProviderOpenAiResponses\Workflow\OpenAiResponsesApplicationBuildWorkflow;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class OpenAiResponsesApplicationFixture
{
    private static int $sequence = 0;

    public readonly OpenAiResponsesAdapterFixture $adapterFixture;
    public readonly InvocationPlanSource $source;
    public readonly string $artifactBytes;
    public readonly ModelInvocationApplicationProfile $modelProfile;
    public readonly OpenAiResponsesApplicationProfile $profile;
    public readonly string $root;
    public readonly string $output;
    public readonly string $artifactFile;
    private ?CurrentOpenAiResponsesApplicationBuild $currentBuild = null;

    /** @param list<bool> $egressCancellationAnswers */
    public function __construct(
        ResultMode $mode = ResultMode::Text,
        bool $streaming = false,
        string $transportMode = 'success',
        array $egressCancellationAnswers = [],
        ?ResponseInterface $response = null,
        ?Throwable $creationFailure = null,
        bool $build = true,
    ) {
        ++self::$sequence;
        $suffix = '.application.' . self::$sequence;
        if (null === $response && ResultMode::Structured === $mode) {
            $response = self::responseFixture('completed-structured.json');
        }
        if (null === $response && ResultMode::ActionSuggestion === $mode) {
            $response = self::responseFixture('completed-function-call.json');
        }
        $this->adapterFixture = new OpenAiResponsesAdapterFixture(
            response: $response,
            cancellationAnswers: $egressCancellationAnswers,
            transportMode: $transportMode,
            creationFailure: $creationFailure,
            streaming: $streaming,
            resultMode: $mode,
        );
        $base = $this->adapterFixture->source;
        $this->source = new InvocationPlanSource(
            new InvocationPlanIdentity('plan.openai.responses' . $suffix),
            $base->adapterIdentity(),
            $base->request(),
            $base->budget(),
        );
        $this->artifactBytes = (new InvocationPlanArtifactBuilder())->build($this->source)->canonicalBytes();
        $this->modelProfile = (new ModelInvocationApplicationProfileFactory())->create($this->source, $this->artifactBytes);
        $this->profile = (new OpenAiResponsesApplicationProfileFactory())->create(
            $this->adapterFixture->openAiProfile,
            $this->modelProfile,
            $this->adapterFixture->profile(),
        );

        $this->root = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-openai-application-' . bin2hex(random_bytes(8)));
        $this->output = $this->root . '/generated';
        $this->artifactFile = $this->root . '/invocation-plan.json';
        if (!mkdir($this->root) || !mkdir($this->output) || strlen($this->artifactBytes) !== file_put_contents($this->artifactFile, $this->artifactBytes)) {
            throw new RuntimeException('Unable to create the owned application fixture.');
        }
        if ($build) {
            $this->build();
        }
    }

    public function build(): CurrentOpenAiResponsesApplicationBuild
    {
        return $this->currentBuild = (new OpenAiResponsesApplicationBuildWorkflow())->build(
            $this->root,
            $this->artifactFile,
            $this->source,
            $this->output,
            $this->adapterFixture->openAiProfile,
            $this->adapterFixture->profile(),
        );
    }

    public function check(): CurrentOpenAiResponsesApplicationBuild
    {
        return $this->currentBuild = (new OpenAiResponsesApplicationBuildWorkflow())->check(
            $this->root,
            $this->artifactFile,
            $this->source,
            $this->output,
            $this->adapterFixture->openAiProfile,
            $this->adapterFixture->profile(),
        );
    }

    public function publishExpectedGeneratedWiring(): string
    {
        $generated = (new ModelInvocationWiringGenerator())->generate($this->modelProfile);

        return (new ModelInvocationWiringPublisher())->publish($this->root, $this->output, $generated);
    }

    public function application(): OpenAiResponsesApplication
    {
        if (!$this->currentBuild instanceof CurrentOpenAiResponsesApplicationBuild) {
            throw new RuntimeException('The fixture has no current build.');
        }

        return $this->currentBuild->application();
    }

    /** @param list<bool> $invocationCancellationAnswers */
    public function runInput(
        array $invocationCancellationAnswers = [],
        ?OpenAiResponsesExternalServiceApplicationFactory $factory = null,
    ): OpenAiResponsesApplicationRunInput {
        $context = $this->adapterFixture->context;

        return new OpenAiResponsesApplicationRunInput(
            $factory ?? $this->adapterFixture,
            $context->operationIdentity(),
            $context->credentialPurpose(),
            $context->credentialMaximumLifetimeMilliseconds(),
            $context->receiptIdentity(),
            $context->attemptIdentity(),
            $context->authorityReference(),
            $context->authorityCurrentness(),
            $context->cancellation(),
            $context->egressClock(),
            $context->credentialClock(),
            new ReceiptIdentity('receipt.openai.application.' . self::$sequence),
            new class ($invocationCancellationAnswers) implements InvocationCancellation {
                /** @param list<bool> $answers */
                public function __construct(private array $answers) {}
                public function isCancellationRequested(): bool
                {
                    return array_shift($this->answers) ?? false;
                }
            },
            new class implements InvocationMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );
    }

    public function cleanup(): void
    {
        $paths = glob($this->output . '/*');
        foreach (false === $paths ? [] : $paths as $path) {
            @unlink($path);
        }
        @unlink($this->artifactFile);
        @rmdir($this->output);
        @rmdir($this->root);
    }

    private static function responseFixture(string $name): ResponseInterface
    {
        $bytes = file_get_contents(dirname(__DIR__) . '/Fixture/Response/' . $name);
        if (false === $bytes) {
            throw new RuntimeException('Unable to read the local response fixture.');
        }

        return new Response(200, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream($bytes));
    }
}
