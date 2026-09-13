<?php

declare(strict_types=1);

namespace Kongono\Tests\External\View;

use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Observability\ObservabilityContext;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Profile\ViewApplicationProfile;
use Kongono\ViewApplication\Profile\ViewApplicationProfileIdentity;
use Kongono\ViewApplication\Runtime\ViewApplication;
use Kongono\ViewApplication\Runtime\ViewApplicationRunInput;
use Kongono\ViewApplication\ViewApplicationFactory;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewHttp\Policy\ViewContentType;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use Kongono\ViewInspection\Derivation\ViewIntelligenceDeriver;
use Kongono\ViewInspection\Graph\ViewGraphEvidence;
use Kongono\ViewInspection\Inspection\ViewInspectionRequest;
use Kongono\ViewInspection\Inspection\ViewInspector;
use Kongono\ViewInspection\Projection\ViewObservationProjector;
use Kongono\ViewInspection\Projection\ViewSecurityAuditProjector;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;
use Kongono\ViewInspection\Verification\ViewIntelligenceVerdict;
use Kongono\ViewInspection\Verification\ViewIntelligenceVerifier;
use Kongono\ViewRuntime\Exception\RenderRetirementException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class Phase18ViewAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 180;
    private const string FAILURE = "Kongono Phase 18 external acceptance failed.\n";
    private const string PRIVATE_LITERAL = 'private-phase18-adversarial.example/secret';
    private int $caseCount = 0;

    public function __construct(private readonly string $repositoryRoot) {}

    /**
     * @param list<string> $arguments
     * @param resource     $stdout
     * @param resource     $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        try {
            if (['--child-success'] === $arguments) {
                return $this->successChild($stdout);
            }
            if (['--child-failure'] === $arguments) {
                $workspace = new TemporaryViewWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException(self::PRIVATE_LITERAL);
                } finally {
                    $workspace->cleanup();
                }
            }
            if (['--child-malformed'] === $arguments) {
                fwrite($stdout, "{}\n");

                return 0;
            }
            if (['--child-overflow-stdout'] === $arguments) {
                fwrite($stdout, str_repeat('x', self::STREAM_LIMIT + 1));

                return 0;
            }
            if (['--child-overflow-stderr'] === $arguments) {
                fwrite($stderr, str_repeat('x', self::STREAM_LIMIT + 1));

                return 1;
            }
            if (['--child-timeout'] === $arguments) {
                sleep(5);

                return 0;
            }
            if ([] !== $arguments) {
                throw new RuntimeException('phase18_mode_failed');
            }

            $before = $this->repositoryFingerprint();
            $residue = $this->temporaryResidue();
            $single = $this->child('--child-success');
            $failure = $this->child('--child-failure');
            $later = $this->child('--child-success');
            $sequential = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $malformed = $this->child('--child-malformed');
            $this->require($this->fails(fn() => $this->child('--child-overflow-stdout')));
            $this->require($this->fails(fn() => $this->child('--child-overflow-stderr')));
            $this->require($this->fails(fn() => $this->child('--child-timeout', 1)));
            $this->require(0 === $single->exitCode && '' === $single->stderr && $this->isCanonicalSuccess($single->stdout));
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require(0 === $later->exitCode && $single->stdout === $later->stdout && '' === $later->stderr);
            $this->require(0 === $sequential->exitCode && $single->stdout === $sequential->stdout && '' === $sequential->stderr);
            $this->require(0 === $concurrentA->exitCode && 0 === $concurrentB->exitCode);
            $this->require($single->stdout === $concurrentA->stdout && $concurrentA->stdout === $concurrentB->stdout);
            $this->require('' === $concurrentA->stderr && '' === $concurrentB->stderr);
            $this->require(0 === $malformed->exitCode && !$this->isCanonicalSuccess($malformed->stdout));
            $this->require(!str_contains($failure->stderr, self::PRIVATE_LITERAL));
            $this->require($before === $this->repositoryFingerprint());
            $this->require($residue === $this->temporaryResidue());
            fwrite($stdout, $single->stdout);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        $workspace = new TemporaryViewWorkspace($this->repositoryRoot);
        try {
            $this->directStack($workspace);
            $this->skeletonStack($workspace);
            $this->case($this->caseCount > 0);
            $bytes = self::successBytes($this->caseCount);
            $this->case(!str_contains($bytes, self::PRIVATE_LITERAL));
        } finally {
            $workspace->cleanup();
        }
        fwrite($stdout, self::successBytes($this->caseCount));

        return 0;
    }

    private function directStack(TemporaryViewWorkspace $workspace): void
    {
        $applicationRoot = $workspace->directory('application');
        $workspace->directory('application/views');
        $outputRoot = $workspace->directory('application/var');
        $sourcePath = $workspace->write('application/views/views.json', self::sourceBytes());
        $definition = new ViewApplicationDefinition($applicationRoot, $sourcePath, $outputRoot);
        $workflow = new ViewApplicationBuildWorkflow();
        $source = (new ViewSourceParser())->parse(self::sourceBytes());
        $firstArtifact = (new CompiledViewArtifactBuilder())->build($source);
        $secondArtifact = (new CompiledViewArtifactBuilder())->build((new ViewSourceParser())->parse(self::sourceBytes()));
        $this->case($firstArtifact->equals($secondArtifact));
        $this->case($firstArtifact->metadataBytes() === $secondArtifact->metadataBytes());
        $this->case($firstArtifact->generatedPhpBytes() === $secondArtifact->generatedPhpBytes());
        $this->case(!class_exists($firstArtifact->generatedClass(), false));

        $plan = $workflow->plan($definition);
        $this->case($plan->publicationRequired());
        $this->case(!class_exists($firstArtifact->generatedClass(), false));
        $current = $workflow->build($definition);
        $publishedIdentity = $this->directoryIdentity($outputRoot);
        $again = $workflow->build($definition);
        $this->case($current->artifact()->equals($again->artifact()));
        $this->case($publishedIdentity === $this->directoryIdentity($outputRoot));
        $checked = $workflow->check($definition);
        $this->case($current->artifact()->equals($checked->artifact()));
        $this->case($publishedIdentity === $this->directoryIdentity($outputRoot));
        $this->case(!class_exists($firstArtifact->generatedClass(), false));

        $this->inspect($current);
        $this->case(!class_exists($firstArtifact->generatedClass(), false));
        $this->sourceFailures($workspace, $applicationRoot, $outputRoot, $sourcePath);
        $this->artifactFailures($workspace, $definition, $current);

        $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
        $this->case(class_exists($firstArtifact->generatedClass(), false));
        $policy = new ViewResponsePolicy(201, 'Created', ViewContentType::HtmlUtf8, [
            new ViewResponseHeader('X-Frame-Options', ['DENY']),
        ]);
        $request = $this->pageRequest($current->compiledViewIdentity(), '<&>"\'', '<&>"\'');
        $response = $application->run(new ViewApplicationRunInput($request, $policy));
        $expected = '<div title="&lt;&amp;&gt;&quot;&apos;">&lt;&amp;&gt;"\'</div>';
        $this->case(!$response instanceof RenderFailure);
        if ($response instanceof RenderFailure) {
            throw new RuntimeException('phase18_render_failed');
        }
        $this->case(201 === $response->getStatusCode());
        $this->case('Created' === $response->getReasonPhrase());
        $this->case(['Content-Type', 'Content-Length', 'X-Frame-Options'] === array_keys($response->getHeaders()));
        $this->case('text/html; charset=utf-8' === $response->getHeaderLine('Content-Type'));
        $this->case((string) strlen($expected) === $response->getHeaderLine('Content-Length'));
        $this->case('DENY' === $response->getHeaderLine('X-Frame-Options'));
        $this->case($expected === (string) $response->getBody());
        $this->case(!str_contains((string) $response->getBody(), self::PRIVATE_LITERAL));

        $this->renderFailures($application, $current, $policy);
        $healthy = $application->run(new ViewApplicationRunInput($this->pageRequest($current->compiledViewIdentity(), 'healthy', 'healthy'), $policy));
        $this->case(!$healthy instanceof RenderFailure && '<div title="healthy">healthy</div>' === (string) $healthy->getBody());

        $cleanupFailure = RenderRetirementException::failed('bound_exceeded', new RuntimeException(self::PRIVATE_LITERAL));
        $this->case('KGN-VIEW-RUNTIME-1837' === $cleanupFailure->diagnosticId());
        $this->case('bound_exceeded' === $cleanupFailure->diagnosticContext()->fields()[1]->value());
        $this->case(!str_contains((string) $cleanupFailure, self::PRIVATE_LITERAL));
    }

    private function inspect(CurrentViewApplicationBuild $current): void
    {
        $artifact = $current->artifact();
        $profileIdentity = new ViewApplicationProfileIdentity(hash('sha256', implode("\n", [
            'kongono.view-application-profile',
            '1',
            $artifact->sourceIdentity()->value(),
            $artifact->compiledViewIdentity()->value(),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            (string) $artifact->viewCount(),
        ])));
        $profile = new ViewApplicationProfile($current, $profileIdentity);
        $inspection = (new ViewInspector())->inspect(new ViewInspectionRequest($profile));
        $this->case($inspection->structurallyValidated());
        $this->case($inspection->suppliedCurrentEvidence());
        $this->case(false === $inspection->flags()['generated_php_executed']);
        $this->case(false === $inspection->flags()['rendering_performed']);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ViewObservationProjector())->project($inspection, $context);
        $audit = (new ViewSecurityAuditProjector())->project($inspection, $context);
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            $projection = null;
            foreach ($attributes->attributes() as $attribute) {
                if ('inspection.projection' === $attribute->key()->value()) {
                    $projection = $attribute->value()->value();
                }
            }
            $this->case('constructed-not-recorded' === $projection);
        }
        foreach (ViewInspectionDeveloperRole::cases() as $role) {
            $view = (new ViewIntelligenceDeriver())->derive($inspection, $role);
            $verification = (new ViewIntelligenceVerifier())->verify($inspection, $view);
            $this->case(ViewIntelligenceVerdict::Pass === $verification->verdict());
            $this->case(ViewGraphEvidence::RELATIONSHIP === $view->graphEvidence()->relationship());
            $this->case('not-performed' === $view->roleExecution());
            $this->case('not-performed' === $view->recording());
            $this->case('not-performed' === $view->exporting());
        }
    }

    private function sourceFailures(TemporaryViewWorkspace $workspace, string $applicationRoot, string $outputRoot, string $sourcePath): void
    {
        $parser = new ViewSourceParser();
        foreach ([
            '',
            '{}',
            "{\"schema\":\"kongono.view-source-set\",\"schema\":\"duplicate\",\"version\":1,\"views\":[]}\n",
            self::unsupportedSource(['kind' => 'raw', 'text' => self::PRIVATE_LITERAL]),
            self::unsupportedSource(['kind' => 'element', 'name' => 'script', 'attributes' => [], 'children' => []]),
            self::unsupportedSource(['kind' => 'element', 'name' => 'a', 'attributes' => [['name' => 'href', 'value' => ['kind' => 'literal', 'text' => self::PRIVATE_LITERAL]]], 'children' => []]),
            self::unsupportedSource(['kind' => 'element', 'name' => 'div', 'attributes' => [['name' => 'style', 'value' => ['kind' => 'literal', 'text' => self::PRIVATE_LITERAL]]], 'children' => []]),
            self::unsupportedSource(['kind' => 'element', 'name' => 'div', 'attributes' => [['name' => 'onclick', 'value' => ['kind' => 'literal', 'text' => self::PRIVATE_LITERAL]]], 'children' => []]),
        ] as $invalid) {
            $this->case($this->fails(static fn() => $parser->parse($invalid)));
        }

        $deep = ['kind' => 'text', 'text' => 'leaf'];
        for ($index = 0; $index < 70; ++$index) {
            $deep = ['kind' => 'element', 'name' => 'div', 'attributes' => [], 'children' => [$deep]];
        }
        $this->case($this->fails(static fn() => $parser->parse(self::unsupportedSource($deep))));
        $manyViews = json_decode(self::sourceBytes(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($manyViews) || !is_array($manyViews['views'] ?? null) || !is_array($manyViews['views'][0] ?? null)) {
            throw new RuntimeException('phase18_source_failed');
        }
        $manyViews['views'] = array_fill(0, 129, $manyViews['views'][0]);
        foreach ($manyViews['views'] as $index => &$view) {
            $view['id'] = 'page.item' . $index;
        }
        unset($view);
        $manyBytes = json_encode($manyViews, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
        $this->case($this->fails(static fn() => $parser->parse($manyBytes)));

        $outside = $workspace->write('outside.json', self::sourceBytes());
        $outsideDefinition = new ViewApplicationDefinition($applicationRoot, $outside, $outputRoot);
        $this->case($this->fails(static fn() => (new ViewApplicationBuildWorkflow())->plan($outsideDefinition)));

        $link = $workspace->path('application/views/linked.json');
        if (!@symlink($sourcePath, $link) && !@link($sourcePath, $link)) {
            throw new RuntimeException('phase18_link_proof_unavailable');
        }
        try {
            $linked = new ViewApplicationDefinition($applicationRoot, $link, $outputRoot);
            $this->case($this->fails(static fn() => (new ViewApplicationBuildWorkflow())->plan($linked)));
        } finally {
            if (!unlink($link)) {
                throw new RuntimeException('phase18_link_cleanup_failed');
            }
        }
    }

    private function artifactFailures(TemporaryViewWorkspace $workspace, ViewApplicationDefinition $definition, CurrentViewApplicationBuild $current): void
    {
        $workflow = new ViewApplicationBuildWorkflow();
        $directory = $current->artifactDirectory();
        $metadataPath = $directory . '/artifact.json';
        $generatedPath = $directory . '/compiled.php';
        $metadata = (string) file_get_contents($metadataPath);
        $generated = (string) file_get_contents($generatedPath);

        $this->withRestoredFile($metadataPath, $metadata, '', static fn() => $workflow->check($definition));
        $this->withRestoredFile($generatedPath, $generated, '', static fn() => $workflow->check($definition));
        $this->withRestoredFile($metadataPath, $metadata, '{}', static fn() => $workflow->check($definition));

        $other = (new CompiledViewArtifactBuilder())->build((new ViewSourceParser())->parse(str_replace('page.home', 'page.other', self::sourceBytes())));
        $this->withRestoredFile($generatedPath, $generated, $other->generatedPhpBytes(), static fn() => $workflow->check($definition));

        $extra = $directory . '/additional';
        $this->case(1 === file_put_contents($extra, 'x'));
        try {
            $this->case($this->fails(static fn() => $workflow->check($definition)));
        } finally {
            if (!unlink($extra)) {
                throw new RuntimeException('phase18_cleanup_failed');
            }
        }

        $sourcePath = $definition->sourceFile();
        $source = (string) file_get_contents($sourcePath);
        $changed = str_replace('page.home', 'page.changed', $source);
        $this->case(strlen($changed) === file_put_contents($sourcePath, $changed));
        try {
            $this->case($this->fails(static fn() => $workflow->check($definition)));
        } finally {
            if (strlen($source) !== file_put_contents($sourcePath, $source)) {
                throw new RuntimeException('phase18_cleanup_failed');
            }
        }

        $conflictRoot = $workspace->directory('application/conflict');
        $conflictDefinition = new ViewApplicationDefinition($definition->applicationRoot(), $sourcePath, $conflictRoot);
        $plan = $workflow->plan($conflictDefinition);
        $target = $workspace->directory('application/conflict/' . $plan->artifactBasename());
        $workspace->write('application/conflict/' . $plan->artifactBasename() . '/artifact.json', $metadata);
        $workspace->write('application/conflict/' . $plan->artifactBasename() . '/compiled.php', 'conflict');
        $this->case($this->fails(static fn() => $workflow->build($conflictDefinition)));
        $this->case('conflict' === file_get_contents($target . '/compiled.php'));
        $this->case($current->artifact()->equals($workflow->check($definition)->artifact()));
    }

    private function withRestoredFile(string $path, string $original, string $replacement, callable $operation): void
    {
        $this->case(strlen($replacement) === file_put_contents($path, $replacement));
        try {
            $this->case($this->fails($operation));
        } finally {
            if (strlen($original) !== file_put_contents($path, $original)) {
                throw new RuntimeException('phase18_cleanup_failed');
            }
        }
    }

    private function renderFailures(ViewApplication $application, CurrentViewApplicationBuild $current, ViewResponsePolicy $policy): void
    {
        $identity = $current->compiledViewIdentity();
        $schema = self::pageSchema();
        foreach ([
            fn() => new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('missing'), new LogicalViewIdentity('page.home'), $identity, $schema, new ViewContext(['message' => new StringValue('value')]), new RenderLimits(256, 32)),
            fn() => new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('extra'), new LogicalViewIdentity('page.home'), $identity, $schema, new ViewContext(['message' => new StringValue('value'), 'title' => new StringValue('value'), 'extra' => new StringValue('value')]), new RenderLimits(256, 32)),
            fn() => new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('wrong'), new LogicalViewIdentity('page.home'), $identity, $schema, new ViewContext(['message' => new ListValue([]), 'title' => new StringValue('value')]), new RenderLimits(256, 32)),
            fn() => new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('oversized'), new LogicalViewIdentity('page.home'), $identity, $schema, new ViewContext(['message' => new StringValue(str_repeat('x', 257)), 'title' => new StringValue('value')]), new RenderLimits(256, 32)),
            fn() => PresentationValueSchema::record([new PresentationFieldSchema('duplicate', PresentationValueSchema::string(8), true), new PresentationFieldSchema('duplicate', PresentationValueSchema::string(8), true)]),
            fn() => new ViewContext(array_fill(0, 129, new StringValue('x'))),
        ] as $invalid) {
            $this->case($this->fails($invalid));
        }

        $nested = new StringValue('leaf');
        $depthFailed = false;
        try {
            for ($index = 0; $index < 40; ++$index) {
                $nested = new ListValue([$nested]);
            }
        } catch (Throwable) {
            $depthFailed = true;
        }
        $this->case($depthFailed);

        $boundedBytes = $application->run(new ViewApplicationRunInput($this->pageRequest($identity, 'value', 'value', 1, 32), $policy));
        $boundedSteps = $application->run(new ViewApplicationRunInput($this->pageRequest($identity, 'value', 'value', 256, 1), $policy));
        $unknown = $application->run(new ViewApplicationRunInput($this->pageRequest($identity, 'value', 'value', 256, 32, 'page.unknown'), $policy));
        $this->case($boundedBytes instanceof RenderFailure && 'bound_exceeded' === $boundedBytes->kind()->value);
        $this->case($boundedSteps instanceof RenderFailure && 'bound_exceeded' === $boundedSteps->kind()->value);
        $this->case($unknown instanceof RenderFailure && 'invalid_request' === $unknown->kind()->value);
        $this->case($this->fails(fn() => $application->run(new ViewApplicationRunInput($this->pageRequest(new CompiledViewIdentity(str_repeat('f', 64)), 'value', 'value'), $policy))));

        $mismatchSchema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('message', PresentationValueSchema::string(255), true),
            new PresentationFieldSchema('title', PresentationValueSchema::string(128), true),
        ]));
        $mismatch = new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('schema-mismatch'), new LogicalViewIdentity('page.home'), $identity, $mismatchSchema, new ViewContext(['message' => new StringValue('value'), 'title' => new StringValue('value')]), new RenderLimits(256, 32));
        $mismatchResult = $application->run(new ViewApplicationRunInput($mismatch, $policy));
        $this->case($mismatchResult instanceof RenderFailure && 'schema_mismatch' === $mismatchResult->kind()->value);

        $itemSchema = PresentationValueSchema::record([new PresentationFieldSchema('name', PresentationValueSchema::string(64), true)]);
        $listSchema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('items', PresentationValueSchema::list($itemSchema, 2), true),
        ]));
        $items = [1, 2, 3];
        $this->case($this->fails(fn() => new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity('iteration'), new LogicalViewIdentity('page.list'), $identity, $listSchema, new ViewContext(['items' => new ListValue(array_map(static fn(int $number): RecordValue => new RecordValue(['name' => new StringValue((string) $number)]), $items))]), new RenderLimits(256, 32))));
    }

    private function skeletonStack(TemporaryViewWorkspace $workspace): void
    {
        $application = $workspace->copyTree($this->repositoryRoot . '/skeletons/view-minimal', 'skeletons/view-minimal');
        $packages = $workspace->directory('packages');
        $this->localizeLockedPackages($application, $packages);
        $environment = $this->processEnvironment($workspace->root());
        $install = $this->process([...$this->composer(), 'install', '--no-interaction', '--no-ansi', '--no-plugins', '--no-scripts'], $application, $environment);
        $this->case(0 === $install->exitCode);
        $this->case('1' === $environment['COMPOSER_DISABLE_NETWORK']);
        $this->case(!isset($environment['HTTP_PROXY'], $environment['HTTPS_PROXY'], $environment['ALL_PROXY']));
        $owned = $this->ownedIdentity($application);

        $directBuild = $this->process([PHP_BINARY, 'bin/build.php'], $application, $environment);
        $afterDirect = $this->generatedIdentity($application);
        $composerBuild = $this->process([...$this->composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directBuild->exitCode && '' === $directBuild->stderr);
        $this->case($directBuild->stdout === $composerBuild->stdout && '' === $composerBuild->stderr);
        $this->case($afterDirect === $this->generatedIdentity($application));

        $beforeCheck = $this->generatedIdentity($application);
        $directCheck = $this->process([PHP_BINARY, 'bin/check.php'], $application, $environment);
        $composerCheck = $this->process([...$this->composer(), 'view:check', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directCheck->exitCode && '' === $directCheck->stderr);
        $this->case($directCheck->stdout === $composerCheck->stdout && '' === $composerCheck->stderr);
        $this->case($beforeCheck === $this->generatedIdentity($application));

        $directRun = $this->process([PHP_BINARY, 'bin/run.php'], $application, $environment);
        $composerRun = $this->process([...$this->composer(), 'view:run', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directRun->exitCode && '' === $directRun->stderr && "Hello from Kongono.\n" === $directRun->stdout);
        $this->case($directRun->stdout === $composerRun->stdout && '' === $composerRun->stderr);

        $notFound = $this->process([PHP_BINARY, '-r', <<<'PHP'
$root = $argv[1];
require $root . '/vendor/autoload.php';
require $root . '/src/Http/NotFoundHandler.php';
$request = (new Kongono\Http\Message\Factory\ServerRequestFactory())->createServerRequest('GET', '/missing');
$response = (new ViewMinimalApp\Http\NotFoundHandler())->handle($request);
if (404 !== $response->getStatusCode() || 'text/plain; charset=utf-8' !== $response->getHeaderLine('Content-Type') || "Not Found.\n" !== (string) $response->getBody()) {
    exit(1);
}
fwrite(STDOUT, (string) $response->getBody());
PHP, $application], $application, $environment);
        $this->case(0 === $notFound->exitCode && '' === $notFound->stderr && "Not Found.\n" === $notFound->stdout);
        $this->case($owned === $this->ownedIdentity($application));

        $compiled = $this->singleGenerated($application . '/var/kongono/view', 'compiled.php');
        $this->case(9 === file_put_contents($compiled, 'conflict!'));
        $conflict = $this->process([...$this->composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 !== $conflict->exitCode && 'conflict!' === file_get_contents($compiled));
        $this->case(!str_contains($conflict->stdout . $conflict->stderr, $application));

        $source = $application . '/views/home.view.json';
        $sourceBytes = (string) file_get_contents($source);
        $this->case(strlen(self::PRIVATE_LITERAL) === file_put_contents($source, self::PRIVATE_LITERAL));
        $rejected = $this->process([...$this->composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 !== $rejected->exitCode);
        $this->case(!str_contains($rejected->stdout . $rejected->stderr, self::PRIVATE_LITERAL));
        $this->case(!str_contains($rejected->stdout . $rejected->stderr, $application));
        $this->case(strlen($sourceBytes) === file_put_contents($source, $sourceBytes));
        $this->case($owned === $this->ownedIdentity($application));
    }

    private function localizeLockedPackages(string $application, string $packages): void
    {
        $lockPath = $application . '/composer.lock';
        $lock = json_decode((string) file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($lock)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }
        $locked = $lock['packages'] ?? null;
        if (!is_array($locked)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }
        foreach ($locked as $index => $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !is_array($package['dist'] ?? null)) {
                throw new RuntimeException('phase18_skeleton_failed');
            }
            $name = $package['name'];
            if (str_starts_with($name, 'kongono/')) {
                $basename = substr($name, strlen('kongono/'));
                $source = $this->repositoryRoot . '/packages/' . $basename;
            } elseif (str_starts_with($name, 'psr/')) {
                $basename = 'psr-' . substr($name, strlen('psr/'));
                $source = $this->repositoryRoot . '/vendor/psr/' . substr($name, strlen('psr/'));
            } else {
                throw new RuntimeException('phase18_skeleton_failed');
            }
            $this->copyTree($source, $packages . '/' . $basename);
            $package['dist'] = ['type' => 'path', 'url' => '../../packages/' . $basename, 'reference' => $package['dist']['reference'] ?? null, 'shasum' => null];
            $package['transport-options'] = ['symlink' => false, 'relative' => true];
            unset($package['source']);
            $locked[$index] = $package;
        }
        $lock['packages'] = $locked;
        $bytes = json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        if (strlen($bytes) !== file_put_contents($lockPath, $bytes)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }
    }

    private function copyTree(string $source, string $destination): void
    {
        if ((!mkdir($destination, 0700, true) && !is_dir($destination)) || is_link($source)) {
            throw new RuntimeException('phase18_copy_failed');
        }
        $source = str_replace('\\', '/', $source);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                throw new RuntimeException('phase18_copy_failed');
            }
            $relative = substr(str_replace('\\', '/', $entry->getPathname()), strlen($source) + 1);
            $target = $destination . '/' . $relative;
            if ($entry->isDir()) {
                if (!mkdir($target, 0700, true) && !is_dir($target)) {
                    throw new RuntimeException('phase18_copy_failed');
                }
            } elseif (!$entry->isFile() || !copy($entry->getPathname(), $target)) {
                throw new RuntimeException('phase18_copy_failed');
            }
        }
    }

    /** @return list<string> */
    private function composer(): array
    {
        $path = getenv('PATH');
        foreach (is_string($path) ? explode(PATH_SEPARATOR, $path) : [] as $directory) {
            $candidate = rtrim($directory, '\\/') . '/composer.phar';
            $resolved = realpath($candidate);
            if (false !== $resolved && is_file($resolved) && !is_link($candidate)) {
                return [PHP_BINARY, str_replace('\\', '/', $resolved)];
            }
        }
        throw new RuntimeException('phase18_composer_failed');
    }

    /** @return array<string, string> */
    private function processEnvironment(string $temporary): array
    {
        $environment = [
            'COMPOSER_CACHE_DIR' => $temporary . '/composer-cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_HOME' => $temporary . '/composer-home',
            'COMPOSER_NO_INTERACTION' => '1',
        ];
        foreach (['SystemRoot', 'WINDIR', 'COMSPEC', 'PATH', 'TEMP', 'TMP', 'TMPDIR'] as $name) {
            $value = getenv($name);
            if (is_string($value) && '' !== $value) {
                $environment[$name] = $value;
            }
        }

        return $environment;
    }

    /** @param array<string, string> $environment
     * @param list<string> $command
     */
    private function process(array $command, string $directory, array $environment, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $directory, $environment, ['bypass_shell' => true]);
        if (!is_resource($process) || !isset($pipes[0], $pipes[1], $pipes[2])) {
            throw new RuntimeException('phase18_process_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        $overflow = false;
        $timedOut = false;
        try {
            while ($status['running']) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
                if (microtime(true) >= $deadline) {
                    $timedOut = true;
                    break;
                }
                usleep(10_000);
                $status = proc_get_status($process);
            }
            if (!$timedOut) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
            }
        } finally {
            foreach ([1, 2] as $pipe) {
                if (isset($pipes[$pipe]) && is_resource($pipes[$pipe])) {
                    fclose($pipes[$pipe]);
                }
            }
        }
        if ($timedOut && $status['running']) {
            proc_terminate($process);
        }
        $closed = proc_close($process);
        if ($timedOut || $overflow) {
            throw new RuntimeException('phase18_process_failed');
        }
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

        return new ProcessResult($exit, $stdout, $stderr);
    }

    private function child(string $mode, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        return $this->closeCaptured($this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', $mode]), $timeout);
    }

    /** @return array{ProcessResult, ProcessResult} */
    private function childPair(): array
    {
        $first = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $second = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->closeCaptured($first), $this->closeCaptured($second)];
    }

    /** @param list<string> $command
     * @return array{resource, TemporaryViewWorkspace, string, string}
     */
    private function openCaptured(array $command): array
    {
        $workspace = new TemporaryViewWorkspace($this->repositoryRoot);
        $stdoutPath = $workspace->write('stdout', '');
        $stderrPath = $workspace->write('stderr', '');
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['file', $stdoutPath, 'wb'], ['file', $stderrPath, 'wb']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            $workspace->cleanup();
            throw new RuntimeException('phase18_process_failed');
        }
        fclose($pipes[0]);

        return [$process, $workspace, $stdoutPath, $stderrPath];
    }

    /** @param array{resource, TemporaryViewWorkspace, string, string} $opened */
    private function closeCaptured(array $opened, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        [$process, $workspace, $stdoutPath, $stderrPath] = $opened;
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        try {
            while ($status['running'] && microtime(true) < $deadline) {
                usleep(10_000);
                $status = proc_get_status($process);
            }
            $timedOut = $status['running'];
            if ($timedOut) {
                proc_terminate($process);
            }
            $closed = proc_close($process);
            $stdout = file_get_contents($stdoutPath, false, null, 0, self::STREAM_LIMIT + 1);
            $stderr = file_get_contents($stderrPath, false, null, 0, self::STREAM_LIMIT + 1);
            if ($timedOut || false === $stdout || false === $stderr || strlen($stdout) > self::STREAM_LIMIT || strlen($stderr) > self::STREAM_LIMIT) {
                throw new RuntimeException('phase18_process_failed');
            }
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

            return new ProcessResult($exit, $stdout, $stderr);
        } finally {
            $workspace->cleanup();
        }
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->closeCaptured($this->openCaptured(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '--untracked-files=all']));
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private function directoryIdentity(string $root): string
    {
        $root = str_replace('\\', '/', (string) realpath($root));
        $records = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink() || !$entry->isFile()) {
                throw new RuntimeException('phase18_workspace_failed');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $bytes = file_get_contents($path);
            if (false === $bytes) {
                throw new RuntimeException('phase18_workspace_failed');
            }
            $records[] = substr($path, strlen($root) + 1) . '|' . strlen($bytes) . '|' . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private function temporaryResidue(): string
    {
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase18-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase18_acceptance_failed');
        }
        sort($paths, SORT_STRING);

        return hash('sha256', implode("\n", $paths));
    }

    /** @return array<string, string> */
    private function ownedIdentity(string $application): array
    {
        $ownership = json_decode((string) file_get_contents($application . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($ownership)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }
        $paths = $ownership['template_paths'] ?? null;
        if (!is_array($paths)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }
        $identity = [];
        foreach ($paths as $path) {
            if (!is_string($path)) {
                throw new RuntimeException('phase18_skeleton_failed');
            }
            $sha256 = hash_file('sha256', $application . '/' . $path);
            if (!is_string($sha256)) {
                throw new RuntimeException('phase18_skeleton_failed');
            }
            $identity[$path] = $sha256;
        }

        return $identity;
    }

    /** @return array<string, string> */
    private function generatedIdentity(string $application): array
    {
        $identity = [];
        foreach ([$application . '/var/kongono/build', $application . '/var/kongono/view'] as $root) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
                if (!$entry instanceof SplFileInfo || !$entry->isFile() || '.gitignore' === $entry->getFilename()) {
                    continue;
                }
                $path = str_replace('\\', '/', $entry->getPathname());
                $sha256 = hash_file('sha256', $path);
                if (!is_string($sha256)) {
                    throw new RuntimeException('phase18_skeleton_failed');
                }
                $identity[substr($path, strlen($application) + 1)] = $sha256;
            }
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }

    private function singleGenerated(string $root, string $basename): string
    {
        $matches = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && $basename === $entry->getFilename()) {
                $matches[] = str_replace('\\', '/', $entry->getPathname());
            }
        }
        if (1 !== count($matches)) {
            throw new RuntimeException('phase18_skeleton_failed');
        }

        return $matches[0];
    }

    private function appendBounded(string &$buffer, string $chunk, bool &$overflow): void
    {
        $remaining = self::STREAM_LIMIT - strlen($buffer);
        if (strlen($chunk) > $remaining) {
            $overflow = true;
            $buffer .= substr($chunk, 0, max(0, $remaining));

            return;
        }
        $buffer .= $chunk;
    }

    /** @param resource $stream */
    private function readAvailable($stream): string
    {
        $bytes = stream_get_contents($stream, self::STREAM_LIMIT + 1);
        if (false === $bytes) {
            throw new RuntimeException('phase18_process_failed');
        }

        return $bytes;
    }

    private function isCanonicalSuccess(string $bytes): bool
    {
        if (!str_ends_with($bytes, "\n") || str_contains(substr($bytes, 0, -1), "\n")) {
            return false;
        }
        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($decoded)
            && ['status', 'profile', 'case_count', 'cleanup'] === array_keys($decoded)
            && 'ok' === $decoded['status']
            && 'kongono-phase18-server-rendered-presentation' === $decoded['profile']
            && is_int($decoded['case_count'])
            && $decoded['case_count'] > 0
            && 'complete' === $decoded['cleanup']
            && self::successBytes($decoded['case_count']) === $bytes;
    }

    private function fails(callable $operation): bool
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            return !str_contains((string) $failure, self::PRIVATE_LITERAL);
        }

        return false;
    }

    private function case(bool $passed): void
    {
        $this->require($passed);
        ++$this->caseCount;
    }

    private function require(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('phase18_acceptance_failed');
        }
    }

    private static function successBytes(int $caseCount): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase18-server-rendered-presentation', 'case_count' => $caseCount, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function sourceBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => [[
                'id' => 'page.home',
                'context' => ['kind' => 'record', 'fields' => [
                    ['name' => 'message', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 256]],
                    ['name' => 'title', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 128]],
                ]],
                'body' => [[
                    'kind' => 'element',
                    'name' => 'div',
                    'attributes' => [['name' => 'title', 'value' => ['kind' => 'value', 'path' => '$.title']]],
                    'children' => [['kind' => 'value', 'path' => '$.message']],
                ]],
            ], [
                'id' => 'page.list',
                'context' => ['kind' => 'record', 'fields' => [[
                    'name' => 'items',
                    'required' => true,
                    'schema' => ['kind' => 'list', 'max_items' => 2, 'item' => ['kind' => 'record', 'fields' => [[
                        'name' => 'name', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 64],
                    ]]]],
                ]]],
                'body' => [['kind' => 'each', 'path' => '$.items', 'as' => 'item', 'max_items' => 2, 'body' => [['kind' => 'value', 'path' => '@item.name']]]],
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @param array<string, mixed> $node */
    private static function unsupportedSource(array $node): string
    {
        return json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => [[
                'id' => 'page.unsupported',
                'context' => ['kind' => 'record', 'fields' => []],
                'body' => [$node],
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    private static function pageSchema(): ViewContextSchema
    {
        return new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('message', PresentationValueSchema::string(256), true),
            new PresentationFieldSchema('title', PresentationValueSchema::string(128), true),
        ]));
    }

    private function pageRequest(CompiledViewIdentity $identity, string $message, string $title, int $maxBytes = 1024, int $maxSteps = 64, string $view = 'page.home'): RenderRequest
    {
        return new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('phase18.render.' . str_replace('.', '-', $view)),
            new LogicalViewIdentity($view),
            $identity,
            self::pageSchema(),
            new ViewContext(['message' => new StringValue($message), 'title' => new StringValue($title)]),
            new RenderLimits($maxBytes, $maxSteps),
        );
    }
}
