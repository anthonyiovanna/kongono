<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Tests\Unit\Runtime;

use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\ViewApplication\Exception\ViewApplicationLoadException;
use Kongono\ViewApplication\Exception\ViewApplicationOperationException;
use Kongono\ViewApplication\Runtime\ViewApplicationRunInput;
use Kongono\ViewApplication\Tests\Fixture\ViewApplicationFixture;
use Kongono\ViewApplication\ViewApplicationFactory;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewHttp\Exception\ViewHttpAdaptationException;
use Kongono\ViewHttp\Policy\ViewContentType;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use WeakReference;

final class ViewApplicationTest extends TestCase
{
    public function testSuccessfulRenderUsesExplicitResponsePolicy(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $response = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity()),
                new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
            ));
            self::assertNotInstanceOf(RenderFailure::class, $response);
            self::assertSame(200, $response->getStatusCode());
            self::assertSame('OK', $response->getReasonPhrase());
            self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
            self::assertSame('20', $response->getHeaderLine('Content-Length'));
            self::assertSame("Hello from Kongono.\n", (string) $response->getBody());
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testLowerRenderFailureIsReturnedUnchanged(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $failure = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity(), view: 'page.missing'),
                new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
            ));
            self::assertInstanceOf(RenderFailure::class, $failure);
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testRenderFailureCausesZeroHttpFactoryActivity(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $responses = new class implements ResponseFactoryInterface {
                public int $calls = 0;

                public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
                {
                    ++$this->calls;
                    throw new RuntimeException('response factory must not run');
                }
            };
            $streams = new class implements StreamFactoryInterface {
                public int $calls = 0;

                public function createStream(string $content = ''): StreamInterface
                {
                    ++$this->calls;
                    throw new RuntimeException('stream factory must not run');
                }

                public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
                {
                    throw new RuntimeException('unsupported test method');
                }

                public function createStreamFromResource($resource): StreamInterface
                {
                    throw new RuntimeException('unsupported test method');
                }
            };
            $application = (new ViewApplicationFactory())->create($current, $responses, $streams);
            $failure = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity(), view: 'page.missing'),
                new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
            ));
            self::assertInstanceOf(RenderFailure::class, $failure);
            self::assertSame(0, $responses->calls);
            self::assertSame(0, $streams->calls);
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testCompiledIdentityMismatchFailsBeforeRendering(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            try {
                $application->run(new ViewApplicationRunInput(
                    ViewApplicationFixture::request(new CompiledViewIdentity(str_repeat('f', 64))),
                    new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
                ));
                self::fail('Expected a mismatched compiled identity to fail closed.');
            } catch (ViewApplicationOperationException $failure) {
                self::assertSame('KGN-VIEW-APPLICATION-1854', $failure->diagnosticId());
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testTamperedCurrentEvidenceFailsBeforeFactoryActivity(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            self::assertSame(9, file_put_contents($current->artifactDirectory() . '/artifact.json', "tampered\n"));
            $responses = new class implements ResponseFactoryInterface {
                public int $calls = 0;

                public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
                {
                    ++$this->calls;
                    throw new RuntimeException('response factory must not run');
                }
            };
            $streams = new class implements StreamFactoryInterface {
                public int $calls = 0;

                public function createStream(string $content = ''): StreamInterface
                {
                    ++$this->calls;
                    throw new RuntimeException('stream factory must not run');
                }

                public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
                {
                    throw new RuntimeException('unsupported test method');
                }

                public function createStreamFromResource($resource): StreamInterface
                {
                    throw new RuntimeException('unsupported test method');
                }
            };
            try {
                (new ViewApplicationFactory())->create($current, $responses, $streams);
                self::fail('Expected tampered current evidence to fail before factory activity.');
            } catch (ViewApplicationLoadException $failure) {
                self::assertSame('KGN-VIEW-APPLICATION-1853', $failure->diagnosticId());
                self::assertSame(0, $responses->calls);
                self::assertSame(0, $streams->calls);
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testEmptyNormalAndMaximumAcceptedOutputRemainExact(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            foreach (['', 'normal', str_repeat('m', 64)] as $message) {
                $response = $application->run(new ViewApplicationRunInput(
                    ViewApplicationFixture::request($current->compiledViewIdentity(), $message),
                    new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
                ));
                self::assertNotInstanceOf(RenderFailure::class, $response);
                self::assertSame($message . "\n", (string) $response->getBody());
                self::assertSame((string) (strlen($message) + 1), $response->getHeaderLine('Content-Length'));
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testResponseFactoryFailurePreservesTheAcceptedLowerFailure(): void
    {
        $fixture = ViewApplicationFixture::create();
        $private = new RuntimeException('private-response-factory');
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $responses = new class ($private) implements ResponseFactoryInterface {
                public function __construct(private readonly RuntimeException $private) {}

                public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
                {
                    throw $this->private;
                }
            };
            $application = (new ViewApplicationFactory())->create($current, $responses, new StreamFactory());
            try {
                $application->run(new ViewApplicationRunInput(
                    ViewApplicationFixture::request($current->compiledViewIdentity()),
                    new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
                ));
                self::fail('Expected response factory failure.');
            } catch (ViewHttpAdaptationException $failure) {
                self::assertSame('KGN-VIEW-HTTP-1843', $failure->diagnosticId());
                self::assertSame($private, $failure->getPrevious());
                self::assertStringNotContainsString('private-response-factory', (string) $failure);
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testStreamFactoryFailurePreservesTheAcceptedLowerFailure(): void
    {
        $fixture = ViewApplicationFixture::create();
        $private = new RuntimeException('private-stream-factory');
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $streams = new class ($private) implements StreamFactoryInterface {
                public function __construct(private readonly RuntimeException $private) {}

                public function createStream(string $content = ''): StreamInterface
                {
                    throw $this->private;
                }

                public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
                {
                    throw $this->private;
                }

                public function createStreamFromResource($resource): StreamInterface
                {
                    throw $this->private;
                }
            };
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), $streams);
            try {
                $application->run(new ViewApplicationRunInput(
                    ViewApplicationFixture::request($current->compiledViewIdentity()),
                    new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
                ));
                self::fail('Expected stream factory failure.');
            } catch (ViewHttpAdaptationException $failure) {
                self::assertSame('KGN-VIEW-HTTP-1842', $failure->diagnosticId());
                self::assertSame($private, $failure->getPrevious());
                self::assertStringNotContainsString('private-stream-factory', (string) $failure);
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testSequentialOperationsRetainNoState(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            for ($index = 0; $index < 24; ++$index) {
                $message = 'message-' . $index;
                $response = $application->run(new ViewApplicationRunInput(
                    ViewApplicationFixture::request($current->compiledViewIdentity(), $message),
                    new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
                ));
                self::assertNotInstanceOf(RenderFailure::class, $response);
                self::assertSame($message . "\n", (string) $response->getBody());
            }
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testSuccessAfterFailureAndFailureAfterSuccessRetainNoState(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $failure = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity(), 'private-failure', 'page.missing'),
                new ViewResponsePolicy(503, 'Unavailable', ViewContentType::HtmlUtf8),
            ));
            self::assertInstanceOf(RenderFailure::class, $failure);
            $success = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity(), 'public-success'),
                new ViewResponsePolicy(201, 'Created', ViewContentType::HtmlUtf8),
            ));
            self::assertNotInstanceOf(RenderFailure::class, $success);
            self::assertSame("public-success\n", (string) $success->getBody());
            self::assertSame(201, $success->getStatusCode());
            $again = $application->run(new ViewApplicationRunInput(
                ViewApplicationFixture::request($current->compiledViewIdentity(), 'private-again', 'page.missing'),
                new ViewResponsePolicy(418, 'Teapot', ViewContentType::HtmlUtf8),
            ));
            self::assertInstanceOf(RenderFailure::class, $again);
            self::assertStringNotContainsString('public-success', print_r($again, true));
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testSeparateApplicationsRemainIsolatedUnderCooperativeInterleaving(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $left = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $right = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $leftFiber = new \Fiber(static function () use ($left, $current): array {
                \Fiber::suspend('left-ready');

                return [$left->profile()->identity()->value(), $left->run(new ViewApplicationRunInput(ViewApplicationFixture::request($current->compiledViewIdentity(), 'left'), new ViewResponsePolicy(201, 'Created', ViewContentType::HtmlUtf8)))];
            });
            $rightFiber = new \Fiber(static function () use ($right, $current): array {
                \Fiber::suspend('right-ready');

                return [$right->profile()->identity()->value(), $right->run(new ViewApplicationRunInput(ViewApplicationFixture::request($current->compiledViewIdentity(), 'right'), new ViewResponsePolicy(202, 'Accepted', ViewContentType::HtmlUtf8)))];
            });
            self::assertSame('left-ready', $leftFiber->start());
            self::assertSame('right-ready', $rightFiber->start());
            $rightFiber->resume();
            $leftFiber->resume();
            $leftResult = $leftFiber->getReturn();
            $rightResult = $rightFiber->getReturn();
            self::assertIsArray($leftResult);
            self::assertIsArray($rightResult);
            self::assertSame($leftResult[0], $rightResult[0]);
            self::assertInstanceOf(ResponseInterface::class, $leftResult[1]);
            self::assertInstanceOf(ResponseInterface::class, $rightResult[1]);
            self::assertSame("left\n", (string) $leftResult[1]->getBody());
            self::assertSame(201, $leftResult[1]->getStatusCode());
            self::assertSame("right\n", (string) $rightResult[1]->getBody());
            self::assertSame(202, $rightResult[1]->getStatusCode());
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testReturnedApplicationRetainsNoOperationInputGraph(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $current = (new ViewApplicationBuildWorkflow())->build($fixture['definition']);
            $application = (new ViewApplicationFactory())->create($current, new ResponseFactory(), new StreamFactory());
            $request = ViewApplicationFixture::request($current->compiledViewIdentity(), 'ephemeral');
            $context = $request->context();
            $policy = new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8);
            $input = new ViewApplicationRunInput($request, $policy);
            $requestReference = WeakReference::create($request);
            $contextReference = WeakReference::create($context);
            $policyReference = WeakReference::create($policy);
            $inputReference = WeakReference::create($input);
            $response = $application->run($input);
            self::assertInstanceOf(ResponseInterface::class, $response);
            unset($input, $policy, $context, $request);
            gc_collect_cycles();
            self::assertNull($inputReference->get());
            self::assertNull($policyReference->get());
            self::assertNull($contextReference->get());
            self::assertNull($requestReference->get());
            self::assertSame("ephemeral\n", (string) $response->getBody());
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }
}
