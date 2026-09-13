<?php

declare(strict_types=1);

namespace ViewMinimalApp\Http;

use Kongono\ViewApplication\Runtime\ViewApplication;
use Kongono\ViewApplication\Runtime\ViewApplicationRunInput;
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
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewHttp\Policy\ViewContentType;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/** Renders the minimal application's single matched response. */
final readonly class HomeHandler implements RequestHandlerInterface
{
    public function __construct(private ViewApplication $views, private CompiledViewIdentity $identity) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('message', PresentationValueSchema::string(64), true),
        ]));
        $render = new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('view-minimal.home'),
            new LogicalViewIdentity('page.home'),
            $this->identity,
            $schema,
            new ViewContext(['message' => new StringValue('Hello from Kongono.')]),
            new RenderLimits(64, 16),
        );
        $response = $this->views->run(new ViewApplicationRunInput(
            $render,
            new ViewResponsePolicy(200, 'OK', ViewContentType::HtmlUtf8),
        ));
        if ($response instanceof RenderFailure) {
            throw new RuntimeException('View rendering failed.');
        }

        // CGI owns final transport framing and recomputes Content-Length.
        return $response->withoutHeader('Content-Length');
    }
}
