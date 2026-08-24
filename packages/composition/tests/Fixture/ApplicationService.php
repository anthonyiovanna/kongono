<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class ApplicationService
{
    public function __construct(
        private readonly ConfigInput $config,
        private readonly Logger $logger,
        private readonly Handler $handlers,
        private readonly Logger $fallback = new DefaultLogger(),
    ) {}

    public function shutdown(): void {}

    /** @return array{ConfigInput, Logger, Handler, Logger} */
    public function dependencies(): array
    {
        return [$this->config, $this->logger, $this->handlers, $this->fallback];
    }
}
